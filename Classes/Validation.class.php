<?php

/**
 * 验证类
 * Class Validation
 */
class Validation
{

    /**
     * 需要验证的数据
     * @var
     */
    private $data;
    /**
     * 验证结果
     * @var
     */
    private $error;

    /**
     * 选择验证数据
     * @param $data
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;
        $this->error = 0;
        return $this;
    }

    /**
     * 返回检验结果
     * @return bool
     */
    public function check()
    {
        $error = $this->error;
        $this->data = '';
        $this->error = 0;
        return $error > 0 ? false : true;
    }

    /**
     * 检验ip是否合法
     * @param  $final
     * @return $this
     */
    public function isIp($final = false)
    {
        if (!filter_var($this->data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !filter_var($this->data,
                FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
        ) {
            $this->error += 1;
        }
        if ($final) {
            $this->check();
        }
        return $this;
    }

    /**
     * 检验email是否合法
     * @param  $final
     * @return $this
     */
    public function isEmail($final = false)
    {
        if (!filter_var($this->data, FILTER_VALIDATE_EMAIL)) {
            $this->error += 1;
        }
        if ($final) {
            $this->check();
        }
        return $this;
    }

    /**
     * 检验手机号是否合法
     * @param bool $final
     * @return $this
     */
    public function isMobile($final = false)
    {
        if (!preg_match('/^1[0-9]{10}$/', $this->data)) {
            $this->error += 1;
        }
        if ($final) {
            $this->check();
        }
        return $this;
    }


}
