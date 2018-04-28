<?php
/**
 * m站首页
 *
 * @author camfee <camfee@yeah.net>
 */

require '../app.inc.php';

class index extends \Smarty\Action
{
    function doIndex()
    {
        $smarty = $this->app->page();
        $smarty->value('title', 'm站');
        $smarty->output();
    }
}

$app->run();