<?php

namespace Model\RedisDB;

use Bare\DB;
use Common\RedisConst;

/**
 * RedisQueue.class.php
 * Created by IntelliJ IDEA.
 *
 * Date: 2018/1/11
 * Time: 17:16
 */
class RedisQueue
{
    //异步更新所有表数据队列
    const TYPE_ASYNC_TABLES = 'AsyncAllTables';
    // 通知队列
    const TYPE_MESSAGE_NOTICE = 'MessageNotice';

    private static $_instance = [];
    private $_queueName = '';
    private $_redis = null;

    public function __construct($queueName)
    {
        $this->_queueName = $queueName;
        $this->_redis = self::getRedis(true);
    }

    public static function instance($queueName)
    {
        if (empty(self::$_instance[$queueName])) {
            self::$_instance[$queueName] = new RedisQueue($queueName);
        }

        return self::$_instance[$queueName];
    }

    /**
     * 添加数据到队列
     *
     * @param $data
     * @return bool|int
     */
    public function push($data)
    {
        try {
            return $this->_redis->rPush($this->_queueName, serialize($data));
        } catch (\Exception $exception) {
            debug_log($exception);

            return false;
        }
    }

    /**
     * 获取队列长度
     *
     * @return int
     */
    public function len()
    {
        return $this->_redis->lLen($this->_queueName);
    }

    /**
     * 清空队列
     */
    public function clear()
    {
        $this->_redis->delete($this->_queueName);
    }

    /**
     * 队列出栈
     *
     * @return bool|mixed|string
     */
    public function pop()
    {
        try {
            $ret = $this->_redis->lPop($this->_queueName);
            if ($ret !== false) {
                return unserialize($ret);
            }

            return $ret;
        } catch (\Exception $exception) {
            debug_log($exception);

            return false;
        }
    }

    /**
     * 获取redis队列实例
     *
     * @param bool $write
     * @return \Bare\DataDriver\RedisDriver
     */
    public function getRedis($write = true)
    {
        return DB::redis($write ? RedisConst::SYNC_DB_W : RedisConst::SYNC_DB_R, RedisConst::SYNC_DB_INDEX);
    }
}
