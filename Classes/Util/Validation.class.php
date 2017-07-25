<?php
/**
 * 数据验证处理
 *
 * @author 付祥 <892576@gmail.com>
 *
 * $Id$
 */

namespace lib\util;

class Validation
{

    /**
     * 检验: 是否是POST
     */
    const POST = 0x01;
    /**
     * 检验: 是否是GET
     */
    const GET = 0x02;
    /**
     * 检验: 是否必传
     */
    const REQUIRED = 0x03;
    /**
     * 检验: 是否是字符串
     */
    const STRING = 0x10;
    /**
     * 检验: 是否是数值
     */
    const INT = 0x11;
    /**
     * 检验: 是否是布尔
     */
    const BOOL = 0x12;
    /**
     * 检验: 是否是邮箱
     */
    const EMAIL = 0x20;
    /**
     * 检验: 是否是合格的IP
     */
    const IP = 0x21;
    /**
     * 检验: 是否是合格的手机号
     */
    const PHONE = 0x22;
    /**
     * 检验: 是否是合格的URL
     */
    const URL = 0x23;
    /**
     * 检验: 是否是合格的日期(Y-m-d)
     */
    const DATE = 0x24;
    /**
     * 检验: 是否超过最大值 相当于>=
     */
    const MAX = 0x30;
    /**
     * 检验: 是否低于最小值 相当于<=
     */
    const MIN = 0x31;
    /**
     * 检验: 是否大于某值
     */
    const GT = 0x32;
    /**
     * 检验: 是否小于某值
     */
    const LT = 0x33;
    /**
     * 检验: 是否不等于某值
     */
    const NEQ = 0x34;
    /**
     * 检验: 是否大于等于某值
     */
    const GTE = 0x35;
    /**
     * 检验: 是否小于等于某值
     */
    const LTE = 0x36;
    /**
     * 检验: 是否等于某值
     */
    const EQ = 0x37;
    /**
     * 检验: 范围值 相当于(>= && <=)
     */
    const RANGE = 0x38;
    /**
     * 检验: 普通字符长度
     */
    const LEN = 0x40;
    /**
     * 检验: 中文字符长度
     */
    const CLEN = 0x41;
    /**
     * 检验: 普通字符长度
     */
    const MINLEN = 0x42;
    /**
     * 检验: 中文字符长度
     */
    const MINCLEN = 0x43;


    /*
     * 验证参数
     */
    private static $_checker_map = [
        self::POST => 'Post',
        self::GET => 'Get',
        self::REQUIRED => 'Required',
        self::STRING => 'String',
        self::INT => 'Int',
        self::BOOL => 'Bool',
        self::EMAIL => 'Email',
        self::IP => 'IP',
        self::PHONE => 'Phone',
        self::URL => 'Url',
        self::DATE => 'Date',
        self::MAX => 'Max',
        self::MIN => 'Min',
        self::GT => 'Gt',
        self::LT => 'Lt',
        self::NEQ => 'Neq',
        self::GTE => 'Gte',
        self::LTE => 'Lte',
        self::EQ => 'Eq',
        self::RANGE => 'Range',
        self::LEN => 'Len',
        self::CLEN => 'Clen',
        self::MINLEN => 'Minlen',
        self::MINCLEN => 'Minclen',
    ];

    /*
     * 输出错误信息
     */
    private static $_out_error = [
        self::POST => '非POST提交的数据',
        self::GET => '非GET提交的数据',
        self::REQUIRED => '必填值没有提交',
        self::STRING => '提交的数据非String类型',
        self::INT => '提交的数据非Int类型',
        self::BOOL => '提交的数据非Bool类型',
        self::EMAIL => 'Email格式不正确',
        self::IP => 'IP地址不正确',
        self::PHONE => '手机号码填写不正确',
        self::URL => 'Url不正确',
        self::DATE => 'Date日期格式不符合要求',
        self::MAX => '数值超过上限',
        self::MIN => '数值低于下限',
        self::GT => '数值小于限定',
        self::LT => '数值大于限定',
        self::NEQ => '数值不符合要求',
        self::GTE => '数据小于限定',
        self::LTE => '数值大于限定',
        self::EQ => '数据不符合要求',
        self::RANGE => '数据超出允许值范围',
        self::LEN => '字符数过长',
        self::CLEN => '字符数过长',
        self::MINLEN => '字符数太短',
        self::MINCLEN => '字符数太短',
    ];

    /**
     * 对应比较符
     */
    private static $_symlob = [
        self::GT => ">",
        self::LT => "<",
        self::NEQ => "<>",
        self::GTE => ">=",
        self::LTE => "<=",
        self::EQ => "=",
    ];

    /**
     * 正确数组
     */
    public $value = [];
    /**
     * 错误数组
     */
    public $error = [];

    /**
     * Validation constructor.
     */
    public function __construct()
    {
    }

    /**
     * 析构函数 清除掉静态变量信息
     */
//    public function __destruct()
//    {
//        $this->addError();
//        $this->addValue();
//    }

    /**
     * 验证传递过来的数据
     *
     * @param array $rule 规则数组 //如［'title' => 'post|required|string'］
     *                            //title表示传递的Key, 过滤规则请参看self::_checker_map
     *                            //比较符请使用 $>1|$<=10 前面须用 $开头 支持的比较符请查看self::_symlob
     *                            //rande规则书写请按 range:1-2格式
     * @return bool 如果有验证不通过的话返回false, 执行成功返回true;
     */
    public function validate($rule)
    {
        if (is_array($rule) && count($rule) > 0) {
            foreach ($rule as $k => $v) {
                $singlerule = explode('|', trim($v));
                foreach ($singlerule as $val) {
                    $ret = $this->formatRule($val, $k);
                    if (!empty($ret['error'])) {
                        $this->addError([$k => $val . $ret['error']]);
                    }
                    $this->$ret['func']($ret);
                }
            }
            $error = $this->getError();
            if ($error) {
                return false;
            }
            return true;
        } else {
            $this->addError(['0' => '没有配制规则']);
            return false;
        }
    }

    protected function addError($data = [])
    {
        if (empty($data)) {
            $this->error = null;
        } else {
            foreach ($data as $k => $v) {
                unset($this->value[$k]);
                $this->error[$k] = $v;
            }
        }

        return $this->error;
    }

    protected function formatRule($rule, $col)
    {
        $frule = [];
        if (strpos($rule, ":") === false) {
            if (strpos($rule, "$") === false) {
                $frule['rule'] = ucfirst($rule);
                $frule['value'] = $rule;
            } else {
                preg_match('/\$([^\d]+)([\d]+)$/isU', $rule, $data);
                if (in_array($data[1], self::$_symlob)) {
                    $tsymlob = array_flip(self::$_symlob);
                    $frule['rule'] = self::$_checker_map[$tsymlob[$data[1]]];
                    $frule['value'] = $data[2];
                }
            }
        } else {
            $temp = explode(':', $rule);
            $frule['rule'] = ucfirst($temp[0]);
            $frule['value'] = $temp[1];
        }
        if (isset($_GET[$col])) {
            $frule['data'] = $_GET[$col];
        }
        if (isset($_POST[$col])) {
            $frule['data'] = $_POST[$col];
        }
        if (isset($frule['rule'])) {
            $frule['col'] = $col;
            $frule['func'] = "_check" . $frule['rule'];
        } else {
            return ['error' => '没有找到与之相应的规则方法'];
        }

        return $frule;
    }

    /**
     * 取验证后符合要求的参数
     *
     * @return array 如［'title' => 'test'］
     */
    public function getValue()
    {
        $error = $this->error;
        foreach ($error as $k => $v) {
            unset($this->value[$k]);
        }

        return $this->value;
    }

    /**
     * 取验证后不符合规则的错误信息
     *
     * @return array 如［'title' => '必填值没有提交'］
     */
    public function getError()
    {
        return $this->error;
    }

    protected function _checkPost($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (isset($_POST[$val['col']])) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::POST]]);
        }
    }

    protected function addValue($data = [])
    {
        if (empty($data)) {
            $this->value = null;

            return [];
        } else {
            foreach ($data as $k => $v) {
                $this->value[$k] = $v;
            }

            return $this->value;
        }
    }

    protected function _checkGet($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (isset($_GET[$val['col']])) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::GET]]);
        }
    }

    protected function _checkRequired($val)
    {
        if (!isset($_POST[$val['col']]) && !isset($_GET[$val['col']])) {
            return $this->addError([$val['col'] => self::$_out_error[self::REQUIRED]]);
        }

        return $this->addValue([$val['col'] => $val['data']]);
    }

    protected function _checkString($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (is_string($val['data']) || is_numeric($val['data'])) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::STRING]]);
        }
    }

    protected function _checkInt($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (is_int($val['data'])) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::INT]]);
        }
    }

    protected function _checkBool($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (is_bool($val['data'])) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::BOOL]]);
        }
    }

    protected function _checkEmail($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (filter_var($val['data'], FILTER_VALIDATE_EMAIL)) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::EMAIL]]);
        }
    }

    protected function _checkUrl($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (filter_var($val['data'], FILTER_VALIDATE_URL)) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::URL]]);
        }
    }

    protected function _checkIP($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (ip2long($val['data'])) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::IP]]);
        }
    }

    protected function _checkPhone($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        $phone = $val['data'];
        $reg = "/^1[3|4|5|7|8]\d{4,9}$/";
        if (strlen($phone) == 11 && preg_match($reg, $phone) > 0) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::PHONE]]);
        }
    }

    protected function _checkDate($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        $t = date_parse_from_format('Y-m-d', $val['data']);
        if (empty($t['errors'])) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::DATE]]);
        }
    }

    protected function _checkMax($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if ($val['data'] <= $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::MAX]]);
        }
    }

    protected function _checkMin($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if ((int)$val['data'] >= $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::MIN]]);
        }
    }

    protected function _checkGt($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if ((int)$val['data'] > $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::GT]]);
        }
    }

    protected function _checkLt($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if ((int)$val['data'] < $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::LT]]);
        }
    }

    protected function _checkNeq($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if ($val['data'] <> $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::NEQ]]);
        }
    }

    protected function _checkGte($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if ((int)$val['data'] >= $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::GTE]]);
        }
    }

    protected function _checkLte($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if ((int)$val['data'] <= $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::LTE]]);
        }
    }

    protected function _checkEq($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if ($val['data'] == $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::EQ]]);
        }
    }

    protected function _checkRange($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        list($min, $max) = explode('-', $val['value']);
        if ((int)$val['data'] >= $min && $val['data'] <= $max) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::RANGE]]);
        }
    }

    protected function _checkLen($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (strlen($val['data']) <= $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::LEN]]);
        }
    }

    protected function _checkClen($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (mb_strlen($val['data']) <= $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::CLEN]]);
        }
    }

    protected function _checkMinlen($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (strlen($val['data']) >= $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::MINLEN]]);
        }
    }

    protected function _checkMinclen($val)
    {
        if (!isset($val['data'])) {
            return null;
        }
        if (mb_strlen($val['data']) >= $val['value']) {
            return $this->addValue([$val['col'] => $val['data']]);
        } else {
            return $this->addError([$val['col'] => self::$_out_error[self::MINCLEN]]);
        }
    }
}