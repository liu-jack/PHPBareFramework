<?php
/**
 * AdminLogin.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/7/29 12:25
 *
 */

namespace Model\Admin;


class AdminLogin
{
    /**
     * 登录
     *
     * @param string $login_name 登录账号
     * @param string $pwd 原始密码
     * @param bool $auto_login 自动登录
     * @return array      成功返回用户信息, 错误返回['code' => 失败代码, 'msg' => 失败原因]
     */
    public static function doLogin($login_name, $pwd, $auto_login = true)
    {
        $userinfo = AdminUser::getUserByName($login_name);
        if (empty($userinfo['UserId'])) {
            return [
                'code' => 202,
                'msg' => '手机号或者密码不正确'
            ];
        }
        $userid = (int)$userinfo['UserId'];

        if (!password_verify($pwd, $userinfo['Password'])) {
            return [
                'code' => 203,
                'msg' => '手机号或者密码不正确'
            ];
        }
        if ($userinfo['Status'] == 0) {
            return [
                'code' => 204,
                'msg' => '账号已经被禁止'
            ];
        }

        self::initSession($userinfo);
        if ($auto_login) {
            self::initCookie($userid, $auto_login);
        }
        unset($userinfo['Password'], $userinfo['SpecialGroups']);
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
        $_SESSION['AdminUserId'] = $userinfo['UserId'];
        $_SESSION['AdminUserName'] = $userinfo['UserName'];
        $_SESSION['AdminRealName'] = $userinfo['RealName'];
        $_SESSION['AdminUserGroup'] = $userinfo['UserGroup'];
        $_SESSION['AdminSpecialGroups'] = $userinfo['SpecialGroups'];
    }

    /**
     * 初始化cookie
     *
     * @param int $uid 用户ID
     * @param bool $auto_login 是否保持登录
     */
    public static function initCookie($uid, $auto_login = true)
    {
        $expire = 0;
        if ($auto_login) {
            $expire = time() + 3600 * 8;
        }
        setcookie('_admin_auth', cookie_encode($uid), $expire, '/');
    }

    /**
     * 退出登录 (用于Web/Wap端)
     */
    public static function logout()
    {
        unset(
            $_SESSION['AdminUserId'],
            $_SESSION['AdminUserName'],
            $_SESSION['AdminRealName'],
            $_SESSION['AdminUserGroup'],
            $_SESSION['AdminSpecialGroups']
        );
        session_destroy();
        setcookie('_admin_auth', '', 1, '/');
    }

    /**
     * 是否登录
     *
     * @return bool
     */
    public static function isLogin()
    {
        if (empty($_SESSION['AdminUserId']) && !empty($_COOKIE['_admin_auth'])) {
            $_SESSION['AdminUserId'] = cookie_decode($_COOKIE['_admin_auth']);
        }
        if (!empty($_SESSION['AdminUserId'])) {
            if (empty($_SESSION['AdminUserName']) || empty($_SESSION['AdminRealName'])|| empty($_SESSION['AdminUserGroup']) || empty($_SESSION['AdminSpecialGroups'])) {
                $userinfo = AdminUser::getUserByIds($_SESSION['AdminUserId']);
                if (!empty($userinfo['UserId'])) {
                    $_SESSION['AdminUserId'] = $userinfo['UserId'];
                    $_SESSION['AdminUserName'] = $userinfo['UserName'];
                    $_SESSION['AdminRealName'] = $userinfo['RealName'];
                    $_SESSION['AdminUserGroup'] = $userinfo['UserGroup'];
                    $_SESSION['AdminSpecialGroups'] = $userinfo['SpecialGroups'];
                    return true;
                }
                return false;
            }
            return true;
        }
        return false;
    }


    public static function getAuthMenu()
    {
        if (!self::isLogin()) {
            redirect('admin/index/index');
        }
        $auth_list = AdminGroup::getGroupByIds($_SESSION['AdminUserGroup']);
        if (!empty($_SESSION['AdminSpecialGroups'])) {
            $auth_list = array_merge($auth_list, $_SESSION['AdminSpecialGroups']);
        }
        $menu_list = AdminMenu::getMenus();
    }
}