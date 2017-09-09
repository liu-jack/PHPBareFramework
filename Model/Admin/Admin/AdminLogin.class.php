<?php
/**
 * AdminLogin.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/7/29 12:25
 *
 */

namespace Model\Admin\Admin;


class AdminLogin
{
    /**
     * 登录
     *
     * @param string $login_name 登录账号
     * @param string $pwd        原始密码
     * @param bool   $auto_login 自动登录
     * @return array      成功返回用户信息, 错误返回['code' => 失败代码, 'msg' => 失败原因]
     */
    public static function doLogin($login_name, $pwd, $auto_login = true)
    {
        $userinfo = AdminUser::getUserByName($login_name);
        if (empty($userinfo['UserId'])) {
            return [
                'code' => 202,
                'msg' => '账号或者密码不正确'
            ];
        }
        $userid = (int)$userinfo['UserId'];

        if (!password_verify($pwd, $userinfo['Password'])) {
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
        $_SESSION['_admin_info'] = [
            'AdminUserId' => $userinfo['UserId'],
            'AdminUserName' => $userinfo['UserName'],
            'AdminRealName' => $userinfo['RealName'],
            'AdminUserGroup' => $userinfo['UserGroup']
        ];
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
            $expire = time() + 3600 * 8;
        }
        setcookie('_admin_auth', cookie_encode($uid), $expire, '/');
    }

    /**
     * 退出登录 (用于Web/Wap端)
     */
    public static function logout()
    {
        unset($_SESSION['_admin_info']);
        session_destroy();
        setcookie('_admin_auth', '', 1, '/');
    }

    /**
     * 是否登录 刷新登录
     *
     * @param int $uid
     * @return int|mixed
     */
    public static function isLogin($uid = 0)
    {
        if ($uid > 0) {
            $_SESSION['_admin_info']['AdminUserId'] = intval($uid);
        } elseif (empty($_SESSION['_admin_info']['AdminUserId'])) {
            if (!empty($_COOKIE['_admin_auth'])) {
                $uid = cookie_decode($_COOKIE['_admin_auth']);
                if (!empty($uid)) {
                    $_SESSION['_admin_info']['AdminUserId'] = intval($uid);
                }
            }
        }
        if (!empty($_SESSION['_admin_info']['AdminUserId'])) {
            if (!isset($_SESSION['_admin_info']['AdminUserName']) || !isset($_SESSION['_admin_info']['AdminRealName']) || !isset($_SESSION['_admin_info']['AdminUserGroup'])) {
                $userinfo = AdminUser::getUserByIds($_SESSION['_admin_info']['AdminUserId']);
                if (!empty($userinfo['UserId'])) {
                    $_SESSION['_admin_info'] = [
                        'AdminUserId' => $userinfo['UserId'],
                        'AdminUserName' => $userinfo['UserName'],
                        'AdminRealName' => $userinfo['RealName'],
                        'AdminUserGroup' => $userinfo['UserGroup']
                    ];
                    $uid = (int)$_SESSION['_admin_info']['AdminUserId'];
                } else {
                    $uid = 0;
                }
            } else {
                $uid = (int)$_SESSION['_admin_info']['AdminUserId'];
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
        $admin_group_id = SUPER_ADMIN_GROUP;
        $auth_list = self::getAuthList();
        if (isset($auth_list[$GLOBALS['_URL']]) || $_SESSION['_admin_info']['AdminUserGroup'] == $admin_group_id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取授权的菜单
     *
     * @param int $pid
     * @param int $level
     * @return array|mixed
     */
    public static function getAuthMenu($pid = -1, $level = 3)
    {
        $admin_group_id = SUPER_ADMIN_GROUP;
        $auth_list = self::getAuthList();
        $menu_list = AdminMenu::getMenus();
        $mlist = [];
        foreach ($menu_list['data'] as $k => $v) {
            $mlist[$v['ParentId']][$v['AdminMenuId']] = $v;
        }
        foreach ($mlist as $k => $v) {
            foreach ($v as $kk => $vv) {
                if ($vv['ParentId'] == 0) {
                    $mlist[$k][$kk]['Level'] = 1;
                } elseif (isset($mlist[0][$vv['ParentId']])) {
                    $mlist[$k][$kk]['Level'] = 2;
                } else {
                    $mlist[$k][$kk]['Level'] = 3;
                }
            }
        }
        foreach ($mlist[0] as $k => $v) {
            if (!empty($mlist[$v['AdminMenuId']])) {
                foreach ($mlist[$v['AdminMenuId']] as $kk => $vv) {
                    if ($level == 3) {
                        if (!empty($mlist[$vv['AdminMenuId']])) {
                            foreach ($mlist[$vv['AdminMenuId']] as $k3 => $v3) {
                                if (!isset($auth_list[$v3['Url']]) && $_SESSION['_admin_info']['AdminUserGroup'] != $admin_group_id && $pid != -2) {
                                    unset($mlist[$vv['AdminMenuId']][$k3]);
                                }
                            }
                            if (empty($mlist[$vv['AdminMenuId']]) && $pid != -2) {
                                unset($mlist[$v['AdminMenuId']][$kk]);
                            }
                        } elseif ($pid != -2) {
                            unset($mlist[$vv['AdminMenuId']]);

                        }
                    } else {
                        if (!isset($auth_list[$vv['Url']]) && $_SESSION['_admin_info']['AdminUserGroup'] != $admin_group_id && $pid != -2) {
                            unset($mlist[$v['AdminMenuId']][$kk]);
                        }
                    }
                }
                if (empty($mlist[$v['AdminMenuId']]) && $pid != -2) {
                    unset($mlist[0][$k]);
                }
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
     * 获取所有可访问的菜单
     */
    public static function getAllAuthMenu()
    {
        $menu = self::getAuthMenu(-2);

        $methods_name = $methods_pos = $methods_hash = $methods_exist = [];
        $methods_exist = [];
        foreach ($menu[0] as $k => $v) {
            foreach ($menu[$k] as $kk => $vv) {
                foreach ($menu[$kk] as $k3 => $v3) {
                    $methods_exist[$v3['Url']] = $v3['Url'];
                    $str = substr($v3['Url'], 0, strrpos($v3['Url'], '/'));
                    if (!empty($str) && empty($methods_hash[$str])) {
                        $methods_name[$str] = $v3['Name'];
                        $methods_pos[$str] = $kk . '|' . $k3;
                        $methods_hash[$str] = $str;
                    }
                }
            }
        }
        foreach ($methods_hash as $v) {
            $path = CONTROLLER_PATH . ADMIN_PATH . '/' . $v . EXT;
            if (file_exists($path)) {
                $class = 'Controller\\' . ADMIN_PATH . '\\' . str_replace('/', '\\', $v);
                $method = getMethods($class, 'public');
                foreach ($method as $mk => $mv) {
                    $url = $v . '/' . $mk;
                    if (!isset($methods_exist[$url])) {
                        $key = explode('|', $methods_pos[$v], 2)[0];
                        $mid = $methods_pos[$v] . '|' . $mk;
                        $menu[$key][$mid] = [
                            'AdminMenuId' => $url,
                            'Name' => $methods_name[$v] . '-' . $mk,
                            'Url' => $url
                        ];
                    }
                }
            }
        }

        return $menu;
    }

    /**
     * 获取授权列表
     *
     * @return array|bool
     */
    public static function getAuthList()
    {
        $auth_list = [];
        if ($uid = self::isLogin()) {
            if (!empty($_SESSION['_admin_auth_list'])) {
                $auth_list = $_SESSION['_admin_auth_list'];
            } else {
                $auth_list = AdminGroup::getGroupByIds($_SESSION['_admin_info']['AdminUserGroup']);
                $auth_list = $auth_list['AdminAuth'];
                $user = AdminUser::getUserByIds($uid);
                if (!empty($user['SpecialGroups'])) {
                    $auth_list = array_merge($auth_list, $user['SpecialGroups']);
                }
                $auth_list = self::getMenuByAuth($auth_list);
                $_SESSION['_admin_auth_list'] = $auth_list;
            }
        }

        return $auth_list;
    }

    /**
     * 根据授权id获取授权url
     *
     * @param $auth_list
     * @return array
     */
    public static function getMenuByAuth($auth_list)
    {
        $mids = [];
        foreach ($auth_list as $k => $v) {
            if (is_numeric($v)) {
                $mids[] = $v;
                unset($auth_list[$k]);
            }
        }
        $menu = AdminMenu::getMenuByIds($mids);
        foreach ($menu as $v) {
            $auth_list[$v['Url']] = $v['Url'];
        }
        $auth_list = array_combine($auth_list, $auth_list);

        return $auth_list;
    }
}