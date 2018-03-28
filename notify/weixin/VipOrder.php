<?php
/**
 *  微信会员订单
 * 文德胜
 *
 */

define("NO_CHECK", true);
require_once dirname(__DIR__) . '/../common.inc.php';

include_once BASEPATH_CONFIG . 'mobileapi/base.cfg.php';

use lib\plugins\weixin\NotifyData;
use lib\plugins\weixin\PayApi;
use MinApp\QBVip\QBVipOrder;

/**
 * Class VipOrder
 */
class VipOrder
{
    /*public function doTest()
    {
        $tradeNo='asdfas';
        $orderNo = $_POST['order_no'];
        $orderAmt = $_POST['order_amt'];
        $vipOrder = QBVipOrder::getVipOrderByOrderNo($orderNo);
        if (empty($vipOrder) || $vipOrder[QBVipOrder::FIELD_ORDER_AMT] != $orderAmt) {
            $this->exitJson(["return_code" => 'SUCCESS', "return_msg" => '订单无效']);
        } elseif ($vipOrder[QBVipOrder::FIELD_PAYMENT_STATE] == 1) {
            $this->exitJson(["return_code" => 'SUCCESS', "return_msg" => '支付成功']);
        }

        try {
            QBVipOrder::payment($vipOrder, $tradeNo);
        } catch (Exception $e) {
            pay_fail_log("VipOrder PayFailed, OrderNo:" . $orderNo . ' error ' . $e->getMessage());
        }
    }*/

    public function doDefault()
    {
        $paymentType = intval($_GET['paymentType']);
        if ($paymentType == QBVipOrder::PAYMENT_TYPE_APP) {
            $weixin_cfg = loadconf('mobileapi/plugins')['weixin'];
        } else {
            $weixin_cfg = loadconf('minapp/minapp')['QBStory'];
            $channel    = isset($_GET['channel']) ? $_GET['channel'] : '';
            if ($channel) {
                $channelConfig = loadconf('minapp/qbstory_channel');
                if (isset($channelConfig[$channel])) {
                    $weixin_cfg = $channelConfig[$channel];
                }
            }
        }

        $res = $this->post_data();
        if (empty($res)) {
            $this->exitJson(["return_code" => 'FAIL', "return_msg" => '没有数据']);
        }
        debug_log($res, JF_LOG_INFO);

        $weixinResult = NotifyData::parseFromXml($res);
        $weixinPay = new PayApi($weixin_cfg);

        if ($weixinResult->checkSign($weixinPay->getConfig()->getKey()) === false) {
            $this->exitJson($weixinResult->replayNotify());
        }
        $orderNo = $weixinResult->getValue('out_trade_no');//商户订单号
        $orderAmt = $weixinResult->getValue('total_fee');//订单总金额，单位为分
        $tradeNo = $weixinResult->getValue('transaction_id');//微信支付订单号
        $trade_status = $weixinResult->getValue('trade_state');

        if ($trade_status === 'SUCCESS') {
            $vipOrder = QBVipOrder::getVipOrderByOrderNo($orderNo);
            if (empty($vipOrder) || $vipOrder[QBVipOrder::FIELD_ORDER_AMT] != $orderAmt) {
                $this->exitJson(["return_code" => 'SUCCESS', "return_msg" => '订单无效']);
            } elseif ($vipOrder[QBVipOrder::FIELD_PAYMENT_STATE] == 1) {
                $this->exitJson(["return_code" => 'SUCCESS', "return_msg" => '支付成功']);
            }

            try {
                QBVipOrder::payment($vipOrder, $tradeNo);
            } catch (Exception $e) {
                pay_fail_log("VipOrder PayFailed, OrderNo:" . $orderNo . ' error ' . $e->getMessage());
            }
            $this->exitJson(["return_code" => 'SUCCESS', "return_msg" => '支付成功']);
        } else {
            $this->exitJson(["return_code" => 'SUCCESS', "return_msg" => '不处理当前支付']);
        }
    }

    private function exitJson($json)
    {
        $jsonString = json_encode($json);
        debug_log($jsonString, JF_LOG_INFO);
        exit($jsonString);
    }

    private function post_data()
    {
        $receipt = $_REQUEST;
        if ($receipt == null) {
            $receipt = file_get_contents("php://input");
            if ($receipt == null) {
                $receipt = $GLOBALS['HTTP_RAW_POST_DATA'];
            }
        }

        return $receipt;
    }

    private function getAccessToken($weixin_cfg)
    {
        $weixinPayConfig = $weixin_cfg;

        return \lib\plugins\weixin\Oauth::getAccessTokenWithCache($weixinPayConfig['AppId'],
            $weixinPayConfig['AppSecret']);
    }


}

$app->run();