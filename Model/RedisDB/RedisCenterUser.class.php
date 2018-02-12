<?php
/**
 * 用户中心redis缓存
 *
 * @author camfee<camfee@foxmail.com>
 *
 */

namespace Model\RedisDB;

use Bare\DB;

class RedisCenterUser extends RedisDB
{
    const REDIS_DB = DB::REDIS_DB_CACHE_W;
    const REDIS_DB_INDEX = self::REDIS_DB_INDEX_ACCOUNT;

    public static function instance($redisDb = self::REDIS_DB, $dbIndex = self::REDIS_DB_INDEX)
    {
        return parent::instance($redisDb, $dbIndex);
    }

    /**
     * 获取缓存key
     *
     * @param int $uid
     * @return string
     */
    public static function getKey($uid)
    {
        $arr = ['C_User_' . sprintf('%02x', $uid % 256), $uid];

        return parent::getKey($arr);
    }

    public function async(
        $redisKey,
        $db,
        $tableName,
        $primaryKey,
        $primaryValue,
        $fields,
        $redisDbIndex = self::REDIS_DB_INDEX,
        $redisDb = self::REDIS_DB
    ) {
        parent::async($redisKey, $db, $tableName, $primaryKey, $primaryValue, $fields, $redisDbIndex, $redisDb);

    }
}
