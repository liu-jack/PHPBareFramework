<?php
/**
 * redisQueue.php
 *
 */

namespace Controller\Cron;

use Bare\Controller;

use Model\RedisDB\RedisQueue as RQ;

class redisQueue extends Controller
{
    public function index()
    {
        need_cli();
        $queueName = $_GET['argv'][1];
        $func = $_GET['argv'][2];
        if (empty($queueName) || empty($func)) {
            fwrite(STDERR, "{$_GET['argv'][0]} queueName funcName\n");

            return;
        }

        var_dump(RQ::instance($queueName)->$func());
    }
}
