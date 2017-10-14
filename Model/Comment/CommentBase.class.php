<?php

/**
 * 评论基础类
 *
 * @package    modules
 * @subpackage Comment
 * @author     suning <snsnsky@gmail.com>
 */

namespace Model\Comment;

use Bare\DB;
use Bare\CommonModel;
use Common\DataType;

abstract class CommentBase extends CommonModel
{
    const LOG_FAIL_PATH = 'Comment/CommentBase/Fail';
    const LOG_SUCC_PATH = 'Comment/CommentBase/Succ';

    // MC缓存KEY: 对象的评论列表
    const MC_KEY_ITEM_CMTS = 'Comment_Type:%d_Item:%d';
    // MC缓存KEY: 单个评论详情
    const MC_KEY_COMMENT = 'Comment_%d';

    const ENTIRE_TABLE_NAME = 'Comment';
    const SHARD_TABLE_NAME = 'Comment_%02x';

    // 评论类型: 文章
    const TYPE_ARTICLE = 1;

    protected static $_comment_types = [
        self::TYPE_ARTICLE => 'Article'
    ];
    // 状态字段值: 已删除
    const REAL_STATUS_DELETED = 0;
    // 状态字段值: 正常
    const REAL_STATUS_PENDING = 1;
    // 状态字段值: 垃圾评论
    const REAL_STATUS_SPAM = 2;

    protected static $_real_status_map = [
        self::REAL_STATUS_DELETED => '已删除',
        self::REAL_STATUS_PENDING => '正常',
        self::REAL_STATUS_SPAM => '垃圾',
    ];
    //  计数字段: 子评论数
    const COUNT_SUBCOMMENT = 1;

    protected static $_count_fields = [
        self::COUNT_SUBCOMMENT => 'SubCommentCnt',
    ];


    protected $_app = null;
    protected $_mc = null;

    protected $_type = null;

    protected $_itemid = 0;
    protected $_item = null;

    protected $_commentCount = 0;

    protected static $_comment_field_schema = [
        'CommentId' => self::VAR_TYPE_KEY,
        'ItemId' => self::VAR_TYPE_INT,
        'UserId' => self::VAR_TYPE_INT,
        'ReplyId' => self::VAR_TYPE_INT,
        'Type' => self::VAR_TYPE_INT,
        'AtUserId' => self::VAR_TYPE_INT,
        'Content' => self::VAR_TYPE_STRING,
        'Platform' => self::VAR_TYPE_INT,
        'Status' => self::VAR_TYPE_INT,
        'SubCommentCnt' => self::VAR_TYPE_STRING,
        'ExtraInfo' => self::VAR_TYPE_ARRAY,
        'CreateTime' => self::VAR_TYPE_STRING,
    ];

    /**
     * 实例化评论对象
     *
     * @param integer $type   评论的对象类型, 请参考 self::TYPE_* 系列常量
     * @param integer $itemid 评论对象ID
     * @throws \Exception
     */
    protected function __construct($type, $itemid)
    {
        global $app;

        $this->_app = $app;
        $this->_mc = DB::memcache(DB::MEMCACHE_DEFAULT);

        $this->_type = $type;
        $this->_itemid = $itemid;
        $this->_item = $this->getItem();
    }

    /**
     * 获取被评论的目标对象
     *
     * @param array $extra 额外参数
     * @return mixed
     */
    abstract public function getItem($extra = []);

    /**
     * 更新评论主体的评论计数字段
     *
     * @param integer $count  新的计数, [count | "[+|-]count"]
     * @param integer $itemid 评论主体ID, >0 时, 表示强制指定, 否则应该使用 $this->_itemid
     * @return mixed
     */
    abstract public function updateItemCommentCount($count, $itemid = 0);

    /**
     * 获取评论实例对象
     *
     * @param integer $type   对象类型, 请参考 self::TYPE_* 系列常量
     * @param integer $itemid 对象ID
     * @return CommentBase
     * @throws \Exception
     */
    public static function getEntity($type, $itemid)
    {
        if (!self::isValidType($type) || !is_numeric($itemid) || ($itemid = (int)$itemid) <= 0) {
            throw new \Exception('参数错误: ' . strval($type) . ', ', $itemid);
        }

        static $_static = [];

        $key = "{$type}_{$itemid}";
        if (!isset($_static[$key])) {
            $name = self::$_comment_types[$type];
            $class = "\\Comment\\{$name}Comment";

            $_static[$key] = new $class($type, $itemid);
        }

        return $_static[$key];
    }

    /**
     * 发表一条评论
     *
     * @param array $info           评论数据, ['UserId'=>'用户ID','Content'=>'内容','Platform'=>'来源']
     *                              Platform => 0:Web 1:Android 2:iPhone 3:Wap
     * @return array
     */
    public function post($info)
    {
        static $required_fields = [
            'UserId' => true,
            'Content' => true,
            'Platform' => true,
        ];

        $data = self::_sanitizeFields((array)$info);

        $diff = array_diff_key($required_fields, $data);
        // 信息不完整
        if (count($diff) > 0) {
            return back(201, '信息不完整');
        }
        // 本期暂不做评论的子评论
        //        $reply_id = isset($data['ReplyId']) ? $data['ReplyId'] : null;
        //        $reply_id = (is_numeric($reply_id) && ($reply_id = (int)$reply_id) > 0) ? $reply_id : 0;
        $reply_id = 0;

        $cmt_uid = (int)$data['UserId'];
        $at_uid = 0;

        if ($reply_id > 0) {
            $comment = self::getCommentsByIds($reply_id);

            if ($comment === null || $comment['Status'] == '0') {
                return back(202, '对象不存在');
            }

            $at_uid = (int)$comment['UserId'];
            $cmt_item_id = (int)$comment['ItemId'];

            if ($cmt_item_id !== $this->_itemid) {
                return back(203, '不匹配');
            }
        }

        // 评论扩展字段
        $extra_info = isset($data['ExtraInfo']) ? $data['ExtraInfo'] : null;
        if ($extra_info !== null) {
            $extra_info = is_array($extra_info) ? serialize($extra_info) : ((is_string($extra_info) || is_numeric($extra_info)) ? "{$extra_info}" : null);

            $data['ExtraInfo'] = strlen($extra_info) > 0 ? $extra_info : null;
        }

        $item = $this->getItem();
        $item_uid = (int)$item['UserId'];

        $now = date('Y-m-d H:i:s');

        $data = array_merge($data, [
            'ItemId' => $this->_itemid,
            'ReplyId' => $reply_id,
            'AtUserId' => $at_uid,
            'Type' => $this->_type,
            'Status' => self::REAL_STATUS_PENDING,
            'CreateTime' => $now,
        ]);

        $id = 0;
        $ins_result = $commit_result = false;

        $tables = [self::ENTIRE_TABLE_NAME, self::shardTable($this->_itemid)];

        $db = DB::pdo(DB::DB_COMMENT_W, [
            'errorMode' => \PDO::ERRMODE_EXCEPTION,
        ]);

        try {
            $db->beginTransaction();

            foreach ($tables as $table) {
                $ins_result = $db->insert($table, $data);
                if ($ins_result === false) {
                    break;
                }
                if (!isset($data['CommentId'])) {
                    $data['CommentId'] = $id = (int)$db->lastInsertId();
                }
            }
            if ($ins_result === false) {
                $db->rollBack();
            } else {
                $commit_result = $db->commit();
            }
        } catch (\Exception $e) {
            logs(array_merge([
                '_time' => date("Y-m-d H:i:s"),
                '_from' => __CLASS__ . '->' . __METHOD__,
                '_exception' => $e->getMessage()
            ], $data), self::LOG_FAIL_PATH);
        }

        if ($commit_result) {
            //            if ($reply_id > 0) {
            //                // 此条评论若为子评论, 应更新父评论的子评论数
            //                $this->updateCount($reply_id, [self::COUNT_SUBCOMMENT => '+1']);
            //            } else {
            //                //更新评论对象的评论总数
            //
            //            }
            $this->updateItemCommentCount('+1');
            // 回复他人评论时, 向目标用户发送消息
            if ($at_uid > 0) {
                if ($cmt_uid !== $at_uid) {
                    // TODO 发送通知给 $at_uid
                }
            } // 评论他人作品时, 向作者发送消息
            else {
                if ($cmt_uid !== $item_uid) {
                    // TODO 发送通知给 $item_uid
                }
            }

            $mc = $this->_mc;
            // 清除MC缓存: 对象的评论列表
            $mc->delete(sprintf(self::MC_KEY_ITEM_CMTS, $this->_type, $this->_itemid));

            $rets = back(200);
            $rets['id'] = $id;
            $rets['at_uid'] = $at_uid;

            return $rets;
        }

        return back(299);
    }

    /**
     * 获取评论列表
     *
     * @param array $extra 额外参数
     *                     EXTRA_SORT_ORDER  - 指定排序方式, self::SORT_ORDER_DESC/self::SORT_ORDER_ASC
     *                     EXTRA_OFFSET      - 此次获取的结果集偏移
     *                     EXTRA_LIMIT       - 此次获取的结果集大小
     *
     * @return array
     */
    public function getList($extra = [])
    {
        $args = self::_parseExtras($extra);

        $raw_sort_order = isset($extra[self::EXTRA_SORT_ORDER]) ? $extra[self::EXTRA_SORT_ORDER] : null;
        $sort_order = $raw_sort_order === self::SORT_ORDER_DESC ? 'DESC' : 'ASC';

        $cmt_ids = self::loadComments($this->_itemid, $this->_type, $args);
        $this->_commentCount = $total = count($cmt_ids);

        $list = [];

        if ($total > 0) {
            $offset = $args[self::EXTRA_OFFSET];
            if ($offset >= 0) {
                $limit = $args[self::EXTRA_LIMIT];
                $limit = $limit > $total ? $total : $limit;

                if ($sort_order === 'ASC') {
                    $cmt_ids = array_slice($cmt_ids, $offset, $limit, true);
                } else {
                    if (($total - $offset) < $limit) {
                        $start = 0;
                        $limit = $total - $offset;
                    } else {
                        $start = $total - $offset - $limit;
                    }
                    $cmt_ids = array_slice($cmt_ids, $start, $limit, true);
                    krsort($cmt_ids);
                }
            }

            if (count($cmt_ids) > 0) {
                $all_cmt_ids = [];

                foreach ($cmt_ids as $cmt_id => $sub_cmt_ids) {
                    $all_cmt_ids[$cmt_id] = $cmt_id;

                    if (count($sub_cmt_ids) > 0) {
                        $all_cmt_ids += $sub_cmt_ids;
                    }
                }

                $list = self::getCommentsByIds($all_cmt_ids);
            }
        }

        return ['total' => $total, 'data' => $list];
    }

    /**
     * 获取评论总数
     *
     * @param bool $reload 是否重新统计
     * @param bool $from_w 是否从写库取数据
     *
     * @return integer
     */
    public function getCommentCount($reload = false, $from_w = false)
    {
        if ($this->_commentCount == 0 || $reload) {
            $cmt_ids = self::loadComments($this->_itemid, $this->_type, [
                self::EXTRA_REFRESH => $reload,
                self::EXTRA_FROM_W => $from_w,
            ]);

            $this->_commentCount = count($cmt_ids);
        }

        return $this->_commentCount;
    }


    /**
     * 获取指定评论的子评论数
     *
     * @param integer $id 评论ID
     * @return integer
     */
    public function getSubCommentCount($id)
    {
        $cmt = self::getCommentsByIds($id);

        if ($cmt) {
            return (int)$cmt['SubCommentCnt'];
        }

        return 0;
    }

    /**
     * 更新评论相关计数字段
     *
     * @param integer $id        评论ID
     * @param array   $info      要更新的计数字段数据
     *                           [self::COUNT_SUBCOMMENT => [count | "[+|-]count"], ..]
     * @return array
     */
    public function updateCount($id, $info)
    {
        return static::_updateCount($this->_itemid, $id, $info);
    }

    /**
     * 更新评论相关字段
     *
     * @param integer $id   评论ID
     * @param array   $info 要更新的字段数据
     *
     * @return array
     */
    public function updateExtData($id, $info)
    {
        return static::_updateExtData($this->_itemid, $id, $info);
    }

    /**
     * 更新评论状态
     *
     * @param array   $ids    评论ID数组(只能是item下评论ID)
     * @param integer $status 新状态
     * @return bool
     */
    public function updateStatus($ids, $status)
    {
        $id_map = [];
        $type = $this->_type;
        $itemid = $this->_itemid;

        $item_cmt_list_key = sprintf(self::MC_KEY_ITEM_CMTS, $type, $itemid);

        foreach ((array)$ids as $id) {
            if (is_numeric($id) && ($id = (int)$id) > 0) {
                $id_map[$id] = $id;
            }
        }

        if (empty($id_map)) {
            return back(201, '参数错误');
        }

        $shardTable = self::shardTable($itemid);

        $id_cdn = implode(',', array_keys($id_map));
        $sql = "SELECT CommentId,ReplyId,UserId,Status FROM `{$shardTable}` WHERE ItemId = {$itemid} AND CommentId IN ({$id_cdn})";

        $db = DB::pdo(DB::DB_COMMENT_W, [
            'errorMode' => \PDO::ERRMODE_EXCEPTION,
        ]);

        $stmt = $db->query($sql);
        if ($stmt) {
            $result = $stmt->fetchAll();

            if (!is_array($result)) {
                return back(202, '操作失败');
            }

            if (empty($result)) {
                return back(200);
            }

            $mc = $this->_mc;

            // 清除MC缓存: 该对象的评论列表
            $mc->delete($item_cmt_list_key);

            $shards = [$shardTable, self::ENTIRE_TABLE_NAME];

            foreach ($result as $row) {
                $id = (int)$row['CommentId'];
                $origin_status = (int)$row['Status'];

                if ($status === $origin_status) {
                    continue;
                }

                if ($origin_status === self::REAL_STATUS_PENDING) {
                    $count = '-1';
                } else {
                    $count = ($status === self::REAL_STATUS_PENDING) ? '+1' : 0;
                }

                if ($count === 0) {
                    continue;
                }

                $commit_result = $upd_result = false;

                try {
                    $db->beginTransaction();
                    foreach ($shards as $table) {
                        $upd_sql = "UPDATE `{$table}` SET Status = {$status} WHERE CommentId = {$id}";
                        $upd_result = $db->exec($upd_sql);
                        if ($upd_result === false) {
                            break;
                        }
                    }
                    if ($upd_result === false) {
                        $db->rollBack();
                    } else {
                        $commit_result = $db->commit();
                    }
                } catch (\Exception $e) {
                    logs([
                        '_time' => date("Y-m-d H:i:s"),
                        '_from' => __CLASS__ . '->' . __METHOD__,
                        '_exception' => $e->getMessage(),
                        'itemid' => $itemid,
                        'ids' => $ids,
                        'status' => $status
                    ], self::LOG_FAIL_PATH);
                }

                if ($commit_result) {
                    // 若为子评论，更新主评论计数
                    if (($replyId = (int)$row['ReplyId']) > 0) {
                        $this->updateCount($replyId, [self::COUNT_SUBCOMMENT => $count]);
                    } else {
                        $count = $this->getCommentCount(true, true);
                        // 更新评论对象的评论计数
                        $this->updateItemCommentCount($count);
                    }
                }

                // 清除MC缓存: 评论详情
                $mc->delete(sprintf(self::MC_KEY_COMMENT, $id));
            }
        }

        return back(200);
    }

    /**
     * 加载当前对象的评论列表
     *
     * @param integer $itemid 评论对象ID
     * @param integer $type   对象类型
     * @param array   $extra  额外参数
     *                        EXTRA_REFRESH   - 为true时表示不使用缓存,直接从数据库中取值, 默认为false
     *                        EXTRA_FROM_W    - 是否从写库取数据
     *                        EXTRA_OFFSET    - 此次获取的结果集偏移
     *                        EXTRA_LIMIT     - 此次获取的结果集大小
     * @return array
     */
    public static function loadComments($itemid, $type, $extra = [])
    {
        $args = self::_parseExtras($extra);

        $mc_key = sprintf(self::MC_KEY_ITEM_CMTS, $type, $itemid);

        $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
        $cache = $args[self::EXTRA_REFRESH] ? null : $mc->get($mc_key);

        // 如果不为空而且不是数组，尝试解压
        $cache_status = 0;
        if (!empty($cache) && !is_array($cache) && ($cache_status = (int)$cache) !== -1) {
            $cache = unserialize($cache);
        }
        is_array($cache) || $cache = [];

        if (empty($cache) && $cache_status !== -1) {
            $shardTable = self::shardTable($itemid);

            $sql = "SELECT CommentId,ReplyId FROM `{$shardTable}` WHERE ItemId = {$itemid} AND Type = {$type} AND Status = 1 ORDER BY CommentId ASC";
            $db = DB::pdo($args[self::EXTRA_FROM_W] ? DB::DB_COMMENT_W : DB::DB_COMMENT_R);
            $stmt = $db->query($sql);
            if ($stmt) {
                $result = $stmt->fetchAll();
                if (is_array($result)) {
                    foreach ($result as $row) {
                        $cid = (int)$row['CommentId'];
                        $cache[$cid] = [];
                    }

                    $mc->set($mc_key, empty($cache) ? -1 : serialize($cache), self::ONE_DAY);
                }
            }
        }

        return $cache;
    }

    /**
     * 获取指定ID的评论详情
     *
     * @param array|int $ids   评论ID列表
     * @param array     $extra 评论扩展参数
     * @return array
     */
    public static function getCommentsByIds($ids, $extra = [])
    {
        $args = self::_parseExtras($extra);

        $id_map = [];
        foreach ((array)$ids as $id) {
            if (is_numeric($id) && ($id = (int)$id) > 0) {
                $id_map[$id] = sprintf(self::MC_KEY_COMMENT, $id);
            }
        }

        if (empty($id_map)) {
            return [];
        }

        $mc = DB::memcache();
        $cache = $args[self::EXTRA_REFRESH] ? [] : $mc->get($id_map);
        $rets = $tmp_cache = $nocache_ids = [];

        foreach ($id_map as $id => $mc_key) {
            $item = isset($cache[$mc_key]) ? $cache[$mc_key] : null;

            if (is_array($item) && isset($item['CommentId'])) {
                $tmp_cache[$id] = $item;
            } else {
                $nocache_ids[$id] = $id;
            }
        }

        if (count($nocache_ids) > 0) {
            $table = self::ENTIRE_TABLE_NAME;
            $ids_cdn = implode(',', $nocache_ids);
            $sql = "SELECT * FROM `{$table}` WHERE CommentId IN ({$ids_cdn})";

            $db = DB::pdo($args[self::EXTRA_FROM_W] ? DB::DB_COMMENT_W : DB::DB_COMMENT_R);
            $stmt = $db->query($sql);
            if ($stmt) {
                $result = $stmt->fetchAll();
                if (is_array($result)) {
                    foreach ($result as $row) {
                        $id = (int)$row['CommentId'];

                        $tmp_cache[$id] = $row;

                        $mc->set($id_map[$id], $row, self::ONE_DAY);
                    }
                }
            }
        }

        foreach ($id_map as $id => $mc_key) {
            if (isset($tmp_cache[$id])) {
                $rets[$id] = $tmp_cache[$id];
            }
        }

        if (!is_array($ids)) {
            return (empty($rets) ? null : current($rets));
        }

        return $rets;
    }

    /**
     * 批量删除用户的评论数据
     *
     * @param integer $uid   用户ID
     * @param integer $range 单次处理删除的评论数
     * @return bool
     */
    public static function batchDeleteByUserId($uid, $range = 200)
    {
        $range = (is_numeric($range) && ($range = (int)$range) >= 10 && $range <= 1000) ? $range : 200;

        $sql = "SELECT `CommentId`,`Type`,`ItemId` FROM Comment WHERE UserId = {$uid} AND Status = 1 ORDER BY `ItemId` ASC, `Type` ASC LIMIT {$range}";

        $db = DB::pdo(DB::DB_COMMENT_W);

        do {
            $loop = false;

            $stmt = $db->query($sql);
            if ($stmt) {
                $result = $stmt->fetchAll();
                if (is_array($result)) {
                    $map = [];

                    foreach ($result as $row) {
                        $type = (int)$row['Type'];
                        $item_id = (int)$row['ItemId'];
                        $cmt_id = (int)$row['CommentId'];

                        if (!isset($map[$type])) {
                            $map[$type] = [];
                        }
                        if (!isset($map[$type][$item_id])) {
                            $map[$type][$item_id] = [];
                        }

                        $map[$type][$item_id][$cmt_id] = $row;
                    }

                    foreach ($map as $type => $item_cmt_map) {
                        foreach ($item_cmt_map as $item_id => $cmt_list) {
                            $proxy = self::getEntity($type, $item_id);

                            $proxy->updateStatus(array_keys($cmt_list), CommentBase::REAL_STATUS_DELETED);
                        }
                    }

                    $loop = count($result) >= $range ? true : false;
                }
            }
        } while ($loop);

        return true;
    }

    /**
     * 更新指定评论的状态
     *
     * @param array   $ids    评论ID数组
     * @param integer $status 新状态
     * @return array
     */
    public static function updateStatusByCommentIds(array $ids, $status)
    {
        $id_map = [];

        foreach ((array)$ids as $id) {
            if (is_numeric($id) && ($id = (int)$id) > 0) {
                $id_map[$id] = $id;
            }
        }

        if (empty($id_map) || !is_numeric($status) || !isset(self::$_real_status_map[$status])) {
            return back(201, '参数错误');
        }

        $entireTable = self::ENTIRE_TABLE_NAME;

        $id_cdn = implode(',', array_keys($id_map));
        $sql = "SELECT CommentId,ItemId,Type,ReplyId,UserId,Status FROM `{$entireTable}` WHERE CommentId IN ({$id_cdn}) AND `Status` != {$status}";

        $db = DB::pdo(DB::DB_COMMENT_W, [
            'errorMode' => \PDO::ERRMODE_EXCEPTION,
        ]);

        $stmt = $db->query($sql);
        if (!$stmt) {
            return back(202, '操作失败');
        }

        $result = $stmt->fetchAll();

        if (!is_array($result)) {
            return back(203, '操作失败');
        }

        if (empty($result)) {
            return back(200);
        }

        $mc = DB::memcache(DB::MEMCACHE_DEFAULT);

        $map = [];

        foreach ($result as $row) {
            $itemid = (int)$row['ItemId'];
            $cmtId = (int)$row['CommentId'];
            $type = (int)$row['Type'];

            $origin_status = (int)$row['Status'];

            if ($origin_status === self::REAL_STATUS_PENDING) {
                $count = '-1';
            } else {
                $count = ($status === self::REAL_STATUS_PENDING) ? '+1' : 0;
            }

            if ($count === 0) {
                continue;
            }

            $shardTable = self::shardTable($itemid);

            if (!isset($map[$type])) {
                $map[$type] = [];
            }
            if (!isset($map[$type][$shardTable])) {
                $map[$type][$shardTable] = [];
            }
            if (!isset($map[$type][$shardTable][$itemid])) {
                $map[$type][$shardTable][$itemid] = [];
            }

            $row['_UpdateCount'] = $count;

            $map[$type][$shardTable][$itemid][$cmtId] = $row;
        }

        foreach ($map as $type => $table_item_map) {
            foreach ($table_item_map as $shardTable => $item_cmts_map) {
                $shards = [$shardTable, $entireTable];

                foreach ($item_cmts_map as $itemid => $cmt_lists) {
                    if (empty($cmt_lists)) {
                        continue;
                    }

                    $cmt_ids = array_keys($cmt_lists);
                    $ids_cdn = implode(',', $cmt_ids);

                    $commit_result = $upd_result = false;

                    try {
                        $db->beginTransaction();

                        foreach ($shards as $table) {
                            $upd_sql = "UPDATE `{$table}` SET Status = {$status} WHERE CommentId IN ({$ids_cdn})";
                            $upd_result = $db->exec($upd_sql);

                            if ($upd_result === false) {
                                break;
                            }
                        }

                        if ($upd_result === false) {
                            $db->rollBack();
                        } else {
                            $commit_result = $db->commit();
                        }
                    } catch (\Exception $e) {
                        logs([
                            '_time' => date("Y-m-d H:i:s"),
                            '_from' => __CLASS__ . '->' . __METHOD__,
                            '_exception' => $e->getMessage(),
                            'ids' => $ids,
                            'status' => $status
                        ], self::LOG_FAIL_PATH);
                    }

                    if ($commit_result) {
                        $reply_cache = [];
                        $cmt_update_count = 0;

                        foreach ($cmt_lists as $cmt_id => $cmt) {
                            if (($replyId = (int)$cmt['ReplyId']) > 0) {
                                if (!isset($reply_cache[$replyId])) {
                                    $reply_cache[$replyId] = 0;
                                }

                                $reply_cache[$replyId] += $cmt['_UpdateCount'];
                            } else {
                                $cmt_update_count += $cmt['_UpdateCount'];
                            }
                            // 清除MC缓存: 评论详情
                            $mc->delete(sprintf(self::MC_KEY_COMMENT, $cmt_id));
                        }

                        // 若为子评论，更新主评论的子评论计数
                        foreach ($reply_cache as $replyId => $count) {
                            if ($count !== 0) {
                                static::_updateCount($itemid, $replyId, [
                                    self::COUNT_SUBCOMMENT => $count,
                                ]);
                            }
                        }
                        // 更新对象的评论数
                        if ($cmt_update_count !== 0) {
                            $sub = self::getEntity($type, $itemid);
                            $sub->updateItemCommentCount($cmt_update_count, $itemid);
                        }
                    }
                    // 清除MC缓存: 该对象的评论列表
                    $mc->delete(sprintf(self::MC_KEY_ITEM_CMTS, $type, $itemid));
                }
            }
        }

        return back(200);
    }

    /**
     * 更新评论相关数据
     *
     * @param integer $itemid 评论对象ID
     * @param integer $id     评论ID
     * @param array   $info   要更新数据
     *
     * @return array
     */
    protected static function _updateExtData($itemid, $id, $info)
    {
        $data = self::getCommentsByIds($id);
        if (empty($data)) {
            return back(201, '对象不存在');
        }
        $_info = [];
        foreach ($info as $k => $v) {
            if (isset(self::$_comment_field_schema[$k])) {
                $_info[] = '`' . $k . '` = ' . $v . ' ';
            }
        }
        if (count($_info) < 1) {
            return back(202, '参数错误');
        }
        $newdata = implode(',', $_info);
        $sql_tpl = "UPDATE `%s` SET {$newdata} WHERE CommentId = {$id}";

        $upd_result = $commit_result = false;

        $db = DB::pdo(DB::DB_COMMENT_W, [
            'errorMode' => \PDO::ERRMODE_EXCEPTION,
        ]);

        try {
            $tables = [self::ENTIRE_TABLE_NAME, self::shardTable($itemid)];
            $db->beginTransaction();
            foreach ($tables as $table) {
                $sql = sprintf($sql_tpl, $table);
                $upd_result = $db->exec($sql);
                if ($upd_result === false) {
                    break;
                }
            }
            if ($upd_result === false) {
                $db->rollBack();
            } else {
                $commit_result = $db->commit();
            }
        } catch (\Exception $e) {
            logs([
                '_time' => date("Y-m-d H:i:s"),
                '_from' => __CLASS__ . '->' . __METHOD__,
                '_exception' => $e->getMessage(),
                'itemid' => $itemid,
                'id' => $id,
                'info' => $info
            ], self::LOG_FAIL_PATH);
        }

        if ($commit_result) {
            $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
            // 清除MC缓存: 评论详情
            $mc->delete(sprintf(self::MC_KEY_COMMENT, $id));

            return back(200);
        }

        return back(299);
    }

    /**
     * 更新评论相关计数字段
     *
     * @param integer $itemid       评论对象ID
     * @param integer $id           评论ID
     * @param array   $info         要更新的计数字段数据
     *                              [self::COUNT_SUBCOMMENT => [count | "[+|-]count"], ..]
     * @return array
     */
    protected static function _updateCount($itemid, $id, $info)
    {
        $data = self::getCommentsByIds($id);
        if (empty($data)) {
            return back(201, '对象不存在');
        }

        $_info = self::_parseCount($info, self::$_count_fields);
        if (empty($_info)) {
            return back(201, '数据无效');
        }

        $newdata = implode(',', $_info);

        $sql_tpl = "UPDATE `%s` SET {$newdata} WHERE CommentId = {$id}";

        $upd_result = $commit_result = false;

        $db = DB::pdo(DB::DB_COMMENT_W, [
            'errorMode' => \PDO::ERRMODE_EXCEPTION,
        ]);

        try {
            $tables = [self::ENTIRE_TABLE_NAME, self::shardTable($itemid)];
            $db->beginTransaction();
            foreach ($tables as $table) {
                $sql = sprintf($sql_tpl, $table);
                $upd_result = $db->exec($sql);
                if ($upd_result === false) {
                    break;
                }
            }
            if ($upd_result === false) {
                $db->rollBack();
            } else {
                $commit_result = $db->commit();
            }
        } catch (\Exception $e) {
            logs([
                '_time' => date("Y-m-d H:i:s"),
                '_from' => __CLASS__ . '->' . __METHOD__,
                '_exception' => $e->getMessage(),
                'itemid' => $itemid,
                'id' => $id,
                'info' => $info
            ], self::LOG_FAIL_PATH);
        }
        if ($commit_result) {
            $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
            // 清除MC缓存: 评论详情
            $mc->delete(sprintf(self::MC_KEY_COMMENT, $id));

            return back(200);
        }

        return back(299);
    }

    /**
     * 设置为优质评论
     *
     * @param $itemid
     * @param $id
     * @param $num
     * @return bool
     */
    public static function setGoodComment($itemid, $id, $num = 1)
    {
        $num = intval($num);
        $cmtinfo = self::getCommentsByIds($id);
        if (!empty($cmtinfo)) {
            static::_updateExtData($itemid, $id, ['IsGood' => $num]);

            return true;
        }

        return false;
    }

    /**
     * 取消优质评论
     *
     * @param $itemid
     * @param $id
     * @return bool
     */
    public static function cancelGoodComment($itemid, $id)
    {
        $cmtinfo = self::getCommentsByIds($id);
        if (!empty($cmtinfo)) {
            static::_updateExtData($itemid, $id, ['IsGood' => 0]);

            return true;
        }

        return false;
    }

    /**
     * 检测是否为合法的对象类型
     *
     * @param integer $type 对象类型, 请参考self::TYPE_*常量
     * @return bool
     */
    public static function isValidType($type)
    {
        return (is_numeric($type) && isset(self::$_comment_types[$type]));
    }

    /**
     * 获取评论类型列表
     *
     * @return array
     */
    public static function getCommentTypes()
    {
        return self::$_comment_types;
    }

    /**
     * 获取评论状态列表
     *
     * @return array
     */
    public static function getRealStatusMap()
    {
        return self::$_real_status_map;
    }

    /**
     * 获取评论计数字段列表
     *
     * @return array
     */
    public static function getCountFields()
    {
        return self::$_count_fields;
    }

    /**
     * 计算数据表名
     *
     * @param integer $id 对象ID
     * @return string
     */
    public static function shardTable($id)
    {
        return sprintf(self::SHARD_TABLE_NAME, $id % 256);
    }

    /**
     * 检验字段, 删除不合法字段
     *
     * @param array $info 传入字段
     * @return array
     */
    protected static function _sanitizeFields($info)
    {
        $field_schema = self::$_comment_field_schema;
        $pured = self::checkFields((array)$info, $field_schema);

        return $pured;
    }
}
