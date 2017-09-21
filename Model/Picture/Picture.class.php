<?php

namespace Model\Picture;

use Bare\DB;
use Bare\Model;

class Picture extends Model
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
        'table' => 'Picture',
        // 必选, 字段信息
        'fields' => [
            'PictureId' => self::VAR_TYPE_KEY,
            'AtlasId' => self::VAR_TYPE_INT,
            'PicUrl' => self::VAR_TYPE_STRING,
            'Url' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        self::CF_MC => DB::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => 'PIC:%d',
        // 可选, 超时时间, 默认不过期
        self::CF_MC_TIME => 86400
    ];

    /**
     * @param $data
     * @return bool|int|string
     */
    public static function addPicture($data, $ignore = true)
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
    public static function updatePicture($id, $data)
    {
        if ($id > 0 && !empty($data)) {
            return parent::updateData($id, $data);
        }
    }

    /**
     * @param $id
     * @return array
     */
    public static function getPictureById($id)
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
    public static function getPictures($where = [], $offset = 0, $limit = 0, $order = '')
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
    public static function delPicture($id)
    {
        if ($id > 0) {
            return parent::delData($id);
        }
    }

}