<?php
/**
 * 微信支付数据相关.
 *
 * @author     hjh <hjh@jf.com>
 *             Date: 2017/1/13
 *             Time: 12:50
 */

namespace lib\plugins\weixin;

use Common\Bridge;

class JSSDK
{
    const MC_TOKEN_KEY = 'WX_JS_TOKEN_%s'; //app_id
    const MC_TICKET_KEY = 'WX_JS_TICKET_%s'; //app_id
    const MC_EXPIRE = 3600;

    const URL_PREFIX = 'https://api.weixin.qq.com/cgi-bin/';

    private $_config;

    public function __construct($config_array)
    {
        $this->_config = new Config($config_array);
    }

    public function getSignPackage($url = '')
    {
        $ticket = $this->getTicket();
        if (empty($ticket)) {
            return false;
        }
        // 注意 URL 一定要动态获取，不能 hardcode.
        if (empty($url)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
            $url = "{$protocol}{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        }

        $timestamp = time();
        $nonceStr = Data::getNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket={$ticket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";
        $signature = sha1($string);

        $signPackage = [
            'appId' => $this->_config->getJsAppId(),
            'nonceStr' => $nonceStr,
            'timestamp' => $timestamp,
            'url' => $url,
            'signature' => $signature,
            //            "rawString" => $string
        ];

        return $signPackage;
    }

    public function getAccessToken()
    {
        return Oauth::getAccessTokenWithCache($this->_config->getJsAppId(), $this->_config->getJsAppSecret());
//        $key = $this->getMcTokenKey();
//        $token = self::getMc()->get($key);
//        if (!empty($token)) {
//            return $token;
//        }
//        $app_id = $this->_config->getJsAppId();
//        $secret = $this->_config->getJsAppSecret();
//        $url = self::URL_PREFIX."token?grant_type=client_credential&appid={$app_id}&secret={$secret}";
//        $res = self::httpGet($url);
//        if (empty($res)) {
//            return false;
//        }
//        $result = json_decode($res);
//        if (!isset($result->access_token)) {
//            return false;
//        }
//        $token = $result->access_token;
//
//        self::getMc()->set($key, $token, self::MC_EXPIRE);
//
//        return $token;
    }

    private function getTicket()
    {
        $key = self::getMcTicketKey();
        $ticket = self::getMc()->get($key);
        if (!empty($ticket)) {
            return $ticket;
        }
        $token = $this->getAccessToken();
        if (empty($token)) {
            return false;
        }

        $url = self::URL_PREFIX."ticket/getticket?type=jsapi&access_token={$token}";

        $res = self::httpGet($url);
        if (empty($res)) {
            return false;
        }
        $result = json_decode($res);
        if (empty($result->ticket)) {
            return false;
        }
        $ticket = $result->ticket;

        self::getMc()->set($key, $ticket, self::MC_EXPIRE);

        return $ticket;
    }

    private static function getMc()
    {
        return Bridge::memcache(Bridge::MEMCACHE_DEFAULT);
    }

    private function getMcTokenKey()
    {
        return sprintf(self::MC_TOKEN_KEY, $this->_config->getJsAppId().'');
    }

    private function getMcTicketKey()
    {
        return sprintf(self::MC_TICKET_KEY, $this->_config->getJsAppId().'');
    }

    private static function httpGet($url, $second = 2000)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $second);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        if (empty($res)) {
            debug_log(['httpGetError', $url]);
        }

        return $res;
    }
}
