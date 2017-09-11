<?php
/**
 * Test.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-9-11 上午11:46
 *
 */

namespace Model\Mongo;

use Bare\MongoModel;

class Test extends MongoModel
{
    // 库名
    protected static $_db = 'test';
    // 集合名
    protected static $_collection = 'test_hash';
    // 列表
    const FIELD_TEST_LIST = 'test_hash';
    // 总数限制
    const LIMIT_COUNT = 1000;

    /**
     * 字段列表
     *
     * @var array
     */
    private static $fields = [
        self::FIELD_TEST_LIST => self::FIELD_TEST_LIST,
    ];

    /**
     * 记录数据
     *
     * @param int|string $id
     * @param int|array
     * @return bool
     */
    public static function upsert($id, $data)
    {
        if (empty($id) || empty($data)) {
            return false;
        }
        $data = !is_numeric(key($data)) ? [$data] : $data;

        $ret = self::update($id, [
            '$addToSet' => [
                self::FIELD_TEST_LIST => [
                    '$each' => $data
                ]
            ]
        ], true);
        if ($ret !== false) {
            self::update($id, [
                '$push' => [
                    self::FIELD_TEST_LIST => [
                        '$each' => [],
                        '$slice' => -self::LIMIT_COUNT
                    ]
                ]
            ]);

            return true;
        }

        return false;
    }

    /**
     * 获取数据
     *
     * @param int|string $id
     * @param array      $fields 字段,默认全部,可选[self::FIELD_TEST_LIST, ...]
     * @return array     [self::FIELD_TEST_LIST => '', ....]
     */
    public static function getInfo($id, array $fields = [])
    {
        $user_fields = ['_id' => 0];
        foreach ($fields as $v) {
            if (self::$fields[$v]) {
                $user_fields[$v] = 1;
            }
        }

        $ret = self::get($id, ['projection' => $user_fields]);

        return $ret;
    }
}