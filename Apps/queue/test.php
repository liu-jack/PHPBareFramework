<?php

require dirname(__DIR__) . '/app.inc.php';

/**
 * 队列控制器
 */
class test
{
    /**
     * 队列入口 php test.php
     */
    public function doIndex()
    {
        need_cli();

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'log']);
            pcntl_signal(SIGINT, [$this, 'log']);
        }
        $i = 0;
        while (true) {
            $i++;
            sleep(5);
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }
    }

    public static function log()
    {
        debug_log(date('Y-m-d H:i:s'));
    }
}

$app->run();
