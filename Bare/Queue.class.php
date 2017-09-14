<?php

/**
 * 队列基类
 *
 * @author camfee<camfee@yeah.net>
 * @since  v1.0 2017.04.13
 */

namespace Bare;

declare(ticks=1);

use Common\RedisConst;

/**
 *
 * 队列类
 *
 */
class Queue
{
    /**
     * 队列名称
     *
     * @var string
     */
    protected $name;

    /**
     * 构造函数
     *
     * @param string $name 队列名称
     *
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Redis 实例
     *
     * @param bool $type 是否写库
     * @return \Redis
     */
    protected static function getRedis($type = false)
    {
        if ($type) {
            return DB::redis(RedisConst::QUEUE_DB_W, RedisConst::QUEUE_DB_INDEX);
        } else {
            return DB::redis(RedisConst::QUEUE_DB_R, RedisConst::QUEUE_DB_INDEX);
        }
    }

    /**
     * 获取队列值
     *
     * @param string $name 队列名
     * @return bool|string
     */
    public function get($name)
    {
        $data = $this->getRedis()->lPop($name);
        if (empty($data)) {
            return false;
        }

        return $data;
    }

    /**
     * 设置队列值
     *
     * @param string       $name 队列名
     * @param string|array $data 队列数据
     * @return int
     */
    public static function add($name, $data)
    {
        $data = is_string($data) ? trim($data) : serialize($data);
        $redis = null;
        $redis = self::getRedis(true);
        $result = $redis->rPush($name, $data);
        if (!$result) {
            $logs = [
                'status' => 'fail',
                'name' => $name,
                'data' => $data,
            ];
            logs($logs, "Queue/{$name}_Fail");

            return false;
        }

        return true;
    }

    /**
     * 向队列中添加多条数据
     *
     * @param string $name 队列名
     * @param array  $data 队列数据数组
     * @return boolean
     */
    public static function addMulti($name, array $data)
    {
        if (empty($name) || empty($data)) {
            return false;
        }
        $redis = null;
        $redis = self::getRedis(true);
        $pured = [];
        $result = false;
        foreach ($data as $item) {
            $pured[] = is_string($item) ? trim($item) : serialize($item);
        }
        $chunks = array_chunk($pured, 100);
        foreach ($chunks as $chunk) {
            array_unshift($chunk, $name);
            $result |= call_user_func_array([$redis, 'rPush'], $chunk);
        }
        if (!$result) {
            $logs = [
                'status' => 'fail',
                'name' => $name,
                'data' => $data,
            ];
            logs($logs, "Queue/{$name}_Fail");

            return false;
        }

        return true;
    }

    /**
     * 向队列中添加多条数据［实际是按单条进行添加］
     *
     * @param string $name 队列名
     * @param array  $data 队列数据数组
     * @return boolean
     */
    public static function addMultiByForeach($name, array $data)
    {
        if (empty($name) || empty($data)) {
            return false;
        }
        $pured = [];
        $result = false;
        foreach ($data as $item) {
            $pured[] = is_string($item) ? trim($item) : serialize($item);
        }
        $chunks = array_chunk($pured, 100);
        $redis = null;
        $redis = self::getRedis(true);
        foreach ($chunks as $chunk) {
            array_unshift($chunk, $name);
            $result |= call_user_func_array([$redis, 'rPush'], $chunk);
        }
        if (!$result) {
            $logs = [
                'status' => 'fail',
                'name' => $name,
                'data' => $data
            ];
            logs($logs, "Queue/{$name}_Fail");

            return false;
        }

        return true;
    }

    /**
     * 取队列状态
     *
     * @param string $name 队列名
     * @return array|bool
     */
    public static function getQueueStatus($name)
    {
        if (empty($name)) {
            return false;
        }
        $redis = null;
        $redis = self::getRedis(true);
        $result = [];
        $result['unread'] = $redis->lLen($name);

        return json_encode($result);
    }

    /**
     * 队列循环入口
     *
     * @param \Model\Queue\Queue $class
     */
    public function doLoop($class)
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$class, 'shutDownSignal']);
            pcntl_signal(SIGINT, [$class, 'shutDownSignal']);
        }
        $count = 0;
        while (true) {
            // 判断运行时段
            if (!empty($class->run_period)) {
                $now = time();
                $seconds = $now % 86400;
                // 当前时间已超出运行时段, 休眠至下一个运行开始时段
                if ($seconds >= $class->run_period[1]) {
                    $resume_time = ($now - $seconds) + 86400 + $class->run_period[0];
                    time_sleep_until($resume_time);
                }
            }

            $data = $this->get($this->name);
            if ($data !== false) {
                $count = 0;
                $class->lastdata = $data;
                $data = unserialize($data);
                $class->run($data);
            } else {
                $class->activeSignal();
                $count = $count > 3 ? 3 : ++$count;
            }

            if ($count == 3) {
                if ($class->sleeptime > 0) {
                    sleep($class->sleeptime);
                }
            } else {
                if ($class->usleeptime > 0) {
                    usleep($class->usleeptime);
                }
            }
        }
    }
}
