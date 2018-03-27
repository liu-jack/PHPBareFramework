<?php
/**
 * 微信支付接口相关
 *
 * @package lib
 * @subpackage plugins.weixin
 *
 * @author hjh<hjhworld@gmail.com>
 */


namespace lib\plugins\weixin;

class PayApi
{
    //api请求地址前缀
    const API_PAY_URL_PREFIX = 'https://api.mch.weixin.qq.com/';

    //配置类
    private $config;

    public function __construct($config_array)
    {
        $this->config = new Config($config_array);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getPayPublicParams()
    {
        return array(
            'appid' => $this->config->getAppId(),
            'mch_id' => $this->config->getMchId(),
            'nonce_str' => Data::getNonceStr(),
        );
    }


    // 企业付款到零钱
    public function getPayPublicParams2()
    {
        return array(
            'mch_appid' => $this->config->getAppId(),
            'mchid' => $this->config->getMchId(),
            'nonce_str' => Data::getNonceStr(),
        );
    }

    // 企业付款到银行卡
    public function getPayPublicParams3()
    {
        return array(
            'mch_id' => $this->config->getMchId(),
            'nonce_str' => Data::getNonceStr(),
        );
    }

    /**
     * 企业付款到零钱
     *
     * @param array $params 企业付款
     * @return bool|mixed
     */
    public function enterprisePay($params)
    {
        $url = self::API_PAY_URL_PREFIX . 'mmpaymkttransfers/promotion/transfers';
        $required_fields = [
            "partner_trade_no",
            "openid",
            "check_name",
            "amount",
            "desc",
            'spbill_create_ip',
        ];

        $xml = $this->getXmlData2($params, $required_fields);
        if ($xml === false) {
            return false;
        }

        return $this->postXmlCurl($xml, $url, true);
    }

    /**
     * 企业付款到银行卡
     *
     * @param array $params 企业付款到银行卡
     * @return bool|mixed
     */
    public function enterprisePayCard($params)
    {
        $url = self::API_PAY_URL_PREFIX . 'mmpaysptrans/pay_bank';
        $required_fields = [
            "partner_trade_no",
            "enc_bank_no",
            "enc_true_name",
            "bank_code",
            "amount",
            'desc',
        ];

        $xml = $this->getXmlData2($params, $required_fields, 3);
        if ($xml === false) {
            return false;
        }

        return $this->postXmlCurl($xml, $url, true);
    }

    /**
     * 企业付款到银行卡rsa加密key
     *
     * @return bool|mixed
     */
    public function enterprisePayCardRsa()
    {
        $url = 'https://fraud.mch.weixin.qq.com/risk/getpublickey';
        $xml = $this->getXmlData2([], [], 3);
        if ($xml === false) {
            return false;
        }

        return $this->postXmlCurl($xml, $url, true);
    }

    /**
     * 统一下单
     *
     * @param array $params 统一下单参数
     * @return bool|mixed
     */
    public function unified($params)
    {
        $url = self::API_PAY_URL_PREFIX . 'pay/unifiedorder';
        $required_fields = [
            "out_trade_no",
            "body",
            "total_fee",
            "notify_url",
            "trade_type",
            'spbill_create_ip',
        ];

        $xml = $this->getXmlData($params, $required_fields);
        if ($xml === false) {
            return false;
        }
        return $this->postXmlCurl($xml, $url);
    }

    /**
     * 查询订单
     *
     * @param array $params 查询订单参数
     * @return bool|mixed
     */
    public function query($params)
    {
        $url = self::API_PAY_URL_PREFIX . 'pay/orderquery';
        $required_fields = [
            'out_trade_no'
        ];
        $xml = $this->getXmlData($params, $required_fields);
        if ($xml === false) {
            return false;
        }
        return $this->postXmlCurl($xml, $url);
    }

    /**
     * 申请退款
     *
     * @param array $params 申请退款参数
     * @return bool|mixed
     */
    public function refund($params)
    {
        $url = self::API_PAY_URL_PREFIX . 'secapi/pay/refund';
        $required_fields = [
            "out_trade_no",
            "out_refund_no",
            "total_fee",
            "refund_fee",
            //"refund_desc",
        ];

        $xml = $this->getXmlData($params, $required_fields);
        if ($xml === false) {
            return false;
        }
        return $this->postXmlCurl($xml, $url, true);
    }

    /**
     * 获取请求的xml数据
     *
     * @param array $params 请求参数
     * @param array $required_params 必须参数
     * @return bool|string
     */
    private function getXmlData($params, $required_params)
    {
        $data = new Data(array_merge($params, $this->getPayPublicParams()));
        if ($data->checkData($required_params) === false) {
            return false;
        }
        $data->setSign($this->config->getKey());
        return $data->toXml();
    }

    /**
     * 获取请求的xml数据
     *
     * @param array $params          请求参数
     * @param array $required_params 必须参数
     * @param int   $type            企业付款类型 2：付款到零钱 3：付款到银行卡
     * @return bool|string
     */
    private function getXmlData2($params, $required_params, $type = 2)
    {
        if ($type == 3) {
            $data = new Data(array_merge($params, $this->getPayPublicParams3()));
        } else {
            $data = new Data(array_merge($params, $this->getPayPublicParams2()));
        }
        if ($data->checkData($required_params) === false) {
            return false;
        }
        $data->setSign($this->config->getKey());

        return $data->toXml();
    }

    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml 需要post的xml数据
     * @param string $url 请求地址
     * @param bool $useCert 是否使用证书，默认不需要
     * @param int $second url执行超时时间，默认30s
     * @return mixed
     */
    private function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($useCert == true) {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $this->config->getSslCertPath());
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $this->config->getSslKeyPath());
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            $msg = curl_error($ch);
            curl_close($ch);
            debug_log("curl出错，错误码: $error, msg: {$msg}", JF_LOG_ERROR);
            return false;
        }
    }
}
