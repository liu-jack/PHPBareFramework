<?php
/**
 * redisQueue.php
 *
 */

require dirname(dirname(__DIR__)) . '/app.inc.php';

use Model\RedisDB\CronQueue as MQueue;

class cronQueue extends \Bare\C\AppsAction
{
    /**
     *  *\/5 * * * * php cronQueue.php Notice 300 5    通知定时每5分钟运行一次
     */
    public function doIndex()
    {
        need_cli();
        global $argv;
        $queue_name = $argv[1];
        $duration = (int)($argv[2]);
        if (empty($queue_name) || empty($duration)) {
            exit("usage: {$argv[0]} [queue_name] [duration] [interval]\n");
        }
        $interval = 5;
        if (!empty($argv[3])) {
            $interval = (int)$argv[3];
        }
        $start_time = time();
        while (true) {
            $now = time();
            if ($now > $start_time + $duration) {
                break;
            }
            $data = MQueue::get($queue_name);
            if (empty($data)) {
                sleep($interval);
                continue;
            }
            try {
                //$queue_name = '\Model\CronQueue\\' . $queue_name;
                //$queue = new $queue_name();
                $this->model('CronQueue\\' . $queue_name);
                $this->$queue_name->run($data);
            } catch (\Exception $exception) {
                var_dump($exception);
                logs($exception, 'cronQueue/Error');
            }
        }
    }
}

$app->run();
