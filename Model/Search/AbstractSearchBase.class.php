<?php
/**
 *
 * 搜索基类
 *
 */

namespace Model\Search;

use Common\Bridge;
use lib\search\ElasticSearch;
use Common\DataType;

abstract class AbstractSearchBase
{
    // 排序 - 逆序
    const SORT_TYPE_DESC = 'desc';
    // 排序 - 顺序
    const SORT_TYPE_ASC = 'asc';

    //主键
    const FIELD_ID = 'id';

    //data type
    const FIELD_DATA_TYPE_INT = 'int';
    const FIELD_DATA_TYPE_STRING = 'string';
    const FIELD_DATA_TYPE_ARRAY = 'array';
    const FIELD_DATA_TYPE_DOUBLE = 'double';
    const FIELD_DATA_TYPE_DATE = 'date';

    //field
    protected static $SEARCH_FIELD_MAP = null;

    /**
     * 查询结果并格式化
     *
     * @param $index
     * @param $query
     * @param $fields
     * @return array
     */
    protected static function getSearchResults($index, $query, $fields)
    {
        $queryRet = self::query($index, $query);

        return self::formatResult($queryRet, $fields);
    }

    /**
     * 查询
     *
     * @param $index
     * @param $query
     * @return array|bool
     */
    protected static function query($index, $query)
    {
        $es = self::getES();
        $queryRet = $es->query($index . '_search', ElasticSearch::HTTP_POST, $query);
        if ($queryRet === false) {
            debug_log(get_called_class() . ' query failed: search index:[' . $index . '],query data:[' . json_encode($query) . '],error msg:' . json_encode($es->getLastError()), JF_LOG_ERROR);
        }

        return $queryRet;
    }

    /**
     * @param $queryRet
     * @param $fields
     * @return array
     */
    protected static function formatResult($queryRet, $fields)
    {
        $total = empty($queryRet['hits']['total']) ? 0 : $queryRet['hits']['total'];
        $data = [];
        if ($queryRet != false) {
            $hits = $queryRet['hits']['hits'];
            foreach ($hits as $result) {
                $items = [];
                foreach ($fields as $key) {
                    $items[$key] = $result['_source'][$key];
                }
                $data[] = $items;
            }
        }

        return ['total' => $total, 'data' => $data];
    }


    /**
     * @return ElasticSearch
     */
    protected static function getES()
    {
        return Bridge::search(Bridge::SEARCH_DEFAULT);
    }

    /**
     * @param        $index
     * @param string $data
     * @return array|bool
     */
    public static function createIndex($index, $data = '')
    {
        $es = self::getES();

        $ret = $es->query($index, ElasticSearch::HTTP_PUT, $data);

        var_dump($es->getLastError());

        return $ret;
    }

    /**
     * 直接添加搜索数据到ES
     *
     * @param $index
     * @param $id
     * @param $info
     * @return array|bool
     */
    public static function addDirect($index, $id, $info)
    {
        $data = static::formatAddData($id, $info);
        if ($data === false) {
            return false;
        }
        $query = $index . $id;
        $es = self::getES();
        $ret = $es->query($query, ElasticSearch::HTTP_PUT, $data);
        if ($ret === false) {
            debug_log(get_called_class() . ' addDirect failed: ' . json_encode($data), JF_LOG_ERROR);
        }

        return $ret;
    }

    /**
     * 更新数据，直接操作搜索引擎
     *
     * @param $index
     * @param $id
     * @param $info
     * @return array|bool
     */
    public static function updateDirect($index, $id, $info)
    {
        $data = static::formatUpdateData($id, $info);
        if ($data === false) {
            return false;
        }
        $query = $index . $id . '/_update';
        $es = self::getES();
        $ret = $es->query($query, ElasticSearch::HTTP_POST, [
            'doc' => $data
        ]);
        if ($ret === false) {
            debug_log(get_called_class() . ' updateDirect failed: ' . json_encode($data), JF_LOG_ERROR);
        }

        return $ret;
    }

    /**
     * 构建字段类型
     *
     * @param $index
     * @param $docType
     * @param $data
     * @return array|bool
     */
    public static function mapping($index, $docType, $data)
    {
        $es = self::getES();
        $query = $index . '/_mapping/' . $docType . '?pretty';
        $ret = $es->query($query, ElasticSearch::HTTP_POST, $data);
        if ($ret === false) {
            var_dump($es->getLastError());
        }

        return $ret;
    }

    /**
     * 格式化添加数据
     *
     * @param $id
     * @param $info
     * @return array|bool
     */
    public static function formatAddData($id, $info)
    {
        assert(!empty(static::$SEARCH_FIELD_MAP));

        $diffKey = array_diff_key(static::$SEARCH_FIELD_MAP, $info);
        if (count($diffKey) > 0) {
            debug_log(['formatInputData ', $diffKey], JF_LOG_ERROR);

            return false;
        }
        $data = [self::FIELD_ID => $id];
        foreach (static::$SEARCH_FIELD_MAP as $key => $value) {
            $data[$value[0]] = self::formatData($value[1], $info[$key]);
        }

        return $data;
    }

    /**
     * 格式化更新数据
     *
     * @param $id
     * @param $info
     * @return array|bool
     */
    public static function formatUpdateData($id, $info)
    {
        assert(!empty(static::$SEARCH_FIELD_MAP));

        $data = [];
        foreach (static::$SEARCH_FIELD_MAP as $key => $value) {
            if (!isset($info[$key])) {
                continue;
            }
            $data[$value[0]] = self::formatData($value[1], $info[$key]);
        }
        if (count($data) == 0) {
            debug_log('formatUpdateData failed, data is empty', JF_LOG_ERROR);

            return false;
        }
        $data[self::FIELD_ID] = $id;

        return $data;
    }

    /**
     * 根据数据类型格式化数据
     *
     * @param $dataType
     * @param $value
     * @return array|int
     */
    protected static function formatData($dataType, $value)
    {
        switch ($dataType) {
            case self::FIELD_DATA_TYPE_INT:
                return (int)$value;
                break;
            case self::FIELD_DATA_TYPE_ARRAY:
                if (empty($value)) {
                    return [];
                }
                if (!is_array($value)) {
                    $value = json_decode($value, JSON_UNESCAPED_UNICODE);
                }

                return self::buildArray($value);
                break;
            case self::FIELD_DATA_TYPE_DATE:
                if (strpos($value, '0000-00-00') === false && !empty($value)) {
                    return $value;
                } else {
                    return DataType::datetime(0);
                }
            default:
                return $value;
                break;
        }
    }

    /**
     * 格式化数组
     *
     * @param $arr
     * @return array
     */
    protected static function buildArray($arr)
    {
        $data = [];
        foreach ($arr as $k => $v) {
            $data[] = $v;
        }

        return $data;
    }

    /**
     * @param $q
     * @return string
     */
    protected static function escape($q)
    {
        return addcslashes($q, '+ - && || ! ( ) { } [ ] ^ " ~ * ? : \ /');
    }
}