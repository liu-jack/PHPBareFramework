<?php
/**
 * Connect.php
 *
 * @author 周剑锋 <camfee@foxmail.com>
 *
 */

namespace Controller\Api\Passport;

use Bare\C\Controller;
use Model\Passport\Register;

/**
 * 通行证- 第三方用户接口
 *
 * @package Passport
 * @author  周剑锋 <camfee@foxmail.com>
 * @since   1.0.0 2016-11-25
 */
class Connect extends Controller
{
    /**
     * 第三方用户 注册
     *
     * <pre>
     * POST
     *   openid :  必选, 第三方开放平台唯一ID
     *   type:     必选, 帐号类型ID, 20：新浪微博|22：QQ|26：微信|27:web微信
     *   platform: 必选， 注册平台,0：web|1：Android|2：iPhone|3：wap
     *   unionid:  可选, 第三方多平台统一ID
     *   ip:       必选，用户的IP地址，无法获取时传当前服务器IP
     *   bind:     可选, 要绑定的手机号或邮箱
     * </pre>
     *
     * @return string|void json
     *
     * <pre>
     * {
     *     "Status": 200, // 206 账号已注册
     *     "Result": {
     *         "UserId": 1500, //用户Id
     *         "UserName": '333444', // 用户名
     *         "Email": "", // 邮箱
     *         "Mobile": "", // 手机号码
     *         "QCoin": 0, // 亲币数
     *         "IsSetPassword": 1 // 是否设置密码 1:是 0：否
     *     }
     * }
     * 异常状态
     * 201: 缺少参数openid
     * 202: 缺少参数unionid
     * 203: 帐号类型ID错误
     * 204: 账号已经被禁止
     * 205: 绑定的手机或邮箱格式不正确
     * 206: 账号已经注册
     * 207: 绑定手机或邮箱时出错
     * 208: 手机或邮箱已绑定过该类型的帐号，不能再绑定
     * </pre>
     */
    public function reg()
    {
        $openid = trim($_POST['openid']);
        $unionid = trim($_POST['unionid']);
        $type = intval($_POST['type']);
        $siteid = $GLOBALS['g_appid'];
        $ip = trim($_POST['ip']);
        $bind = trim($_POST['bind']);
        $reg_type = Register::REG_TYPE_CONNECT;

        if (!self::isIp($ip)) {
            $ip = $this->clientIp();
        }
        if (empty($openid)) {
            $this->output(201, '缺少参数openid');
        }
        $platforms = self::PLATFORM_LISTS;
        if (!isset($platforms[$type])) {
            $this->output(203, '类型ID错误');
        }

        $con = Con::getInfoByOpenId($siteid, $openid, $type);
        if (empty($con['UserId'])) { // 注册新用户
            if (in_array($type, [self::PLATFORM_WEIXIN, self::PLATFORM_WEIXIN_WEB]) && empty($unionid)) {
                //$this->output(202, '缺少参数unionid');
            }
            if (in_array($type, [self::PLATFORM_WEIXIN, self::PLATFORM_WEIXIN_WEB]) && !empty($unionid)) {
                $union = Con::getInfoByUnionId($unionid);
            }
            if (!empty($union['UserId'])) { // 微信unionid已存在
                $r = Con::addUser($siteid, $union['UserId'], $type, $openid, $unionid);
                $res = self::_getUserById($union['UserId']);
                $users = [
                    'UserId' => (int)$res['UserId'],
                    'Email' => (string)$res['Email'],
                    'Mobile' => (string)$res['Mobile'],
                    'UserName' => (string)$res['UserName'],
                    'QCoin' => (int)$res['QCoin'],
                    'IsSetPassword' => !empty($res['Password']) ? 1 : 0
                ];
                $this->output(200, $users);
            } else {
                if (!empty($bind)) {
                    $uid = 0;
                    if (self::isEmail($bind)) {
                        $uid = self::isEmailExists($bind);
                        $data['Email'] = $bind;
                        $reg_type = Register::REG_TYPE_EMAIL;
                    } elseif (self::isMobile($bind)) {
                        $uid = self::isMobileExists($bind);
                        $data['Mobile'] = $bind;
                        $reg_type = Register::REG_TYPE_MOBILE;
                    } else {
                        $this->output(205, '要绑定的手机或邮箱格式不对');
                    }
                    if ($uid) { // 已有帐号，直接绑定第三方帐号后登录
                        $con2 = Con::getInfoByUserId($siteid, $uid, $type);
                        if (!empty($con2['UserId'])) {
                            $this->output(208, '手机或邮箱已绑定过该类型的帐号，不能再绑定');
                        }
                        $r = Con::addUser($siteid, $uid, $type, $openid, $unionid);
                        if ($r) {
                            $users = $this->connectLogin($uid, $ip);
                            $this->output(200, $users);
                        } else {
                            $this->output(207, '绑定手机或邮箱时出错');
                        }
                    }
                }
                $data['FromPlatform'] = intval($_POST['platform']);
                $data['FromProduct'] = $GLOBALS['g_appid'];
                $data['FromWay'] = $type;
                $data['RegIp'] = $ip;
                $data['UserName'] = Register::getRandomName();

                $res = Register::addUser($reg_type, $data, false);
                if (isset($res['status']) && $res['status']) {
                    $this->output($res['status'], $res['msg']);
                } else {
                    $users = [
                        'UserId' => (int)$res['UserId'],
                        'Email' => (string)$res['Email'],
                        'Mobile' => (string)$res['Mobile'],
                        'UserName' => (string)$res['UserName'],
                        'QCoin' => (int)$res['QCoin'],
                        'IsSetPassword' => 0
                    ];
                    Con::addUser($siteid, $res['UserId'], $type, $openid, $unionid);
                    $this->output(200, $users);
                }
            }
        } else { // 用户已存在
            $this->output(206, '账号已经注册');
        }
    }

    /**
     * 第三方用户 登录或查询是否注册过
     *
     * <pre>
     * POST
     *   openid : 必选, 第三方开放平台唯一ID
     *   type:    必选, 帐号类型ID, 20：新浪微博|22：QQ|26：微信
     *   ip:      必选，用户的IP地址，无法获取时传当前服务器IP
     * </pre>
     *
     * @return string|void json
     *
     * <pre>
     * {
     *     "Status": 200, // 203 账号未注册
     *     "Result": {
     *         "UserId": 1500, //用户Id
     *         "UserName": '333444', // 用户名
     *         "Email": "", // 邮箱
     *         "Mobile": "", // 手机号码
     *         "QCoin": 0, // 亲币数
     *         "IsSetPassword": 1 // 是否设置密码 1:是 0：否
     *     }
     * }
     * 异常状态
     * 201: 缺少参数openid
     * 202: 帐号类型ID错误
     * 203: 账号还未注册
     * 204: 账号已经被禁止
     * </pre>
     */
    public function login()
    {
        $openid = trim($_POST['openid']);
        $type = intval($_POST['type']);
        $siteid = $GLOBALS['g_appid'];
        $ip = trim($_POST['ip']);
        if (!self::isIp($ip)) {
            $ip = $this->clientIp();
        }
        if (empty($openid)) {
            $this->output(201, '缺少参数openid');
        }
        $platforms = self::PLATFORM_LISTS;
        if (!isset($platforms[$type])) {
            $this->output(202, '类型ID错误');
        }

        $con = Con::getInfoByOpenId($siteid, $openid, $type);
        if (empty($con['UserId'])) { // 帐号未注册
            $this->output(203, '账号还未注册');
        } else { // 用户直接登录
            $users = $this->connectLogin($con['UserId'], $ip);
            $this->output(200, $users);
        }
    }

    /**
     * 第三方用户绑定手机/邮箱
     *
     * <pre>
     * POST
     *   openid: 必选, 第三方开放平台唯一ID
     *   type:   必选, 帐号类型ID, 20：新浪微博|22：QQ|26：微信
     *   mobile: 必选, 要绑定的手机号码，手机/邮箱必选一，可以都选
     *   email:  必选, 要绑定的邮箱，手机/邮箱必选一
     * </pre>
     *
     * @return string|void json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *          "Msg": "绑定成功",
     *      }
     * }
     * 异常状态
     * 201: 邮箱格式不正确
     * 202: 手机号码格式不正确
     * 203: 参数错误：openid缺失或帐号类型ID错误
     * 204: 手机/邮箱必选一
     * 205: 用户不存在[1]
     * 206: 用户不存在[2]
     * 207: 已经绑定手机,不可再绑定
     * 208: 已经绑定邮箱,不可再绑定
     * 221: 邮箱已经被使用
     * 222: 手机号码已经被使用
     * </pre>
     */
    public function bindUser()
    {
        $openid = trim($_POST['openid']);
        $type = (int)$_POST['type'];
        $mobile = trim($_POST['mobile']);
        $email = trim($_POST['email']);

        $platforms = self::PLATFORM_LISTS;
        if (empty($openid) || !isset($platforms[$type])) {
            $this->output(203, '参数错误：openid或类型ID错误');
        }
        if (empty($mobile) && empty($email)) {
            $this->output(204, '手机/邮箱必选一');
        }
        $con = Con::getInfoByOpenId($GLOBALS['g_appid'], $openid, $type);
        if (empty($con['UserId'])) {
            $this->output(205, '用户不存在');
        }
        $uid = $con['UserId'];
        $userinfo = self::_getUserById($uid);
        if (empty($userinfo)) {
            $this->output(206, '用户不存在');
        }

        $update_data = [];
        if (!empty($mobile)) {
            if (!empty($userinfo['Mobile'])) {
                $this->output(207, '已绑定手机,不可再绑定');
            }
            $update_data['Mobile'] = $mobile;
        }
        if (!empty($email)) {
            if (!empty($userinfo['Email'])) {
                $this->output(208, '已绑定邮箱,不可再绑定');
            }
            $update_data['Email'] = $email;
        }

        $res = self::_updateUser($uid, $update_data);
        if ($res['status']) {
            $this->output($res['status'], $res['msg']);
        } else {
            $this->output(200, ['Msg' => '绑定成功']);
        }
    }

    /**
     * 已登录用户绑定第三方帐号
     *
     * <pre>
     * POST
     *   openid:  必选, 要绑定的第三方开放平台唯一ID
     *   type:    必选, 要绑定的帐号类型ID,  20：新浪微博|22：QQ|26：微信
     *   uid:     必选, 要绑定到的用户id(当前登录的用户id)
     *   unionid: 可选, 第三方多平台统一ID，绑定微信必选
     * </pre>
     *
     * @return string|void json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *          "Msg": "绑定成功",
     *      }
     * }
     * {
     *     "Status": 207,
     *     "Result": {  // 只在 第三方帐号已绑定了其他手机号时返回
     *          "Mobile": "13888888888",  // 第三方帐号绑定的手机号
     *          "ErrorMsg": "帐号已经被绑定，不能再次绑定",
     *      }
     * }
     * 异常状态
     * 201: 参数错误：openid缺失
     * 202: 参数错误：帐号类型ID错误
     * 203: 用户id错误
     * 204: 绑定微信平台统一不能为空
     * 205：绑定失败
     * 206：已绑定过同类型的帐号，不能再绑定
     * 207：帐号已经被绑定，不能再次绑定
     * 208：要绑定到的用户不存在
     * </pre>
     */
    public function bindConnect()
    {
        $openid = trim($_POST['openid']);
        $type = (int)$_POST['type'];
        $uid = intval($_POST['uid']);
        $unionid = trim($_POST['unionid']);
        $siteid = $GLOBALS['g_appid'];

        if (empty($openid)) {
            $this->output(201, '参数错误：openid错误');
        }
        $platforms = self::PLATFORM_LISTS;
        if (!isset($platforms[$type])) {
            $this->output(202, '参数错误：帐号类型ID错误');
        }
        if ($uid < 1) {
            $this->output(203, '用户id错误');
        }
        if ($type === 26 && empty($unionid)) {
            $this->output(204, '绑定微信平台统一不能为空');
        }
        $con2 = Con::getInfoByUserId($siteid, $uid, $type);
        if (!empty($con2['UserId'])) {
            $this->output(206, '同一类型的帐号只能绑定一个');
        }
        $con = Con::getInfoByOpenId($siteid, $openid, $type);
        if (empty($con['UserId'])) {
            $userinfo = self::_getUserById($uid);
            if (empty($userinfo)) {
                $this->output(208, '要绑定到的用户不存在');
            }
            $r = Con::addUser($siteid, $uid, $type, $openid, $unionid);
            if ($r) {
                $this->output(200, ['Msg' => '绑定成功']);
            } else {
                $this->output(205, '绑定失败');
            }
        } else {
            $userinfo = self::_getUserById($con['UserId']);
            if (!empty($userinfo['Mobile'])) {
                $return = ['Mobile' => $userinfo['Mobile'], 'ErrorMsg' => '帐号已经被绑定，不能再次绑定'];
            } else {
                $return = '帐号已经被绑定，不能再次绑定';
            }
            $this->output(207, $return);
        }
    }

    /**
     * 已登录用户解绑第三方帐号
     *
     * <pre>
     * POST
     *   openid: 必选, 第三方开放平台唯一ID
     *   type:   必选, 帐号类型ID, 必选, 20：新浪微博|22：QQ|26：微信
     *   uid:    必选, 要解绑的用户id
     * </pre>
     *
     * @return string|void json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *          "Msg": "解绑成功",
     *      }
     * }
     * 异常状态
     * 201: 参数错误：openid缺失
     * 202: 参数错误：帐号类型ID错误
     * 203: 用户id错误
     * 204：解绑失败
     * 205：用户未绑定该第三方帐号
     * </pre>
     */
    public function unbindConnect()
    {
        $openid = trim($_POST['openid']);
        $type = (int)$_POST['type'];
        $uid = intval($_POST['uid']);
        $siteid = $GLOBALS['g_appid'];

        if (empty($openid)) {
            $this->output(201, '参数错误：openid错误');
        }
        $platforms = self::PLATFORM_LISTS;
        if (!isset($platforms[$type])) {
            $this->output(202, '参数错误：帐号类型ID错误');
        }
        if ($uid < 1) {
            $this->output(203, '用户id错误');
        }
        $con = Con::getInfoByOpenId($siteid, $openid, $type);
        if (!empty($con['UserId']) && $con['UserId'] == $uid) {
            $r = Con::unbindUser($siteid, $uid, $type);
            if ($r) {
                $this->output(200, ['Msg' => '解绑成功']);
            } else {
                $this->output(204, '解绑失败');
            }
        } else {
            $this->output(205, '用户未绑定该第三方帐号');
        }
    }

    /**
     * 已登录用户获取已绑定的第三方帐号列表
     *
     * <pre>
     * GET
     *   uid: 必选, 用户id
     * </pre>
     *
     * @return string|void json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {  // 未绑定过第三方帐号时："Result": {}
     *          {
     *              "Type": 22, // 帐号类型ID, 20：新浪微博|22：QQ|26：微信
     *              "OpenId": "xxxxxxxx", // 第三方平台唯一id
     *              "UnionId": "xxxxxxx", // 第三方(微信)平台统一id
     *              "CreateTime": "2016-10-17 14:35:36"，// 绑定时间
     *           },
     *          {
     *              "Type": 22, // 帐号类型ID, 20：新浪微博|22：QQ|26：微信
     *              "OpenId": "xxxxxxxx", // 第三方平台唯一id
     *              "UnionId": "xxxxxxx", // 第三方(微信)平台统一id
     *              "CreateTime": "2016-10-17 14:35:36"，// 绑定时间
     *          },
     *      }
     * }
     * 异常状态
     * 201: 用户id错误
     * </pre>
     */
    public function getBindList()
    {
        $uid = intval($_GET['uid']);
        $siteid = $GLOBALS['g_appid'];

        if ($uid < 1) {
            $this->output(202, '用户id错误');
        }
        $list = Con::getBindList($siteid, $uid);
        $data = [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $data[$k]['Type'] = (int)$v['PlatformId'];
                $data[$k]['OpenId'] = $v['OpenId'];
                $data[$k]['UnionId'] = $v['UnionId'];
                $data[$k]['CreateTime'] = $v['CreateTime'];
            }
        }
        $this->output(200, $data);
    }

    /**
     * 第三方用户登录
     *
     * @param $userid
     * @param $ip
     * @return array
     */
    private function connectLogin($userid, $ip)
    {
        $userinfo = self::_getUserById($userid);

        if ($userinfo['Status'] == 0) {
            $this->output(204, '账号已经被禁止');
        }
        //更新登录
        $update_data = [
            'LoginTime' => date("Y-m-d H:i:s"),
            'LoginIp' => $ip,
            'LoginCount' => ['LoginCount', '+1']
        ];
        self::_updateUser($userid, $update_data);

        $users = [
            'UserId' => (int)$userinfo['UserId'],
            'Email' => (string)$userinfo['Email'],
            'Mobile' => (string)$userinfo['Mobile'],
            'UserName' => (string)$userinfo['UserName'],
            'QCoin' => (int)$userinfo['QCoin'],
            'IsSetPassword' => !empty($userinfo['Password']) ? 1 : 0
        ];

        return $users;
    }
}
