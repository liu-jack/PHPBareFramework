<?php

namespace Model\Jufeng;

use Bare\DB;
use Bare\M\Model;

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
            'w' => DB::DB_TEST_W,
            'r' => DB::DB_TEST_R
        ],
        // 必选, 数据表名
        'table' => 'weixin_collect',
        // 必选, 字段信息
        'fields' => [
            'id' => self::VAR_TYPE_KEY,
            'title' => self::VAR_TYPE_STRING,
            'digest' => self::VAR_TYPE_STRING,
            'cover' => self::VAR_TYPE_STRING,
            'content_url' => self::VAR_TYPE_STRING,
            'fileid' => self::VAR_TYPE_INT,
            'datetime' => self::VAR_TYPE_STRING,
            'fakeid' => self::VAR_TYPE_STRING,
            'msgid' => self::VAR_TYPE_INT,
            'author' => self::VAR_TYPE_STRING,
            'source_url' => self::VAR_TYPE_STRING,
            'content' => self::VAR_TYPE_STRING,
            'from' => self::VAR_TYPE_STRING,
            'status' => self::VAR_TYPE_INT,
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
    public static function addWeixin($data)
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
    public static function updateWeixin($id, $data)
    {
        if ($id > 0 && !empty($data)) {
            return parent::updateData($id, $data);
        }
    }

    /**
     * @param $id
     * @return array
     */
    public static function getWeixinById($id)
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
    public static function getWeixins($where = [], $offset = 0, $limit = 0, $field = '*', $order = '')
    {
        $extra = [
            'fields' => $field,
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
    public static function delWeixin($id)
    {
        if ($id > 0) {
            return parent::delData($id);
        }
    }

}