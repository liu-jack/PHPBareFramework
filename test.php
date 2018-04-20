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
use Model\Application\GroupBuy;
use Model\Application\GroupBuyList;
use Model\Application\Product;
use Classes\Pay\Pay;
use Model\Application\Order;

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
        //        $add = [
        //            'UserId' => 1,
        //            'Province' => '湖南',
        //            'City' => '长沙',
        //            'Area' => '芙蓉区',
        //            'Address' => '人民东路58号3',
        //        ];
        //        var_dump(Address::add($add));
        //        var_dump(Address::setDefault(2, 1));
        //        pre(Address::getListByUid(1));
        //        $now = time();
        //        $start = date('Y-m-d H:i:s', $now);
        //        $end = date('Y-m-d H:i:s', $now + 86400 * 10);
        //        var_dump(Product::add([
        //            'Title' => 'test1',
        //            'Price' => 1,
        //            'GroupPrice' => 0.5,
        //            'IsGroup' => 1,
        //            'GroupNum' => 2,
        //            'Inventory' => 100,
        //            'GroupStartTime' => $start,
        //            'GroupEndTime' => $end
        //        ]));
        //        var_dump(GroupBuy::createGroupBuy(1, 1));
        //        var_dump(GroupBuy::startGroupBuy(1));
        //        \Bare\DB::memcache()->flush();
        //        var_dump(GroupBuyList::addMember(1, 2));
        //        pre(GroupBuy::getInfoByIds(1),GroupBuyList::getGroupList(1));
        //        pre(GroupBuy::getProductGroup(1));
        //        $sn = \Model\Payment\Order::generateOrderNo(int2str(1));
        //                pre(date('YmdHis'), $sn, strlen($sn));
        //                pre(md5(microtime(true) . md5(microtime(true))));
        //        $id = 1;//pow(10, 11);
        //        $str = int2str($id);
        //        pre($id, $str, str2int($str));
//        $config = config('pay/pay');
//        $params = [
//            'app_id' => $config['AppId'],
//            'app_secret' => $config['AppSecret'],
//            'mid' => $config['MchId'],
//            'out_trade_no' => Order::generateOrderNo(Order::PAY_TYPE_PAY, 'default'),
//            'body' => 'test',
//            'total_fee' => 1,
//            'notify_url' => 'zf.bare.com/notify/pay/notify.php',
//            'create_ip' => ip(),
//        ];
//        pre(Pay::unified($params));

    }
}

$app->run();