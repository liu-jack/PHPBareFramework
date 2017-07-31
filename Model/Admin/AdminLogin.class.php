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
        $uid = 0;
        if (empty($_SESSION['AdminUserId'])) {
            if (!empty($_COOKIE['_admin_auth'])) {
                $uid = cookie_decode($_COOKIE['_admin_auth']);
                if (!empty($uid)) {
                    $_SESSION['AdminUserId'] = intval($uid);
                }
            }
        }
        if (!empty($_SESSION['AdminUserId'])) {
            if (!isset($_SESSION['AdminUserName']) || !isset($_SESSION['AdminRealName']) || !isset($_SESSION['AdminUserGroup']) || !isset($_SESSION['AdminSpecialGroups'])) {
                $userinfo = AdminUser::getUserByIds($_SESSION['AdminUserId']);
                if (!empty($userinfo['UserId'])) {
                    $_SESSION['AdminUserId'] = $userinfo['UserId'];
                    $_SESSION['AdminUserName'] = $userinfo['UserName'];
                    $_SESSION['AdminRealName'] = $userinfo['RealName'];
                    $_SESSION['AdminUserGroup'] = $userinfo['UserGroup'];
                    $_SESSION['AdminSpecialGroups'] = $userinfo['SpecialGroups'];
                    $uid = (int)$_SESSION['AdminUserId'];
                } else {
                    $uid = 0;
                }
            } else {
                $uid = (int)$_SESSION['AdminUserId'];
            }
        }
        return $uid;
    }

    /**
     * 是否有操作权限
     *
     * @return bool
     */
    public static function isHasAuth()
    {
        $auth_list = self::getAuthList();
        if (isset($auth_list[$GLOBALS['_PATH']])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取授权的菜单
     *
     * @param int $pid
     * @return array|mixed
     */
    public static function getAuthMenu($pid = -1)
    {
        $auth_list = self::getAuthList();
        $menu_list = AdminMenu::getMenus();
        $mlist = [];
        foreach ($menu_list['data'] as $k => $v) {
            $mlist[$v['ParentId']][$v['AdminMenuId']] = $v;
        }
        foreach ($mlist[0] as $k => $v) {
            if (!empty($mlist[$v['AdminMenuId']])) {
                foreach ($mlist[$v['AdminMenuId']] as $kk => $vv) {
                    if (!empty($mlist[$vv['AdminMenuId']])) {
                        foreach ($mlist[$vv['AdminMenuId']] as $k3 => $v3) {
                            if (!isset($auth_list[$v3['Url']])) {
                                unset($mlist[$vv['AdminMenuId']][$k3]);
                            }
                        }
                        if (empty($mlist[$vv['AdminMenuId']])) {
                            unset($mlist[$v['AdminMenuId']][$kk]);
                        }
                    } else {
                        unset($mlist[$vv['AdminMenuId']]);
                    }
                }
                if (empty($mlist[$v['AdminMenuId']])) {
                    unset($mlist[0][$k]);
                }
            } else {
                unset($mlist[$v['AdminMenuId']]);
            }
        }

        $list = [];
        if ($pid < 0) {
            $list = array_filter($mlist);
        } elseif (!empty($mlist[$pid])) {
            $list = $mlist[$pid];
        }
        return $list;
    }

    /**
     * 获取授权列表
     *
     * @return array|bool
     */
    public static function getAuthList()
    {
        $auth_list = [];
        if (self::isLogin()) {
            $auth_list = AdminGroup::getGroupByIds($_SESSION['AdminUserGroup']);
            if (!empty($_SESSION['AdminSpecialGroups'])) {
                $auth_list = array_merge($auth_list, $_SESSION['AdminSpecialGroups']);
            }
            $auth_list = array_flip($auth_list);
        }
        return $auth_list;
    }
}