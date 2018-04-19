<?php
/**
 * notify.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-19 下午3:20
 *
 */

require_once dirname(__DIR__) . '/../app.inc.php';

class notify
{
    public function doIndex()
    {
        pre($_POST);
        debug_log($_POST);
    }
}

$app->run();