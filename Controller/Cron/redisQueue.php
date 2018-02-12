<?php
/**
 * redisQueue.php
 * Created by IntelliJ IDEA.
 *
 * Date: 2018/1/13
 * Time: 21:56
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
