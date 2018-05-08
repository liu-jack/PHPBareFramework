<?php
/**
 * Pay.class.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-19 下午4:07
 *
 */

namespace Controller\Api\Pay;

use Bare\Controller;
use Classes\Pay\Pay as MPay;
use Model\Application\Order;
use Model\Application\Product;

/**
 * 支付
 *
 * @package    Pay
 * @author     camfee<camfee@foxmail.com>
 * @date       2018-04-19
 *
 */
class Pay extends Controller
{
    const NOTIFY_URL = 'http://zf.bare.com/Apps/notify/pay/notify.php';

    /**
     * 下单
     *
     * <pre>
     * POST:
     *     product_id: 必选, 商品id
     *     group_id:   可选, 团购id
     *     address_id: 可选, 收货地址id
     * </pre>
     *
     * @return void|string 返回JSON数组
     *
     * <pre>
     * {
     *     "Code": 200,
     *     "Data": {
     *         "Package": {
     *             "app_id": "19XTC6F1TT",
     *             "mid": 1,
     *             "order_no": "19XTC6F1TT2018041917162370668241",
     *             "body": "test1",
     *             "total_fee": 1,
     *             "notify_url": "http://zf.bare.com/notify/pay/notify.php",
     *             "sign": "SfWboh/x0PB28LV/qBniDtdsicPww=="
     *         }
     *     }
     * }
     * 异常状态：
     * 201：参数错误
     * 202：支付平台下单失败
     * 203：数据新增失败
     * </pre>
     */
    public function order()
    {
        $uid = $this->isLogin();
        $pid = intval($_POST['product_id']);
        $gid = intval($_POST['group_id']);
        $aid = intval($_POST['address_id']);
        $goods = Product::getInfoByIds($pid);
        if (empty($goods)) {
            $this->output(201, '参数错误');
        }
        $sn = Order::generateOrderNo(Order::PAY_TYPE_PAY, $GLOBALS[G_CHANNEL]);
        $config = config('pay/pay');
        $params = [
            'app_id' => $config['AppId'],
            'app_secret' => $config['AppSecret'],
            'mid' => $config['MchId'],
            'out_trade_no' => $sn,
            'body' => $goods['Title'],
            'total_fee' => $goods['Price'] * 100,
            'notify_url' => self::NOTIFY_URL,
            'create_ip' => ip(),
        ];
        $res = MPay::unified($params);
        if (!empty($res['OrderNo'])) {
            $add = [
                'AppId' => $config['AppId'],
                'UserId' => $uid,
                'GoodInfo' => $goods['Title'],
                'OrderNo' => $sn,
                'ProductId' => $pid,
                'PayType' => Order::PAY_TYPE_PAY,
                'TotalFee' => $goods['Price'] * 100,
                'TradeNo' => $res['OrderNo'],
                'Channel' => $GLOBALS[G_CHANNEL],
                'GroupId' => $gid,
                'AddressId' => $aid,
            ];
            $ret = Order::add($add);
            if ($ret === false) {
                logs($add, 'Api/Pay/Pay_Err');
                $this->output(203, '数据新增失败');
            }
            $params['order_no'] = $res['OrderNo'];
            $data['Package'] = MPay::package($config, $params);
            $this->output(200, $data);
        } else {
            $this->output(202, '支付平台下单失败' . $res['msg']);
        }
    }

    /**
     * 查询
     *
     * <pre>
     * GET:
     *     sn: 必选, 商户订单号
     * </pre>
     *
     * @return void|string 返回JSON数组
     *
     * <pre>
     * {
     *     "Code": 200,
     *     "Data": {}
     * }
     * 异常状态：
     * 201：参数错误
     * 202：订单不存在
     * </pre>
     */
    public function query()
    {
        $uid = $this->isLogin();
        $sn = trim($_GET['sn']);
        $order_info = Order::getOrderByNo($sn);
        if (empty($order_info) || $order_info['UserId'] != $uid) {
            $this->output(201, '参数错误');
        }
        if ($order_info['Status'] == Order::STATUS_SUCCESS) {
            $this->output(200);
        } elseif (in_array($order_info['Status'], [Order::STATUS_WAIT, Order::STATUS_PAYING])) {
            $config = config('pay/pay');
            $params = [
                'app_id' => $config['AppId'],
                'app_secret' => $config['AppSecret'],
                'mid' => $config['MchId'],
                'order_no' => $order_info['TradeNo'],
            ];
            $res = MPay::query($params);
            if (!empty($res['OrderNo'])) {
                $update = [
                    'ThirdNo' => $res['OrderNo'],
                    'Status' => Order::STATUS_SUCCESS,
                    'PayTime' => date('Y-m-d H:i:s'),
                    'Content' => $res,
                ];
                Order::paySuccess($sn, $update);
                $this->output(200);
            } else {
                $this->output(202, '订单不存在' . $res['msg']);
            }
        }

    }

    /**
     * 退款
     *
     * <pre>
     * GET:
     *     sn: 必选, 商户订单号
     * </pre>
     *
     * @return void|string 返回JSON数组
     *
     * <pre>
     * {
     *     "Code": 200,
     *     "Data": {
     *     }
     * }
     * 异常状态：
     * 201：参数错误
     * 202：订单不存在
     * </pre>
     */
    public function refund()
    {
        $uid = $this->isLogin();
        $sn = trim($_GET['sn']);
        $order_info = Order::getOrderByNo($sn);
        if (empty($order_info) || $order_info['UserId'] != $uid) {
            $this->output(201, '参数错误');
        }
        $config = config('pay/pay');
        $params = [
            'app_id' => $config['AppId'],
            'app_secret' => $config['AppSecret'],
            'mid' => $config['MchId'],
            'order_no' => $order_info['TradeNo'],
        ];
        $res = MPay::refund($params);
        if (!empty($res['OrderNo'])) {
            $this->output(200, $res);
        } else {
            $this->output(202, '订单不存在' . $res['msg']);
        }
    }
}