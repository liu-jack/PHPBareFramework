<?php
/**
 * 华为支付 app签名包
 *
 *
 * @author     camfee<camfee@foxmail.com>
 *
 */

namespace Classes\HuaWei;

class HuaWeiPay
{
    /**
     * @see http://developer.huawei.com/consumer/cn/service/hms/catalog/HuaweiJointOperation.html?page=hmssdk_jointOper_api_reference_c10
     * @param $config
     * @param $param
     * @return array
     */
    public static function generatePackageParams($config, $param)
    {
        $params = [
            'applicationID' => $config['AppID'], // APP ID
            'merchantId' => $config['MchId'], // 商户ID
            'sdkChannel' => 1, // 渠道信息  0 代表自有应用，无渠道 1 代表应用市场渠道  2 代表预装渠道 3 代表游戏中心
            'requestId' => $param['requestId'], // 商户订单号
            'productName' => $param['productName'], // 商品的自定义名称
            'productDesc' => $param['productDesc'], // 商品描述
            'amount' => $param['amount'], // 商品金额
            'url' => $param['url'], // 回调地址
            'country' => 'CN',
            'currency' => 'CNY',
            'urlver' => '2',
        ];
        $data = self::getPreSign($params);
        $params['sign'] = self::sign($data);
        $params['merchantName'] = $config['MchName']; // 商户名称
        $params['serviceCatalog'] = 'X5'; // 商品类型
        $params['extReserved'] = ''; // 商户侧保留信息

        return $params;
    }

    /**
     * 生成预签名字符串
     *
     * @param $params
     * @return string
     */
    public static function getPreSign($params)
    {
        ksort($params);
        unset($params['sign'], $params['signType']);
        $content = '';
        $i = 0;
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null && $value !== 'null') {
                $content .= ($i == 0 ? '' : '&') . $key . '=' . $value;
            }
            $i++;
        }

        return $content;
    }

    /**
     * 获取签名
     *
     * @param        $data
     * @param string $signType
     * @return string
     */
    public static function sign($data, $signType = 'RSA256')
    {
        $priKey = loadconf('mobileapi/plugins')['huawei']['RsaPrivateKey'];
        $priKey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($priKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        $priKey = openssl_pkey_get_private($priKey);
        ($priKey) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ('RSA256' == $signType) {
            openssl_sign($data, $sign, $priKey, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $priKey);
        }
        $sign = base64_encode($sign);
        if ($priKey) {
            //释放资源
            openssl_free_key($priKey);
        }

        return $sign;
    }

    /**
     * 签名验证
     *
     * @param        $data
     * @param        $sign
     * @param string $signType
     * @return bool
     */
    public static function verify($data, $sign, $signType = 'RSA256')
    {
        $pubKey = loadconf('mobileapi/plugins')['huawei']['RsaPublicKey'];
        $pubKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $pubKey = openssl_pkey_get_public($pubKey);
        ($pubKey) or die('RSA公钥错误。请检查公钥文件格式是否正确');
        //调用openssl内置方法验签，返回bool值
        if ('RSA256' == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $pubKey, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $pubKey);
        }
        if ($pubKey) {
            //释放资源
            openssl_free_key($pubKey);
        }

        return $result;
    }
}

