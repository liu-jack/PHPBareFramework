<?php
/**
 *
 */

namespace Classes\Alipay;

class Alipay
{
    const API_URL = 'https://openapi.alipay.com/gateway.do';
    const NOTIFY_URL = 'http://notify.qbaoting.com/alipay/pay.php';
    const APPID = '2017010304820182';
    const RSA_PRIVATE = 'MIICXQIBAAKBgQClACSGbxb8EFmjKxhdj3ttbe/+g+CS34AOF9dtKyaktj3lJd5CFrHlh+VBGu7QFOYFt55n+0ldBv2OqXKHqZ2isVuDeMIwJLWNNqWFyXuVEGW2V5O5M5RqGPuw2nGwLjQvLWTrNrawe/K+zoFfydf+l3E456AymdWNuRO91w2L0wIDAQABAoGBAITyEXv0JHvinKbZAV/ZTSUF0Lqs/ZS52o8AbMZ8Xz2VzVdF5MgxSxNbJMAJeGRWgmQW5952XU1EZaa+Jxbh9q0ldRCnialm0mNCqxXbhhGtJBo7/PHaOpXczp3nAfpSDkfce2bob8R6RmLS0dXwE0uuK8isyL3abWZ3b7BZOVlJAkEA1XAaR3cHG3TJDInJ8Nrgu2jXNSxstLrFGy+xB6rXRcX5B/XCtxH8ellV5evq2/BZA3O76pF2tzdBATBo1XIorQJBAMXnVojQP6Pgf6WmHj0gr71pFSQVvISbhX3rDWURBn0pAZ+1uCV5/NQHSjH+iLbsGnJ6d1txFmvZplR7vFKuln8CQFeUpHPmt5fgmA75C1A1wDmmj4hWLlUKvo6lRzMqOyN6VPGbOsb8LmnV9pVd9QVC3oO4Hcfm4JvVpGrkfl/3dBECQQCpNEbPSMXwtkRM+7/E4cp/9nVl2dPJyTKUW0Cjla/nmQTTaUodeLQLEISGRCrdwvZFxKGCJClYYsmMWBLG2pC/AkBbBr32pQD7l/mmhTssJ0BO3BwH8YIgVog3GbKwkEVL0ZDA/0m+9SSGCApUSQnn9ZSPvHAkxJHK03KkotcIHUEL';

    const RSA_PUBLIC = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';

    const CHARSET = 'UTF-8';

    public static function tradeAppPay($out_trade_no, $total_amount, $subject, $timeout_express = '6h')
    {
        $params = [
            'biz_content' => json_encode([
                'out_trade_no' => $out_trade_no,
                'total_amount' => $total_amount,
                'subject' => $subject,
                'timeout_express' => $timeout_express,
                'seller_id' => '',
                'product_code' => 'QUICK_MSECURITY_PAY'
            ], JSON_UNESCAPED_UNICODE)
        ];

        $sysParams = self::getSysParam('alipay.trade.app.pay');
        $sysParams['notify_url'] = self::NOTIFY_URL;
        $dataParams = array_merge($sysParams, $params);

        $sign = self::generateSign($dataParams);
        $dataParams['sign'] = $sign;

        return self::generateQuery($dataParams);
    }

    private static function generateQuery($dataParams)
    {
        $requestUrl = '';
        foreach ($dataParams as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode(self::characet($sysParamValue, self::CHARSET)) . '&';
        }
        $requestUrl = substr($requestUrl, 0, -1);

        return $requestUrl;
    }

    public static function tradeQuery($out_trade_no, $trade_no)
    {
        $method = 'alipay.trade.query';
        $sysParams = self::getSysParam($method);

        $params = [
            'biz_content' => json_encode([
                'out_trade_no' => $out_trade_no,
                'trade_no' => $trade_no
            ], JSON_UNESCAPED_UNICODE)
        ];
        $dataParams = array_merge($sysParams, $params);
        $sign = self::generateSign($dataParams);
        $dataParams['sign'] = $sign;

        return self::curl(self::API_URL, $dataParams);
    }

    public static function generateSign($params, $signType = 'RSA')
    {
        return self::sign(self::getSignContent($params), $signType);
    }

    private static function getSysParam($method)
    {
        $sysParams['app_id'] = self::APPID;
        $sysParams['method'] = $method;
        $sysParams['format'] = 'json';
        $sysParams['charset'] = self::CHARSET;
        $sysParams['sign_type'] = 'RSA';
        $sysParams['version'] = '1.0';
        $sysParams['timestamp'] = date('Y-m-d H:i:s');

        return $sysParams;
    }

    public static function getSignContent($params)
    {
        ksort($params);

        $stringToBeSigned = '';
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === self::checkEmpty($v) && '@' != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = self::characet($v, self::CHARSET);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . '=' . "$v";
                } else {
                    $stringToBeSigned .= '&' . "$k" . '=' . "$v";
                }
                ++$i;
            }
        }

        unset($k, $v);

        return $stringToBeSigned;
    }

    protected static function checkEmpty($value)
    {
        if (!isset($value)) {
            return true;
        }
        if ($value === null) {
            return true;
        }
        if (trim($value) === '') {
            return true;
        }

        return false;
    }

    /**
     * 转换字符集编码
     *
     * @param $data
     * @param $targetCharset
     *
     * @return string
     */
    public static function characet($data, $targetCharset)
    {
        if (!empty($data)) {
            $fileType = self::CHARSET;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //				$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }

        return $data;
    }

    protected static function sign($data, $signType = 'RSA')
    {
        $priKey = self::RSA_PRIVATE;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($priKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        if ('RSA2' == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }

        $sign = base64_encode($sign);

        return $sign;
    }

    private static function curl($url, $postFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $postBodyString = '';
        $encodeArray = [];
        $postMultipart = false;

        if (is_array($postFields) && 0 < count($postFields)) {
            foreach ($postFields as $k => $v) {
                if ('@' != substr($v, 0, 1)) { //判断是不是文件上传

                    $postBodyString .= "$k=" . urlencode(self::characet($v, self::CHARSET)) . '&';
                    $encodeArray[$k] = self::characet($v, self::CHARSET);
                } else { //文件上传用multipart/form-data，否则用www-form-urlencoded
                    $postMultipart = true;
                    $encodeArray[$k] = new \CURLFile(substr($v, 1));
                }
            }
            unset($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }

        if ($postMultipart) {
            $headers = ['content-type: multipart/form-data;charset=' . self::CHARSET . ';boundary=' . self::getMillisecond()];
        } else {
            $headers = ['content-type: application/x-www-form-urlencoded;charset=' . self::CHARSET];
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $reponse = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \RuntimeException(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new \RuntimeException($reponse, $httpStatusCode);
            }
        }

        curl_close($ch);

        return $reponse;
    }

    /** rsaCheckV1 & rsaCheckV2
     *  验证签名
     *  在使用本方法前，必须初始化AopClient且传入公钥参数。
     *  公钥是否是读取字符串还是读取文件，是根据初始化传入的值判断的。
     **/
    public static function rsaCheckV1($params, $signType = 'RSA')
    {
        $sign = $params['sign'];
        $params['sign_type'] = null;
        $params['sign'] = $sign;

        return self::verify(self::getSignContent($params), $sign, $signType);
    }

    public static function rsaCheckV2($params, $signType = 'RSA')
    {
        $sign = $params['sign'];
        $params['sign'] = null;

        return self::verify(self::getSignContent($params), $sign, $signType);
    }

    public static function verify($data, $sign, $signType = 'RSA')
    {
        $pubKey = self::RSA_PUBLIC;
        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";

        ($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');

        //调用openssl内置方法验签，返回bool值

        if ('RSA2' == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        }
        if (!self::checkEmpty(self::RSA_PUBLIC)) {
            //释放资源
            openssl_free_key(openssl_pkey_get_public($res));
        }

        return $result;
    }

    protected static function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());

        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }
}
