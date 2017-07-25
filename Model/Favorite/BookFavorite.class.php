<?php

/**
 * 书本收藏
 */

namespace Model\Favorite;

use Model\Book\Book;
use Model\Account\User as AUser;

class BookFavorite extends Favorite
{
    /**
     * 收藏类型配置
     *
     */
    public static function setFavConf()
    {
        self::$_type = self::TYPE_BOOK;
    }

    /**
     * 更新项目收藏记录计数
     *
     * @param  int $itemid 项目ID
     * @param  string $count 计数变化， '+1/-1'
     * @return bool
     */
    public static function updateItemFavCount($itemid, $count)
    {
        return Book::updateBook($itemid, ['FavoriteCount' => ['FavoriteCount', $count]]);
    }

    /**
     * 更新用户收藏计数
     *
     * @param  int $userid 用户ID
     * @param  string $count 计数变化， '+1/-1'
     * @return bool
     */
    public static function updateUserFavCount($userid, $count)
    {
        if ($count < 0) {
            $user = AUser::getUserById($userid);
            if ($user['BookCount'] < 1) {
                return false;
            }
        }
        return AUser::updateCount($userid, [AUser::COUNT_FAVORITE => $count]);
    }

    /**
     * 添加收藏 (若已经收藏返回成功)
     *
     * @param  integer $userid 用户ID
     * @param  integer $itemid 项目ID
     * @return bool
     */
    public static function add($userid, $itemid)
    {
        $ret = true;
        if (!self::isFavorite($userid, $itemid)) {
            $ret = self::addFav($userid, $itemid);
        }
        return $ret;
    }

    /**
     * 删除收藏 (未收藏时返回成功)
     *
     * @param  integer $userid 用户ID
     * @param  integer $itemid 项目ID
     * @return bool
     */
    public static function remove($userid, $itemid)
    {
        $ret = self::removeFav($userid, $itemid);
        return $ret;
    }
}
