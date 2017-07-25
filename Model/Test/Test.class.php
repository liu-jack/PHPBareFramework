<?php

namespace Model\Test;

use Bare\DB;
use Bare\Model;

class Test extends Model
{

    /**
     * 配置文件
     *
     * @var array
     */
    protected static $_conf = [
        // 必选, 数据库代码 (来自DB配置), w: 写, r: 读
        'db' => [
            'w' => DB::DB_TEST_W,
            'r' => DB::DB_TEST_R
        ],
        // 必选, 数据表名
        'table' => 'tag',
        // 必选, 字段信息
        'fields' => [
            'id' => self::VAR_TYPE_KEY,
            'cate' => self::VAR_TYPE_STRING,
            'second' => self::VAR_TYPE_STRING,
            'third' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        'mc' => '',
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        'mckey' => '',
        // 可选, 超时时间, 默认不过期
        'mctime' => 0
    ];

    /**
     * @param $data
     * @return bool|int|string
     */
    public static function addTag($data)
    {
        if (!empty($data)) {
            return parent::addData($data);
        }
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     */
    public static function updateTag($id, $data)
    {
        if ($id > 0 && !empty($data)) {
            return parent::updateData($id, $data);
        }
    }

    /**
     * @param $id
     * @return array
     */
    public static function getTagById($id)
    {
        if ($id > 0) {
            return parent::getDataById($id);
        }
    }

    /**
     * @param array $where
     * @param int $offset
     * @param int $limit
     * @param string $order
     * @return array
     */
    public static function getTags($where = [], $offset = 0, $limit = 0, $order = '')
    {
        $extra = [
            'fields' => '*',
            'offset' => $offset,
            'limit' => $limit,
            'order' => $order,
        ];
        return parent::getDataByFields($where, $extra);
    }

    /**
     * @param $id
     * @return bool
     */
    public static function delTag($id)
    {
        if ($id > 0) {
            return parent::delData($id);
        }
    }

}