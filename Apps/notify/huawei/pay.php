<?php
/**
 *  华为支付回调通知
 *
 * @author zhoujf <camfee@foxmail.com>
 *
 */

define("NO_CHECK", true);
require_once dirname(__DIR__) . '/../app.inc.php';

use Center\Payment;
use Classes\HuaWei\HuaWeiPay;

class pay
{
    public function doIndex()
    {
        $sign = $_POST['sign'];
        $result_fail = json_encode(['result' => 1]);
        if (empty($sign)) {
            exit($result_fail);
        }
        $content = HuaWeiPay::getPreSign($_POST);
        $signType = trim($_POST['signType']);
        $ok = HuaWeiPay::verify($content, $sign, $signType);

        $sn = trim($_POST['requestId']);
        if ($ok) { //支付成功处理业务
            $tradeNo = trim($_POST['orderId']);
            $amount = trim($_POST['amount']);
            $payment_info = Payment::getPaymentBySN($sn);
            if ($amount != format_price($payment_info['TotalFee'])) {
                debug_log("huawei amount error, order amount:{$payment_info['TotalFee']}, payment amount:{$amount}");
                exit(json_encode(['result' => 3]));
            }

            if (empty($payment_info)) {
                debug_log("huawei notify error, payment not found SN:{$sn}");
                exit(json_encode(['result' => 3]));
            } else {
                $data = [
                    'ThirdSN' => $tradeNo,
                    'TradeNo' => $tradeNo,
                    'Content' => serialize($_POST),
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

                        if (($paymentType == Payment::PAY_TYPE_HUAWEI && is_numeric($product_id))) {
                            BeanOrderLog::recharge($user_id, $GLOBALS['g_appid'], $payment_info['PaymentId'], $user_bag['Bean'], $bean, BeanOrderLog::ITEM_TYPE_DIRECTORY_BUY_ALBUM);
                            $uid = $payment_info['UserId'];
                            $album_id = $payment_info['ProductId'];
                            $album = Album::getStoryAlbumById($album_id);
                            if (empty($album)) {
                                pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, 专辑不存在");
                                exit(json_encode(['result' => 94]));
                            }
                            if (\Center\UserAlbumList::checkAlbum($uid, $album_id) === true) {
                                pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, 已经拥有此专辑");
                                exit(json_encode(['result' => 94]));
                            }
                            $payType = (int)Album::getAlbumTypeByInfo($album);
                            if (!in_array($payType, [
                                    Album::ALBUM_TYPE_PAYMENT,
                                    Album::ALBUM_TYPE_PAYMENT_INVITE
                                ]) || (int)$album['Status'] === Album::STATUS_HIDE) {
                                pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, 此专辑不能购买");
                                exit(json_encode(['result' => 94]));
                            }
                            $need_bean = $album['Price'];
                            $user_bag_info = UserBag::getUserBagByUserId($uid, false);
                            if (empty($user_bag_info) || (int)$user_bag_info['Bean'] < (int)$need_bean) {
                                pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, 宝豆不够");
                                exit(json_encode(['result' => 94]));
                            }
                            if (UserBag::subtractBean($uid, (int)$need_bean) === false) {
                                pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, 扣除宝豆失败");
                                exit(json_encode(['result' => 94]));
                            }
                            try {
                                pay_succ_log("buy album, uid:{$uid}, albumId:{$album_id}, bean:{$need_bean}");
                                $user_bag_info = UserBag::getUserBagByUserId($uid);
                                BeanOrderLog::addAlbum($uid, $GLOBALS['g_appid'], $user_bag_info['Bean'], $album_id, $need_bean);
                                if (UserAlbumList::addAlbum($uid, $album_id, UserAlbumList::TYPE_FROM_BUY, $album['DotationTotal']) === false) {
                                    pay_fail_log("buy album, add album failed, uid:{$uid}, albumId:{$album_id}, bean:{$need_bean}, 添加专辑失败");
                                    exit(json_encode(['result' => 94]));
                                }
                                pay_succ_log("buy album ,add album, uid:{$uid}, albumId:{$album_id}");
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

            exit(json_encode(['result' => 0]));
        } else {
            debug_log("weixin pay notify failed, SN:{$sn}, result:{$_POST['result']}", JF_LOG_WARNING);
            Payment::updatePaymentBySN($sn, ['Status' => Payment::STATUS_PAY_FAILURE]);
            exit($result_fail);
        }
    }
}

$app->run();