<?php
/**
 * Pay.php
 * 支付平台
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-13 下午5:30
 *
 */

namespace Controller\Api\Payment;

use Bare\Controller;
use Classes\Encrypt\Rsa;
use Classes\Payment\PayUtil;
use Model\Passport\Login;
use Model\Payment\Application;
use Model\Payment\Merchant;
use Model\Payment\Order;
use Model\Payment\User;

/**
 * 支付平台相关
 *
 * @package    Payment
 * @author     camfee<camfee@foxmail.com>
 * @date       2018-04-13
 *
 */
class Pay extends Controller
{
    const ERR_LOG_ORDER = 'Api/Payment/Pay/order_err'; // 下单错误日志

    /**
     * 下单
     *
     * <pre>
     * POST:
     *     app_id:       必选, 应用appid
     *     mid:          必选, 商户id
     *     out_trade_no: 必选, 商户订单号
     *     body:         必选, 商品名称
     *     total_fee:    必选, 价格 单位分
     *     notify_url:   必选, 回调通知地址
     *     sign:         必选, 签名
     *     create_ip:    可选, 客户ip
     * </pre>
     *
     * @return void|string 返回JSON数组
     *
     * <pre>
     * {
     *     "Code": 200,
     *     "Data": {
     *         "OrderNo": "19XTC6F1TT2018041917162370668241", // 平台订单号
     *     }
     * }
     * 异常状态：
     * 250：参数错误
     * 251：appid错误
     * 254：下单失败
     * 290：签名验证失败
     * </pre>
     */
    public function order()
    {
        $mid = intval($_POST['mid']);
        $app_ids = trim($_POST['app_id']);
        $sign = trim($_POST['sign']);
        $out_sn = trim($_POST['out_trade_no']);
        $body = trim($_POST['body']);
        $amount = intval($_POST['total_fee']);
        $url = trim($_POST['notify_url']);
        if (empty($out_sn) || empty($sign) || empty($body) || empty($url) || $amount < 1) {
            $this->output(250, '参数错误');
        }
        $app_id = str2int($app_ids);
        $app_info = Application::getInfoByIds($app_id);
        if (empty($app_info)) {
            $this->output(251, 'appid错误');
        }
        $params = $_POST;
        $params['app_secret'] = $app_info['AppSecret'];
        $sign_str = PayUtil::signStr($params);
        if (PayUtil::verify($sign_str, $sign, $mid)) {
            $sn = Order::generateOrderNo($app_ids);
            $add = [
                'AppId' => $app_id,
                'OutTradeNo' => $out_sn,
                'Body' => $body,
                'TotalFee' => $amount,
                'NotifyUrl' => $url,
                'OrderNo' => $sn,
                'CreateIp' => !empty($_POST['create_ip']) ? trim($_POST['create_ip']) : ip(),
                'ExpireTime' => date('Y-m-d H:i:s', time() + 3600 * 8)
            ];
            $ret = Order::add($add);
            if ($ret === false) {
                logs($add, self::ERR_LOG_ORDER);
                $this->output(254, '下单失败');
            } else {
                $this->output(200, ['OrderNo' => $sn]);
            }
        } else {
            $this->output(290, '签名验证失败');
        }
    }

    public function pay()
    {
        $uid = self::isLogin();
        $sign = trim($_POST['sign']);
        $order_no = trim($_POST['order_no']);
        $pwd = trim($_POST['pwd']);
        $order_info = Order::getOrderByNo($order_no);
        if (empty($order_info)) {
            $this->output(251, '订单号错误');
        }
        if ($order_info['Status'] == Order::STATUS_SUCCESS) {
            $this->output();
        }
        $app_info = Application::getInfoByIds($order_info['AppId']);
        $mid = $app_info['MerchantId'];
        $params = $_POST;
        $sign_str = PayUtil::signStr($params);
        if (PayUtil::verify($sign_str, $sign, $mid)) {
            $rsa_key = file_get_contents(DATA_PATH . 'rsa/payment.pem');
            $pwd = Rsa::private_decode($pwd, $rsa_key);
            if (empty($pwd)) {
                $this->output(252, '密码校验失败');
            }
            $userinfo = User::getInfoByIds($uid);
            debug_log($pwd);
            if (!password_verify($pwd, $userinfo['PayPassword'])) {
                $this->output(253, '支付密码不正确');
            }
            $order_info['UserId'] = $uid;
            $ret = Order::pay($order_info);
            if ($ret === false) {
                $this->output(254, '支付失败');
            }
            Order::notify($order_no);
            $this->output();
        } else {
            $this->output(290, '签名验证失败');
        }
    }

    /**
     * 查询
     *
     * <pre>
     * POST:
     *     app_id:   必选, 应用appid
     *     mid:      必选, 商户id
     *     order_no: 必选, 平台订单号
     *     sign:     必选, 签名
     * </pre>
     *
     * @return void|string 返回JSON数组
     *
     * <pre>
     * {
     *     "Code": 200,
     *     "Data": {
     *         "OutTradeNo": "19XTC6F1TT2018041917162370668241", // 商户订单号
     *         "Body": "test", // 商品名称
     *         "TotalFee": 1, // 订单金额
     *         "ExpireTime": "有效支付日期",
     *         "Status": 0, // 订单状态 0：待支付 1：支付成功 2：取消支付 3:支付失败 4：已退款
     *         "OrderNo": "19XTC6F1TT2018041917162370668241", // 平台订单号
     *         "PayTime": "支付日期",
     *         "CreateTime": "创建日期",
     *     }
     * }
     * 异常状态：
     * 250：参数错误
     * 251：appid错误
     * 252：订单号错误
     * 290：签名验证失败
     * </pre>
     */
    public function query()
    {
        $mid = intval($_POST['mid']);
        $app_id = trim($_POST['app_id']);
        $sign = trim($_POST['sign']);
        $order_no = trim($_POST['order_no']);
        if (empty($order_no) || empty($sign)) {
            $this->output(250, '参数错误');
        }
        $app_id = str2int($app_id);
        $app_info = Application::getInfoByIds($app_id);
        if (empty($app_info)) {
            $this->output(251, 'appid错误');
        }
        $order_info = Order::getOrderByNo($order_no);
        if (empty($order_info)) {
            $this->output(252, '订单号错误');
        }
        $params = $_POST;
        $params['app_secret'] = $app_info['AppSecret'];
        $sign_str = PayUtil::signStr($params);
        if (PayUtil::verify($sign_str, $sign, $mid)) {
            $data = [
                'OutTradeNo' => $order_info['OutTradeNo'],
                'Body' => $order_info['Body'],
                'TotalFee' => $order_info['TotalFee'],
                'ExpireTime' => $order_info['ExpireTime'],
                'Status' => $order_info['Status'],
                'OrderNo' => $order_info['OrderNo'],
                'PayTime' => (string)$order_info['PayTime'],
                'CreateTime' => $order_info['CreateTime'],
            ];
            $this->output(200, $data);
        } else {
            $this->output(290, '签名验证失败');
        }
    }

    /**
     * 查询
     *
     * <pre>
     * POST:
     *     app_id:   必选, 应用appid
     *     mid:      必选, 商户id
     *     order_no: 必选, 平台订单号
     *     sign:     必选, 签名
     * </pre>
     *
     * @return void|string 返回JSON数组
     *
     * <pre>
     * {
     *     "Code": 200,
     *     "Data": {
     *         "OutTradeNo": "19XTC6F1TT2018041917162370668241", // 商户订单号
     *         "Body": "test", // 商品名称
     *         "TotalFee": 1, // 订单金额
     *         "ExpireTime": "有效支付日期",
     *         "Status": 0, // 订单状态
     *         "OrderNo": "19XTC6F1TT2018041917162370668241", // 平台订单号
     *         "PayTime": "支付日期",
     *         "CreateTime": "创建日期",
     *     }
     * }
     * 异常状态：
     * 250：参数错误
     * 251：appid错误
     * 252：订单号错误
     * 290：签名验证失败
     * </pre>
     */
    public function refund()
    {
        $mid = intval($_POST['mid']);
        $app_id = trim($_POST['app_id']);
        $sign = trim($_POST['sign']);
        $order_no = trim($_POST['order_no']);
        if (empty($order_no) || empty($sign)) {
            $this->output(250, '参数错误');
        }
        $app_id = str2int($app_id);
        $app_info = Application::getInfoByIds($app_id);
        if (empty($app_info)) {
            $this->output(251, 'appid错误');
        }
        $order_info = Order::getOrderByNo($order_no);
        if (empty($order_info)) {
            $this->output(252, '订单号错误');
        }
        if (empty($order_info['Status'] != Order::STATUS_SUCCESS)) {
            $this->output(253, '订单未支付');
        }
        $params = $_POST;
        $params['app_secret'] = $app_info['AppSecret'];
        $sign_str = PayUtil::signStr($params);
        if (PayUtil::verify($sign_str, $sign, $mid)) {
            $res = Order::refund($order_info);
            if ($res === false) {
                $this->output(254, '退款失败');
            }
            $this->output();
        } else {
            $this->output(290, '签名验证失败');
        }
    }
}