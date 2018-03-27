<?php
/**
 * Project: story-server
 * File Created at 2017-03-22
 * Copyright 2014 qbaoting.cn All right reserved. This software is the
 * confidential and proprietary information of qbaoting.cn ("Confidential
 * Information"). You shall not disclose such Confidential Information and shall
 * use it only in accordance with the terms of the license agreement you entered
 * into with qbaoting.cn .
 */
define('NO_CHECK', true);
require_once dirname(__DIR__) . '/../common.inc.php';

include_once BASEPATH_CONFIG . 'mobileapi/base.cfg.php';
use Center\BeanOrderLog;
use Center\Payment;
use Center\UserBag;
use Common\DataType;

class pay
{
    public function doDefault()
    {
        $params = $_POST;
        $data = \lib\plugins\alipay\Alipay::rsaCheckV1($params);

        debug_log('weixin pay query productId:{} not exist user: ' . serialize($data), JF_LOG_ERROR);
        $notify_time = $_POST['notify_time']; //通知的发送时间。格式为yyyy-MM-dd HH:mm:ss
        $notify_type = $_POST['notify_type']; //通知的类型
        $notify_id = $_POST['notify_id']; //通知校验ID
        $app_id = $_POST['app_id'];
        $trade_no = $_POST['trade_no']; //支付宝交易凭证号
        $out_trade_no = $_POST['out_trade_no']; //原支付请求的商户订单号
        if (empty($trade_no) && empty($out_trade_no)) {
            exit();
        }
        //发起api请求
        $payment_info = Payment::getPaymentBySN($out_trade_no);
        if (empty($payment_info)) {
            exit();
        }

        $payment_id = $payment_info['PaymentId'];
        $app_id = $payment_info['AppId'];
        $user_id = $payment_info['UserId'];
        $product_id = $payment_info['ProductId'];
        if (!isset($pay_config[$product_id])) {
            debug_log("weixin pay query productId:{$product_id} not exist user: $user_id", JF_LOG_ERROR);
            exit();
        }
        $old_status = $payment_info['Status'];
        if ($old_status == Payment::STATUS_PAY_SUCCESS) {
            echo 'success';
        }

        $result = \lib\plugins\alipay\Alipay::tradeQuery($out_trade_no, $trade_no);

        $result = json_decode($result, true);

        $response = $result['alipay_trade_query_response'];
        if ($response['code'] == 10000) {
            $trade_status = $response['trade_status'];

            $responsePrice = $response['total_amount'];
            if ($responsePrice != '0.01') {
            } elseif ($trade_status == 'TRADE_CLOSED') { //未付款交易超时关闭，或支付完成后全额退款
                debug_log("alipay pay query TRADE_CLOSED, paymentId:{$payment_id}, status:{$trade_status}", JF_LOG_WARNING);
                Payment::updatePaymentBySN($out_trade_no, ['Status' => Payment::STATUS_PAY_CANCELED]);
            } elseif ($trade_status == 'TRADE_SUCCESS') { //交易支付成功

                $data = [
                    'ThirdSN'    => $response['trade_no'],
                    'Content'    => $response['store_name'],
                    'Status'     => Payment::STATUS_PAY_SUCCESS,
                    'UpdateTime' => DataType::datetime(),
                ];
                if (Payment::paySuccess($out_trade_no, $data) === 0) {
                    $bean = (int) $pay_config[$product_id]['Bean'];

                    pay_succ_log("paySuccess, paymentId:{$payment_id}, userId:{$user_id}");

                    if (UserBag::addBean($user_id, $bean) === false) {
                        pay_fail_log("addBean failed, paymentId:{$payment_id}, userId:{$user_id}, Bean:{$bean}");
                        Payment::updatePaymentBySN($out_trade_no, ['Status' => Payment::STATUS_SEND_GOODS_FAILED]);
                    } else {
                        pay_succ_log("addBean, paymentId:{$payment_id}, userId:{$user_id}, Bean:{$bean}");
                        $user_bag = UserBag::getUserBagByUserId($user_id);
                        BeanOrderLog::recharge($user_id, $app_id, $payment_id, $user_bag['Bean'], $bean, BeanOrderLog::ITEM_TYPE_RECHARGE);
                    }
                } else {
                    pay_fail_log("paySuccess failed, paymentId:{$payment_id}, uid:{$user_id}");
                }
                echo 'success';
            } elseif ($trade_status == 'TRADE_FINISHED') { //交易结束，不可退款
                debug_log("alipay notify pay query TRADE_FINISHED, paymentId:{$payment_id}, status:{$trade_status}", JF_LOG_WARNING);
            } elseif ($trade_status == 'WAIT_BUYER_PAY') { //交易创建，等待买家付款
                debug_log("alipay notify pay query faild, paymentId:{$payment_id}, status:{$trade_status}", JF_LOG_WARNING);
            }
        }
        exit;
    }
}

$app->run();
