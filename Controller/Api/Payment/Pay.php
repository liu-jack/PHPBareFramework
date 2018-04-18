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
use Classes\Payment\PayUtil;

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
    /**
     *
     */
    public function order()
    {
        $mid = intval($_POST['McId']);
        $appid = trim($_POST['AppId']);
        $sign = trim($_POST['sign']);
        $sign_str = PayUtil::signStr($_POST);
        if (PayUtil::verify($sign_str, $sign, $mid)) {

        } else {
            $this->output(255, '签名验证失败');
        }
    }

    public function pay()
    {

    }

    public function query()
    {

    }

    public function refund()
    {

    }
}