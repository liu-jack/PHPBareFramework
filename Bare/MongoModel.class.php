<?php
/**
 * MongoModel.class.php
 * mongodb抽象基类模型
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-28 上午10:18
 *
 */

namespace Bare;

use Config\DBConfig;

abstract class MongoModel extends MongoBase
{
    // 数据库
    protected static $_db = 'test';
    // 数据表
    protected static $_table = 'test';
    // mongodb 连接参数
    protected static $_dns = DBConfig::MONGODB_DEFAULT;

    const FIELD_ID = '_id';
    const FIELD_CREATE_TIME = 'CreateTime';
    /**
     * @var array 字段
     */
    protected static $_fields = [
        self::FIELD_ID => self::VAR_TYPE_INT,
        self::FIELD_CREATE_TIME => self::VAR_TYPE_STRING,
    ];

    /**
     * @see \Bare\MongoModel::add() 新增
     * @see \Bare\MongoModel::update() 更新
     * @see \Bare\MongoModel::getInfoByIds() 按主键id查询
     * @see \Bare\MongoModel::getList() 条件查询
     * @see \Bare\MongoModel::delete() 删除
     */

    // 主键/字段类型
    const VAR_TYPE_INT = 'int';
    const VAR_TYPE_FLOAT = 'float';
    const VAR_TYPE_STRING = 'string';
    const VAR_TYPE_ARRAY = 'array';
    const VAR_TYPE_JSON = 'json';
    const VAR_TYPE_PASSWORD = 'password';

    /**
     * 前置操作 建立索引
     */
    protected static function _before()
    {
        parent::createIndex([
            self::FIELD_ID => -1,
            self::FIELD_CREATE_TIME => -1
        ], ['unique' => true]);
    }

    /**
     * 新增数据
     *
     * @param $data
     * @return bool|mixed
     */
    public static function add($data)
    {
        $data = self::checkFields($data);

        return parent::insertOneData($data);
    }

    /**
     * 更新
     *
     * @param $id
     * @param $data
     * @return bool
     */
    public static function update($id, $data)
    {
        $data = self::checkFields($data);

        return parent::updateById($id, $data);
    }

    /**
     * 根据id获取数据
     *
     * @param $id
     * @return array|bool|null|object
     */
    public static function getInfoByIds($id)
    {
        if (is_array($id)) {
            static $_cache;
            $data = [];
            $list = parent::findData([
                static::FIELD_ID => ['$in' => $id]
            ]);
            if (!empty($list)) {
                foreach ($list as $v) {
                    $_cache[$v[static::FIELD_ID]] = $v;
                }
            }
            foreach ($id as $_id) {
                if (isset($_cache[$_id])) {
                    $data[$_id] = $_cache[$_id];
                }
            }
        } else {
            $data = parent::getById($id);
        }

        return !empty($data) ? $data : [];
    }

    /**
     * 获取列表
     *
     * @param array        $where ['Title'=>['LIKE'=>'test'],'Type'=>['$gte'=>0]]
     * @param int          $offset
     * @param int          $limit
     * @param string|array $field
     * @param array        $order
     * @return array
     */
    public static function getList($where = [], $offset = 0, $limit = 0, $field = '', $order = [])
    {
        return parent::getPageList($where, $offset, $limit, $field, $order);
    }

    /**
     * 删除数据
     *
     * @param $id
     * @return bool
     */
    public static function delete($id)
    {
        return parent::deleteById($id);
    }

    /**
     * 字段类型验证
     *
     * @param array $rows
     * @return array
     */
    private static function checkFields($rows = [])
    {
        foreach ($rows as $k => &$v) {
            if (is_numeric($k)) {
                $v = static::checkFields($v);
            } else {
                if (!isset(static::$_fields[$k])) {
                    unset($rows[$k]);
                } else {
                    switch (static::$_fields[$k]) {
                        case static::VAR_TYPE_INT:
                            $v = is_array($v) ? $v : intval($v);
                            break;
                        case static::VAR_TYPE_FLOAT:
                            $v = is_array($v) ? $v : floatval($v);
                            break;
                        case static::VAR_TYPE_ARRAY:
                            $v = is_array($v) ? serialize($v) : $v;
                            break;
                        case static::VAR_TYPE_JSON:
                            $v = is_array($v) ? json_encode($v) : $v;
                            break;
                        case static::VAR_TYPE_PASSWORD:
                            $v = !empty($v) ? password_hash($v, PASSWORD_DEFAULT) : $v;
                            break;
                        case static::VAR_TYPE_STRING:
                            $v = is_array($v) ? $v : strval($v);
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        return $rows;
    }
}