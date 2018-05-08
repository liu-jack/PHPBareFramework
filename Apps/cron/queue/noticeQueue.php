<?php
/**
 *  小程序通知 noticeQueue.php
 *
 * php noticeQueue.php Notice       通知 定时每5分钟运行一次
 *
 * @author camfee <camfee@foxmail.com>
 * @date   2018/1/2
 */

require dirname(dirname(__DIR__)) . '/app.inc.php';

use Bare\DB;
use Model\RedisDB\CronQueue;

class noticeQueue
{
    public function doIndex()
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

global $argv;
if (empty($argv[1])) {
    $do = 'Index';
} else {
    $do = trim($argv[1]);
}
$app->run($do);
