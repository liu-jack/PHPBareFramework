<?php
/**
 *  华为支付会员订单回调
 *
 * @author camfee<camfee@foxmail.com>
 */

define("NO_CHECK", true);
require_once dirname(__DIR__) . '/../common.inc.php';

use MinApp\QBVip\QBVipOrder;
use lib\plugins\huawei\HuaweiApp;

/**
 * Class VipOrder
 */
class VipOrder
{
    public function doDefault()
    {
        $sign = $_POST['sign'];
        $result_fail = json_encode(['result' => 1]);
        if (empty($sign)) {
            exit($result_fail);
        }
        $content =HuaweiApp::getPreSign($_POST);
        $signType = trim($_POST['signType']);
        $ok = HuaweiApp::verify($content, $sign, $signType);

        $orderNo = trim($_POST['requestId']);
        if ($ok) { //支付成功处理业务
            $tradeNo = trim($_POST['orderId']);
            $amount = trim($_POST['amount']);
            $vipOrder = QBVipOrder::getVipOrderByOrderNo($orderNo);
            if ($amount != format_price($vipOrder['TotalFee'])) {
                debug_log("huawei amount error, order amount:{$vipOrder['TotalFee']}, payment amount:{$amount}");
                exit(json_encode(['result' => 3]));
            }
            try {
                QBVipOrder::payment($vipOrder, $tradeNo);
                exit(json_encode(['result' => 0]));
            } catch (Exception $e) {
                pay_fail_log("VipOrder PayFailed, OrderNo:" . $orderNo . ' error ' . $e->getMessage());
            }
        }
        exit($result_fail);
    }
}

$app->run();