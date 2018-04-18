<?php
/**
 * 支付平台工具类
 *
 * @author     camfee<camfee@foxmail.com>
 *
 */

namespace Classes\Payment;

use Model\Payment\Merchant;

class PayUtil
{
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
     * @param string $sign_str
     * @param int    $mid
     * @return string
     */
    public static function sign($sign_str, $mid)
    {
        $mc_info = Merchant::getInfoByIds($mid);
        $pri_key = file_get_contents(DATA_PATH . $mc_info['RsaPrivateKey']);
        $pri_key = openssl_pkey_get_private($pri_key);
        ($pri_key) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ($mc_info['RsaType'] == 2) {
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
     * @param string $sign_str
     * @param string $sign
     * @param int    $mid
     * @return bool
     */
    public static function verify($sign_str, $sign, $mid)
    {
        $mc_info = Merchant::getInfoByIds($mid);
        $pub_key = file_get_contents(DATA_PATH . $mc_info['RsaPublicKey']);
        $pub_key = openssl_pkey_get_public($pub_key);
        ($pub_key) or die('RSA公钥错误。请检查公钥文件格式是否正确');
        //调用openssl内置方法验签，返回bool值
        if ($mc_info['RsaType'] == 2) {
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

