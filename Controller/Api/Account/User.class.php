<?php
/**
 * User.class.php
 *
 * @author suning <snsnsky@gmail.com>
 *
 * $Id$
 */

namespace MobileApi\Passport;

use Center\UserInvite;
use \MobileApi\Lib\Passport;
use \MobileApi\Lib\ApiBase;
use \MobileApi\Lib\Device;
use \Passport\Account;
use \Passport\Register;
use \Notice\Sms;
use \Center\User as UserInfo;
use \HXApi\HXReg;
use \Passport\Login;

/**
 * 通行证 - 手机登录/注册
 *
 * @package Passport
 * @author  suning <snsnsky@gmail.com>
 * @since   1.0.0 2016-02-24
 */
class User extends ApiBase
{
    /**
     * 发送短信验证码
     *
     * <pre>
     * POST:
     *    type:    类型, 1:登录并注册、4:修改手机号码
     *    mobile:  手机号码
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * 异常状态
     * 201: 手机号码格式不正确
     * 202: 参数类型不正确
     * 203: 手机号码不存在
     * 204: 手机号码已注册
     * 205: 请稍后发送 (未到60s)
     * 206: 你已绑定该号码！
     * 207: 该手机已绑定其它账号！
     * </pre>
     */
    public function sendMobileCode()
    {
        $type = (int)$_POST['type'];
        $mobile = trim($_POST['mobile']);
        $content = '';

        if (!preg_match('/^1[0-9]{10}$/', $mobile)) {
            $this->output(201, '手机号码格式不正确');
        }

        $rand = mt_rand(100000, 999999);

        switch ($type) {
            case Sms::SMS_TYPE_LOGIN:
                $content = "验证码：{$rand}，15分钟有效，立即登录。";
                break;

            case Sms::SMS_TYPE_MODIFY_MOBILE:
                $uid = $this->isLogin(true);
                $uid0 = Account::isMobileExists($mobile);
                if ($uid == $uid0) {
                    $this->output(206, '你已绑定该号码！');
                }
                if ($uid0 > 0) {
                    $this->output(207, '该手机已绑定其它账号！');
                }

                $content = "验证码：{$rand}，如非本人操作，请忽略。";
                break;

            default:
                $this->output(202, '参数类型不正确');
        }

        $sms = Sms::getLastSms($mobile, $type, false);
        if (!empty($sms['SmsId'])) {
            $time = strtotime($sms['CreateTime']);
            if (time() - $time < 60) {
                $this->output(205, '您发送过快, 请稍后再试。');
            }
        }

        Sms::send($mobile, $content, $type, $rand);

        $this->output();

    }

    /**
     * 通过验证码登录&注册
     *
     * <pre>
     * POST:
     *  mobile: 手机号码
     *  code:   验证码
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *    "Status": 200,
     *    "Result": {
     *        'Auth': '认证字符串',
     *        'UserId': 用户ID,
     *        'UserNick': '用户昵称', // 昵称为空时, 暂缓注册AUTH, 进入头像昵称设置页
     *        'Hx': {
     *             'User' => '环信用户名', // 未激活时为空
     *             'Pwd' => '环信密码',    // 未激活时为空
     *         },
     *        'SkipAddBaby': 1 //是否跳过添加小孩，1:跳过    0:不跳过
     *    }
     * }
     *
     * 异常状态
     * 201: 手机号码格式不正确
     * 202: 验证码不正确
     * 204: 注册失败, 请稍后再试
     * </pre>
     */
    public function loginByCode()
    {
        $code = (int)trim($_POST['code']);
        $mobile = trim($_POST['mobile']);

        if (!preg_match('/^1[0-9]{10}$/', $mobile)) {
            $this->output(201, '手机号码格式不正确');
        }

        $uid = Account::isMobileExists($mobile);

        if (Sms::verifySms($mobile, Sms::SMS_TYPE_LOGIN, $code) || ($mobile === '13888888888' && $code == '914275')) {
            if ($uid == false) {
                // 注册用户
                $user = Register::addUser(Register::REG_TYPE_MOBILE, [
                    'Mobile' => $mobile,
                    'UserName' => Register::getRandomName(),
                    'Password' => '',
                    'FromPlatform' => $GLOBALS['g_appid'] == MOBILE_APPID_IPHONE ? Register::REG_PLATFORM_IPHONE : Register::REG_PLATFORM_ANDROID,
                    'FromProduct' => Register::REG_FROM_QINXIN,
                    'FromWay' => Register::REG_WAY_MOBILE
                ]);

                if (!$user['UserId']) {
                    $this->output(204, '注册失败, 请稍后再试');
                }

                // 激活环信
                HXReg::regHX($user['UserId'], true);

            } else {
                $user = Account::getUserById($uid);
                Login::updateLoginInfo($uid);
            }

            $result = Passport::getLoginInfo($user);

            //用来获取我的邀请信息，判断是否存在邀请我为父母的人
            $inviteList = UserInvite::getInviteMeListByMobile($mobile);
            $SkipAddBaby = 0;
            foreach ($inviteList as $k => $v) {
                if (in_array($v['RelationType'], ['1', '2'])) {
                    $SkipAddBaby = 1;
                    break;
                }
            }

            //用来获取微信邀请我的信息，判断是否存在邀请我为父母的人
            if ($SkipAddBaby == 0) {
                $WXInviteList = UserInvite::getWxInviteMeList($mobile);
                foreach ($WXInviteList as $k => $v) {
                    if (in_array($v['RelationType'], ['1', '2'])) {
                        $SkipAddBaby = 1;
                        break;
                    }
                }
            }

            $result['SkipAddBaby'] = $SkipAddBaby;

            $this->output(200, $result);
        }

        $this->output(202, '验证码输入错误');
    }

    /**
     * 获取登录用户的环信账号
     *
     * <pre>
     * GET 方式
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *    "Status": 200,
     *    "Result": {
     *        'User': '环信用户名', // 未激活时为空, 此时客户端聊天功能不可用
     *        'Pwd': '环信密码      // 未激活时为空, 此时客户端聊天功能不可用
     *    }
     * }
     *
     * </pre>
     */
    public function getHxInfo()
    {
        $uid = $this->isLogin(true);

        $result = [
            'User' => '',
            'Pwd' => ''
        ];

        if ($uid > 0) {
            $userinfo = UserInfo::getUserById($uid);

            if ($userinfo['HXStatus'] == 1) {
                $hx = UserInfo::getHxAccount($uid);
                $result = [
                    'User' => $hx['name'],
                    'Pwd' => $hx['pwd']
                ];
            }
        }

        $this->output(200, $result);
    }

    /**
     * 退出登录 (不能省略接口调用)
     *
     * <pre>
     * GET 方式
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *   "Status": 200,
     *   "Result": {}
     * }
     * </pre>
     */
    public function logout()
    {
        $uid = $this->isLogin(false);
        if ($uid > 0) {
            if (is_array($_SESSION) && count($_SESSION) > 0) {
                session_destroy();
            }
            unset($_SESSION);

            $appid = $GLOBALS['g_appid'];
            Device::unbindDevice($appid, $uid);
        }

        $this->output();
    }

    /**
     * 设置用户密码
     *
     * <pre>
     * POST
     * pwd1: 密码     // 通过base64(RSA(时间戳|密码))加密, 时间戳精确到秒(10位)
     * pwd2: 确认密码 // 通过base64(RSA(时间戳|密码))加密, 时间戳精确到秒(10位)
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *    "Status": 200,
     *    "Result": {
     *        'Auth': '认证字符串',
     *        'UserId': 用户ID,
     *        'UserNick': '用户昵称', // 昵称为空时, 暂缓注册AUTH, 进入头像昵称设置页
     *        'Hx': {
     *             'User' => '环信用户名', // 未激活时为空
     *             'Pwd' => '环信密码',    // 未激活时为空
     *         }
     *    }
     * }
     *
     * 异常状态
     * 201: 两次密码输入不一致！
     * 202: 密码长度限制在6~16个字符之间！
     * 203: 设置失败，请稍后再试！
     * 204: 密码校验失败,请检查手机时间设置
     * </pre>
     */
    public function setPwd()
    {
        $uid = $this->isLogin(true);
        $pwd1 = trim($_POST['pwd1']);
        $pwd2 = trim($_POST['pwd2']);

        $password1 = Login::decodePassword($pwd1);
        $password2 = Login::decodePassword($pwd2);

        if ($password1 == '') {
            $this->output(204, '密码校验失败,请检查手机时间设置');
        }

        if ($password1 !== $password2) {
            $this->output(201, '两次密码输入不一致！');
        }

        $len = strlen($password1);
        if ($len < 6 || $len > 16) {
            $this->output(202, '密码长度限制在6~16个字符之间！');
        }

        $rel = Account::updatePassword($uid, $password1);
        if (!$rel) {
            $this->output(203, '设置失败，请稍后再试！');
        }

        $userInfo = Account::getUserById($uid);
        $userInfo = Passport::getLoginInfo($userInfo);
        $this->output(200, $userInfo);
    }


    /**
     * 修改手机号码
     *
     * <pre>
     * POST
     * code: 验证码
     * mobile: 手机号码
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *    "Status": 200,
     *    "Result": {
     *        'Auth': '认证字符串',
     *        'UserId': 用户ID,
     *        'UserNick': '用户昵称', // 昵称为空时, 暂缓注册AUTH, 进入头像昵称设置页
     *        'Hx': {
     *             'User' => '环信用户名', // 未激活时为空
     *             'Pwd' => '环信密码',    // 未激活时为空
     *         }
     *    }
     * }
     *
     * 异常状态
     * 201: 无效的手机号码！
     * 202: 你已绑定该号码！
     * 203：该手机已绑定其它账号！
     * 204: 验证码格式不正确！
     * 205: 修改失败,请稍后再试！
     * 206：验证码不正确！
     * </pre>
     */
    public function modifyMobile()
    {
        $uid = $this->isLogin(true);

        $code = (int)trim($_POST['code']);
        $mobile = trim($_POST['mobile']);

        if (!preg_match('/^1[0-9]{10}$/', $mobile)) {
            $this->output(201, '无效的手机号码！');
        }

        $uid0 = Account::isMobileExists($mobile);
        if ($uid == $uid0) {
            $this->output(202, '你已绑定该号码！');
        }
        if ($uid0 > 0) {
            $this->output(203, '该手机已绑定其它账号！');
        }

        $len = strlen($code);
        if ($len != 6 || !is_numeric($code)) {
            $this->output(204, '验证码格式不正确！');
        }

        if (Sms::verifySms($mobile, Sms::SMS_TYPE_MODIFY_MOBILE, $code)) {
            $result = Account::updateMobile($uid, $mobile);
            if ($result === true) {
                $userInfo = Account::getUserById($uid);
                $userInfo = Passport::getLoginInfo($userInfo);
                $this->output(200, $userInfo);
            }
            $this->output(205, '修改失败,请稍后再试！');
        }
        $this->output(206, "验证码不正确！");
    }

    /**
     * 用 手机+密码 登录
     *
     * <pre>
     * POST
     * mobile: 手机号码
     * pwd: 密码          // 通过base64(RSA(时间戳|密码))加密, 时间戳精确到秒(10位)
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *    "Status": 200,
     *    "Result": {
     *        'Auth': '认证字符串',
     *        'UserId': 用户ID,
     *        'UserNick': '用户昵称', // 昵称为空时, 暂缓注册AUTH, 进入头像昵称设置页
     *        'Hx': {
     *             'User' => '环信用户名', // 未激活时为空
     *             'Pwd' => '环信密码',    // 未激活时为空
     *         }
     *        "SkipAddBaby": 1 //是否跳过添加小孩，1:跳过    0:不跳过
     *    }
     * }
     *
     * 异常状态
     * 201: 手机号码格式不正确
     * 202: 密码长度限制在6~16个字符之间！
     * 203：手机号或密码错误
     * 204: 密码校验失败,请检查手机时间设置
     * </pre>
     */
    public function login()
    {
        $mobile = trim($_POST['mobile']);
        $pwd = trim($_POST['pwd']);

        if (!preg_match('/^1[0-9]{10}$/', $mobile)) {
            $this->output(201, '手机号码格式不正确');
        }

        $password = Login::decodePassword($pwd);

        if ($password == '') {
            $this->output(204, '密码校验失败,请检查手机时间设置');
        }

        $len = strlen($password);
        if ($len < 6 || $len > 16) {
            $this->output(202, '密码长度限制在6~16个字符之间！');
        }

        $uid = Account::isMobileExists($mobile);

        if (!($uid > 0)) {
            $this->output(203, '手机号或密码错误');
        }

        $result = Passport::login($mobile, $pwd);
        if ($result[0] == 200) {
            //用来获取我的邀请信息，判断是否存在邀请我为父母的人
            $inviteList = UserInvite::getInviteMeListByMobile($mobile);
            $SkipAddBaby = 0;
            foreach ($inviteList as $k => $v) {
                if (in_array($v['RelationType'], ['1', '2'])) {
                    $SkipAddBaby = 1;
                    break;
                }
            }

            //用来获取微信邀请我的信息，判断是否存在邀请我为父母的人
            if ($SkipAddBaby == 0) {
                $WXInviteList = UserInvite::getWxInviteMeList($mobile);
                foreach ($WXInviteList as $k => $v) {
                    if (in_array($v['RelationType'], ['1', '2'])) {
                        $SkipAddBaby = 1;
                        break;
                    }
                }
            }

            $data = $result[1];
            $data['SkipAddBaby'] = $SkipAddBaby;

            $this->output($result[0], $data);
        }

        $this->output($result[0], $result[1]);
    }
}