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
use Model\Mongo\Book;
use Model\Search\BookSearch;
use Bare\DB;
use Common\FileData;
use Common\RuntimeLog;

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
        pre(md5(microtime(true) . md5(microtime(true))));
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
        //        $puser = [
        //            'UserId' => 2,
        //            'UserNick' => 'test2',
        //            'RealName' => 'camfee',
        //            'Balance' => 99999,
        //            'PayPassword' => 123456
        //        ];
        //        var_dump(\Model\Payment\User::add($puser));
        //        $p_pay = [
        //            'order_no' => '19XTC6F1TT2018042314124061375043',
        //            'pwd' => '123456',
        //        ];
        //        $auth = 'ce2829e83efd946613bb0c870f5b99449946cad5a3eeb174';
        //        var_dump(Classes\Pay\Pay::pay($p_pay, $auth));
        //        $book = [
        //            Book::FIELD_ID => 3,
        //            Book::FIELD_NAME => 'test3',
        //            Book::FIELD_AUTHOR => 'camfee',
        //            Book::FIELD_TYPE => 3,
        //            Book::FIELD_CREATE_TIME => date('Y-m-d H:i:s'),
        //        ];
        //        var_dump(Book::add($book));
        //        pre(Book::getInfoByIds([1, 2, 3]));
        //        pre(array_combine(['k1', 'k2', 'k3', 'k4'], range(1, 4, 1)));
        //        pre(Book::getList(['Name' => ['like' => 'test']], 0, 0, '_id,Name'));
        //        var_dump(Book::delete(1));

        //        $test = [
        //            'name' => 'test',
        //            'value' => 'test',
        //            'date' => date('Y-m-d H:i:s'),
        //        ];
        //        var_dump(FileData::set(FileData::KEY_TEST, $test));
        //        pre(FileData::get(FileData::KEY_TEST));
        //        pre(version_app_key(APP_APPID_ADR, 'v1.0.1'));
        //        RuntimeLog::start();
        //        sleep(4);
        //        RuntimeLog::end();
        //        RuntimeLog::start();
        //        usleep(100000);
        //        RuntimeLog::end();
        //        RuntimeLog::start();
        //        for ($i = 0; $i <= 100; $i++) {
        //            usleep(1000 * $i);
        //        }
        //        RuntimeLog::end();
        //var_dump(DingDingRobot::setSendUrl('xxxx')->sendText('[测试]机器人消息测试推送'));
        //var_dump(DingDingRobot::setSendUrl(DingDingRobot::TEST_URL)->sendText('[测试]机器人消息测试推送', [13974800627], true));
        //var_dump(DingDingRobot::setSendUrl(DingDingRobot::TEST_URL)->sendLink('链接消息标题', '链接消息内容', 'http://www.baidu.com', 'http://meitetest.oss-cn-hangzhou.aliyuncs.com/xcxSet/xcxFollowSet/miniAppQRcode_1530091018.jpg'));
        //var_dump(DingDingRobot::setSendUrl(DingDingRobot::TEST_URL)->sendMarkdown('长沙天气', "#### 长沙天气  \n > 9度，@13974800627 西北风1级，空气良89，相对温度73%\n\n > ![screenshot](http://i01.lw.aliimg.com/media/lALPBbCc1ZhJGIvNAkzNBLA_1200_588.png)\n  > ###### 14点20分发布 [天气](http://www.thinkpage.cn/) "));
        //var_dump(DingDingRobot::setSendUrl(DingDingRobot::TEST_URL)->sendActionCard("乔布斯 20 年前想打造一间苹果咖啡厅，而它正是 Apple Store 的前身", "![screenshot](@lADOpwk3K80C0M0FoA) \n #### 乔布斯 20 年前想打造的苹果咖啡厅 \n\n Apple Store 的设计正从原来满满的科技感走向生活化，而其生活化的走向其实可以追溯到 20 年前苹果一个建立咖啡馆的计划", '阅读全文', 'https://www.baidu.com'));
        $btns = [
            [
                "title" => "内容不错",
                "actionURL" => "https://www.baidu.com/"
            ],
            [
                "title" => "不感兴趣",
                "actionURL" => "https://www.taobao.com/"
            ]
        ];
        //var_dump(DingDingRobot::setSendUrl(DingDingRobot::TEST_URL)->sendActionCard("乔布斯 20 年前想打造一间苹果咖啡厅，而它正是 Apple Store 的前身", "![screenshot](@lADOpwk3K80C0M0FoA) \n #### 乔布斯 20 年前想打造的苹果咖啡厅 \n\n Apple Store 的设计正从原来满满的科技感走向生活化，而其生活化的走向其实可以追溯到 20 年前苹果一个建立咖啡馆的计划", $btns, 'https://www.baidu.com'));
        $links = [
            [
                "title" => "专项巡视一览表：被巡视单位、巡视时间、巡视组值班电话",
                "messageURL" => "https://www.toutiao.com/group/6614757926080872973/",
                "picURL" => "https://p98.pstatp.com/list/190x124/pgc-image/R76hcZV6SNPAIr"
            ],
            [
                "title" => "外媒：美国是一夜暴富的“土豪”，中国已升格为国际主要“玩家”",
                "messageURL" => "https://www.toutiao.com/group/6615045951532827139/",
                "picURL" => "https://p3.pstatp.com/list/dfic-imagehandler/bf768ee0-ce2d-4e6b-86f2-24c244ba4878"
            ],
            [
                "title" => "70城房价涨幅冲高回落 多地出现打砸售楼处现象",
                "messageURL" => "https://www.toutiao.com/group/6615053023116788231/",
                "picURL" => "https://p1.pstatp.com/list/190x124/pgc-image/R748qxp4yIA3bN"
            ],
        ];
        //var_dump(DingDingRobot::setSendUrl(DingDingRobot::TEST_URL)->sendFeedCard($links));

        // 先回应 然后继续后台运行
        ignore_user_abort(true);
        if (!function_exists('fastcgi_finish_request')) {
            ob_end_flush();
            ob_start();
        }

        echo date('Y-m-d H:i:s');

        if (!function_exists('fastcgi_finish_request')) {
            header("Content-Type: text/html;charset=utf-8");
            header("Connection: close");
            header('Content-Length: ' . ob_get_length());
            ob_flush();
            flush();
        } else {
            fastcgi_finish_request();
        }

        sleep(5);
        logs(date('Y-m-d H:i:s'), 'test/test');
    }
}

$app->run();
