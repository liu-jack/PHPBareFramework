<?php

/**
 * 登录处理类
 *
 */

namespace Model\Passport;

use Classes\Encrypt\Rsa;
use Model\Account\User as AUser;

class Login extends Passport
{
    /**
     * 登录
     *
     * @param string $login_name   登录账号,手机/邮箱/用户名
     * @param string $pwd          原始密码
     * @param bool   $auto_init    是否初始化session和cookie(用于web/wap)
     * @param bool   $auto_login   是否记住登录状态,当$auto_init为true时才有效
     * @param bool   $update_count 是否更新计数, 后台登陆时不更新
     * @return array               成功返回用户信息, 错误返回['code' => 失败代码, 'msg' => 失败原因]
     */
    public static function doLogin($login_name, $pwd, $auto_init = true, $auto_login = true, $update_count = true)
    {
        if (filter_var($login_name, FILTER_VALIDATE_EMAIL)) {
            $userid = self::isEmailExists($login_name, true);
        } elseif (preg_match('/^1[0-9]{10}$/', $login_name)) {
            $userid = self::isMobileExists($login_name, true);
        } else {
            $userid = self::isUserNameExists($login_name, true);
        }
        if ($userid === false) {
            return [
                'code' => 202,
                'msg' => '账号或者密码不正确'
            ];
        }
        $userinfo = self::getUserById($userid);
        if (!password_verify($pwd, $userinfo['Password']) || $userinfo['UserId'] != $userid) {
            return [
                'code' => 203,
                'msg' => '账号或者密码不正确'
            ];
        }
        if ($userinfo['Status'] == 0) {
            return [
                'code' => 204,
                'msg' => '账号已经被禁止'
            ];
        }
        if ($auto_init) {
            self::initSession($userinfo);
            self::initCookie($userid, $auto_login);
        }
        self::updateLoginInfo($userid, $update_count);

        return $userinfo;
    }

    /**
     * 初始化session
     *
     * @param array $userinfo 用户信息
     * @return void
     */
    public static function initSession($userinfo)
    {
        $_SESSION['UserId'] = $userinfo['UserId'];
        $_SESSION['UserName'] = $userinfo['UserName'];
    }

    /**
     * 初始化cookie
     *
     * @param int  $uid        用户ID
     * @param bool $auto_login 是否保持登录
     */
    public static function initCookie($uid, $auto_login = true)
    {
        $expire = 0;
        if ($auto_login) {
            $expire = time() + 31536000;
        }
        setcookie('_auth', cookie_encode($uid), $expire, '/');
    }

    /**
     * 更新登录变更信息
     *
     * @param int  $userid       用户ID
     * @param bool $update_count 是否更新登录计数
     * @return bool
     */
    public static function updateLoginInfo($userid, $update_count = true)
    {
        $userinfo = AUser::getUserById($userid);
        if (empty($userinfo)) {
            $user = self::getUserById($userid);
            AUser::addUser([
                'UserId' => $user['UserId'],
                'LoginName' => $user['UserName'],
                'UserNick' => !empty($user['Mobile']) ? self::hideMobileName($user['Mobile']) : $user['UserName']
            ]);
        }
        if ($update_count) {
            AUser::updateCount($userid, [AUser::COUNT_LOGIN => '+1']);
        }
        $passport_update = [
            'LoginTime' => date("Y-m-d H:i:s"),
            'LoginCount' => ['LoginCount', '+1'],
            'LoginIp' => ip()
        ];

        return self::updateUser($userid, $passport_update);
    }

    /**
     * 退出登录 (用于Web/Wap端)
     */
    public static function logout()
    {
        unset($_SESSION['UserId'], $_SESSION['UserName']);
        session_destroy();
        setcookie('_auth', '', 1, '/');
    }

    /**
     * 解密密码
     *
     * @param string $pwd 加密密码
     * @return string        解密密码, 失败返回空
     */
    public static function decodePassword($pwd)
    {
        $decode = Rsa::private_decode($pwd);
        if (!empty($decode)) {
            $key = explode('|', $decode, 2);
            if (abs(time() - intval($key[0])) <= 1800) {
                return $key[1];
            }
        }

        return '';
    }
}