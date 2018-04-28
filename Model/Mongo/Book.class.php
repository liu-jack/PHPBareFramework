<?php
/**
 * Book.class.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-28 下午3:29
 *
 */

namespace Model\Mongo;

use Bare\MongoModel;

class Book extends MongoModel
{
    // 数据库
    protected static $_db = 'test';
    // 数据表
    protected static $_table = 'book';

    const FIELD_ID = '_id';
    const FIELD_NAME = 'Name';
    const FIELD_AUTHOR = 'Author';
    const FIELD_TYPE = 'Type';
    const FIELD_CREATE_TIME = 'CreateTime';
    /**
     * @var array 字段
     */
    protected static $_fields = [
        self::FIELD_ID => self::VAR_TYPE_INT,
        self::FIELD_NAME => self::VAR_TYPE_STRING,
        self::FIELD_AUTHOR => self::VAR_TYPE_STRING,
        self::FIELD_TYPE => self::VAR_TYPE_INT,
        self::FIELD_CREATE_TIME => self::VAR_TYPE_STRING,
    ];

    /**
     * @see \Bare\MongoModel::add() 新增
     * @see \Bare\MongoModel::update() 更新
     * @see \Bare\MongoModel::getInfoByIds() 按主键id查询
     * @see \Bare\MongoModel::getList() 条件查询
     * @see \Bare\MongoModel::delete() 删除
     */
}