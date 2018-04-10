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
use Model\Application\Address;

class test
{
    function doIndex()
    {
        //        $str = 'abcdefghijklmnopqrstuvwxyz';
        //        $arr = [];
        //        for ($i = 0; $i < 20; $i++) {
        //            $arr[$i . $str{mt_rand(1, 26)}] = mt_rand(1, 9999);
        //        }
        //        pre($arr, Sort::insertion($arr));
        //        $cs_yz = Area::getAreasDistance('长沙', '永州');
        //        $cs_hy = Area::getAreasDistance('长沙', '衡阳');
        //        $yz_hy = Area::getAreasDistance('衡阳', '永州');
        //        $cs_cz = Area::getAreasDistance('长沙', '郴州');
        //        $yz_cz = Area::getAreasDistance('永州', '郴州');
        //        pre($cs_yz, $cs_hy, $yz_hy, $cs_cz, $yz_cz);
        //        pre(Area::getAreaNear('长沙'));
        //        pre(Area::getAreaRange('汝城', 60));
        $add = [
            'UserId' => 1,
            'Province' => '湖南',
            'City' => '长沙',
            'Area' => '芙蓉区',
            'Address' => '人民东路58号3',
        ];
        //        var_dump(Address::add($add));
        //        var_dump(Address::setDefault(2, 1));
        pre(Address::getListByUid(1));

    }
}

$app->run();