<?php
/**
 * CronQueue.class.php 定时脚本触发队列
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-1-11 上午10:13
 */

namespace Model\RedisDB;

use Bare\DB;

class CronQueue
{
    // 队列数据保存位置
    const REDIS_DB_W = DB::REDIS_QUEUE_W;
    const REDIS_DB_R = DB::REDIS_QUEUE_R;
    const REDIS_DB_INDEX = 3;

    // 通知
    const NOTICE = 'Notice';

    /**
     * 向队列中添加一条数据
     *
     * @param string       $name 队列名
     * @param string|array $data 队列数据
     * @return boolean
     */
    public static function add($name, $data)
    {
        if (empty($name)) {
            return false;
        }
        $data = is_string($data) ? trim($data) : serialize($data);
        static $redis = null;
        if ($redis === null) {
            $redis = DB::redis(self::REDIS_DB_W, self::REDIS_DB_INDEX);
        }
        $result = $redis->rPush($name, $data);
        if (!$result) {
            $logs = [
                'status' => 'fail',
                'name' => $name,
                'data' => $data,
                'time' => date("Y-m-d H:i:s")
            ];
            logs($logs, "CronQueue/{$name}_Fail");

            return false;
        }

        return true;
    }

    /**
     * 获取队列值
     *
     * @param string $name 队列名
     * @return bool|string
     */
    public static function get($name)
    {
        static $redis = null;
        if ($redis === null) {
            $redis = DB::redis(self::REDIS_DB_R, self::REDIS_DB_INDEX);
        }
        $data = $redis->lPop($name);
        if (empty($data)) {
            return false;
        }

        return $data;
    }
}