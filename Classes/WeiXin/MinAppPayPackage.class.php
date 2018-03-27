<?php
/**
 * 小程序支付签名包
 *
 * @author: hjh <hjh@jf.com>
 *
 * Date: 2017/11/30
 * Time: 19:53
 */

namespace lib\plugins\MinApp;

use lib\plugins\weixin\Config;
use lib\plugins\weixin\Data;

class MinAppPayPackage extends Data
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
