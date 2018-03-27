<?php
/**
 * 微信支付 app签名包
 *
 * @package lib
 * @subpackage plugins.weixin
 *
 * @author hjh<hjhworld@gmail.com>
 *
 */

namespace lib\plugins\weixin;

class AppPackageData extends Data
{
    private $config;

    /**
     * AppPackageData constructor.
     * @param string $prepayId 预支付号
     * @param array $config_array 配置参数
     */
    public function __construct($config_array, $prepayId)
    {
        $this->config = new Config($config_array);

        $params = [
            'appid' => $this->config->getAppId() . '',
            'partnerid' => $this->config->getMchId() . '',
            'prepayid' => $prepayId,
            'package' => 'Sign=WXPay',
            'noncestr' => self::getNonceStr(),
            'timestamp' => time() . '',
        ];
        parent::__construct($params);
    }

    public function generatePackageParams()
    {
        $this->setSign($this->config->getKey());
        $this->setValue('packageValue', $this->getValue('package'));
        return $this->getValues();
    }
}
