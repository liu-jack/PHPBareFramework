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
    // 库名 继承修改
    protected static $_db = 'test';
    // 集合名  继承修改
    protected static $_collection = 'test';
    // mongodb 连接参数
    protected static $_dns = DB::MONGODB_DEFAULT;
    // mongodb 实例
    protected static $_mongo = null;
    // mongodb conn
    protected static $_conn = null;

    /**
     * 获取连接实例
     *
     * @return null|\MongoDB\Client
     */
    protected static function getConn()
    {
        if (empty(static::$_conn)) {
            static::$_conn = DB::mongodb(static::$_dns);
        }

        return static::$_conn;
    }

    /**
     * 获取实例化类
     *
     * @return null|\MongoDB\Collection
     */
    protected static function getMongodb()
    {
        if (empty(static::$_mongo)) {
            static::$_mongo = self::getConn()->selectCollection(static::$_db, static::$_collection);
        }

        return static::$_mongo;
    }

    /**
     * 切换连接
     *
     * @param $dns
     * @return \MongoDB\Client
     */
    public static function changeConn($dns)
    {
        static::$_conn = DB::mongodb($dns);

        return static::$_conn;
    }

    /**
     * 切换数据库
     *
     * @param string $db
     * @param array  $options
     * @return \MongoDB\Database
     */
    public static function selectDatabase($db, $options = [])
    {
        return static::getConn()->selectDatabase($db, $options);
    }

    /**
     * 切换集合
     *
     * @param       $collection
     * @param null  $db
     * @param array $options
     * @return \MongoDB\Collection
     */
    public static function selectCollection($collection, $db = null, $options = [])
    {
        if (empty($db)) {
            $db = static::$_db;
        }

        return static::getConn()->selectCollection($db, $collection, $options);
    }

    /**
     * 获取所有数据库
     *
     * @param array $options
     * @return array|\MongoDB\Model\DatabaseInfoIterator
     */
    public static function getDataBases($options = [])
    {
        $dbs = [];
        $databases = static::getConn()->listDatabases($options);
        foreach ($databases as $databaseInfo) {
            $name = $databaseInfo->getName();
            if (!empty($name)) {
                $dbs[$name] = $name;
            }
        }

        return $dbs;
    }

    /**
     * 删除数据库
     *
     * @param       $db
     * @param array $options
     * @return array|object|bool
     */
    public static function removeDataBase($db, $options = [])
    {
        $ret = (array)static::getConn()->dropDatabase($db, $options);
        if (!empty($ret['ok'])) {
            return true;
        }

        return false;
    }

    /**
     * 获取所有集合
     *
     * @param string $db
     * @param array  $options
     * @return array|\MongoDB\Model\CollectionInfoIterator
     */
    public static function getCollections($db = '', $options = [])
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        $colls = [];
        $collects = static::selectDatabase($db)->listCollections($options);
        foreach ($collects as $collect) {
            $name = $collect->getName();
            if (!empty($name)) {
                $colls[$name] = $name;
            }
        }

        return $colls;
    }

    /**
     * 新建集合
     *
     * @param string $collection
     * @param string $db
     * @param array  $options
     * @return array|object|bool
     */
    public static function createCollection($collection, $db = '', $options = [])
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        $ret = (array)static::selectDatabase($db)->createCollection($collection, $options);
        if (!empty($ret['ok'])) {
            return true;
        }

        return false;
    }

    /**
     * 删除集合
     *
     * @param string $collection
     * @param string $db
     * @param array  $options
     * @return bool
     */
    public static function removeCollection($collection, $db = '', $options = [])
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        $ret = (array)static::selectDatabase($db)->dropCollection($collection, $options);
        if (!empty($ret['ok'])) {
            return true;
        }

        return false;
    }

    /**
     * 获取所有索引
     *
     * @param string $db
     * @param string $collection
     * @param array  $options
     * @return array|\MongoDB\Model\IndexInfoIterator
     */
    public static function getIndexes($db = '', $collection = '', $options = [])
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        if (empty($collection)) {
            $collection = static::$_collection;
        }
        $idx = [];
        $indexs = static::selectCollection($collection, $db)->listIndexes($options);
        foreach ($indexs as $index) {
            $name = $index->getName();
            if (!empty($name)) {
                $idx[$name] = $name;
            }
        }

        return $idx;
    }

    /**
     * 创建索引
     *
     * @param array  $key
     * @param string $db
     * @param string $collection
     * @param array  $options
     * @return string
     */
    public static function createIndex($key, $db = '', $collection = '', $options = [])
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        if (empty($collection)) {
            $collection = static::$_collection;
        }
        $ret = (array)static::selectCollection($collection, $db)->createIndex($key, $options);
        if (!empty($ret['ok'])) {
            return true;
        }

        return false;
    }

    /**
     * 删除索引
     *
     * @param null   $indexName
     * @param string $db
     * @param string $collection
     * @param array  $options
     * @return array|object|bool
     */
    public static function removeIndexes($indexName = null, $db = '', $collection = '', $options = [])
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        if (empty($collection)) {
            $collection = static::$_collection;
        }
        if ($indexName === null) {
            $ret = (array)static::selectCollection($collection, $db)->dropIndexes($options = []);
        } else {
            $ret = (array)static::selectCollection($collection, $db)->dropIndex($indexName, $options);
        }
        if (!empty($ret['ok'])) {
            return true;
        }

        return false;
    }

    /**
     * 聚合操作
     *
     * @param        $pipeline
     * @param string $db
     * @param string $collection
     * @param array  $options
     * @return bool|\Traversable
     */
    public static function aggregation($pipeline, $db = '', $collection = '', $options = [])
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        if (empty($collection)) {
            $collection = static::$_collection;
        }
        try {
            $ret = static::selectCollection($collection, $db)->aggregate($pipeline, $options);
        } catch (\Exception $e) {
            static::log($e, $pipeline);

            return false;
        }

        return $ret;
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
        $ret = static::findOneData([
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
        $ret = static::getDataCount([
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
        $ret = static::insertOneData($data);

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

        $ret = static::updateOneData([
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
        $ret = static::deleteOneData(['_id' => $id]);

        return $ret;
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
            $ret = static::getMongodb()->count($filter, $options);
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
            $ret = static::getMongodb()->findOne($filter, $options);
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
            $list = static::getMongodb()->find($filter, $options);
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
            $ret = static::getMongodb()->deleteMany($filter, $options);
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
            $ret = static::getMongodb()->deleteOne($filter, $options);
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
            $ret = static::getMongodb()->insertOne($document, $options);
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
            $ret = static::getMongodb()->updateOne($filter, $updates, $options);
        } catch (\Exception $e) {
            static::log($e, $updates);

            return false;
        }
        if (!$ret->isAcknowledged()) {
            return false;
        }

        return true;
    }

    /**
     * 错误日志
     *
     * @param \Exception $e
     * @param string     $data
     */
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