<?php
/**
 *
 * Redis 对象存储,  可继承，修改 DB_INDEX, DB_READ, DB_WRITE
 *
 * @author
 *
 * Date: 2018/1/15
 * Time: 15:33
 */

namespace Model\RedisDB;

use Bare\DB;

class RedisDB
{
    // 通行证
    const REDIS_DB_INDEX_PASSPORT = 0;
    // 用户中心
    const REDIS_DB_INDEX_ACCOUNT = 1;

    const REDIS_DB_INDEX = 0;
    const REDIS_DB_READ = DB::REDIS_DB_CACHE_R;
    const REDIS_DB_WRITE = DB::REDIS_DB_CACHE_W;

    const FIELD_REDIS_KEY = 'RedisKey';
    const FIELD_REDIS_DB = 'RedisDb';
    const FIELD_REDIS_DB_INDEX = 'RedisDbIndex';
    const FIELD_DB_PARAM = 'DbParam';
    const FIELD_DB_TABLE_NAME = 'TableName';
    const FIELD_PRIMARY_KEY = 'PrimaryKey';
    const FIELD_PRIMARY_VALUE = 'PrimaryValue';
    const FIELD_FIELDS = 'Fields';
    const FIELD_SYNC_FLAG = 'ASYNC_FLAG'; //每一个DBProject 都会存储，避免重复提交更新

    private static $_instance = [];
    private $_redis = null;

    /**
     * @param int $redisDb
     * @param int $dbIndex
     * @return RedisDB
     */
    public static function instance($redisDb = self::REDIS_DB_READ, $dbIndex = 0)
    {
        if (empty(self::$_instance[$redisDb][$dbIndex])) {
            self::$_instance[$redisDb][$dbIndex] = new static($redisDb, $dbIndex);
        }

        return self::$_instance[$redisDb][$dbIndex];
    }

    public function __construct($redisDb, $dbIndex)
    {
        if ($redisDb == 0) {
            $redisDb = static::REDIS_DB_WRITE;
        }
        if ($dbIndex == 0) {
            $dbIndex = static::REDIS_DB_INDEX;
        }
        $this->_redis = DB::redis($redisDb, $dbIndex);
    }

    /**
     * 保存数据(可以保存部分)
     *
     * @param $key
     * @param $info
     *
     * @return bool
     */
    public function save($key, $info)
    {
        return $this->_redis->hMset($key, $info);
    }

    /**
     * 加载数据
     *
     * @param $key
     *
     * @return array
     */
    public function load($key)
    {
        return $this->_redis->hGetAll($key);
    }

    /**
     * 加载多个数据
     *
     * @param array $keys keys数组
     * @return array
     */
    public function loads($keys)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[] = $this->_redis->hGetAll($key);
        }
        $data = [];
        if (!empty($result)) {
            $keys = array_values($keys);
            foreach ($result as $k => $v) {
                if (!empty($v)) {
                    $data[$keys[$k]] = $v;
                }
            }
        }

        return $data;
    }

    /**
     * 设置某个字段的值
     *
     * @param $key
     * @param $field
     * @param $value
     *
     * @return int
     */
    public function set($key, $field, $value)
    {
        return $this->_redis->hSet($key, $field, $value);
    }

    /**
     * 获取 某个字段的值
     *
     * @param $key
     * @param $field
     *
     * @return string
     */
    public function get($key, $field)
    {
        return $this->_redis->hGet($key, $field);
    }

    /**
     * 获取多个字段的值
     *
     * @param $key
     * @param $fields
     *
     * @return array
     */
    public function getMulti($key, $fields)
    {
        return $this->_redis->hMGet($key, $fields);
    }

    /**
     * 增加某个字段的值
     *
     * @param $key
     * @param $field
     * @param $add
     *
     * @return int
     */
    public function hIncrBy($key, $field, $add)
    {
        return $this->_redis->hIncrBy($key, $field, $add);
    }

    /**
     * 增加某个浮点型字段的值
     *
     * @param $key
     * @param $field
     * @param $add
     *
     * @return float
     */
    public function hIncryByFloat($key, $field, $add)
    {
        return $this->_redis->hIncrByFloat($key, $field, $add);
    }

    /**
     * 删除某个字段
     *
     * @param $key
     * @param $filed
     *
     * @return int
     */
    public function hDel($key, $filed)
    {
        return $this->_redis->hDel($key, $filed);
    }

    /**
     * 删除整个key
     *
     * @param $key
     *
     * @return mixed
     */
    public function del($key)
    {
        return $this->_redis->del($key);
    }

    /**
     * 生成Key
     *
     * @param $arr array 数组, 第一个必须是表名
     *
     * @return string
     */
    public static function getKey($arr)
    {
        return implode(":", $arr);
    }

    /**
     * 同步数据到mysql，异步操作
     *
     * @param string $redisKey     redis的Key
     * @param int    $db           Bridge::DB_XXXXX_W
     * @param string $tableName    表名
     * @param string $primaryKey   主键值名称
     * @param string $primaryValue 主键值
     * @param array  $fields       修改的那几个字段
     * @param int    $redisDbIndex RedisDb index
     * @param int    $redisDb      RedisDb
     */
    public function async(
        $redisKey,
        $db,
        $tableName,
        $primaryKey,
        $primaryValue,
        $fields,
        $redisDbIndex = self::REDIS_DB_INDEX_PASSPORT,
        $redisDb = 0
    ) {
        if ($redisDb == 0) {
            $redisDb = static::REDIS_DB_WRITE;
        }
        RedisQueue::instance(RedisQueue::TYPE_ASYNC_TABLES)->push([
            self::FIELD_REDIS_KEY => $redisKey,
            self::FIELD_REDIS_DB => $redisDb,
            self::FIELD_REDIS_DB_INDEX => $redisDbIndex,

            self::FIELD_DB_PARAM => $db,
            self::FIELD_DB_TABLE_NAME => $tableName,
            self::FIELD_PRIMARY_KEY => $primaryKey,
            self::FIELD_PRIMARY_VALUE => $primaryValue,
            self::FIELD_FIELDS => $fields,
        ]);
    }


    /**
     * 设置异步更新标志
     *
     * @param $redisDb
     * @param $redisDbIndex
     * @param $redisKey
     */
    public function setAsyncFlag($redisDb, $redisDbIndex, $redisKey)
    {
        DB::redis($redisDb, $redisDbIndex)->hSet($redisKey, self::FIELD_SYNC_FLAG, time() + 300);
    }


    /**
     * 检查是否需要异步更新
     *
     * @param $redisDb
     * @param $redisDbIndex
     * @param $redisKey
     *
     * @return bool
     */
    private function checkNeedAsync($redisDb, $redisDbIndex, $redisKey)
    {
        $t = DB::redis($redisDb, $redisDbIndex)->hGet($redisKey, self::FIELD_SYNC_FLAG);
        if ($t == false) {
            return true;
        }
        $now = time();
        if ($now < $t) {
            return false;
        }

        return true;
    }
}
