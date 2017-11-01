<?php
/**
 * UserData.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/7/5 15:32
 *
 */

namespace Model\Mongo;

use Bare\MongoModel;

class UserData extends MongoModel
{
    // 库名
    protected static $_db = 'user';
    // 集合名
    protected static $_collection = 'userdata';
    // 已读书本记录
    const FIELD_BOOK_READ_HISTORY = 'book_read_history';
    // 总数限制
    const LIMIT_COUNT = 1000;

    /**
     * 字段列表
     *
     * @var array
     */
    private static $fields = [
        self::FIELD_BOOK_READ_HISTORY => self::FIELD_BOOK_READ_HISTORY,
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
                self::update($uid, [
                    '$pull' => [
                        self::FIELD_BOOK_READ_HISTORY => $id
                    ]
                ], true);
            }
        }

        if (count($bids) > 0) {
            $ret = self::update($uid, [
                '$addToSet' => [
                    self::FIELD_BOOK_READ_HISTORY => [
                        '$each' => $bids
                    ]
                ]
            ], true);
            if ($ret !== false) {
                self::update($uid, [
                    '$push' => [
                        self::FIELD_BOOK_READ_HISTORY => [
                            '$each' => [],
                            '$slice' => -self::LIMIT_COUNT
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
     * @param array      $fields 字段,默认全部,可选[self::FIELD_BOOK_READ, ...]
     * @return array     [self::FIELD_BOOK_READ_HISTORY => '', ....]
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