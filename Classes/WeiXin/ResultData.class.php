<?php
/**
 * 微信支付接口返回结果
 *
 * @package lib
 * @subpackage plugins.weixin
 *
 * @author hjh<hjhworld@gmail.com>
 *
 */

namespace lib\plugins\weixin;

class ResultData extends Data
{
    /**
     *  从xml得到data
     * @param $xml
     * @return bool|ResultData
     */
    public static function parseFromXml($xml)
    {
        if (!$xml) {
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return new ResultData($values);
    }

    /**
     * 检测签名
     * @param string $key 签名用的key
     * @return bool
     */
    public function checkSign($key)
    {
        //fix异常
        if (!$this->isSignSet()) {
            debug_log('返回结果签名错误');
            return false;
        }

        $sign = $this->makeSign($key);
        if ($this->getSign() == $sign) {
            return true;
        }
        debug_log("sign error: makeSign: {$sign}, getSign: {$this->getSign()}");
        return false;
    }

    /**
     * 检查返回结果是否正确
     * @param string &$msg 存储错误信息
     * @return bool
     */
    public function checkResult(&$msg)
    {
        if ($this->values['return_code'] !== 'SUCCESS') {
            $msg = $this->values['return_msg'];
            debug_log('接口请求失败： ' . $msg, JF_LOG_ERROR);
            return false;
        }

        if ($this->values['result_code'] !== 'SUCCESS') {
            $msg = $this->values['err_code'];
            debug_log('接口业务请求失败： ' . $msg, JF_LOG_ERROR);
            return false;
        }
        return true;
    }
}
