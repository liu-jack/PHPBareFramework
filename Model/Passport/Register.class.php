<?php

/**
 * 注册处理类
 *
 */

namespace Model\Passport;

use Common\RedisConst;
use Model\Account\User as AUser;
use Bare\DB;

class Register extends Passport
{
    // 注册平台
    const REG_PLATFORM_WEB = 0;            // Web 站
    const REG_PLATFORM_WAP = 1;            // Wap 站
    const REG_PLATFORM_ANDROID = 2;        // Android 手机
    const REG_PLATFORM_IPHONE = 3;         // iPhone 手机
    const REG_PLATFORM_XCX = 4;         // 小程序

    // 注册产品来源
    const REG_FROM_PASSPORT = 0;           // 通行证

    // 注册方式
    const REG_WAY_USERNAME = 0;            // 用户名
    const REG_WAY_EMAIL = 1;               // 邮件
    const REG_WAY_MOBILE = 2;              // 手机

    // 注册类型
    const REG_TYPE_USERNAME = 0;           // 用户名注册
    const REG_TYPE_EMAIL = 1;              // 邮件注册
    const REG_TYPE_MOBILE = 2;             // 手机注册

    /**
     * appid注册平台map
     *
     * @var array
     */
    public static $appid_reg_platform = [
        APP_APPID_WEB => self::REG_PLATFORM_WEB,
        APP_APPID_WAP => self::REG_PLATFORM_WAP,
        APP_APPID_ADR => self::REG_PLATFORM_ANDROID,
        APP_APPID_IOS => self::REG_PLATFORM_IPHONE,
        APP_APPID_XCX => self::REG_PLATFORM_XCX,
    ];

    /**
     * 注册平台
     *
     * @var array
     */
    private static $reg_platform = [
        self::REG_PLATFORM_WEB => 'web',
        self::REG_PLATFORM_WAP => 'wap',
        self::REG_PLATFORM_ANDROID => 'Android',
        self::REG_PLATFORM_IPHONE => 'iPhone',
        self::REG_PLATFORM_XCX => 'xcx',
    ];

    /**
     * 注册来源 (用于本站多平台)
     *
     * @var array
     */
    private static $reg_from = [
        self::REG_FROM_PASSPORT => 'passport',
    ];

    /**
     * 注册来路 (用于第三方平台)
     *
     * @var array
     */
    private static $reg_way = [
        self::REG_WAY_EMAIL => 'email',
        self::REG_WAY_MOBILE => 'mobile',
        self::REG_WAY_USERNAME => 'username',
    ];

    /**
     * 注册一个用户
     *
     * @param array $data       注册字段数据 ['Email','Mobile','UserName','Password','FromPlatform','FromProduct','FromWay']
     * @param int   $type       类型,见REG_TYPE_*
     * @param bool  $init_auser 是否初始化account用户
     * @return array          失败返回 ['code' => 失败代码, 'msg' => 失败原因]
     *                          成功返回 ['UserId' => 用户ID, ...]
     */
    public static function addUser($data = [], $type = self::REG_TYPE_USERNAME, $init_auser = true)
    {
        $data['UserName'] = trim($data['UserName']);
        $data['Email'] = trim($data['Email']);
        $data['Mobile'] = trim($data['Mobile']);
        $data['Password'] = trim($data['Password']);

        if (!isset(self::$reg_platform[$data['FromPlatform']])) {
            return ['code' => 211, 'msg' => 'FromPlatform 不符合定义'];
        }

        if (!isset(self::$reg_from[$data['FromProduct']])) {
            return ['code' => 212, 'msg' => 'FromProduct 不符合定义'];
        }

        if (!isset(self::$reg_way[$data['FromWay']])) {
            return ['code' => 213, 'msg' => 'FromWay 不符合定义'];
        }

        $user = [
            'Password' => empty($data['Password']) ? '' : password_hash($data['Password'], PASSWORD_DEFAULT),
            'RegTime' => date("Y-m-d H:i:s"),
            'RegIp' => ip(),
            'LoginCount' => 0,
            'Status' => 1,
            'FromPlatform' => $data['FromPlatform'],
            'FromProduct' => $data['FromProduct'],
            'FromWay' => $data['FromWay'],
        ];

        if ($type == self::REG_TYPE_EMAIL) {
            if (filter_var($data['Email'], FILTER_VALIDATE_EMAIL) === false) {
                return ['code' => 204, 'msg' => 'Email格式不正确'];
            }
            $user['Email'] = $data['Email'];
        }
        if ($type == self::REG_TYPE_MOBILE) {
            if (!preg_match('/^1[0-9]{10}$/', $data['Mobile'])) {
                return ['code' => 205, 'msg' => '手机号码格式不正确'];
            }
            $user['Mobile'] = $data['Mobile'];
        }

        if (($checkname = self::checkUserName($data['UserName'])) !== true) {
            return $checkname;
        }
        $user['UserName'] = $data['UserName'];

        if (self::isUserNameExists($data['UserName'])) {
            return ['code' => 221, 'msg' => '用户名已被使用'];
        }
        if ($type == self::REG_TYPE_EMAIL && self::isEmailExists($data['Email'])) {
            return ['code' => 222, 'msg' => '邮箱已经被使用'];
        }
        if ($type == self::REG_TYPE_MOBILE && self::isMobileExists($data['Mobile'])) {
            return ['code' => 223, 'msg' => '手机号码已经被使用'];
        }

        $pdo = DB::pdo(DB::DB_PASSPORT_W);
        $res = $pdo->insert('User', $user, ['ignore' => true]);
        if ($res === 1) {
            $user['UserId'] = $pdo->lastInsertId();
            $res = $pdo->insert('User_' . table($user['UserId']), $user);

            if ($res == false || $res < 1) {
                logs($user, 'Passport/Reg/DbInsertFail');

                return ['code' => 215, 'msg' => '数据库写入失败'];
            }
            self::_initRedis($user);
            if ($init_auser) {
                // 初始化用户表
                AUser::addUser([
                    'UserId' => $user['UserId'],
                    'LoginName' => $user['UserName'],
                    'UserNick' => !empty($data['Mobile']) ? self::hideMobileName($data['Mobile']) : $user['UserName']
                ]);
            }

            return $user;
        } else {
            if ($res === 0 && $res !== false) {
                return ['code' => 217, 'msg' => '用户已经存在'];
            }
        }

        return ['code' => 216, 'msg' => '数据库写入失败'];
    }

    /**
     * 获得一个随机名字
     *
     * @return string
     */
    public static function getRandomName()
    {
        return 'F' . base_convert(time() . mt_rand(1000, 9999), 10, 36);
    }

    /**
     * 初始化redis
     *
     * @param array $user 用户信息
     * @return bool
     */
    private static function _initRedis($user)
    {
        $redis = DB::redis(RedisConst::PASSPORT_DB_W, RedisConst::PASSPORT_DB_INDEX);
        $redis->multi(\Redis::PIPELINE);
        if (!empty($user['Email'])) {
            $redis->set(self::$_redis_prefix['email'] . $user['Email'], $user['UserId']);
        }
        if (!empty($user['Mobile'])) {
            $redis->set(self::$_redis_prefix['mobile'] . $user['Mobile'], $user['UserId']);
        }
        $redis->set(self::$_redis_prefix['name'] . base64_encode(strtolower($user['UserName'])), $user['UserId']);
        $redis->hMset(self::$_redis_prefix['uid'] . $user['UserId'], [
            'name' => $user['UserName'],
            'email' => $user['Email'],
            'mobile' => $user['Mobile'],
        ]);
        $res = $redis->exec();
        foreach ($res as $v) {
            if ($v === false) {
                logs($user, 'Passport/Reg/InitRedisFail');

                return false;
            }
        }

        return true;
    }
}