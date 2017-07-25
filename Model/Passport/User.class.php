<?php

/**
 * 通行证用户信息类
 *
 */

namespace Model\Passport;

class User extends Passport
{
    /**
     * 获取用户的通行证信息
     *
     * @param int $userid 用户ID
     * @param bool $no_cache true:不使用缓存, false: 使用
     * @return array         失败返回[],成功返回用户数据
     */
    public static function getUserByIds($userid, $no_cache = false)
    {
        return self::getUserById($userid, $no_cache);
    }

    /**
     * 根据手机号码获取用户ID
     *
     * @param string $mobile 手机号码
     * @param bool $check_db 是否从DB获取
     * @return bool|int         存在返回用户ID, 不存在返回false
     */
    public static function getUserIdByMobile($mobile, $check_db = false)
    {
        return self::isMobileExists($mobile, $check_db);
    }

    /**
     * 根据多个手机号码获取用户ID
     *
     * @param array $mobiles 手机号码
     * @return array ['mobile1' => 123, 'mobile2' => false]
     */
    public static function getUserIdByMobiles(array $mobiles)
    {
        return self::checkMobileExists($mobiles);
    }

    /**
     * 根据邮箱获取用户ID
     *
     * @param string $email 手机号码
     * @param bool $check_db 是否从DB获取
     * @return bool|int         存在返回用户ID, 不存在返回false
     */
    public static function getUserIdByEmail($email, $check_db = false)
    {
        return self::isEmailExists($email, $check_db);
    }

    /**
     * 根据用户名获取用户ID
     *
     * @param string $name 用户名
     * @param bool $check_db 是否从DB获取
     * @return bool|int        存在返回用户ID, 不存在返回false
     */
    public static function getUserIdByName($name, $check_db = false)
    {
        return self::isUserNameExists($name, $check_db);
    }

    /**
     * 重置用户密码
     *
     * @param int $userid 用户ID
     * @param string $password 新密码
     * @return bool            成功返回true,失败返回false
     */
    public static function updatePassword($userid, $password)
    {
        if (empty($password)) {
            return false;
        }
        if (self::updateUser($userid, ['Password' => $password]) === true) {
            return true;
        }
        return false;
    }

    /**
     * 修改用户昵称
     *
     * @param int $userid 用户ID
     * @param string $name 用户名
     * @return bool          成功返回true, 失败返回['code' => 失败代码, 'msg' => 失败原因]
     */
    public static function updateUserName($userid, $name)
    {
        return self::updateUser($userid, ['UserName' => $name]);
    }

    /**
     * 设置/修改手机号码
     *
     * @param int $userid 用户ID
     * @param string $mobile 用户名
     * @return bool          成功返回true, 失败返回['code' => 失败代码, 'msg' => 失败原因]
     */
    public static function updateMobile($userid, $mobile)
    {
        return self::updateUser($userid, ['Mobile' => $mobile]);
    }

    /**
     * 设置/修改邮箱地址
     *
     * @param int $userid 用户ID
     * @param string $email 用户名
     * @return bool          成功返回true, 失败返回['code' => 失败代码, 'msg' => 失败原因]
     */
    public static function updateEmail($userid, $email)
    {
        return self::updateUser($userid, ['Email' => $email]);
    }

    /**
     * 锁定/解锁用户
     *
     * @param int $userid 用户ID
     * @param bool $lock true锁定用户,false解锁用户
     * @return bool             成功返回true, 失败返回['code' => 失败代码, 'msg' => 失败原因]
     */
    public static function lockUser($userid, $lock = true)
    {
        $status = $lock ? 0 : 1;

        return self::updateUser($userid, ['Status' => $status]);
    }
}