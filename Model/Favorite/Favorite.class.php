<?php

/**
 * 收藏(订阅)基础类
 *
 */

namespace Model\Favorite;

use Bare\DB;

abstract class Favorite
{
    // 收藏类型
    const TYPE_BOOK = 1; // 书本
    protected static $_types = [
        self::TYPE_BOOK => 'book',
    ];
    //收藏类型
    protected static $_type;

    //日志路径
    const LOG_FAIL_PATH = 'Favorite/Fail';
    //MC KEY, 用户下收藏列表
    const MC_USER_ITEMS = 'F:Type_%d:User_%d';
    // MC KEY, 项目下用户列表
    const MC_ITEM_USERS = 'F:Type_%d:Item_%d';
    const MC_TIME = 86400;
    //表名/分表名
    const DB_TABLE_NAME = 'Favorite';
    const DB_SHARD_TABLE = 'Favorite_%02x';

    /**
     * 收藏类型配置
     *
     */
    abstract public static function setFavConf();

    /**
     * 更新用户收藏计数
     *
     * @param int $userid 用户ID
     * @param string $count 计数变化， +1/-1
     * @return bool
     */
    abstract public static function updateUserFavCount($userid, $count);

    /**
     * 更新项目收藏计数
     *
     * @param int $itemid 项目ID
     * @param string $count 计数变化， +1/-1
     * @return bool
     */
    abstract public static function updateItemFavCount($itemid, $count);

    /**
     * 添加收藏 (若已经收藏返回成功)
     *
     * @param integer $userid 用户ID
     * @param integer $itemid 项目ID
     * @return bool
     */
    public static function addFav($userid, $itemid)
    {
        static::setFavConf();
        if (static::isFavorite($userid, $itemid)) {
            return true;
        }
        $pdo = DB::pdo(DB::DB_FAVORITE_W);
        $data = [
            'UserId' => $userid,
            'ItemId' => $itemid,
            'ItemType' => static::$_type,
            'CreateTime' => date("Y-m-d H:i:s")
        ];
        $row_count = $pdo->insert(self::DB_TABLE_NAME, $data, ['ignore' => true]);
        $last_id = $pdo->lastInsertId();
        if ($row_count > 0 && $last_id > 0) {
            $pdo->insert(static::_shardTable($userid), array_merge(['FavoriteId' => $last_id], $data),
                ['ignore' => true]);
            // 更新收藏对象计数
            static::updateItemFavCount($itemid, '+1');
            // 更新用户计数
            static::updateUserFavCount($userid, '+1');
            $mc = DB::memcache();
            $mcdata = [];
            $mcdata[] = sprintf(self::MC_USER_ITEMS, static::$_type, $userid);
            $mcdata[] = sprintf(self::MC_ITEM_USERS, static::$_type, $itemid);
            $mc->delete($mcdata);
        } elseif ($row_count === false) {
            logs([
                'from' => __CLASS__ . '->' . __METHOD__,
                'ret' => $row_count,
                'UserId' => $userid,
                'ItemId' => $itemid,
                'ItemType' => static::$_type
            ], self::LOG_FAIL_PATH);
            return false;
        }
        return true;
    }

    /**
     * 删除收藏 (未收藏时返回成功)
     *
     * @param integer $userid 用户ID
     * @param integer $itemid 项目ID
     * @return bool
     */
    public static function removeFav($userid, $itemid)
    {
        static::setFavConf();
        if (static::isFavorite($userid, $itemid)) {
            $pdo = DB::pdo(DB::DB_FAVORITE_W);
            $where = [
                'UserId' => $userid,
                'ItemId' => $itemid,
                'ItemType' => static::$_type,
            ];
            $rowcount1 = $pdo->delete(self::DB_TABLE_NAME, $where);
            $rowcount2 = $pdo->delete(static::_shardTable($userid), $where);
            if ($rowcount1 > 0 && $rowcount2 > 0) {
                // 更新收藏对象计数
                static::updateItemFavCount($itemid, '-1');
                // 更新用户计数
                static::updateUserFavCount($userid, '-1');
                $mc = DB::memcache();
                $mcdata = [];
                $mcdata[] = sprintf(self::MC_USER_ITEMS, static::$_type, $userid);
                $mcdata[] = sprintf(self::MC_ITEM_USERS, static::$_type, $itemid);
                $mc->delete($mcdata);
            }
            if ($rowcount1 === false || $rowcount2 === false) {
                logs([
                    'from' => __CLASS__ . '->' . __METHOD__,
                    'ret1' => $rowcount1,
                    'ret2' => $rowcount2,
                    'UserId' => $userid,
                    'ItemId' => $itemid,
                    'ItemType' => static::$_type
                ], self::LOG_FAIL_PATH);
                return false;
            }
        }
        return true;
    }

    /**
     * 判断一个或多个项目是否被用户收藏
     *
     * @param int $userid
     * @param int|array $itemid
     * @return bool|array  一个时返回true/false, 多个是返回['ItemId1' => true, 'ItemId2' => false, ...]
     */
    public static function isFavorite($userid, $itemid)
    {
        $items = static::_getUserItems($userid);
        if (is_array($itemid)) {
            $ret = [];
            $tmp = [];
            foreach ($items as $v) {
                $tmp[$v] = $v;
            }
            foreach ($itemid as $v) {
                if (isset($tmp[$v])) {
                    $ret[$v] = true;
                } else {
                    $ret[$v] = false;
                }
            }
            return $ret;
        }
        return in_array($itemid, $items);
    }

    /**
     * 获取用户的收藏记录
     *
     * @param int $userid 用户ID
     * @param int $offset 偏移量
     * @param int $limit 每次数量
     * @return array          ['total' => 记录总数, 'data' => [ItemId1, ItemId2, ...]]
     */
    public static function getItemsByUserId($userid, $offset = 0, $limit = 10)
    {
        $items = static::_getUserItems($userid);
        $count = count($items);
        if ($offset !== -1) {
            $items = array_slice($items, $offset, $limit);
        }
        return ['total' => $count, 'data' => $items];
    }

    /**
     * 根据项目ID获取用户ID
     *
     * @param int $itemid 项目ID
     * @return array      用户ID [UserId1, UserId2, UserId3]
     */
    public static function getUsersByItemId($itemid)
    {
        static::setFavConf();
        $unpack_data = [];
        $mc = DB::memcache();
        $key = sprintf(self::MC_ITEM_USERS, static::$_type, $itemid);
        $data = $mc->get($key);
        if (empty($data)) {
            $pdo = DB::pdo(DB::DB_FAVORITE_R);
            $ret = $pdo->find(self::DB_TABLE_NAME, [
                'ItemId' => $itemid,
                'ItemType' => static::$_type,
            ], 'UserId', 'FavoriteId ASC');
            $data = '';
            foreach ($ret as $v) {
                $data .= pack('L', $v['UserId']);
                $unpack_data[] = $v['UserId'];
            }
            $mc->set($key, $data, self::MC_TIME);
        }
        if (count($unpack_data) > 0) {
            return $unpack_data;
        }
        $data = unpack('L*', $data);
        is_array($data) || $data = [];
        return $data;
    }

    private static function _getUserItems($userid)
    {
        static::setFavConf();
        $unpack_data = [];
        $mc = DB::memcache();
        $key = sprintf(self::MC_USER_ITEMS, static::$_type, $userid);
        $data = $mc->get($key);
        if (empty($data)) {
            $pdo = DB::pdo(DB::DB_FAVORITE_R);
            $ret = $pdo->find(static::_shardTable($userid), [
                'UserId' => $userid,
                'ItemType' => static::$_type,
            ], 'ItemId', 'FavoriteId DESC');
            $data = '';
            foreach ($ret as $v) {
                $data .= pack('L', $v['ItemId']);
                $unpack_data[] = $v['ItemId'];
            }
            $mc->set($key, $data, self::MC_TIME);
        }
        if (count($unpack_data) > 0) {
            return $unpack_data;
        }
        $data = unpack('L*', $data);
        is_array($data) || $data = [];
        return $data;
    }

    private static function _shardTable($id)
    {
        return sprintf(self::DB_SHARD_TABLE, $id % 256);
    }
}