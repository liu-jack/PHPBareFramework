<?php
/**
 * redisQueue.php
 *
 */

require '../../app.inc.php';

use Model\RedisDB\RedisQueue as RQ;

class redisQueue
{
    public function doIndex()
    {
        need_cli();
        global $argv;
        $queueName = $argv[1];
        $func = $argv[2];
        if (empty($queueName) || empty($func)) {
            exit("usage: {$argv[0]} [queueName] [funcName]\n");
        }

        var_dump(RQ::instance($queueName)->$func());
    }
}

$app->run();
