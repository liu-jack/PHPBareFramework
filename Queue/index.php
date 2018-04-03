<?php

require '../app.inc.php';

use Bare\Queue;

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
            echo "Param Error!\n";
            echo "Usage: php Queue/index.php ModelName\n";
            exit;
        }
        $class_name = trim($argv[1]);
        $class_file = QUEUE_PATH . 'Queues/' . $class_name . CEXT;
        if (file_exists($class_file)) {
            $queue = new Queue($class_name);
            $class_name = '\Queue\Queues\\' . $class_name;
            $class = new $class_name();
            $queue->doLoop($class);
        } else {
            exit('Queue file not exist');
        }
    }
}

$app->run();