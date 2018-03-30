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
require_once dirname(__DIR__) . '/../app.inc.php';

use Center\Payment;
use lib\plugins\weixin\NotifyData;
use lib\plugins\weixin\Config;

class pay
{
    public function doIndex()
    {
        $weixin_key = isset($_GET['mpkey']) ? trim($_GET['mpkey']) : '';
        $weixin_key1 = isset($_GET['mpkey1']) ? trim($_GET['mpkey1']) : '';
        $data = file_get_contents("php://input");
        if (empty($data)) {
            exit(json_encode(["return_code" => 'FAIL', "return_msg" => '没有数据']));
        }

        $channel = isset($_GET['channel']) ? $_GET['channel'] : '';

        $notify_data = NotifyData::parseFromXml($data);

        //发起api请求
        if ($weixin_key === 'weixin_pay') {
            $weixin_cfg = loadconf('mobileapi/plugins')['weixin_pay'];
        } else {
            $weixin_cfg = loadconf('mobileapi/plugins')['weixin'];
        }

        if ($weixin_key1 == 'QBStory') {
            $weixin_cfg = loadconf('minapp/minapp')['QBStory'];
            if ($channel) {
                $channelConfig = loadconf('minapp/qbstory_channel');
                if (isset($channelConfig[$channel])) {
                    $weixin_cfg = $channelConfig[$channel];
                }
            }
        }

        $weixin_config = new Config($weixin_cfg);

        if ($notify_data->checkNotifyData($weixin_config->getKey()) === false) {
            echo $notify_data->replayNotify();
            exit;
        }

        $sn = $notify_data->getValue('out_trade_no');
        $trade_status = $notify_data->getValue('trade_status');
        if ($trade_status === 'USERPAYING') {
            debug_log("weixin notify error, status:{$trade_status}");

        } elseif ($trade_status === 'SUCCESS') {
            $sn = trim($notify_data->getValue('out_trade_no'));
            $payment_info = Payment::getPaymentBySN($sn);

            if (empty($payment_info)) {
                debug_log("weixin notify error, payment not found SN:{$sn}");

                $notify_data->failure('参数错误 out_trade_no');
            } else {
                $data = [
                    'ThirdSN' => $notify_data->getValue('transaction_id'),
                    'Content' => serialize($notify_data->getValues()),
                    'Status' => Payment::STATUS_PAY_SUCCESS,
                    'UpdateTime' => DataType::datetime(),
                ];
                $payment_id = $payment_info['PaymentId'];
                $user_id = $payment_info['UserId'];

                if (Payment::paySuccess($sn, $data) === 0) {

                    $product_id = $payment_info['ProductId'];
                    $app_id = $payment_info['AppId'];
                    $user_id = $payment_info['UserId'];
                    $pay_config = loadconf('mobileapi/pay');
                    if (!is_numeric($product_id) && isset($pay_config[$app_id][$product_id])) {
                        $bean = (int)$pay_config[$app_id][$product_id]['Bean'];
                    } else {
                        $bean = (int)$payment_info['TotalFee'];
                    }


                    $paymentType = $payment_info['PaymentType'];

                    pay_succ_log("paySuccess, paymentId:{$payment_id}, userId:{$user_id}, bean:{$bean}");

                    if (UserBag::addBean($user_id, $bean) === false) {
                        pay_fail_log("addBean failed, paymentId:{$payment_id}, userId:{$user_id}, bean:{$bean}");
                        Payment::updatePaymentBySN($sn, ['Status' => Payment::STATUS_SEND_GOODS_FAILED]);
                    } else {
                        pay_succ_log("addBean, paymentId:{$payment_id}, userId:{$user_id}, bean:{$bean}");
                        $user_bag = UserBag::getUserBagByUserId($user_id);

                        if ($paymentType == Payment::PAY_TYPE_WEIXIN_JSAPI_ALBUM || ($paymentType == Payment::PAY_TYPE_WEIXIN && is_numeric($product_id))) {
                            BeanOrderLog::recharge($user_id, $GLOBALS['g_appid'], $payment_info['PaymentId'], $user_bag['Bean'], $bean, BeanOrderLog::ITEM_TYPE_DIRECTORY_BUY_ALBUM);
                            $uid   = $payment_info['UserId'];
                            $album_id   = $payment_info['ProductId'];
                            $album = Album::getStoryAlbumById($album_id);
                            if (empty($album)) {
                                pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, 专辑不存在");
                                return ;
                            }
                            if (\Center\UserAlbumList::checkAlbum($uid, $album_id) === true) {
                                pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, 已经拥有此专辑");
                                return ;
                            }
                            $payType = (int) Album::getAlbumTypeByInfo($album);
                            if (!in_array($payType, [Album::ALBUM_TYPE_PAYMENT, Album::ALBUM_TYPE_PAYMENT_INVITE]) || (int) $album['Status'] === Album::STATUS_HIDE) {
                                pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, 此专辑不能购买");
                                return ;
                            }
                            $need_bean     = $album['Price'];
                            $user_bag_info = UserBag::getUserBagByUserId($uid, false);
                            if (empty($user_bag_info) || (int) $user_bag_info['Bean'] < (int) $need_bean) {
                                pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, 宝豆不够");
                                return ;
                            }
                            if (UserBag::subtractBean($uid, (int) $need_bean) === false) {
                                pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, 扣除宝豆失败");
                                return ;
                            }
                            try {
                                pay_succ_log("buy album, uid:{$uid}, albumId:{$album_id}, bean:{$need_bean}");
                                $user_bag_info = UserBag::getUserBagByUserId($uid);
                                BeanOrderLog::addAlbum($uid, $GLOBALS['g_appid'], $user_bag_info['Bean'], $album_id, $need_bean);
                                if (UserAlbumList::addAlbum($uid, $album_id, UserAlbumList::TYPE_FROM_BUY, $album['DotationTotal']) === false) {
                                    pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, bean:{$need_bean}");
                                    pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, 添加专辑失败");
                                    return ;
                                }
                            } catch (\Exception $e) {
                                pay_fail_log(["buy album exception, uid:{$uid}, albumId:{$album_id}", $e]);
                            }
                        } else {
                            BeanOrderLog::recharge($user_id, $GLOBALS['g_appid'], $payment_info['PaymentId'], $user_bag['Bean'], $bean, BeanOrderLog::ITEM_TYPE_RECHARGE);
                        }
                    }
                } else {
                    pay_fail_log("paySuccess failed, paymentId:{$payment_id}, userId:{$user_id}, SN:{$sn}");
                }
            }
        } elseif ($trade_status === 'REVOKED') {
            debug_log("weixin pay notify failed, SN:{$sn}, status:{$trade_status}", JF_LOG_WARNING);
            Payment::updatePaymentBySN($sn, ['Status' => Payment::STATUS_PAY_CANCELED]);
        } else {
            debug_log("weixin pay notify failed, SN:{$sn}, status:{$trade_status}", JF_LOG_WARNING);
            Payment::updatePaymentBySN($sn, ['Status' => Payment::STATUS_PAY_FAILURE]);
        }
        echo $notify_data->replayNotify();
        exit;
    }

    protected function getAccessToken($appName, $channel = null)
    {
        $weixinPayConfig = loadconf('minapp/minapp')[$appName];
        if ($channel) {
            $channelConfig = loadconf('minapp/qbstory_channel');
            if (isset($channelConfig[$channel])) {
                $weixinPayConfig = $channelConfig[$channel];
            }
        }

        return \lib\plugins\weixin\Oauth::getAccessTokenWithCache($weixinPayConfig['AppId'], $weixinPayConfig['AppSecret']);
    }
}

$app->run();