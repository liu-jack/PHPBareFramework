<?php
/**
 * StraitQueue.class.php
 * 定时队列基类
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-5-11 上午11:22
 *
 */

namespace Model\CronQueue;

abstract class AbstractQueue
{
    /**
     * 队列数据处理
     *
     * @param $data
     * @return mixed
     */
    abstract function run($data);

    /**
     * 错误日志
     *
     * @param $data
     */
    public function log($data)
    {
        logs($data, 'cronQueue/' . static::class);
    }
}