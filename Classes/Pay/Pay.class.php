<?php
/**
 * 支付
 *
 * @author     camfee<camfee@foxmail.com>
 *
 */

namespace Classes\Pay;

use Bare\Api;

class Pay extends Api
{
    /**
     * 统一下单
     *
     * @param $params
     * @return array|bool
     */
    public static function unified($params)
    {
        $required_fields = [
            'app_id' => true,
            'app_secret' => true,
            'mid' => true,
            'out_trade_no' => true,
            'body' => true,
            'total_fee' => true,
            'notify_url' => true,
            'create_ip' => true,
        ];
        if (count(array_diff_key($required_fields, $params)) > 0) {
            return false;
        }
        $sign_str = self::signStr($params);
        $params['sign'] = self::sign($sign_str);

        $url = self::getUrl('Payment/Pay/order');
        $ret = self::request($url, $params);

        return $ret;
    }

    /**
     *
     * 支付
     *
     * @param $params
     * @param $auth
     * @return array|bool
     */
    public static function pay($params, $auth)
    {
        $required_fields = [
            'app_id' => true,
            'mid' => true,
            'order_no' => true,
            'body' => true,
            'total_fee' => true,
            'notify_url' => true,
            'sign' => true,
        ];
        if (count(array_diff_key($required_fields, $params)) > 0) {
            return false;
        }
        $post = $params;
        $url = self::getUrl('Payment/Pay/pay');
        $post['header'] = [
            'auth:' . $auth
        ];
        $ret = self::request($url, $post);

        return $ret;
    }

    /**
     * 查询
     *
     * @param $params
     * @return array|bool
     */
    public static function query($params)
    {
        $required_fields = [
            'app_id' => true,
            'app_secret' => true,
            'mid' => true,
            'order_no' => true,
        ];
        if (count(array_diff_key($required_fields, $params)) > 0) {
            return false;
        }
        $sign_str = self::signStr($params);
        $params['sign'] = self::sign($sign_str);

        $url = self::getUrl('Payment/Pay/query');
        $ret = self::request($url, $params);

        return $ret;
    }

    /**
     * 查询
     *
     * @param $params
     * @return array|bool
     */
    public static function refund($params)
    {
        $required_fields = [
            'app_id' => true,
            'app_secret' => true,
            'mid' => true,
            'order_no' => true,
        ];
        if (count(array_diff_key($required_fields, $params)) > 0) {
            return false;
        }
        $sign_str = self::signStr($params);
        $params['sign'] = self::sign($sign_str);

        $url = self::getUrl('Payment/Pay/refund');
        $ret = self::request($url, $params);

        return $ret;
    }

    /**
     * 支付参数包
     *
     * @param $config
     * @param $param
     * @return array
     */
    public static function package($config, $param)
    {
        $params = [
            'app_id' => $config['AppId'], // APP ID
            'app_secret' => $config['AppSecret'], // APP SECRET
            'mid' => $config['MchId'], // 商户ID
            'order_no' => $param['order_no'], // 支付平台订单号
            'body' => $param['body'], // 商品的自定义名称
            'total_fee' => $param['total_fee'], // 商品金额
            'notify_url' => $param['notify_url'], // 回调地址
        ];
        $sign_str = self::signStr($params);
        $params['sign'] = self::sign($sign_str);

        return $params;
    }

    /**
     * 生成预签名字符串
     *
     * @param        $params
     * @return string
     */
    public static function signStr(&$params)
    {
        $data = $params;
        ksort($data);
        unset($data['sign']);
        foreach ($data as $k => $v) {
            if ($v === '') {
                unset($data[$k]);
            }
        }
        $sign_str = md5(http_build_query($data));
        unset($params['app_secret']);

        return $sign_str;
    }

    /**
     * 获取签名
     *
     * @param        $sign_str
     * @param int    $sign_type 1 RSA 2 RSA256
     * @return string
     */
    public static function sign($sign_str, $sign_type = 2)
    {
        $pri_key = config('pay/pay')['RsaPrivateKey'];
        $pri_key = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($pri_key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        $pri_key = openssl_pkey_get_private($pri_key);
        ($pri_key) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ($sign_type == 2) {
            openssl_sign($sign_str, $sign, $pri_key, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($sign_str, $sign, $pri_key);
        }
        $sign = base64_encode($sign);
        if ($pri_key) {
            //释放资源
            openssl_free_key($pri_key);
        }

        return $sign;
    }

    /**
     * 签名验证
     *
     * @param        $sign_str
     * @param        $sign
     * @param string $sign_type
     * @return bool
     */
    public static function verify($sign_str, $sign, $sign_type = 'RSA256')
    {
        $pub_key = config('pay/pay')['RsaPublicKey'];
        $pub_key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pub_key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $pub_key = openssl_pkey_get_public($pub_key);
        ($pub_key) or die('RSA公钥错误。请检查公钥文件格式是否正确');
        //调用openssl内置方法验签，返回bool值
        if ('RSA256' == $sign_type) {
            $result = (bool)openssl_verify($sign_str, base64_decode($sign), $pub_key, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($sign_str, base64_decode($sign), $pub_key);
        }
        if ($pub_key) {
            //释放资源
            openssl_free_key($pub_key);
        }

        return $result;
    }
}

