<?php
/**
 * 测试
 *
 * @author camfee <camfee@yeah.net>
 */

require 'app.inc.php';

use Classes\Algorithm\Sort;

class test
{
    function doIndex()
    {
        $str = 'abcdefghijklmnopqrstuvwxyz';
        $arr = [];
        for ($i = 0; $i < 20; $i++) {
            $arr[$i . $str{mt_rand(1, 26)}] = mt_rand(1, 9999);
        }
        pre($arr, Sort::shell($arr, 3));
    }
}

$app->run();