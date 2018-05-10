<?php

namespace Model\Book;

use Bare\DB;
use Bare\M\Model;

class Content extends Model
{
    /**
     * 配置文件
     *
     * @var array
     */
    protected static $_conf = [
        // 必选, 数据库连接(来自DBConfig配置), w: 写, r: 读
        'db' => [
            'w' => DB::DB_29SHU_CONTENT_W,
            'r' => DB::DB_29SHU_CONTENT_R
        ],
        // 必选, 数据表名
        'table' => 'BookContent_',
        // 必选, 字段信息
        'fields' => [
            'ContentId' => self::VAR_TYPE_KEY,
            'ChapterId' => self::VAR_TYPE_INT,
            'Content' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        self::CF_MC => '',
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => '',
        // 可选, 超时时间, 默认不过期
        self::CF_MC_TIME => 86400,
    ];

    /**
     * @param int $bookid
     * @param     $data
     * @return bool|int|string
     */
    public static function addContent(int $bookid, $data)
    {
        if (!empty($data) || $bookid > 0) {
            return parent::addData($data, false, table($bookid));
        }

        return false;
    }

    /**
     * @param int $bookid
     * @param     $id
     * @param     $data
     * @return bool
     */
    public static function updateContent(int $bookid, $id, $data)
    {
        if ($id > 0 && !empty($data) && $bookid > 0) {
            return parent::updateData($id, $data, table($bookid));
        }

        return false;
    }

    /**
     * @param int $bookid
     * @param     $id
     * @return array
     */
    public static function getContentById(int $bookid, $id)
    {
        if ($id > 0 && $bookid > 0) {
            return parent::getDataById($id, [], table($bookid));
        }

        return [];
    }

    /**
     * @param $bookid
     * @param $chapterid
     * @return array
     */
    public static function getContentByChapterId($bookid, $chapterid)
    {
        $where = [
            'ChapterId' => $chapterid,
        ];
        $extra = [
            'fields' => '*',
            'get_count' => 0,
        ];
        $data = parent::getDataByFields($where, $extra, table($bookid));

        return !empty($data['data']) ? current($data['data']) : [];
    }

    /**
     * @param int $bookid
     * @param     $id
     * @return bool
     */
    public static function delContent($bookid, $id)
    {
        if ($id > 0 && $bookid > 0) {
            return parent::delData($id, table($bookid));
        }

        return false;
    }

}
