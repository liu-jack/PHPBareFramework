<?php
/**
 * 用户中心redis缓存
 *
 * @author camfee<camfee@foxmail.com>
 *
 */

namespace Model\RedisDB;

use Bare\DB;
use Common\RedisConst;

class RedisCenterUser extends RedisCache
{
    const REDIS_DB = RedisConst::ACCOUNT_DB_W;
    const REDIS_DB_INDEX = RedisConst::ACCOUNT_DB_INDEX;
    const TABLE_NAME = 'User';
    const MYSQL_DB = DB::DB_ACCOUNT_W;
    const PRIMARY_KEY = 'UserId';

    /**
     * 获取redis缓存实例
     *
     * @param int    $redis_db
     * @param int    $db_index
     * @param string $class
     * @return mixed|RedisCache
     */
    public static function instance($redis_db = self::REDIS_DB, $db_index = self::REDIS_DB_INDEX, $class = __CLASS__)
    {
        return parent::instance($redis_db, $db_index, $class);
    }

    /**
     * 获取缓存key
     *
     * @param int $uid
     * @return string
     */
    public static function getKey($uid)
    {
        $arr = ['CUser_' . sprintf('%02x', $uid % 256), $uid];

        return parent::getKey($arr);
    }

    /**
     * @param string $redis_key
     * @param string $primary_value
     * @param array  $fields
     * @param string $table_name
     * @param int    $db
     * @param string $primary_key
     * @param int    $redis_db_index
     * @param int    $redis_db
     */
    public function async(
        $redis_key,
        $primary_value,
        $fields,
        $table_name = self::TABLE_NAME,
        $db = self::MYSQL_DB,
        $primary_key = self::PRIMARY_KEY,
        $redis_db_index = self::REDIS_DB_INDEX,
        $redis_db = self::REDIS_DB
    ) {
        parent::async($redis_key, $primary_value, $fields, $table_name, $db, $primary_key, $redis_db_index, $redis_db);

    }
}
