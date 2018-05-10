<?php

require dirname(__DIR__) . '/app.inc.php';

use Bare\M\Queue;

/**
 * 队列控制器
 */
class index
{
    /**
     * 队列入口 php index.php SearchBook
     */
    public function doIndex()
    {
        need_cli();
        global $argv;
        if (empty($argv[1])) {
            exit("usage: php index.php [QueueName]\n");
        }
        $class_name = trim($argv[1]);
        $class_file = APPS_PATH . 'Queues/' . $class_name . CEXT;
        if (file_exists($class_file)) {
            $queue = new Queue($class_name);
            $class_name = '\Apps\Queues\\' . $class_name;
            $class = new $class_name();
            $queue->doLoop($class);
        } else {
            exit('Queue file not exist');
        }
    }
}

$app->run();
