<?php
/**
 * 回调通知数据
 *
 * @package    lib
 * @subpackage plugins.weixin
 *
 * @author     hjh<hjhworld@gmail.com>
 *
 */

namespace lib\plugins\weixin;

class NotifyData extends ResultData
{
    private $return_code;
    private $return_msg;

    /**
     *  从xml得到data
     *
     * @param $xml
     * @return bool|NotifyData
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

        return new NotifyData($values);
    }

    public function success()
    {
        $this->return_code = 'SUCCESS';
        $this->return_msg = 'OK';
    }

    public function failure($msg = '')
    {
        $this->return_code = 'FAIL';
        $this->return_msg = $msg;
    }

    /**
     * 检查通知数据的合法性
     *
     * @param $key
     * @return bool
     */
    public function checkNotifyData($key)
    {
        if ($this->checkSign($key) === false) {
            $this->failure('签名错误');

            return false;
        }
        $msg = 'OK';
        if ($this->checkResult($msg) === false) {
            $this->failure($msg);

            return false;
        }
        $this->success();

        return true;
    }

    /**
     * 获取回应回调数据
     *
     * @param bool   $need_sign
     * @param string $key
     * @return bool|string
     */
    public function replayNotify($need_sign = false, $key = '')
    {
        $this->setValues(["return_code" => $this->return_code, "return_msg" => $this->return_msg]);
        if ($need_sign) {
            $this->setSign($key);
        }

        return $this->toXml();
    }
}
