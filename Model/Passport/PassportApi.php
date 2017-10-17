<?php

/**
 * api登录处理类
 *
 */

namespace Model\Passport;

use Classes\Encrypt\Blowfish;
use Model\Account\User as AUser;
use Model\Mobile\Device;

class PassportApi
{
    /**
     * 登录
     *
     * @param string $name 用户名
     * @param string $pwd  密码
     * @return array
     */
    public static function login($name, $pwd)
    {
        $pwd = Login::decodePassword($pwd);
        if (empty($pwd)) {
            return [203, '您输入的手机号或密码有误'];
        }
        $ret = Login::doLogin($name, $pwd, false);
        if (!empty($ret['UserId'])) {
            $result = self::getLoginInfo($ret);

            return [200, $result];
        }

        return [$ret['code'], $ret['msg']];
    }

    /**
     * 获取SSID, 并开启session
     *
     * @param array  $user      用户信息
     * @param string $device_id 设备ID
     * @return string
     */
    public static function getSSID($user, $device_id)
    {
        $str = sprintf('%s|%s|%s', sprintf("%u", crc32(trim($device_id))), $user['UserId'], (int)$user['LoginCount']);
        $ssid = self::encode($str);
        session_id($ssid);
        session_start();
        $_SESSION['uid'] = $user['UserId'];
        $_SESSION['login_count'] = $user['LoginCount'];

        return $ssid;
    }

    /**
     * 返回标准用户登录信息
     *
     * @param array $user 用户信息组
     * @return array
     */
    public static function getLoginInfo($user)
    {
        $userinfo = AUser::getUserById($user['UserId'], [AUser::EXTRA_AVATAR => AUser::AVATAR_MIDDLE]);
        // 覆盖通行证的
        $user['LoginCount'] = $userinfo['LoginCount'];
        $result = [
            'Auth' => self::getSSID($user, trim($_GET['deviceid'])),
            'UserId' => (int)$user['UserId'],
            'UserNick' => (string)$userinfo['UserNick'],
            'Mobile' => (string)$user['Mobile'],
            'AvatarUrl' => $userinfo['AvatarUrl'],
        ];

        // 自动更新设备用户关系
        if (in_array($GLOBALS[G_APP_ID], [APP_APPID_ADR, APP_APPID_IOS])) {
            $info = Device::getDeviceInfo($GLOBALS[G_APP_ID], $GLOBALS[G_DEVICE_ID]);
            if ($info['Id'] && $info['UserId'] != $user['UserId']) {
                $ios_token = isset($info['iOSToken']) ? $info['iOSToken'] : '';
                Device::initDevice($GLOBALS[G_APP_ID], $GLOBALS[G_DEVICE_ID], $info['Channel'], $info['Token'],
                    $user['UserId'], $ios_token);
            }
        }

        return $result;
    }

    /**
     * 登陆认证信息加密
     *
     * @param string $str 加密内容
     * @param string $key 加密密钥
     * @return string
     */
    public static function encode($str, $key = __KEY__)
    {
        return Blowfish::encode($str, $key);
    }

    /**
     * 登陆认证信息解密
     *
     * @param string $str 待解密内容
     * @param string $key 解密密钥
     * @return bool|array           成功返回解密内容, 失败返回false
     */
    public static function decode($str, $key = __KEY__)
    {
        $decode = Blowfish::decode($str, $key);
        if ($decode != false) {
            $arr = explode('|', $decode);
            if (count($arr) == 3) {
                if ($arr[0] == sprintf("%u", crc32(trim($_GET['deviceid']))) && is_numeric($arr[1])) {
                    return ['uid' => $arr[1], 'login_count' => (int)$arr[2]];
                }
            }
        }

        return false;
    }
}