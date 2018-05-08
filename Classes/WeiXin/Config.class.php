<?php
/**
 * 微信支付配置
 *
 * @package lib
 * @subpackage plugins.weixin
 *
 * @author hjh<hjhworld@gmail.com>
 *
 */

namespace lib\plugins\weixin;

class Config
{
    private $app_id;
    private $mch_id;
    private $key;
    private $app_secret;
    private $ssl_cert_path;
    private $ssl_key_path;

    //微信公众号js sdk 用
    private $js_app_id;
    private $js_app_secret;

    public function __construct($config_array)
    {
        $this->app_id = $config_array['AppId'];
        $this->mch_id = $config_array['MchId'];
        $this->key = $config_array['Key'];
        $this->app_secret = $config_array['AppSecret'];
        $this->ssl_cert_path = $config_array['SslCertPath'];
        $this->ssl_key_path = $config_array['SslKeyPath'];

        $this->js_app_id = $config_array['JsAppId'];
        $this->js_app_secret = $config_array['JsAppSecret'];
    }

    public function getAppId()
    {
        return $this->app_id;
    }

    /**
     * @return mixed
     */
    public function getMchId()
    {
        return $this->mch_id;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getSslCertPath()
    {
        return $this->ssl_cert_path;
    }

    /**
     * @return mixed
     */
    public function getSslKeyPath()
    {
        return $this->ssl_key_path;
    }

    /**
     * @return mixed
     */
    public function getAppSecret()
    {
        return $this->app_secret;
    }

    /**
     * @return mixed
     */
    public function getJsAppId()
    {
        return $this->js_app_id;
    }

    /**
     * @return mixed
     */
    public function getJsAppSecret()
    {
        return $this->js_app_secret;
    }
}
