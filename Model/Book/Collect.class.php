<?php

namespace Model\Book;

use Bare\DB;
use Bare\Model;

class Collect extends Model
{
    /**
     * 配置文件
     *
     * @var array
     */
    protected static $_conf = [
        // 必选, 数据库代码 (来自DB配置), w: 写, r: 读
        'db' => [
            'w' => DB::DB_29SHU_W,
            'r' => DB::DB_29SHU_R
        ],
        // 必选, 数据表名
        'table' => 'book_collect',
        // 必选, 字段信息
        'fields' => [
            'CollectId' => self::VAR_TYPE_KEY,
            'BookId' => self::VAR_TYPE_INT,
            'FromId' => self::VAR_TYPE_INT,
            'Url' => self::VAR_TYPE_STRING,
            'CollectTime' => self::VAR_TYPE_STRING,
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
    public static function addCollect($data)
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
    public static function updateCollect($id, $data)
    {
        if ($id > 0 && !empty($data)) {
            return parent::updateData($id, $data);
        }
    }

    /**
     * @param $id
     * @return array
     */
    public static function getCollectById($id)
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
    public static function getCollects($where = [], $offset = 0, $limit = 0, $order = '')
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
    public static function delCollect($id)
    {
        if ($id > 0) {
            return parent::delData($id);
        }
    }

}
