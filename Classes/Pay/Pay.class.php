<?php
/**
 * 支付
 *
 * @author     camfee<camfee@foxmail.com>
 *
 */

namespace Classes\Pay;

class Pay
{
    /**
     * 支付参数包
     *
     * @param $config
     * @param $param
     * @return array
     */
    public static function generatePackageParams($config, $param)
    {
        $params = [
            'appId' => $config['AppId'], // APP ID
            'merchantId' => $config['MchId'], // 商户ID
            'requestId' => $param['requestId'], // 商户订单号
            'body' => $param['productName'], // 商品的自定义名称
            'productDesc' => $param['productDesc'], // 商品描述
            'amount' => $param['amount'], // 商品金额
            'url' => $param['url'], // 回调地址
        ];
        $data = self::signStr($params);
        $params['sign'] = self::sign($data);

        return $params;
    }

    /**
     * 生成预签名字符串
     *
     * @param $params
     * @return string
     */
    public static function signStr($params)
    {
        ksort($params);
        unset($params['sign']);
        foreach ($params as $k => $v) {
            if ($v === '') {
                unset($params[$k]);
            }
        }

        return http_build_query($params);
    }

    /**
     * 获取签名
     *
     * @param        $data
     * @param int    $sign_type 1 RSA 2 RSA256
     * @return string
     */
    public static function sign($data, $sign_type = 1)
    {
        $pri_key = config('pay/pay')['RsaPrivateKey'];
        $pri_key = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($pri_key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        $pri_key = openssl_pkey_get_private($pri_key);
        ($pri_key) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ($sign_type == 2) {
            openssl_sign($data, $sign, $pri_key, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $pri_key);
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
     * @param        $data
     * @param        $sign
     * @param string $sign_type
     * @return bool
     */
    public static function verify($data, $sign, $sign_type = 'RSA256')
    {
        $pub_key = config('pay/pay')['RsaPublicKey'];
        $pub_key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pub_key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $pub_key = openssl_pkey_get_public($pub_key);
        ($pub_key) or die('RSA公钥错误。请检查公钥文件格式是否正确');
        //调用openssl内置方法验签，返回bool值
        if ('RSA256' == $sign_type) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $pub_key, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $pub_key);
        }
        if ($pub_key) {
            //释放资源
            openssl_free_key($pub_key);
        }

        return $result;
    }
}

