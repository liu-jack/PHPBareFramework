<?php
/**
 * MongoModel.class.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/7/5 10:46
 *
 */

namespace Bare;

class MongoModel
{
    /**
     * 库名 继承修改
     */
    protected static $_mongo_db = 'test';

    /**
     * 集合名  继承修改
     */
    protected static $_mongo_collection = 'test';

    /**
     * mongodb 实例化类
     *
     * @var null
     */
    protected static $_mongo = null;

    /**
     * 获取实例化类
     *
     * @param mixed $config
     * @return \MongoDB\Collection|null
     */
    protected static function getMongodb($config = '')
    {
        if (empty(self::$_mongo)) {
            $config = !empty($config) ? $config : DB::MONGODB_DEFAULT;
            $conn = DB::mongodb($config);
            self::$_mongo = $conn->selectCollection(self::$_mongo_db, self::$_mongo_collection);
        }

        return self::$_mongo;
    }

    /**
     * 查询
     *
     * @param       $id
     * @param array $options
     * @return array|bool|null|object
     */
    public static function get($id, $options = [])
    {
        $id = is_numeric($id) ? (int)$id : (string)$id;
        $ret = self::findOneData([
            '_id' => $id
        ], $options);

        return is_array($ret) ? $ret : [];
    }

    /**
     * 获取总数量
     *
     * @param       $id
     * @param array $options
     * @return int
     */
    public static function count($id, $options = [])
    {
        $id = is_numeric($id) ? (int)$id : (string)$id;
        $ret = self::getDataCount([
            '_id' => $id
        ], $options);

        return $ret;
    }

    /**
     * 新增
     *
     * @param $id
     * @param $data
     * @return bool|mixed
     */
    public static function add($id, $data)
    {
        $id = is_numeric($id) ? (int)$id : (string)$id;
        $data['_id'] = $id;
        $ret = self::insertOneData($data);

        return $ret;
    }

    /**
     * 修改
     *
     * @param      $id
     * @param      $data
     * @param bool $upsert
     * @return bool
     */
    public static function update($id, $data, $upsert = true)
    {
        $id = is_numeric($id) ? (int)$id : (string)$id;

        $ret = self::updateOneData([
            '_id' => $id
        ], $data, [
            'upsert' => $upsert
        ]);

        return $ret;
    }

    /**
     * 删除
     *
     * @param $id
     * @return bool
     */
    public static function delete($id)
    {
        $id = is_numeric($id) ? (int)$id : (string)$id;
        $data['_id'] = $id;
        $ret = self::deleteOneData(['_id' => $id]);

        return $ret;
    }

    /**
     * 创建索引
     *
     * @param $key
     * @return string
     */
    public static function createIndex($key)
    {
        return self::getMongodb()->createIndex($key);
    }

    /**
     * 获取总数量
     *
     * @param array $filter
     * @param array $options
     * @return int
     */
    protected static function getDataCount($filter, $options = [])
    {
        try {
            $ret = self::getMongodb()->count($filter, $options);
        } catch (\Exception $e) {
            static::log($e, $filter);
            $ret = 0;
        }

        return $ret;
    }

    /**
     * 获取单个数据
     *
     * @param       $filter
     * @param array $options
     * @return array|bool|null|object
     */
    protected static function findOneData($filter, $options = [])
    {
        try {
            $ret = self::getMongodb()->findOne($filter, $options);
        } catch (\Exception $e) {
            static::log($e, $filter);
            $ret = false;
        }
        if ($ret === null) {
            $ret = false;
        }

        return $ret;
    }

    /**
     * 查找多个
     *
     * @param       $filter
     * @param array $options
     * @return array|bool
     */
    protected static function findData($filter, $options = [])
    {
        try {
            $list = self::getMongodb()->find($filter, $options);
        } catch (\Exception $e) {
            static::log($e, $filter);

            return false;
        }

        return $list->toArray();
    }

    /**
     * 删除多个
     *
     * @param       $filter
     * @param array $options
     * @return bool
     */
    protected static function deleteData($filter, $options = [])
    {
        try {
            $ret = self::getMongodb()->deleteMany($filter, $options);
        } catch (\Exception $e) {
            static::log($e, $filter);

            return false;
        }
        if (!$ret->isAcknowledged()) {
            return false;
        }

        return true;
    }

    /**
     * 删除单个
     *
     * @param       $filter
     * @param array $options
     * @return bool
     */
    protected static function deleteOneData($filter, $options = [])
    {
        try {
            $ret = self::getMongodb()->deleteOne($filter, $options);
        } catch (\Exception $e) {
            static::log($e, $filter);

            return false;
        }
        if (!$ret->isAcknowledged()) {
            return false;
        }

        return true;
    }

    /**
     * 插入一条数据
     *
     * @param       $document
     * @param array $options
     * @return bool|mixed
     */
    protected static function insertOneData($document, $options = [])
    {
        try {
            $ret = self::getMongodb()->insertOne($document, $options);
        } catch (\Exception $e) {
            static::log($e, $document);

            return false;
        }
        if (!$ret->isAcknowledged()) {
            return false;
        }

        return $ret->getInsertedId();
    }

    /**
     * 更新一条记录
     *
     * @param       $filter
     * @param       $updates
     * @param array $options
     * @return bool
     */
    protected static function updateOneData($filter, $updates, $options = [])
    {
        try {
            $ret = self::getMongodb()->updateOne($filter, $updates, $options);
        } catch (\Exception $e) {
            static::log($e, $updates);

            return false;
        }
        if (!$ret->isAcknowledged()) {
            return false;
        }

        return true;
    }

    protected static function log(\Exception $e, $data = '')
    {
        $log = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'msg' => $e->getMessage(),
            'data' => $data,
        ];
        logs($log, "MongoDB/" . __CLASS__);
    }
}