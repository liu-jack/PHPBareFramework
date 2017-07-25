<?php

namespace Model\Picture;

use Bare\DB;
use Bare\Model;

class Atlas extends Model
{

    /**
     * 配置文件
     *
     * @var array
     */
    protected static $_conf = [
        // 必选, 数据库代码 (来自DB配置), w: 写, r: 读
        'db' => [
            'w' => DB::DB_PICTURE_W,
            'r' => DB::DB_PICTURE_R
        ],
        // 必选, 数据表名
        'table' => 'Atlas',
        // 必选, 字段信息
        'fields' => [
            'AtlasId' => self::VAR_TYPE_KEY,
            'Title' => self::VAR_TYPE_STRING,
            'CollectUrl' => self::VAR_TYPE_STRING,
            'CreateTime' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        'mc' => DB::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        'mckey' => 'PIC:%d',
        // 可选, 超时时间, 默认不过期
        'mctime' => 86400
    ];

    /**
     * @param $data
     * @return bool|int|string
     */
    public static function addAtlas($data, $ignore = true)
    {
        if (!empty($data)) {
            return parent::addData($data, $ignore);
        }
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     */
    public static function updateAtlas($id, $data)
    {
        if ($id > 0 && !empty($data)) {
            return parent::updateData($id, $data);
        }
    }

    /**
     * @param $id
     * @return array
     */
    public static function getAtlasById($id)
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
    public static function getAtlas($where = [], $offset = 0, $limit = 0, $order = '')
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
    public static function delAtlas($id)
    {
        if ($id > 0) {
            return parent::delData($id);
        }
    }

}