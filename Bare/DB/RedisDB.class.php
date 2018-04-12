<?php

namespace Bare\DB;

class RedisDB extends \Redis
{
    /**
     * 默认端口号
     *
     * @var integer
     */
    const PORT = 6379;
    /**
     * 默认连接超时时间
     *
     * @var integer
     */
    const TIMEOUT = 60;

    /**
     * Redis值类型: 不存在
     *
     * @var integer
     */
    const REDIS_NONE = 0;
    /**
     * Redis值类型: 字符串
     *
     * @var integer
     */
    const REDIS_STRING = 1;
    /**
     * Redis值类型: 集合
     *
     * @var integer
     */
    const REDIS_SET = 2;
    /**
     * Redis值类型: 列表
     *
     * @var integer
     */
    const REDIS_LIST = 3;
    /**
     * Redis值类型: 有序集合
     *
     * @var integer
     */
    const REDIS_ZSET = 4;
    /**
     * Redis值类型: 哈稀表
     *
     * @var integer
     */
    const REDIS_HASH = 5;

    /**
     * 构造函数
     *
     * @param string|array $host    Redis服务器的主机名或IP地址
     * @param integer      $dbindex 数据库索引号
     * @param integer      $timeout 超时时间
     * @param string|bool  $auth    授权
     */
    public function __construct($host = 'localhost', $dbindex = 0, $timeout = self::TIMEOUT, $auth = false)
    {
        parent::__construct();
        if (is_array($host)) {
            $port = isset($host['port']) ? $host['port'] : self::PORT;
            $timeout = isset($host['timeout']) ? $host['timeout'] : self::TIMEOUT;
            $host = isset($host['host']) ? $host['host'] : 'localhost';
        } else {
            $port = self::PORT;
        }
        $this->connect($host, $port, $timeout);
        if ($auth) {
            $ret = $this->auth($auth);
            if (empty($ret)) {
                throw new \RuntimeException("redis auth error, host: {$host}, port: {$port}, auth: {$auth}", 1);
            }
        }
        $this->select($dbindex);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * 保存序列化数据
     *
     * @param     $key
     * @param     $value
     * @param int $timeout
     * @return bool
     */
    public function setS($key, $value, $timeout = 0)
    {
        return parent::set($key, serialize($value), $timeout);
    }

    /**
     * 获取序列化数据
     *
     * @param $key
     * @return mixed
     */
    public function getS($key)
    {
        return unserialize(parent::get($key));
    }

    /**
     * 检查给定数组中的元素是否为指定集合的成员
     *
     * @param string $key    集合的key
     * @param array  $values 包含要检查元素的数组
     * @return array
     */
    public function sContains($key, $values)
    {
        $values = (array)$values;
        $rets = [];
        if (count($values) > 0) {
            $vals = array_values($values);
            $this->multi(self::PIPELINE);
            foreach ($vals as $val) {
                $this->sIsMember($key, $val);
            }
            $result = $this->exec();
            if (is_array($result)) {
                foreach ($result as $key => $res) {
                    $rets[$vals[$key]] = $res;
                }
            }
        }

        return $rets;
    }

    /**
     * 向集合中添加多个成员
     *
     * @param string $key    集合的key
     * @param array  $values 要加入集合的元素数组
     * @return array
     */
    public function sAddMulti($key, $values)
    {
        $vals = (array)$values;
        $rets = [];
        if (count($vals) > 0) {
            $this->multi(self::PIPELINE);
            foreach ($vals as $val) {
                $this->sAdd($key, $val);
            }
            $rets = $this->exec();
        }

        return $rets;
    }
}
