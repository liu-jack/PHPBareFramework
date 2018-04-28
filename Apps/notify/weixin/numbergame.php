<?php
/**
 *  微信支付回调通知
 *
 * @author hjh <hjh@jf.com>
 *
 * Date: 2017/1/5
 * Time: 19:40
 */

define("NO_CHECK", true);
require_once dirname(__DIR__) . '/../common.inc.php';

include_once BASEPATH_CONFIG . 'mobileapi/base.cfg.php';

use lib\plugins\weixin\NotifyData;
use lib\plugins\weixin\PayApi;
use MinApp\NumberGame\NGPayment;
use Common\DataType;
use MinApp\NumberGame\NGUserInfo;
use MinApp\NumberGame\NGPayConfig;
use MinApp\NumberGame\NGExchangeRecord;
use MinApp\NumberGame\RedisDBNGUser;

class numbergame
{
    public function doDefault()
    {
        $weixin_cfg = loadconf('act/numbergame');

        //$res = file_get_contents("php://input");//获取的结果文件
        $res = $this->post_data();
        if (empty($res)) {
            $this->exitJson(["return_code" => 'FAIL', "return_msg" => '没有数据']);
        }

        debug_log($res, JF_LOG_INFO);

        $weixinResult = NotifyData::parseFromXml($res);
        $weixinPay    = new PayApi($weixin_cfg);

        if ($weixinResult->checkSign($weixinPay->getConfig()->getKey()) === false) {
            $this->exitJson($weixinResult->replayNotify());
        }

        $sn           = $weixinResult->getValue('out_trade_no');//商户订单号
        $trade_status = $weixinResult->getValue('trade_state');//状态

        $ngPaymentInfo = NGPayment::getPaymentInfoBySN($sn);
        if (empty($ngPaymentInfo)) {
            $this->exitJson(["return_code" => 'SUCCESS', "return_msg" => '订单号无效']);
        }

        $payId = $ngPaymentInfo[NGPayment::FIELD_ID];

        $productId = $ngPaymentInfo[NGPayment::FIELD_PRODUCT_ID];
        $oldStatus = (int)$ngPaymentInfo[NGPayment::FIELD_STATUS];
        $amount    = 1;
        if ($productId == 999) {
            $amount = 0;
        }elseif ($productId != 0){//邮费
            $payConfig = NGPayConfig::getDataInfoById($productId);
            if (empty($payConfig) || $payConfig[NGPayConfig::FIELD_TYPE] == NGPayConfig::TYPE_INVITE) {
                $this->exitJson(["return_code" => 'FAIL', "return_msg" => '订单异常']);
            }
            $amount = (int)$payConfig[NGPayConfig::FIELD_AMOUNT];
        }

        if ($oldStatus == NGPayment::STATUS_PAY_CANCELED) {
            $this->exitJson(["return_code" => 'SUCCESS', "return_msg" => '已经取消支付']);
        } elseif ($oldStatus == NGPayment::STATUS_PAY_FAILURE) {
            $this->exitJson(["return_code" => 'SUCCESS', "return_msg" => '支付失败']);
        } elseif ($oldStatus == NGPayment::STATUS_PAY_SUCCESS) {
            $this->exitJson(["return_code" => 'SUCCESS', "return_msg" => '支付成功']);
        }

        $data = [
            NGPayment::FIELD_TRADE_NO    => $weixinResult->getValue('transaction_id'),
            NGPayment::FIELD_CONTENT     => $res,
            NGPayment::FIELD_STATUS      => NGPayment::STATUS_PAY_SUCCESS,
            NGPayment::FIELD_UPDATE_TIME => DataType::datetime(),
        ];
        if (NGPayment::paySuccess($payId, $data)) {
            $userId = $ngPaymentInfo[NGPayment::FIELD_USER_ID];

            pay_succ_log("NGGame paySuccess, NGGame, paymentId:{$payId}, userId:{$userId}");
            if($productId == 999){
                $address = json_decode($ngPaymentInfo[NGPayment::FIELD_ADDRESS],true);
                $videoUrl = $address['videoUrl'];
                $parseAddress = NGExchangeRecord::parseAddress($address['Address']);
                $prizeNumber  = $address['prizeNumber'];
                $ngUserInfo = NGUserInfo::getDataInfoById($userId);
                $res = NGUserInfo::finishAddress($userId, $prizeNumber, $ngUserInfo[NGUserInfo::FIELD_GET_PRIZES]);

                //清除时间记录
                RedisDBNGUser::initPrizeTimes($userId);

                if (empty($res)) {
                    debug_log("NGGame payFailed, finishAddress  failed, userId: {$userId}, prizeNumber: {$prizeNumber}", JF_LOG_ERROR);
                }
                $addressInfo = [
                    NGExchangeRecord::FIELD_USER_ID => $userId,
                    NGExchangeRecord::FIELD_PRIZE_ID => 1,
                    NGExchangeRecord::FIELD_PRIZE_NUMBER => $address['prizeNumber'],
                    NGExchangeRecord::FIELD_ADDRESS => $address['Address'],
                    NGExchangeRecord::FIELD_NAME => !empty($parseAddress[NGExchangeRecord::ADDRESS_NAME])?$parseAddress[NGExchangeRecord::ADDRESS_NAME]:'',
                    NGExchangeRecord::FIELD_POSTAGE => $ngPaymentInfo[NGPayment::FIELD_PRICE],
                    NGExchangeRecord::FIELD_VIDEOURL => $videoUrl,
                    NGExchangeRecord::FIELD_STATUS => !empty($videoUrl)?NGExchangeRecord::STATUS_CHECK_IN:NGExchangeRecord::STATUS_WAIT_SEND,
                ];
                $res = NGExchangeRecord::add($addressInfo);
                if($res['status'] !== 200){
                    debug_log("NGGame payFailed, add ExchangeRecord count failed, userId: {$userId}, info: {$addressInfo}", JF_LOG_ERROR);
                    NGPayment::update($payId, [
                        NGPayment::FIELD_STATUS => NGPayment::STATUS_SEND_GOODS_FAILED
                    ]);
                    pay_fail_log('增加奖品兑换失败');
                }
            }else{
                //增加挑战次数
                if (empty(NGUserInfo::addChallengeCount($userId, $amount))) {
                    debug_log("NGGame payFailed, add Challenge count failed, userId: {$userId}, payId: {$payId}", JF_LOG_ERROR);
                    NGPayment::update($payId, [
                        NGPayment::FIELD_STATUS => NGPayment::STATUS_SEND_GOODS_FAILED
                    ]);
                    pay_fail_log('增加挑战次数失败');
                }
            }
        } else {
            pay_fail_log("NGGame PayFailed, paymentId:{$payId}}");
        }
        $this->exitJson(["return_code" => 'SUCCESS', "return_msg" => '支付成功']);
    }

    public function exitJson($json)
    {
        $jsonString = json_encode($json);
        debug_log($jsonString, JF_LOG_INFO);
        exit($jsonString);
    }

    function post_data()
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
}

$app->run();