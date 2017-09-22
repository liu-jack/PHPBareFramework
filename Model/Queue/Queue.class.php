<?php

namespace Model\Queue;

abstract class Queue
{
    /**
     * 队列空闲Sleep时间
     *
     * @var integer
     */
    public $sleeptime = 5;

    /**
     * 队列运行间隔时间
     *
     * @var integer
     */
    public $usleeptime = 500000;

    /**
     * 队列运行时间段
     *
     * @var array
     */
    public $run_period = null;

    /**
     *  最后一条数据
     *
     * @var
     */
    public $lastdata;

    /**
     * 队列未取到数据时的活动信号传递
     *
     * @return boolean|void
     */
    public function activeSignal()
    {
        $this->checkTrigger();
    }

    /**
     * 队列被终止时调用方法
     *
     * @return boolean
     */
    public function shutDownSignal()
    {
        $this->checkTrigger(true);
        $log = [
            'Killed',
            __CLASS__,
            json_encode($this->lastdata)
        ];
        logs($log, 'Queue/QueueKillInfo');
        exit;
    }

    /**
     * 检查触发更新函数
     *
     * @param bool $force 是否强制更新
     * @return bool
     */
    public function checkTrigger($force = false)
    {
        return $force;
    }

    /**
     * 错误日志路径
     *
     * @return string
     */
    public function logPath()
    {
        return 'Queue/' . static::class;
    }

    /**
     *  用于处理队列返回的数据
     *
     * @param array|string $data 队列中存入的数据
     */
    abstract public function run($data);
}