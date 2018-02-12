<?php
/**
 *  小程序通知 NoticeQueue.php
 *
 * php NoticeQueue.php Notice       通知 定时每5分钟运行一次
 *
 * @author camfee <camfee@foxmail.com>
 * @date   2018/1/2
 */

namespace Controller\Cron;

use Bare\Controller;
use Bare\DB;
use Model\RedisDB\CronQueue;

class NoticeQueue extends Controller
{
    public function index()
    {
        $data = CronQueue::get(CronQueue::NOTICE);
        if (!empty($data)) {
            debug_log("send Notice start, " . json_encode($data), JF_LOG_INFO);
            $data = unserialize($data);
            pre($data);
            // TODO 业务逻辑

            debug_log("send Notice end ", JF_LOG_INFO);
        }
    }
}

