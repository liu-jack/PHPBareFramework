<?php
/**
 * notify.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-19 下午3:20
 *
 */

require_once dirname(__DIR__) . '/../app.inc.php';

use Classes\Pay\Pay;
use Model\Application\Order;

class notify
{
    const ERR_LOG_NOTIFY = 'Notify/pay/notify_err';

    public function doIndex()
    {
        $params = $_POST;
        $sign = trim($_POST['sign']);
        $config = config('pay/pay');
        $params['app_secret'] = $config['AppSecret'];
        $sign_str = Pay::signStr($params);
        if (Pay::verify($sign_str, $sign)) {
            $sn = trim($_POST['OutTradeNo']);
            $status = $_POST['Status'];
            $total_fee = $_POST['TotalFee'];
            $order = Order::getOrderByNo($sn);
            if ($order['Status'] == Order::STATUS_SUCCESS) {
                exit('SUCCESS');
            }
            if (in_array($order['Status'], [
                    Order::STATUS_WAIT,
                    Order::STATUS_PAYING
                ]) && $status == 1 && $order['TotalFee'] == $total_fee) {
                $update = [
                    'ThirdNo' => $_POST['OrderNo'],
                    'Status' => Order::STATUS_SUCCESS,
                    'PayTime' => date('Y-m-d H:i:s'),
                    'Content' => $_POST,
                ];
                Order::paySuccess($sn, $update);
            } else {
                $log = [
                    'data' => $_POST,
                    'msg' => 'pay failure',
                ];
                logs($log, self::ERR_LOG_NOTIFY);
            }
        } else {
            $log = [
                'data' => $_POST,
                'msg' => 'sign check err',
            ];
            logs($log, self::ERR_LOG_NOTIFY);
        }
        exit('SUCCESS');
    }
}

$app->run();