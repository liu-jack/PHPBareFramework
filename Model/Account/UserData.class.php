<?php
/**
 * UserData.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/7/5 15:32
 *
 */

namespace Model\Account;

use Bare\MongoModel;

class UserData extends MongoModel
{
    /**
     * 库名
     */
    protected static $_mongo_db = 'user';

    /**
     * 集合名
     */
    protected static $_mongo_collection = 'userdata';

    /**
     * 已读
     */
    const FIELD_BOOK_READ = 'book_read';

    /**
     * 总数限制
     */
    const LIMIT_BOOK_READ = 1000;

    /**
     * 字段列表
     *
     * @var array
     */
    private static $fields = [
        self::FIELD_BOOK_READ => self::FIELD_BOOK_READ,
    ];

    /**
     * 记录已读的book
     *
     * @param int|string $uid    用户ID|设备ID
     * @param int|array  $bookid bookID，可多个
     * @return bool
     */
    public static function userReadBook($uid, $bookid)
    {
        if (empty($uid) || empty($bookid)) {
            return false;
        }
        $bookid = is_array($bookid) ? $bookid : [$bookid];
        $bids = [];
        foreach ($bookid as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $bids[] = $id;
            }
        }

        if (count($bids) > 0) {
            $ret = self::update($uid, [
                '$addToSet' => [
                    self::FIELD_BOOK_READ => [
                        '$each' => $bids
                    ]
                ]
            ], true);
            if ($ret !== false) {
                self::update($uid, [
                    '$push' => [
                        self::FIELD_BOOK_READ => [
                            '$each' => [],
                            '$slice' => -self::LIMIT_BOOK_READ
                        ]
                    ]
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * 获取用户数据
     *
     * @param int|string $uid    用户ID|设备ID
     * @param array      $fields 字段,默认全部,可选[self::FIELD_UNLIKE, self::FIELD_READ, ...]
     * @return array     [self::FIELD_BOOK_READ => '', ....]
     */
    public static function getUserData($uid, array $fields = [])
    {
        $user_fields = ['_id' => 0];
        foreach ($fields as $v) {
            if (self::$fields[$v]) {
                $user_fields[$v] = 1;
            }
        }

        $ret = self::get($uid, ['projection' => $user_fields]);

        return $ret;
    }
}