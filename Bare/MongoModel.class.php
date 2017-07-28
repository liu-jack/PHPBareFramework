<?php
/**
 * MongoModel.class.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/7/5 10:46
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
     * @var null
     */
    protected static $_mongo = null;

    /**
     * 获取实例化类
     * @param mixed $config
     * @return \MongoDB\Collection|null
     */
    public static function getMongodb($config = '')
    {
        if (empty(self::$_mongo) || !empty($config)) {
            $config = !empty($config) ? $config : DB::MONGODB_DEFAULT;
            $conn = DB::mongodb($config);
            self::$_mongo = $conn->selectCollection(self::$_mongo_db, self::$_mongo_collection);
        }
        return self::$_mongo;
    }

    /**
     * 查询
     * @param $id
     * @param array $options ['projection' => [field]]
     * @return array
     */
    public static function get($id, $options = [])
    {
        self::getMongodb();
        $id = is_numeric($id) ? (int)$id : (string)$id;
        $ret = self::$_mongo->findOne([
            '_id' => $id
        ], $options);
        return is_array($ret) ? $ret : [];
    }

    /**
     * 新增
     * @param $id
     * @param array $data
     * @return bool
     */
    public static function add($id, $data)
    {
        self::getMongodb();
        $id = is_numeric($id) ? (int)$id : (string)$id;
        $data['_id'] = $id;
        try {
            $ret = self::$_mongo->insertOne($data);
        } catch (\Exception $e) {
            self::log($e, $data);
            return false;
        }
        return $ret;
    }

    /**
     * 修改
     * @param $id
     * @param array $data
     * @param bool $upsert
     * @return bool
     */
    public static function update($id, $data, $upsert = true)
    {
        self::getMongodb();
        $id = is_numeric($id) ? (int)$id : (string)$id;
        try {
            $ret = self::$_mongo->updateOne([
                '_id' => $id
            ], $data, [
                'upsert' => $upsert
            ]);
        } catch (\Exception $e) {
            self::log($e, $data);
            return false;
        }
        return $ret;
    }

    /**
     * 删除
     * @param $id
     * @return bool
     */
    public static function delete($id)
    {
        self::getMongodb();
        $id = is_numeric($id) ? (int)$id : (string)$id;
        $data['_id'] = $id;
        try {
            $ret = self::$_mongo->deleteOne(['_id' => $id]);
        } catch (\Exception $e) {
            static::log($e, $data);
            return false;
        }
        return $ret;
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