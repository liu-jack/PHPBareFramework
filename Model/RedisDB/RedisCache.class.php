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
use Common\RedisConst;

class RedisCache
{
    const REDIS_DB_INDEX = RedisConst::PASSPORT_INDEX;
    const REDIS_DB = RedisConst::CACHE_DB_W;
    const TABLE_NAME = 'User';
    const MYSQL_DB = DB::DB_PASSPORT_W;
    const PRIMARY_KEY = 'Id';

    const REDIS_DB_READ = RedisConst::CACHE_DB_R;
    const REDIS_DB_WRITE = RedisConst::CACHE_DB_W;


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
     * @param int    $redis_db
     * @param int    $db_index
     * @param string $class
     * @return mixed|RedisCache
     */
    public static function instance($redis_db = self::REDIS_DB_READ, $db_index = 0, $class = __CLASS__)
    {
        if (empty(self::$_instance[$redis_db][$db_index][$class])) {
            self::$_instance[$redis_db][$db_index][$class] = new static($redis_db, $db_index);
        }

        return self::$_instance[$redis_db][$db_index][$class];
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
     * RedisDB constructor.
     *
     * @param $redis_db
     * @param $db_index
     */
    public function __construct($redis_db, $db_index)
    {
        if ($redis_db == 0) {
            $redis_db = static::REDIS_DB_WRITE;
        }
        if ($db_index == 0) {
            $db_index = static::REDIS_DB_INDEX;
        }
        $this->_redis = DB::redis($redis_db, $db_index);
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
            $keys = array_keys($keys);
            foreach ($result as $k => $v) {
                if (!is_null($v)) {
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
     * 添加到列表 右侧入队
     *
     * @param $key string  缓存名
     * @param $id  string 数据id（主键）
     * @return int
     */
    public function rPush($key, $id)
    {
        return $this->_redis->rpush($key, $id);
    }

    /**
     * 检查给定 key 是否存在
     */
    public function exists($key)
    {
        return $this->_redis->exists($key);
    }

    /**
     * 返回列表 key 的长度
     */
    public function lLen($key)
    {
        return $this->_redis->llen($key);
    }

    /**
     * 返回列表 key 中指定区间内的元素
     */
    public function lRange($key, $offset, $row)
    {
        return $this->_redis->lrange($key, $offset, $row);
    }

    /**
     * 同步数据到mysql，异步操作
     *
     * @param string $redis_key      redis的Key
     * @param string $primary_value  主键值
     * @param array  $fields         修改的那几个字段
     * @param string $table_name     表名
     * @param int    $db             Bridge::DB_XXXXX_W
     * @param string $primary_key    主键值名称
     * @param int    $redis_db_index RedisDb index
     * @param int    $redis_db       RedisDb
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
        if ($redis_db == 0) {
            $redis_db = static::REDIS_DB_WRITE;
        }
        RedisQueue::instance(RedisQueue::TYPE_ASYNC_TABLES)->push([
            self::FIELD_REDIS_KEY => $redis_key,
            self::FIELD_REDIS_DB => $redis_db,
            self::FIELD_REDIS_DB_INDEX => $redis_db_index,
            self::FIELD_DB_PARAM => $db,
            self::FIELD_DB_TABLE_NAME => $table_name,
            self::FIELD_PRIMARY_KEY => $primary_key,
            self::FIELD_PRIMARY_VALUE => $primary_value,
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
    public function checkNeedAsync($redisDb, $redisDbIndex, $redisKey)
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
