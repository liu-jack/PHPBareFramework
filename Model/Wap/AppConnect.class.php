<?php

/**
 * App登录信息互通
 */

namespace Model\Wap;

use Classes\Encrypt\Blowfish;
use Model\Passport\Login;

class AppConnect
{
    public static function init()
    {
        $auth = self::getAuth();
        $decode = Blowfish::decode($auth);

        if ($decode != false) {
            $arr = explode('|', $decode);
            if (count($arr) == 4) {
                $uid = $arr[1];

                Login::initSession([
                    'UserId' => $uid,
                    'UserName' => '',
                ]);
                Login::initCookie($uid, true);

                return $uid;
            }
        } else {
            unset($_SESSION['UserId']);
            unset($_SESSION['UserName']);
            setcookie('_auth', '', 1);
            setcookie('_uid', '', 1);
        }

        return false;
    }

    public static function getAuth()
    {
        $auth = trim($_SERVER['HTTP_AUTH']);
        if (empty($auth)) {
            $auth = trim($_GET['auth']);
        }

        return $auth;
    }
}