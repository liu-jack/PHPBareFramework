<?php

namespace Bare\DB;

use Memcached;

class MemcachedDB
{
    /**
     * @var Memcached $memcached Memcached 缓存连接对象
     * @access public
     */
    public $memcached = null;

    /**
     * @var string $prefix 变量前缀
     */
    public $prefix = '';

    /**
     * @var string $key 唯一KEY
     */
    public $key;

    /**
     * 数据查询的统计
     */
    public static $query = [];

    /**
     * 数据缓存沲
     */
    public static $data = [];

    /**
     * 构造函数
     *
     * @param mixed  $host    Memcached 服务器的主机名或IP地址或者为服务器组相关信息的数组
     * @param int    $port    端口号 当$host为数组时不生效
     * @param int    $timeout 连接超时时间, 默认100毫秒
     * @param string $key     唯一KEY
     */
    public function __construct($host = 'localhost', $port = 11211, $timeout = 60, $key = '')
    {
        $this->memcached = new Memcached();

        $this->key = $key;
        self::$data[$this->key] = [];
        $host = is_array($host) ? $host : [['host' => $host, 'port' => $port]];
        $this->memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, $timeout);
        $this->memcached->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
        $this->memcached->addServers($host);
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->memcached->quit();
    }

    /**
     * 在cache中设置键为$key的项的值，如果该项不存在，则新建一个项
     *
     * @param mixed        $key    如果$var为null,$key必为数组，设置方式为['key' => 'value', ...],如$var不为null, $key值必为string
     * @param string|array $var    值, 如果此值不为null,则$key必为string。设置将会返回false.[增加此参数只为兼容之前方法中的设置]
     * @param int          $expire 到期秒数
     * @return bool 如果成功则返回 TRUE，失败则返回 FALSE。
     * @access public
     */
    public function set($key, $var = null, $expire = 0)
    {
        if (empty($var)) {
            $arr_data = [];
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    $mckey = $this->prefix . $k;
                    self::$data[$this->key][$mckey] = $v;
                    $arr_data[$mckey] = $v;
                }

                if (count($arr_data) > 0) {
                    return $this->memcached->setMulti($arr_data, $expire);
                }
            }

            return false;
        } else {
            //兼容老的版本
            $key = $this->prefix . $key;

            self::$data[$this->key][$key] = $var;

            return $this->memcached->set($key, $var, $expire);
        }
    }

    /**
     * 在cache中获取键为$key的项的值，$key可为键值项的数组
     *
     * @param mixed $key 键值,也可为数组键值
     * @return mixed ，如是单个键值，则为该值的缓存，如无值则返回false，如$key为数组，则返回相应的值的数组
     * @access public
     */
    public function get($key)
    {
        if (is_array($key)) {
            $key_map = $key;
            $v_data = $k_data = $mapdata = [];
            foreach ($key as $v) {
                $v = $this->prefix . $v;
                if (array_key_exists($v, self::$data[$this->key])) {
                    $v_data[$v] = self::$data[$this->key][$v];
                } else {
                    $k_data[] = $v;
                }
            }

            if (count($k_data) > 0) {
                $k_data = $this->memcached->getMulti($k_data);
                if (is_array($k_data) && count($k_data) > 0) {
                    $v_data = array_merge($v_data, $k_data); //合并到返回数组
                    self::$data[$this->key] = array_merge(self::$data[$this->key], $k_data); //合并到缓存数组
                }
            }

            foreach ($key_map as $k) {
                $mapdata[$k] = $v_data[$k];
            }

            return $mapdata;
        } else {
            $key = $this->prefix . $key;
            if (!array_key_exists($key, self::$data[$this->key])) {
                self::$data[$this->key][$key] = $this->memcached->get($key);
            }

            return self::$data[$this->key][$key];
        }
    }


    /**
     * 在MC中获取为$key的自增ID
     *
     * @param string  $key   自增$key键值
     * @param integer $count 自增量,默认为1
     * @return int|bool                 成功返回自增后的数值,失败返回false
     */
    public function increment($key, $count = 1)
    {
        return $this->memcached->increment($key, $count);
    }

    /**
     * 清空cache中所有项
     *
     * @param integer $delay 延迟多少秒进行清空，默认为立即清空
     * @return bool   如果成功则返回 TRUE，失败则返回 FALSE。
     * @access public
     */
    public function flush($delay = 0)
    {
        return $this->memcached->flush($delay);
    }

    /**
     * 删除在cache中键为$key的项的值,$key可为数组。
     *
     * @param mixed $key 键值或键值数组
     * @return bool 如果成功则返回 TRUE，失败则返回 FALSE。
     * @access public
     */
    public function delete($key)
    {
        $newkey = [];
        foreach ((array)$key as $v) {
            $tmpkey = $this->prefix . $v;
            $newkey[] = $tmpkey;
            if (array_key_exists($tmpkey, self::$data[$this->key])) {
                unset(self::$data[$this->key][$v]);
            }
        }

        return $this->memcached->deleteMulti($newkey);
    }
}
