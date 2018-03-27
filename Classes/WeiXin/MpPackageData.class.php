<?php
/**
 * 微信支付 app签名包
 *
 * @package    lib
 * @subpackage plugins.weixin
 * @author     hjh<hjhworld@gmail.com>
 */

namespace lib\plugins\weixin;

class MpPackageData extends Data
{
    private $config;

    /**
     * MpPackageData constructor.
     *
     * @param string $prepayId     预支付号
     * @param array  $config_array 配置参数
     */
    public function __construct($config_array, $prepayId)
    {
        $this->config = new Config($config_array);

        $params = [
            'appId'     => $this->config->getAppId() . '',
            'signType'  => 'MD5',
            'package'   => 'prepay_id=' . $prepayId,
            'nonceStr'  => self::getNonceStr(),
            'timeStamp' => time() . '',
        ];

        parent::__construct($params);
    }

    public function generatePackageParams()
    {
        $sign = $this->makeSign($this->config->getKey());
        $this->setValue('paySign', $sign);
        $this->setValue('packageValue', $this->getValue('package'));
        return $this->getValues();
    }
}
