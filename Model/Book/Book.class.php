<?php

namespace Model\Book;

use Bare\DB;
use Bare\M\Model;
use Model\Search\BookSearch as SBook;

class Book extends Model
{
    const EXTRA_COVER = 'cover';
    /**
     * 配置文件
     *
     * @var array
     */
    protected static $_conf = [
        // 必选, 数据库连接(来自DBConfig配置), w: 写, r: 读
        'db' => [
            'w' => DB::DB_29SHU_W,
            'r' => DB::DB_29SHU_R
        ],
        // 必选, 数据表名
        'table' => 'Book',
        // 必选, 字段信息
        'fields' => [
            'BookId' => self::VAR_TYPE_KEY,
            'BookName' => self::VAR_TYPE_STRING,
            'Author' => self::VAR_TYPE_STRING,
            'Type' => self::VAR_TYPE_INT,
            'TypeName' => self::VAR_TYPE_STRING,
            'Cover' => self::VAR_TYPE_INT,
            'BookDesc' => self::VAR_TYPE_STRING,
            'Words' => self::VAR_TYPE_INT,
            'ViewCount' => self::VAR_TYPE_INT,
            'LikeCount' => self::VAR_TYPE_INT,
            'FavoriteCount' => self::VAR_TYPE_INT,
            'CreateTime' => self::VAR_TYPE_STRING,
            'UpdateTime' => self::VAR_TYPE_STRING,
            'Status' => self::VAR_TYPE_INT,
            'IsFinish' => self::VAR_TYPE_INT,
            'FromSite' => self::VAR_TYPE_STRING,
            'DefaultFromSite' => self::VAR_TYPE_INT,
        ],
        // 可选, MC连接参数
        self::CF_MC => DB::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => 'Book:%d',
        // 可选, 超时时间, 默认不过期
        self::CF_MC_TIME => 86400
    ];

    /**
     * @param      $data
     * @param bool $ignore
     * @return bool|int|string
     */
    public static function addBook($data, $ignore = true)
    {
        $ret = false;
        if (!empty($data)) {
            $ret = parent::addData($data, $ignore);
            if (!empty($ret)) {
                $data['BookId'] = $ret;
                $sdata = [
                    'Type' => 0,
                    'BookDesc' => '',
                    'Words' => 0,
                    'ViewCount' => 0,
                    'LikeCount' => 0,
                    'FavoriteCount' => 0,
                    'Status' => 0,
                    'IsFinish' => 0
                ];
                $data = array_merge($data, $sdata);
                SBook::addSearch($data);
            }
        }

        return $ret;
    }

    /**
     * 更新
     *
     * @param $id
     * @param $data
     * @return bool
     */
    public static function updateBook($id, $data)
    {
        if ($id > 0 && !empty($data)) {
            $ret = parent::updateData($id, $data);
            if (!empty($ret)) {
                SBook::updateSearch($id, $data);
            }

            return $ret;
        }

        return false;
    }

    /**
     * 根据id获取书本信息
     *
     * @param int|array $ids
     * @param array     $extra
     * @return array
     */
    public static function getBookByIds($ids, $extra = [])
    {
        if (empty($ids)) {
            return [];
        }
        $ret = parent::getDataById($ids);
        if (!empty($extra[self::EXTRA_COVER]) && !empty($ret)) {
            if (is_array(current($ret))) {
                foreach ($ret as $k => $v) {
                    $ret[$k]['Cover'] = cover($v['BookId'], $v['Cover']);
                }
            } else {
                $ret['Cover'] = cover($ret['BookId'], $ret['Cover']);
            }
        }

        return $ret;
    }

    /**
     * @param array  $where
     * @param string $fields
     * @param int    $offset
     * @param int    $limit
     * @param string $order
     * @return array
     */
    public static function getBooks($where = [], $fields = '', $offset = 0, $limit = 0, $order = '')
    {
        $extra = [
            'fields' => $fields,
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
    public static function delBook($id)
    {
        if ($id > 0) {
            return parent::delData($id);
        }

        return false;
    }

}
