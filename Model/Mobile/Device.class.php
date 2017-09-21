<?php
/**
 * Device.class.php
 */

namespace Model\Mobile;

use Bare\DB;
use Common\RedisConst;

/**
 * 设备管理
 *
 * @ignore
 * @package Model\Api
 *
 */
class Device
{
    /**
     * MC key
     */
    const MC_KEY = 'Device_';
    /**
     * REDIS Key
     */
    const REDIS_KEY = 'DU:';
    /**
     * 设备表
     *
     * @var array
     */
    private static $table = [
        APP_APPID_IOS => 'iOSDevice',
        APP_APPID_ADR => 'AndroidDevice'
    ];

    /**
     * 解除用户绑定
     *
     * @param int $appid  APPID
     * @param int $userid 用户ID
     * @return bool
     */
    public static function unbindDevice($appid, $userid)
    {
        if (!isset(self::$table[$appid])) {
            return false;
        }
        $flag = $appid == APP_APPID_IOS ? 'ios' : 'and';

        $redis = DB::redis(RedisConst::MOBILE_DB_W, RedisConst::MOBILE_DB_INDEX);
        $redis->hDel(self::REDIS_KEY . $userid, $flag);

        $pdo = DB::pdo(DB::DB_MOBILE_W);

        $old_device_id = [];
        $old_device = $pdo->find(self::$table[$appid], ['UserId' => $userid], 'DeviceId');
        $pdo->update(self::$table[$appid], ['UserId' => 0], ['UserId' => $userid]);

        if (is_array($old_device) && count($old_device) > 0) {
            foreach ($old_device as $v) {
                $old_device_id[] = $v['DeviceId'];
            }
            self::delCache($old_device_id);
        }

        return true;
    }

    /**
     * 删除MC缓存
     *
     * @param string|array $deviceid 设备ID
     * @return bool
     */
    public static function delCache($deviceid)
    {
        $mc = DB::memcache(DB::MEMCACHE_DEFAULT);

        $deviceid = is_array($deviceid) ? $deviceid : [$deviceid];
        foreach ($deviceid as $v) {
            if ($v) {
                $mc->delete(self::MC_KEY . $v);
            }
        }

        return true;
    }

    /**
     * 初始化设备, 允许新增和更新
     *
     * @param int    $appid     APPID
     * @param string $device_id 设备ID
     * @param string $channel   渠道
     * @param string $token     用于推送的TOKEN
     * @param int    $userid    用户ID
     * @param string $ios_token iOS源生token
     * @return bool
     */
    public static function initDevice($appid, $device_id, $channel = '', $token = '', $userid = 0, $ios_token = '')
    {
        $data = [
            'DeviceId' => $device_id,
            'Channel' => $channel,
            'Status' => 1,
            'CreateTime' => date("Y-m-d H:i:s")
        ];
        $clearold = function ($userid) use (& $pdo, & $table) {
            $device_id = [];
            $device = $pdo->find($table, ['UserId' => $userid], 'DeviceId');
            $pdo->update($table, ['UserId' => 0], ['UserId' => $userid]);

            if (is_array($device) && count($device) > 0) {
                foreach ($device as $v) {
                    $device_id[] = $v['DeviceId'];
                }
                self::delCache($device_id);
            }
        };

        if (!isset(self::$table[$appid])) {
            return false;
        }
        $table = self::$table[$appid];
        $flag = $appid == APP_APPID_IOS ? 'ios' : 'and';

        $pdo = DB::pdo(DB::DB_MOBILE_W);
        $device = self::_getDevice($device_id, $table);

        if (empty($device['Id'])) {
            if ($userid > 0) {
                $clearold($userid);
            }

            $data['UserId'] = $userid;
            $data['Token'] = $token;
            if (APP_APPID_IOS == $appid) {
                $data['iOSToken'] = $ios_token;
            }
            $row_count = $pdo->insert($table, $data, ['ignore' => true]);

            if ($userid > 0) {
                $redis = DB::redis(RedisConst::MOBILE_DB_W, RedisConst::MOBILE_DB_INDEX);
                if (!empty($token) && $row_count == 1) {
                    $redis->hset(self::REDIS_KEY . $userid, $flag, $token);
                } else {
                    $redis->hDel(self::REDIS_KEY . $userid, $flag);
                }
            }
        } else {
            $data = [];
            $data['UserId'] = $userid;

            if ($device['UserId'] > 0) {
                $redis = DB::redis(RedisConst::MOBILE_DB_W, RedisConst::MOBILE_DB_INDEX);
                $redis->hDel(self::REDIS_KEY . $device['UserId'], $flag);
            }

            if ($userid > 0) {
                $clearold($userid);

                $redis = DB::redis(RedisConst::MOBILE_DB_W, RedisConst::MOBILE_DB_INDEX);
                $redis->hset(self::REDIS_KEY . $userid, $flag, $token);
            }

            $data['Token'] = $token;
            if (APP_APPID_IOS == $appid) {
                $data['iOSToken'] = $ios_token;
            }
            self::updateDevice($appid, $device['DeviceId'], $data);
        }

        return true;
    }

    /**
     * 获取设备信息
     *
     * @param string $device_id 设备ID
     * @param string $table     表名
     * @return array
     */
    private static function _getDevice($device_id, $table)
    {
        $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
        $info = $mc->get(self::MC_KEY . $device_id);

        if (empty($info)) {
            $pdo = DB::pdo(DB::DB_MOBILE_R);
            $pdo->prepare("SELECT * FROM $table WHERE DeviceId=:id LIMIT 1");
            $pdo->bindValue(':id', $device_id);
            $pdo->execute();
            $info = $pdo->fetch();

            $info = is_array($info) ? $info : [];
            $mc->set(self::MC_KEY . $device_id, $info, 43200);
        }

        return is_array($info) ? $info : [];
    }

    /**
     * 更新设备信息
     *
     * @param integer $appid    APPID
     * @param string  $deviceid 设备ID
     * @param array   $data     要更新的字段， 见DB table
     * @return bool|int
     */
    public static function updateDevice($appid, $deviceid, $data = [])
    {
        $pdo = DB::pdo(DB::DB_MOBILE_W);

        $ret = $pdo->update(self::$table[$appid], $data, [
            'DeviceId' => $deviceid
        ]);

        self::delCache($deviceid);

        return $ret;
    }

    /**
     * 获得设备信息
     *
     * @param string $appid    APP ID
     * @param string $deviceid 设备ID
     * @return array
     */
    public static function getDeviceInfo($appid, $deviceid)
    {
        $table = self::$table[$appid];

        return self::_getDevice($deviceid, $table);
    }

    /**
     * 按用户ID获取token, android返回设备ID
     *
     * @param int $uid 用户ID
     * @return array ['ios' => false, 'android' => 'tokenxxx']
     */
    public static function getTokenByUserId($uid)
    {
        $redis = DB::redis(RedisConst::MOBILE_DB_R, RedisConst::MOBILE_DB_INDEX);
        $token = $redis->hMGet(self::REDIS_KEY . $uid, ['ios', 'and']);

        return [
            'ios' => $token['ios'],
            'android' => $token['and']
        ];
    }
}