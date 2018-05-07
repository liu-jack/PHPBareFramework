<?php
/**
 * MongoBase.class.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/7/5 10:46
 *
 */

namespace Bare;

use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;

class MongoBase
{
    // 库名 继承修改
    protected static $_db = 'test';
    // 集合名  继承修改
    protected static $_table = 'test';
    // mongodb 连接参数
    protected static $_dns = DB::MONGODB_DEFAULT;

    /**
     * mongodb 实例
     *
     * @var null|\MongoDB\Collection
     */
    private static $_mongo = null;

    /**
     * mongodb conn
     *
     * @var null|\MongoDB\Client
     */
    private static $_conn = null;

    /**
     * 获取连接实例
     *
     * @return null|\MongoDB\Client
     */
    private static function getConn()
    {
        if (empty(self::$_conn)) {
            self::$_conn = DB::mongodb(static::$_dns);
        }

        return self::$_conn;
    }

    /**
     * 获取实例化类
     *
     * @return null|\MongoDB\Collection
     */
    protected static function getMongodb()
    {
        if (empty(self::$_mongo)) {
            self::$_mongo = self::getConn()->selectCollection(static::$_db, static::$_table);
        }
        static::_before();

        return self::$_mongo;
    }

    /**
     * 创建索引等前置操作
     *
     * @return bool
     */
    protected static function _before()
    {
        return false;
    }

    /**
     * 切换连接
     *
     * @param $dns
     * @return \MongoDB\Client
     */
    public static function changeConn($dns)
    {
        self::$_conn = DB::mongodb($dns);

        return self::$_conn;
    }

    /**
     * 切换数据表
     *
     * @param        $table
     * @param string $db
     * @return \MongoDB\Collection|null
     */
    public static function changeTable($table, $db = '')
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        self::$_mongo = self::$_conn->selectCollection($db, $table);

        return self::$_mongo;
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
        return self::getConn()->selectDatabase($db, $options);
    }

    /**
     * 切换集合
     *
     * @param        $collection
     * @param array  $options
     * @param string $db
     * @return \MongoDB\Collection
     */
    public static function selectCollection($collection, $options = [], $db = '')
    {
        if (empty($db)) {
            $db = static::$_db;
        }

        return self::getConn()->selectCollection($db, $collection, $options);
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
        $databases = self::getConn()->listDatabases($options);
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
        $ret = (array)self::getConn()->dropDatabase($db, $options);
        if (!empty($ret['ok'])) {
            return true;
        }

        return false;
    }

    /**
     * 获取所有集合
     *
     * @param array  $options
     * @param string $db
     * @return array|\MongoDB\Model\CollectionInfoIterator
     */
    public static function getCollections($options = [], $db = '')
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
     * @param array  $options
     * @param string $db
     * @return array|object|bool
     */
    public static function createCollection($collection, $options = [], $db = '')
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
     * @param array  $options
     * @param string $collection
     * @param string $db
     * @return bool
     */
    public static function removeCollection($collection, $options = [], $db = '')
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
     * @param array  $options
     * @param string $collection
     * @param string $db
     * @return array
     */
    public static function getIndexes($options = [], $collection = '', $db = '')
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        if (empty($collection)) {
            $collection = static::$_table;
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
     * @param        $key
     * @param array  $options
     * @param string $collection
     * @param string $db
     * @return bool
     */
    public static function createIndex($key, $options = [], $collection = '', $db = '')
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        if (empty($collection)) {
            $collection = static::$_table;
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
     * @param array  $options
     * @param string $collection
     * @param string $db
     * @return bool
     */
    public static function removeIndexes($indexName = null, $options = [], $collection = '', $db = '')
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        if (empty($collection)) {
            $collection = static::$_table;
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
     * @param array  $options
     * @param string $collection
     * @param string $db
     * @return bool|\Traversable
     */
    public static function aggregation($pipeline, $options = [], $collection = '', $db = '')
    {
        if (empty($db)) {
            $db = static::$_db;
        }
        if (empty($collection)) {
            $collection = static::$_table;
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
    public static function getById($id, $options = [])
    {
        $id = is_numeric($id) ? (int)$id : (string)$id;
        $ret = static::findOneData([
            '_id' => $id
        ], $options);

        return is_array($ret) ? $ret : [];
    }

    /**
     * 根据id获取详细数据
     *
     * @param $id
     * @return array|bool|null|object
     */
    public function getByObjectId($id)
    {
        $ret = static::findOneData([
            '_id' => new ObjectID($id)
        ]);

        return $ret;
    }

    /**
     * 列表查询
     *
     * @param array        $where ['Title'=>['LIKE'=>'test'],'Type'=>['$gte'=>0]]
     * @param int          $offset
     * @param int          $limit
     * @param array|string $field
     * @param array        $order
     * @return array
     */
    public static function getPageList($where, $offset = 0, $limit = 0, $field = [], $order = ['_id' => -1])
    {
        self::parseWhere($where);
        $options = [
            'limit' => $limit,
            'skip' => $offset,
            'sort' => $order
        ];
        if (!empty($field)) {
            if (is_string($field)) {
                $field = explode(',', $field);
            }
            $val = [];
            for ($i = 1, $j = count($field); $i <= $j; $i++) {
                $val[] = 1;
            }
            $options['projection'] = array_combine($field, $val);
        }
        $total = static::getDataCount($where);
        $rows = static::findData($where, $options);

        return [
            'count' => $total,
            'data' => $rows
        ];
    }

    /**
     * 获取总数量
     *
     * @param       $id
     * @param array $options
     * @return int
     */
    public static function countById($id, $options = [])
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
    public static function addById($id, $data)
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
    public static function updateById($id, $data, $upsert = true)
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
    public static function deleteById($id)
    {
        $id = is_numeric($id) ? (int)$id : (string)$id;
        $data['_id'] = $id;
        $ret = static::deleteOneData(['_id' => $id]);

        return $ret;
    }

    /**
     * where条件解析
     *
     * @param $where
     * @return mixed
     */
    protected static function parseWhere(&$where)
    {
        foreach ($where as $k => &$v) {
            if (strtoupper(key($v)) == 'LIKE') {
                $v = new Regex(current($v), "i");
            }
        }

        return $where;
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
        logs($log, "MongoDB/" . static::class);
    }
}