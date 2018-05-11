<?php
/**
 * m站首页
 *
 * @author camfee <camfee@yeah.net>
 */

require '../app.inc.php';

class index extends \Bare\C\AppsAction
{
    function doIndex()
    {
        $this->value('title', 'm站');
        $this->view();
    }
}

$app->run();