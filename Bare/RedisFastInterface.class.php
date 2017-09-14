<?php

/**
 * Redis 快速数据片接口
 */

namespace Bare;

use Common\RedisConst;

class RedisFastInterface
{
    /**
     * 数据类型 - 手机信息
     */
    const TYPE_APP_INFO = 'appinfo';

    /**
     * 配置信息
     *
     * @var array
     */
    private $config = [
        self::TYPE_APP_INFO => [
            'prefix' => 'appinfo:',
            'db' => RedisConst::FAST_DB_INDEX,
            'conn_write' => RedisConst::FAST_DB_W,
            'conn_read' => RedisConst::FAST_DB_R,
            'fields' => [
                'Version_1' => 'a',
                'Version_3' => 'b',
                'AppScreen_1' => 'e',
                'AppScreen_3' => 'f',
            ],
            'fields_reverse' => [
                'a' => 'Version_1',
                'b' => 'Version_3',
                'e' => 'AppScreen_1',
                'f' => 'AppScreen_3',
            ]
        ]
    ];

    /**
     * 构造函数
     *
     * @param string $type 数据类型
     * @throws \Exception
     */
    public function __construct($type)
    {
        if (!isset($this->config[$type])) {
            throw new \Exception("Unknown Type: " . $type);
        }

        $this->config = $this->config[$type];
    }

    /**
     * 获取数据
     *
     * @param string|array $itemid 项目ID,支持多个
     * @param string|array $fields 字段信息,*表示全部,支持多个,见self::$config
     * @param string       $mod    读取数据的模式w/r, 默认r
     * @return array                    array('itemid' => array(), ...)
     */
    public function get($itemid, $fields = '*', $mod = 'r')
    {
        $itemid = is_array($itemid) ? $itemid : [$itemid];
        $fields = (!is_array($fields) && $fields !== '*') ? [$fields] : $fields;
        $map_fields = [];

        if (is_array($fields)) {
            foreach ($fields as $v) {
                if (isset($this->config['fields'][$v])) {
                    $map_fields[] = $this->config['fields'][$v];
                }
            }
        }
        if ($fields === '*') {
            $map_fields = array_values($this->config['fields']);
        }

        $redis = $this->_redis($mod == 'r' ? 'r' : 'w')->multi();

        foreach ($itemid as $v) {
            $redis->hMGet($this->config['prefix'] . $v, $map_fields);
        }
        $res = $redis->exec();

        $data = array();
        foreach ($res as $k => $v) {
            $data[$itemid[$k]] = $this->_parse($v);
        }

        return $data;
    }

    /**
     * 设置数据
     *
     * @param integer $itemid 项目ID
     * @param array   $vals   数据, array('field' => 'value', ...)
     * @return boolean                成功true, 失败false
     */
    public function set($itemid, $vals)
    {
        $fields = array();

        foreach ($vals as $k => $v) {
            if (!isset($this->config['fields'][$k])) {
                return false;
            }
            $fields[$this->config['fields'][$k]] = $v;
        }

        return $this->_redis('w')->hMset($this->config['prefix'] . $itemid, $fields);
    }

    /**
     * 设置自增数据
     *
     * @param integer $itemid   项目ID
     * @param string  $field    字段名
     * @param integer $incr_num 自增数,可以为负数
     * @return boolean
     */
    public function incr($itemid, $field, $incr_num = 1)
    {
        if (isset($this->config['fields'][$field])) {
            return $this->_redis('w')->hIncrBy($this->config['prefix'] . $itemid, $this->config['fields'][$field],
                $incr_num);
        }

        return false;
    }

    /**
     * 删除一条记录
     *
     * @param integer $itemid 项目ID
     * @return boolean
     */
    public function del($itemid)
    {
        return $this->_redis('w')->del($this->config['prefix'] . $itemid);
    }

    /**
     * 检查Hkey是否存在
     *
     * @param integer $itemid 项目ID
     * @param string  $key    HKey
     * @return boolean
     */
    public function hExists($itemid, $key)
    {
        $key = $this->config['fields'][$key];

        return $this->_redis('w')->hExists($this->config['prefix'] . $itemid, $key);
    }

    /**
     * 解析数据格式
     *
     * @param array $data
     * @return array
     */
    private function _parse($data)
    {

        $table = $this->config['fields_reverse'];

        $tmp = array();
        $empty_flag = true;

        foreach ($data as $k => $v) {
            if (isset($table[$k])) {
                $tmp[$table[$k]] = $v;
            }
            if ($v !== false) {
                $empty_flag = false;
            }
        }

        if ($empty_flag) {
            return [];
        }

        return $tmp;
    }

    /**
     * 初始化Redis连接(读/写)
     *
     * @param string $mod w: Write, r: Read
     * @return \Bare\DB\RedisDB
     */
    private function _redis($mod = 'w')
    {
        $conn = $mod == 'w' ? 'conn_write' : 'conn_read';

        return DB::redis($this->config[$conn], $this->config['db']);
    }
}