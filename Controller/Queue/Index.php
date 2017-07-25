<?php

namespace Controller\Queue;

use Bare\Controller;
use Bare\Queue;

/**
 * 队列控制器
 */
class Index extends Controller
{
    /**
     * 队列入口 php index.php Queue/Index/index SearchBook
     */
    public function index()
    {
        need_cli();
        if (empty($_GET['argv'][1])) {
            echo "Param Error!\n";
            echo "Usage:php Queue.php ModelName\n";
            exit;
        }
        $class_name = trim($_GET['argv'][1]);
        $class_file = MODEL_PATH . $GLOBALS['_M'] . '/' . $class_name . CEXT;
        if (file_exists($class_file)) {
            $queue = new Queue($class_name);
            $class_name = '\Model\Queue\\' . $class_name;
            $class = new $class_name();
            $queue->doLoop($class);
        }
    }
}