<?php
/**
 * 微信支付数据相关
 *
 * @package lib
 * @subpackage plugins.weixin
 *
 * @author hjh<hjhworld@gmail.com>
 *
 */

namespace lib\plugins\weixin;

class Data
{
    protected $values = array();

    public function __construct($values)
    {
        $this->values = $values;
    }

    /**
     * 重新设置 values
     * @param $values
     */
    public function setValues($values)
    {
        $this->values = $values;
    }

    public function getValue($k)
    {
        return $this->values[$k];
    }

    public function setValue($k, $value)
    {
        $this->values[$k] = $value;
    }

    /**
     *  从xml得到data
     * @param $xml
     * @return bool|Data
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
        return new self($values);
    }


    public function setSign($key)
    {
        $sign = $this->makeSign($key);
        $this->values['sign'] = $sign;
        return $sign;
    }

    public function getSign()
    {
        return $this->values['sign'];
    }

    public function isSignSet()
    {
        return array_key_exists('sign', $this->values);
    }

    public function toXml()
    {
        if (!is_array($this->values)
            || count($this->values) <= 0
        ) {
            return false;
        }

        $xml = "<xml>";
        foreach ($this->values as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }


    /**
     * 格式化参数格式化成url参数
     */
    public function toUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 生成签名
     * @return string 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign($key)
    {
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->toUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 获取设置的值
     */
    public function getValues()
    {
        return $this->values;
    }

    public function clearKey($key)
    {
        unset($this->values[$key]);
    }

    /**
     * 检查请求参数
     *
     * @param array $required_fields 必须参数
     * @return bool
     */
    public function checkData($required_fields)
    {
        foreach ($required_fields as $value) {
            if (!isset($this->values, $value)) {
                debug_log("$value required key, is not exists");
                return false;
            }
        }
        return true;
    }

    /**
     * 获取随机字符串
     *
     * @param int $length
     * @return string
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
}
