<?php
/**
 * User.php
 *
 * @author 周剑锋 <camfee@foxmail.com>
 *
 */

namespace Controller\Api\Passport;

use Bare\Controller;
use PassportApi\Connect;
use Model\Passport\Register;
use Classes\Encrypt\Rsa;

/**
 * 通行证- 用户接口
 *
 * @package Passport
 * @author  周剑锋 <camfee@foxmail.com>
 * @since   1.0.0 2016-10-12
 */
class User extends Controller
{
    /**
     * 注册新用户
     *
     * <pre>
     * POST
     * account： 帐号，        必选，               用户名(4-16位字符)|手机|邮箱
     * type:     注册类型，    必选，               0：邮件注册|1：手机注册|3：用户名注册
     * platform: 注册平台,     必选，               0：web|1：Android|2：iPhone|3：wap
     * password: 密码,         用户名|邮箱注册必选，rsa加密
     * ip:       用户的IP地址，必选，               无法获取时传当前服务器IP
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *         "UserId": 1500, // 用户Id
     *         "UserName": '333444', // 用户名
     *         "Email": "", // 邮箱
     *         "Mobile": "", // 手机号码
     *         "QCoin": 0, // 亲币数
     *     }
     * }
     * 异常状态
     * 201: Email格式不正确
     * 202: 手机号码格式不正确
     * 203：帐号必选
     * 204：用户名|邮箱注册密码必选
     * 205：注册类型错误
     * 206：密码为空或解密失败
     * 210: 注册平台不正确
     * 211: 注册来源不正确
     * 212: 注册方式不正确
     * 213: 用户名不符合规范
     * 215: 数据库写入失败[1]
     * 216: 数据库写入失败[2]
     * 217: 用户已经存在
     * 220: 用户名已被使用
     * 221: 邮箱已经被使用
     * 222: 手机号码已经被使用
     * </pre>
     */
    public function reg()
    {
        $account = trim($_POST['account']);
        $type = (int)$_POST['type'];
        if (empty($account)) {
            $this->output(203, '帐号必选');
        }
        if (!in_array($type, [0, 1, 3])) {
            $this->output(205, '注册类型错误');
        }
        switch ($type) {
            case 0:
                $data['Email'] = $account;
                break;
            case 1:
                $data['Mobile'] = $account;
                break;
            case 3:
                $data['UserName'] = $account;
                break;
            default:
                $data['UserName'] = $account;
        }
        $data['Password'] = trim($_POST['password']);
        $data['FromPlatform'] = intval($_POST['platform']);
        $data['FromProduct'] = $GLOBALS['g_appid'];
        $data['FromWay'] = $type;
        $data['RegIp'] = trim($_POST['ip']);

        if (!self::isIp($data['RegIp'])) {
            $data['RegIp'] = $this->clientIp();
        }
        if ((!empty($data['UserName']) || !empty($data['Email'])) && empty($data['Mobile']) && empty($data['Password'])) {
            $this->output(204, '用户名|邮箱注册密码必选');
        }
        if (!empty($data['Password'])) {
            $rsa_key = loadconf('passport/rsa/' . $GLOBALS['g_appid']);
            $data['Password'] = Rsa::private_decode($data['Password'], $rsa_key['private']);
            if (empty($data['Password'])) {
                $this->output(206, '密码为空或解密失败');
            }
        }
        if (empty($data['UserName'])) {
            $data['UserName'] = Register::getRandomName();
        }

        $res = Register::addUser($type, $data, false);
        if ($res['status']) {
            $this->output($res['status'], $res['msg']);
        } else {
            $users = [
                'UserId' => (int)$res['UserId'],
                'Email' => (string)$res['Email'],
                'Mobile' => (string)$res['Mobile'],
                'UserName' => (string)$res['UserName'],
                'QCoin' => (int)$res['QCoin']
            ];
            $this->output(200, $users);
        }

    }

    /**
     * 用户登录
     *
     * <pre>
     * POST
     * account： 账户，         必选，用户名、手机、邮箱三选一
     * password: 密码,          必选，rsa加密 （手机号码用验证码登录时,密码传空,不用RSA加密）
     * ip:       用户的IP地址， 必选，无法获取时传当前服务器IP
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *     "Status": 200,
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
     * 201: 账号不存在
     * 202: 密码不正确
     * 203: 密码为空或解密失败
     * 204: 账号已经被禁止
     * </pre>
     */
    public function login()
    {
        $rsa_key = loadconf('passport/rsa/' . $GLOBALS['g_appid']);
        $login_name = trim($_POST['account']);
        $password = trim($_POST['password']);
        $ip = trim($_POST['ip']);

        if (!self::isIp($ip)) {
            $ip = $this->clientIp();
        }
        $pwd = !empty($password) ? Rsa::private_decode($password, $rsa_key['private']) : '';
        if ($pwd === false) {
            $this->output(203, '密码为空或解密失败');
        }
        $login_type = 0; // 是否手机免密码登录

        if (filter_var($login_name, FILTER_VALIDATE_EMAIL)) {
            $userid = self::isEmailExists($login_name);
        } elseif (preg_match('/^1[0-9]{10}$/', $login_name)) {
            $userid = self::isMobileExists($login_name);
            if ($pwd === '') {
                $login_type = 1;
            }
        } else {
            $userid = self::isUserNameExists($login_name);
        }

        if ($userid === false) {
            $this->output(201, '账号或者密码不正确');
        }

        $userinfo = self::_getUserById($userid);

        if ($login_type !== 1) {
            if (!password_verify($pwd, $userinfo['Password']) || $userinfo['UserId'] != $userid) {
                $this->output(202, '账号或者密码不正确');
            }
        }

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
        $this->output(200, $users);
    }

    /**
     * 修改用户密码
     *
     * <pre>
     * POST
     * userid：      用户id，        必选
     * old_password：原密码，        必选，rsa加密(没有原密码时传空，不加密)
     * password：    新密码，        必选，rsa加密
     * forget:       是否忘记原密码，可选，1：是 0：否 (忘记密码时原密码时传空，不加密)
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *         "Msg":"密码修改成功"
     *     }
     * }
     * 异常状态
     * 201: 用户不存在
     * 202: 原密码不正确
     * 203: 缺少必要参数
     * 204: 原密码为空或解密失败
     * 205: 密码为空或解密失败
     * </pre>
     */
    public function updatePassword()
    {
        $userid = intval($_POST['userid']);
        $forget = intval($_POST['forget']);
        $old_password = trim($_POST['old_password']);
        $password = trim($_POST['password']);
        if ($userid <= 0 || empty($password)) {
            $this->output(203, '缺少必要参数');
        }
        $rsa_key = loadconf('passport/rsa/' . $GLOBALS['g_appid']);
        if (!empty($old_password)) {
            $old_password = Rsa::private_decode($old_password, $rsa_key['private']);
            if (empty($old_password)) {
                $this->output(204, '原密码为空或解密失败');
            }
        }
        $pwd = Rsa::private_decode($password, $rsa_key['private']);
        if (empty($pwd)) {
            $this->output(205, '密码为空或解密失败');
        }

        $userinfo = self::_getUserById($userid, true);
        if (empty($userinfo)) {
            $this->output(201, '用户不存在');
        }
        if (!empty($old_password) || !empty($userinfo['Password'])) {
            if ($forget != 1) { // 忘记密码修改不需验证原密码
                if (!password_verify($old_password, $userinfo['Password']) || $userinfo['UserId'] != $userid) {
                    $this->output(202, '原密码不正确');
                }
            }
        }
        $update_data = [
            'Password' => $pwd
        ];
        $res = self::_updateUser($userid, $update_data);
        if ($res['status']) {
            $this->output($res['status'], $res['msg']);
        } else {
            $data['appid'] = (string)$GLOBALS['g_appid'];
            $data['userid'] = (string)$userid;
            $data['createtime'] = date('Y-m-d H:i:s');
            $data['old_password'] = trim($_POST['old_password']);
            $data['password'] = $password;
            if (!empty($old_password)) {
                runtime_log('Passport/User/updatePassword', $data);
            } else {
                runtime_log('Passport/User/updatePasswordEmpty', $data);
            }

            $this->output(200, ['Msg' => '密码修改成功']);
        }
    }

    /**
     * 检查用户名
     *
     * <pre>
     * GET
     * username：用户名，必选
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *          "UserId": 1500, // UserId=0时用户名可以使用注册
     *          "Msg": "用户名已被使用"
     *      }
     * }
     * 异常状态
     * 203: 用户名最多4-16位字符
     * 204: 用户不能使用特殊字符
     * 205: 用户名被保留
     * 206: 用户名包含非法关键词
     * </pre>
     */
    public function checkName()
    {
        $login_name = trim($_GET['username']);
        $res1 = $this->checkUserName($login_name);
        if ($res1['status']) {
            $this->output($res1['status'], $res1['msg']);
        }
        $res2 = $this->isUserNameExists($login_name);
        if ($res2) {
            $this->output(200, ['UserId' => (int)$res2, 'Msg' => '用户名已被使用']);
        } else {
            $this->output(200, ['UserId' => 0, 'Msg' => '用户名可以使用']);
        }

    }

    /**
     * 检查手机号码
     *
     * <pre>
     * GET
     * mobile:     手机号码，   必选
     * checkbind: 是否检查绑定，可选，0：不检查 1：检查
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *          "UserId": 1500, // UserId=0时手机号可以使用注册
     *          "IsBindQQ" => 0, // 是否绑定了QQ 0：否 1：是  checkbind=1时才返回
     *          "IsBindWeiBo" => 0, // 是否绑定了新浪微博 0：否 1：是  checkbind=1时才返回
     *          "IsBindWeiXin" => 0 // 是否绑定了微信 0：否 1：是  checkbind=1时才返回
     *      }
     * }
     * 异常状态
     * 201: 手机号码格式不正确
     * </pre>
     */
    public function checkMobile()
    {
        $mobile = trim($_GET['mobile']);
        $checkbind = intval($_GET['checkbind']);
        if (!preg_match('/^1[0-9]{10}$/', $mobile)) {
            $this->output(201, '手机号码格式不正确');
        }
        if ($checkbind == 1) {
            $return = [
                'UserId' => 0,
                'IsBindQQ' => 0,
                'IsBindWeiBo' => 0,
                'IsBindWeiXin' => 0
            ];
        } else {
            $return = [
                'UserId' => 0
            ];
        }

        $res = $this->isMobileExists($mobile);
        if ($res) {
            $return['UserId'] = (int)$res;
            if ($checkbind == 1) {
                $list = Connect::getBindList($GLOBALS['g_appid'], $res);
                if (!empty($list)) {
                    foreach ($list as $k => $v) {
                        switch ($v['PlatformId']) {
                            case 20:
                                $return['IsBindWeiBo'] = 1;
                                break;
                            case 22:
                                $return['IsBindQQ'] = 1;
                                break;
                            case 26:
                                $return['IsBindWeiXin'] = 1;
                                break;
                            default:
                        }
                    }
                }
            }
            $this->output(200, $return);
        } else {
            $this->output(200, $return);
        }
    }

    /**
     * 检查邮箱
     *
     * <pre>
     * GET
     * email:      邮箱，       必选
     * checkbind: 是否检查绑定，可选，0：不检查 1：检查
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *          "UserId": 1500, // UserId=0时邮箱可以使用注册
     *          "IsBindQQ" => 0, // 是否绑定了QQ 0：否 1：是  checkbind=1时才返回
     *          "IsBindWeiBo" => 0, // 是否绑定了新浪微博 0：否 1：是  checkbind=1时才返回
     *          "IsBindWeiXin" => 0 // 是否绑定了微信 0：否 1：是  checkbind=1时才返回
     *      }
     * }
     * 异常状态
     * 201: Email格式不正确
     * </pre>
     */
    public function checkEmail()
    {
        $email = trim($_GET['email']);
        $checkbind = intval($_GET['checkbind']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->output(201, 'Email格式不正确');
        }
        if ($checkbind == 1) {
            $return = [
                'UserId' => 0,
                'IsBindQQ' => 0,
                'IsBindWeiBo' => 0,
                'IsBindWeiXin' => 0
            ];
        } else {
            $return = [
                'UserId' => 0
            ];
        }
        $res = $this->isEmailExists($email);
        if ($res) {
            $return['UserId'] = (int)$res;
            if ($checkbind == 1) {
                $list = Connect::getBindList($GLOBALS['g_appid'], $res);
                if (!empty($list)) {
                    foreach ($list as $k => $v) {
                        switch ($v['PlatformId']) {
                            case 20:
                                $return['IsBindWeiBo'] = 1;
                                break;
                            case 22:
                                $return['IsBindQQ'] = 1;
                                break;
                            case 26:
                                $return['IsBindWeiXin'] = 1;
                                break;
                            default:
                        }
                    }
                }
            }
            $this->output(200, $return);
        } else {
            $this->output(200, $return);
        }
    }

    /**
     * 获取用户信息
     *
     * <pre>
     * GET
     * userid：用户id，必选，多个用','隔开，最多不超过100个
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *          "21": { // UserId
     *              "UserId": 21, // 用户id
     *              "Email": "", // 邮箱
     *              "Mobile": "15815529653", // 手机号
     *              "UserName": "测试123w33"，// 用户名
     *              "QCoin": 0, // 亲币数
     *              "IsSetPassword": 1 // 是否设置密码 1:是 0：否
     *           },
     *          "22": {
     *              "UserId": 22,
     *              "Email": "",
     *              "Mobile": "13569428036",
     *              "UserName": "33444",
     *              "QCoin": 0,// 亲币数
     *              "IsSetPassword": 0 // 是否设置密码 1:是 0：否
     *          },
     *      }
     * 异常状态
     * 201：用户id错误
     * 202: 用户数据不存在(获取单个)
     * 203: 用户数据不存在(获取多个)
     * </pre>
     */
    public function info()
    {
        $user_id = trim($_GET['userid']);
        if ($user_id <= 0 || !preg_match('/^[\d,]+$/', $user_id)) {
            $this->output(201, '用户id错误');
        }
        $users = [];
        if (strpos($user_id, ',') !== false) {
            $id_arr = explode(',', $user_id);
            $id_arr = array_unique($id_arr);
            if (count($id_arr) > 100) {
                $id_arr = array_slice($id_arr, 0, 100);
            }
            $userinfo = self::_getUserById($id_arr);
            foreach ($id_arr as $v) {
                if ($userinfo[$v]['UserId']) {
                    $users[$v]['UserId'] = (int)$userinfo[$v]['UserId'];
                    $users[$v]['Email'] = (string)$userinfo[$v]['Email'];
                    $users[$v]['Mobile'] = (string)$userinfo[$v]['Mobile'];
                    $users[$v]['UserName'] = (string)$userinfo[$v]['UserName'];
                    $users[$v]['QCoin'] = (int)$userinfo[$v]['QCoin'];
                    $users[$v]['IsSetPassword'] = !empty($userinfo[$v]['Password']) ? 1 : 0;
                }
            }

            if (!empty($users)) {
                $this->output(200, $users);
            } else {
                $this->output(203, '用户数据不存在');
            }
        } else {
            $user_id = (int)$user_id;
            $userinfo = self::_getUserById($user_id);
            if ($userinfo['UserId']) {
                $users[$user_id]['UserId'] = (int)$userinfo['UserId'];
                $users[$user_id]['Email'] = (string)$userinfo['Email'];
                $users[$user_id]['Mobile'] = (string)$userinfo['Mobile'];
                $users[$user_id]['UserName'] = (string)$userinfo['UserName'];
                $users[$user_id]['QCoin'] = (int)$userinfo['QCoin'];
                $users[$user_id]['IsSetPassword'] = !empty($userinfo['Password']) ? 1 : 0;
                $this->output(200, $users);
            } else {
                $this->output(202, '用户数据不存在');
            }
        }
    }

    /**
     * 增加用户亲币数量
     *
     * <pre>
     * POST
     * userid: 用户id，      必选
     * type:   类型ID，      必选，自定义类型，用于区分获得渠道，范围 [1,99]
     * coin:   亲币数量，    必选，不能负数 范围[1-5000] (超出范围时单独联系开放权限)
     * title:  亲币获得说明，必选，例：签到/红包活动等
     * ip:     用户IP地址，  必选，无法获取时传当前服务器IP
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *          "QCoin": 150, // 增加后的亲币数
     *      }
     * }
     * 异常状态
     * 201: 类型错误
     * 202: 用户id错误
     * 203: 亲币数量错误
     * 204: 亲币获得说明必选
     * 205: 增加亲币失败
     * 206：达到每天发放亲币数限制
     * </pre>
     */
    public function addUserCoin()
    {
        $uid = (int)$_POST['userid'];
        $siteid = $GLOBALS['g_appid'];
        $type = (int)$_POST['type'];
        $coin = (int)$_POST['coin'];
        $title = trim($_POST['title']);
        $ip = trim($_POST['ip']);
        if ($type < 1 || $type > 99) {
            $this->output(201, '类型错误');
        }
        if ($uid <= 0) {
            $this->output(202, '用户id错误');
        }
        if ($coin <= 0 || $coin > 5000) {
            $this->output(203, '亲币数量错误');
        }
        if (empty($title)) {
            $this->output(204, '亲币获得说明必选');
        }
        if (!self::isIp($ip)) {
            $ip = $this->clientIp();
        }

        $r = QCoin::addQCoin($uid, $siteid, $type, $coin, $title, $ip);
        if ($r === true) {
            $qcoin = QCoin::getUserCoin($uid);
            $this->output(200, ['QCoin' => $qcoin]);
        } else {
            if ($r == -1) {
                $this->output(206, '达到每天发放亲币数限制');
            }
            $this->output(205, '增加亲币失败');
        }
    }

    /**
     * 扣除用户亲币数量
     *
     * <pre>
     * POST
     * userid: 用户id，      必选
     * type:   类型ID，      必选，自定义类型，用于区分获得渠道，范围 [101,200]
     * coin:   亲币数量，    必选，不能负数 范围[1-5000] (超出范围时单独联系开放权限)
     * title:  亲币扣除说明，必选，例：购买/兑换活动等
     * ip:     用户IP地址，  必选，无法获取时传当前服务器IP
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *          "QCoin": 150, // 扣除后的亲币数
     *      }
     * }
     * 异常状态
     * 201: 类型错误
     * 202: 用户id错误
     * 203: 亲币数量错误
     * 204: 亲币扣除说明必选
     * 205: 扣除亲币失败
     * 206：扣除数量超过用户拥有亲币数
     * </pre>
     */
    public function deductUserCoin()
    {
        $uid = (int)$_POST['userid'];
        $siteid = $GLOBALS['g_appid'];
        $type = (int)$_POST['type'];
        $coin = (int)$_POST['coin'];
        $title = trim($_POST['title']);
        $ip = trim($_POST['ip']);
        if ($type < 101 || $type > 200) {
            $this->output(201, '类型错误');
        }
        if ($uid <= 0) {
            $this->output(202, '用户id错误');
        }
        if ($coin <= 0 || $coin > 5000) {
            $this->output(203, '亲币数量错误');
        }
        if (empty($title)) {
            $this->output(204, '亲币扣除说明必选');
        }
        if (!self::isIp($ip)) {
            $ip = $this->clientIp();
        }

        $r = QCoin::deductQCoin($uid, $siteid, $type, $coin, $title, $ip);
        if ($r === true) {
            $qcoin = QCoin::getUserCoin($uid);
            $this->output(200, ['QCoin' => $qcoin]);
        } else {
            if ($r === -1) {
                $this->output(206, '扣除数量超过用户拥有亲币数');
            }
            $this->output(205, '扣除亲币失败');
        }
    }

    /**
     * 获取实时亲币数量(尽量少调用此接口，可用info获取)
     *
     * <pre>
     * GET
     * userid:  用户id，必选
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *          "QCoin": 150, // 亲币数
     *      }
     * }
     * 异常状态
     * 201: 参数错误：userid缺失
     * </pre>
     */
    public function getUserCoin()
    {
        $uid = (int)$_GET['userid'];
        if ($uid < 1) {
            $this->output(201, '参数错误：userid');
        }

        $qcoin = QCoin::getUserCoin($uid);
        $this->output(200, ['QCoin' => $qcoin]);
    }

    /**
     * 检查手机号码是否注册(多个)
     *
     * <pre>
     * POST
     * mobiles:    手机号码， 必选, 用英文半角逗号分隔，最多2000个
     * </pre>
     *
     * @return string|void
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *         "18574611486": "1500",   // mobile(手机号) => UserId(用户ID)
     *         "18574611487": "545319",
     *         "18574611488": "545320",
     *         "185746114600": false,
     *          ...
     *     }
     * }
     * 异常状态
     * 201: 参数错误
     * </pre>
     */
    public function checkMobiles()
    {
        $mobiles = trim($_POST['mobiles']);
        if (empty($mobiles)) {
            $this->output(201, '参数错误');
        }

        $mobiles = explode(',', $mobiles);
        if (count($mobiles) > 2000) {
            $mobiles = array_slice($mobiles, 0, 2000);
        }
        $res = $this->checkMobileExists($mobiles);
        $this->output(200, $res);

    }
}
