<?php
/**
 * 测试
 *
 * @author camfee <camfee@yeah.net>
 */

require 'app.inc.php';

use Classes\Algorithm\Sort;
use Classes\Algorithm\Math;
use Model\Application\Area;

class test
{
    function doIndex()
    {
        //        $str = 'abcdefghijklmnopqrstuvwxyz';
        //        $arr = [];
        //        for ($i = 0; $i < 20; $i++) {
        //            $arr[$i . $str{mt_rand(1, 26)}] = mt_rand(1, 9999);
        //        }
        //        pre($arr, Sort::shell($arr, 3));
//        $cs_yz = Area::getAreasDistance('长沙', '永州');
//        $cs_hy = Area::getAreasDistance('长沙', '衡阳');
//        $yz_hy = Area::getAreasDistance('衡阳', '永州');
//        $cs_cz = Area::getAreasDistance('长沙', '郴州');
//        $yz_cz = Area::getAreasDistance('永州', '郴州');
//        pre($cs_yz, $cs_hy, $yz_hy, $cs_cz, $yz_cz);
//        pre(Area::getAreaNear('长沙'));
        pre(Area::getAreaRange('长沙', 50));

    }
}

$app->run();