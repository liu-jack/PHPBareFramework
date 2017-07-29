<?php

namespace Model\Book;

use Bare\DB;
use Bare\Model;

class Column extends Model
{
    const MC_BOOK_COLUMN_LIST = 'BOOK_COLUMN:%d:%d'; // BookId, FromId
    const MC_BOOK_PREV_NEXT = 'BOOK_PREV_NEXT:%d:%d'; // FromId,BookId
    const MC_TIME = 86400;
    // 阅读/推荐redis记录
    const RD_DB_INDEX = 9;
    const RD_READ_RECORD = 'READ_RECORD:%d:%d:%d'; // UserId,FromId,BookId
    const RD_BOOK_RECOMMEND = 'BOOK_RECOMMEND:%d:%d'; // UserId,BookId
    const RD_TIME = 31536000;

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
        'table' => 'BookColumn_',
        // 必选, 字段信息
        'fields' => [
            'ChapterId' => self::VAR_TYPE_KEY,
            'BookId' => self::VAR_TYPE_INT,
            'ChapterName' => self::VAR_TYPE_STRING,
            'FromId' => self::VAR_TYPE_INT,
            'Url' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        'mc' => '',
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        'mckey' => '',
        // 可选, 超时时间, 默认不过期
        'mctime' => 86400
    ];

    /**
     * @param int $bookid
     * @param $data
     * @return bool|int|string
     */
    public static function addColumn(int $bookid, $data)
    {
        if (!empty($data) || $bookid > 0) {
            $ret = parent::addData($data, false, table($bookid));
            if (!empty($ret)) {
                $mc = DB::memcache();
                $mkey = sprintf(self::MC_BOOK_COLUMN_LIST, $bookid, $data['FromId']);
                $mc->delete($mkey);
                $mkeypn = sprintf(self::MC_BOOK_PREV_NEXT, $data['FromId'], $bookid);
                $mc->delete($mkeypn);
            }
            return $ret;

        }
        return false;
    }

    /**
     * @param int $bookid
     * @param $id
     * @param $data
     * @return bool
     */
    public static function updateColumn(int $bookid, $id, $data)
    {
        if ($id > 0 && !empty($data) && $bookid > 0) {
            $ret = parent::updateData($id, $data, table($bookid));
            if (!empty($ret)) {
                $res = self::getColumnById($bookid, $id);
                if (!empty($res['FromId'])) {
                    $mc = DB::memcache();
                    $mkey = sprintf(self::MC_BOOK_COLUMN_LIST, $bookid, $res['FromId']);
                    $mc->delete($mkey);
                    $mkeypn = sprintf(self::MC_BOOK_PREV_NEXT, $data['FromId'], $bookid);
                    $mc->delete($mkeypn);
                }
            }
            return $ret;
        }
        return false;
    }

    /**
     * @param int $bookid
     * @param $id
     * @return array
     */
    public static function getColumnById(int $bookid, $id)
    {
        if ($id > 0 && $bookid > 0) {
            return parent::getDataById($id, [], table($bookid));
        }
        return [];
    }

    /**
     * @param $bookid
     * @param $fromid
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public static function getColumns($bookid, $fromid, $offset = 0, $limit = 0)
    {
        $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
        $mkey = sprintf(self::MC_BOOK_COLUMN_LIST, $bookid, $fromid);
        $list = $mc->get($mkey);
        if (empty($list)) {
            $where = [
                'BookId' => $bookid,
                'FromId' => $fromid,
            ];
            $extra = [
                'fields' => '`ChapterId`,`ChapterName`',
                'order' => 'ChapterId ASC',
                'offset' => $offset,
                'limit' => $limit,
            ];
            $list = parent::getDataByFields($where, $extra, table($bookid));
            if (!empty($list)) {
                $mc->set($mkey, $list, self::MC_TIME);
            }
        }

        return $list;
    }

    /**
     * @param $bookid
     * @param $fromid
     * @param $url
     * @return array
     */
    public static function getColumnByUrl($bookid, $fromid, $url)
    {
        $where = [
            'BookId' => $bookid,
            'FromId' => $fromid,
            'Url' => $url
        ];
        $extra = [
            'fields' => '*',
            'get_count' => 0,
        ];
        $data = parent::getDataByFields($where, $extra, table($bookid));
        return !empty($data['data']) ? current($data['data']) : [];
    }

    /**
     * @param $bookid
     * @param $fromid
     * @return int
     */
    public static function getColumnCount($bookid, $fromid)
    {
        $where = [
            'BookId' => $bookid,
            'FromId' => $fromid
        ];
        $extra = [
            'get_count' => 1,
            'get_result' => 0,
        ];
        $data = parent::getDataByFields($where, $extra, table($bookid));
        return $data['count'] ?? 0;
    }

    /**
     * @param int $bookid
     * @param $id
     * @return bool
     */
    public static function delColumn($bookid, $id)
    {
        if ($id > 0 && $bookid > 0) {
            $ret = parent::delData($id, table($bookid));
            if (!empty($ret)) {
                $res = self::getColumnById($bookid, $id);
                if (!empty($res['FromId'])) {
                    $mc = DB::memcache();
                    $mkey = sprintf(self::MC_BOOK_COLUMN_LIST, $bookid, $res['FromId']);
                    $mc->delete($mkey);
                    $mkeypn = sprintf(self::MC_BOOK_PREV_NEXT, $res['FromId'], $bookid);
                    $mc->delete($mkeypn);
                }
            }
            return $ret;
        }
        return false;
    }

    /**
     * 获取上下章
     * @param $fid
     * @param $bid
     * @param $cid
     * @return array
     */
    public static function getPrevNext($fid, $bid, $cid)
    {
        $mkeypn = sprintf(self::MC_BOOK_PREV_NEXT, $fid, $bid);
        $mc = DB::memcache();
        $column = $mc->get($mkeypn);
        if (empty($column)) {
            $columns = self::getColumns($bid, $fid);
            $columns = !empty($columns['data']) ? $columns['data'] : [];
            $num = 1;
            foreach ($columns as $v) {
                if (!empty($v['ChapterId'])) {
                    $column[$v['ChapterId']] = $num;
                    $num++;
                }
            }
            $mc->set($mkeypn, $column, self::MC_TIME);
        }
        $rcolumn = array_flip($column);
        $cur = !empty($column[$cid]) ? $column[$cid] : 1;
        $prev = !empty($rcolumn[$cur - 1]) ? $rcolumn[$cur - 1] : 0;
        $next = !empty($rcolumn[$cur + 1]) ? $rcolumn[$cur + 1] : 0;
        $count = max(1, count($column));
        $percent = sprintf("%.2f%%", ($cur / $count) * 100);
        return ['prev' => $prev, 'next' => $next, 'percent' => $percent];
    }

    /**
     * 获取用户阅读记录
     * @param $uid
     * @param $fid
     * @param $bid
     * @return mixed
     */
    public static function getReadRecord($uid, $fid, $bid)
    {
        $key = sprintf(self::RD_BOOK_RECOMMEND, $uid, $fid, $bid);
        $redis = DB::redis(DB::REDIS_DEFAULT_R, self::RD_DB_INDEX);
        $ret = $redis->get($key);
        return $ret;
    }

    /**
     * 设置用户阅读记录
     * @param $uid
     * @param $fid
     * @param $bid
     * @param $cid
     * @return mixed
     */
    public static function setReadRecord($uid, $fid, $bid, $cid)
    {
        $key = sprintf(self::RD_BOOK_RECOMMEND, $uid, $fid, $bid);
        $redis = DB::redis(DB::REDIS_DEFAULT_W, self::RD_DB_INDEX);
        return $redis->set($key, $cid, self::RD_TIME);
    }

    /**
     * 获取用户推荐记录
     * @param $uid
     * @param $bid
     * @return mixed
     */
    public static function getRecom($uid, $bid)
    {
        $key = sprintf(self::RD_BOOK_RECOMMEND, $uid, $bid);
        $redis = DB::redis(DB::REDIS_DEFAULT_R);
        $ret = $redis->get($key);
        return $ret;
    }

    /**
     * 设置用户推荐记录
     * @param $uid
     * @param $bid
     * @return mixed
     */
    public static function setRecom($uid, $bid)
    {
        $key = sprintf(self::RD_BOOK_RECOMMEND, $uid, $bid);
        $redis = DB::redis(DB::REDIS_DEFAULT_W);
        return $redis->set($key, 1, strtotime(date('Y-m-d 23:59:59')) - time());
    }
}
