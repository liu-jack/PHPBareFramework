<?php

/**
 * 通行证共用方法
 *
 */

namespace Model\Passport;

use Bare\DB;
use Model\Account\User as AUser;

class Passport
{
    /**
     * redis db 序号
     */
    const REDIS_INDEX = 11;

    /**
     * 通行证用户MC缓存前缀
     *
     * @var string
     */
    protected static $_mc_prefix_user = 'PUser_';

    /**
     * 通行证允许更新的字段
     *
     * @var array
     */
    protected static $_fields = [
        'Email',
        'Mobile',
        'UserName',
        'Password',
        'LoginTime',
        'LoginIp',
        'LoginCount',
        'Status',
    ];

    /**
     * Redis前缀
     *
     * @var array
     */
    protected static $_redis_prefix = [
        'uid' => 'PU:',
        'name' => 'PN:',
        'email' => 'PE:',
        'mobile' => 'PM:'
    ];

    /**
     * 检查用户名是否存在
     *
     * @param string $name 用户名
     * @param bool $check_db 是否从DB检查
     * @return bool|int         存在返回用户ID, 不存在返回false
     */
    public static function isUserNameExists($name, $check_db = false)
    {
        $redis = DB::redis(DB::REDIS_PASSPORT_W, self::REDIS_INDEX);
        $key = self::$_redis_prefix['name'] . base64_encode(strtolower($name));
        $userid = $redis->get($key);
        if ($userid > 0) {
            return $userid;
        }
        if ($check_db) {
            $pdo = DB::pdo(DB::DB_PASSPORT_W);
            $pdo->prepare('select `UserId` from `User` where `UserName`=:name limit 1');
            $pdo->bindValue(':name', $name);
            $pdo->execute();
            $userid = $pdo->fetchColumn();
            if ($userid > 0) {
                $redis->set($key, $userid);
            }
            return $userid > 0 ? $userid : false;
        }
        return false;
    }

    /**
     * 检查邮箱是否存在
     *
     * @param string $email 邮箱地址
     * @param bool $check_db 是否从DB检查
     * @return bool|int         存在返回用户ID, 不存在返回false
     */
    public static function isEmailExists($email, $check_db = false)
    {
        $redis = DB::redis(DB::REDIS_PASSPORT_W, self::REDIS_INDEX);
        $key = self::$_redis_prefix['email'] . $email;
        $userid = $redis->get($key);
        if ($userid > 0) {
            return $userid;
        }
        if ($check_db) {
            $pdo = DB::pdo(DB::DB_PASSPORT_W);
            $pdo->prepare('SELECT `UserId` FROM `User` WHERE `Email`=:email LIMIT 1');
            $pdo->bindValue(':email', $email);
            $pdo->execute();
            $userid = $pdo->fetchColumn();
            if ($userid > 0) {
                $redis->set($key, $userid);
            }
            return $userid > 0 ? $userid : false;
        }
        return false;
    }

    /**
     * 检查手机是否存在
     *
     * @param string $mobile 手机号码
     * @param bool $check_db 是否从DB检查
     * @return bool|int         存在返回用户ID, 不存在返回false
     */
    public static function isMobileExists($mobile, $check_db = false)
    {
        $redis = DB::redis(DB::REDIS_PASSPORT_W, self::REDIS_INDEX);
        $key = self::$_redis_prefix['mobile'] . $mobile;
        $userid = $redis->get($key);
        if ($userid > 0) {
            return $userid;
        }
        if ($check_db) {
            $pdo = DB::pdo(DB::DB_PASSPORT_W);
            $pdo->prepare('SELECT `UserId` FROM `User` WHERE `Mobile`=:mobile LIMIT 1');
            $pdo->bindValue(':mobile', $mobile);
            $pdo->execute();
            $userid = $pdo->fetchColumn();
            if ($userid > 0) {
                $redis->set($key, $userid);
            }
            return $userid > 0 ? $userid : false;
        }
        return false;

    }

    /**
     * 检查多个手机号码是否存在
     *
     * @param array $mobiles 手机号码
     * @return array         ['mobile1' => 123, 'mobile2' => false]
     */
    public static function checkMobileExists(array $mobiles)
    {
        $redis = DB::redis(DB::REDIS_PASSPORT_W, self::REDIS_INDEX);
        $redis->multi(\Redis::PIPELINE);
        foreach ($mobiles as $v) {
            $redis->get(self::$_redis_prefix['mobile'] . $v);
        }
        $ret = $redis->exec();
        $result = [];
        foreach ($ret as $k => $v) {
            $result[$mobiles[$k]] = $v;
        }
        return $result;

    }

    /**
     * 检查用户名是否符合规范
     *
     * @param string $name 用户名
     * @return array|bool        正确返回true,错误返回['code' => 失败代码, 'msg' => 失败原因]
     */
    public static function checkUserName($name)
    {
        $prepare = preg_replace('/[\x{4e00}-\x{9fa5}]/iu', '**', $name);
        $len = strlen($prepare);
        if ($len < 2 || $len > 20) {
            return ['code' => 201, 'msg' => '用户名需要2-20位字符'];
        }
        if (!preg_match('/^[\x{4e00}-\x{9fa5}0-9a-z\.\_\*]+$/iu', $name)) {
            return ['code' => 202, 'msg' => '用户不能使用特殊字符'];
        }
        // 检查禁止用户名
        $forbidden_name = config('passport/name');
        if (preg_match('/(' . implode('|', $forbidden_name) . ')/i', $name)) {
            return ['code' => 203, 'msg' => '用户名被保留'];
        }
        return true;
    }

    /**
     * 检查密码复杂度
     *
     * @param string $pwd 密码
     * @return int           复杂度 0-4, 越高复杂度越高
     */
    public static function checkPwdStrong($pwd)
    {
        if ((strlen($pwd) < 8 && preg_match('/^\d+$/', $pwd)) || empty($pwd)) {
            return 0;
        }
        $number = preg_match('/\d/', $pwd) ? 1 : 0;
        $upper = preg_match('/[A-Z]/', $pwd) ? 1 : 0;
        $lower = preg_match('/[a-z]/', $pwd) ? 1 : 0;
        $symbol = preg_match('/^[a-zA-Z0-9]+$/', $pwd) ? 0 : 1;
        return ($number + $upper + $lower + $symbol);
    }

    /**
     * 隐藏号码昵称
     *
     * @param string $mobile 手机号码
     * @return string
     */
    public static function hideMobileName($mobile)
    {
        return mb_substr($mobile, 0, 3) . '****' . mb_substr($mobile, 7);
    }

    /**
     * 获取用户信息
     *
     * @param int|array $userid 用户ID
     * @param bool $no_cache true:不使用缓存, false: 使用
     * @return array      失败返回[]
     */
    protected static function getUserById($userid, $no_cache = false)
    {
        $userids = is_array($userid) ? $userid : [$userid];
        $data_cache = $userinfo = $mc_ids = $nocache_ids = [];
        foreach ($userids as $v) {
            $v = (int)$v;
            if ($v > 0) {
                $mc_ids[$v] = self::$_mc_prefix_user . $v;
                $nocache_ids[$v] = $v;
            }
        }
        if (empty($mc_ids)) {
            return $mc_ids;
        }
        $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
        if (!$no_cache) {
            $data = $mc->get($mc_ids);
            if (is_array($data) && count($data) > 0) {
                foreach ($data as $v) {
                    $data_cache[$v['UserId']] = $v;
                    unset($nocache_ids[$v['UserId']]);
                }
            }
        }
        if (count($nocache_ids) > 0) {
            $pdo = DB::pdo(DB::DB_PASSPORT_R);
            $ret = $pdo->find('User', [
                'UserId IN' => $nocache_ids
            ]);
            if (is_array($ret) && count($ret) > 0) {
                foreach ($ret as & $v) {
                    $mc->set(self::$_mc_prefix_user . $v['UserId'], $v);
                    $data_cache[$v['UserId']] = $v;
                }
            }
        }
        foreach ($userids as $v) {
            if ($data_cache[$v]['UserId']) {
                $userinfo[$v] = $data_cache[$v];
            }
        }
        if (is_numeric($userid)) {
            return $userinfo[$userid];
        }
        return !empty($userinfo) ? $userinfo : [];
    }

    /**
     * 更新用户信息
     *
     * @param int $userid 用户ID
     * @param array $data 数据
     * @return bool|array          成功返回true, 失败返回['code' => 失败代码, 'msg' => 失败原因]
     */
    protected static function updateUser($userid, $data)
    {
        $userid = (int)$userid;
        if (empty($data)) {
            return ['code' => 200, 'msg' => '没有数据需要更新'];
        }
        foreach ($data as $key => $val) {
            if (!in_array($key, self::$_fields)) {
                return ['code' => 300, 'msg' => "$key 不在更新字段授权中"];
            }
        }
        $userinfo = self::getUserById($userid);
        if (empty($userinfo['UserId'])) {
            return ['code' => 404, 'msg' => '用户不存在'];
        }

        if (!empty($data['Password'])) {
            $data['Password'] = password_hash($data['Password'], PASSWORD_DEFAULT);
        }

        if (!empty($data['Email']) && $data['Email'] != $userinfo['Email']) {
            if (filter_var($data['Email'], FILTER_VALIDATE_EMAIL) === false) {
                return ['code' => 202, 'msg' => 'Email格式不正确'];
            }
            if (self::isEmailExists($data['Email'], true)) {
                return ['code' => 212, 'msg' => '邮箱已经被使用'];
            }
        }

        if (!empty($data['Mobile']) && $data['Mobile'] != $userinfo['Mobile']) {
            if (!preg_match('/^1[0-9]{10}$/', $data['Mobile'])) {
                return ['code' => 203, 'msg' => '手机号码格式不正确'];
            }
            if (self::isMobileExists($data['Mobile'], true)) {
                return ['code' => 213, 'msg' => '手机号码已经被使用'];
            }
        }
        if (!empty($data['UserName']) && $data['UserName'] != $userinfo['UserName']) {
            if (($checkname = self::checkUserName($data['UserName'])) !== true) {
                return $checkname;
            }
            if (self::isUserNameExists($data['UserName'], true)) {
                return ['code' => 211, 'msg' => '用户名已被使用'];
            }
        }

        $pdo = DB::pdo(DB::DB_PASSPORT_W);
        $count = $pdo->update('User_' . table($userid), $data, ['UserId' => $userid]);

        if ($count > 0) {
            $pdo->update('User', $data, ['UserId' => $userid]);

            $redis = DB::redis(DB::REDIS_PASSPORT_W, self::REDIS_INDEX);
            $redis->multi(\Redis::PIPELINE);

            if (!empty($data['UserName']) && $data['UserName'] != $userinfo['UserName']) {
                $redis->hMset(self::$_redis_prefix['uid'] . $userid, ['name' => $data['UserName']]);
                $redis->delete(self::$_redis_prefix['name'] . base64_encode(strtolower($userinfo['UserName'])));
                $redis->set(self::$_redis_prefix['name'] . base64_encode(strtolower($data['UserName'])), $userid);
                // 同步修改
                AUser::updateUser($userid, ['LoginName' => $data['UserName']]);
            }
            if (!empty($data['Email']) && $data['Email'] != $userinfo['Email']) {
                $redis->delete(self::$_redis_prefix['email'] . $userinfo['Email']);
                $redis->set(self::$_redis_prefix['email'] . $data['Email'], $userid);
            }
            if (!empty($data['Mobile']) && $data['Mobile'] != $userinfo['Mobile']) {
                $redis->delete(self::$_redis_prefix['mobile'] . $userinfo['Mobile']);
                $redis->set(self::$_redis_prefix['mobile'] . $data['Mobile'], $userid);
                $redis->hMset(self::$_redis_prefix['uid'] . $userid, ['mobile' => $data['Mobile']]);
            }
            $redis->exec();
            $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
            $mc->delete(self::$_mc_prefix_user . $userid);
        }

        return true;
    }
}