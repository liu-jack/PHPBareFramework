<?php
/**
 * SearchBase.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-8-18 下午9:19
 *
 */

namespace Model\Search;

use Bare\DB;
use Bare\Queue;

class SearchBase
{
    // 数据库主键
    protected static $_primary_key = 'Id';
    // 搜索位置
    protected static $_search_index = '29shu_book/list/';
    // 搜索名称
    protected static $_search_index_prefix = '29shu_book';
    // 搜索队列名称
    protected static $_search_queue = 'SearchBook';
    // 搜索库
    protected static $_search_db = DB::SEARCH_DEFAULT;
    // 搜索连接实例
    private static $_instance = [];

    // 搜索主键
    const INDEX_KEY = '_id';
    const T_INT = 'int';
    const T_STRING = 'string';
    const T_FLOAT = 'float';
    const T_STR2TIME = 'str2time';

    /**
     * 搜索实例
     *
     * @return \Bare\D\ElasticSearch
     */
    protected static function instance()
    {
        if (empty(self::$_instance[static::$_search_db])) {
            self::$_instance[static::$_search_db] = DB::search(static::$_search_db);
        }

        return self::$_instance[static::$_search_db];
    }

    /**
     * 搜索字段
     */
    public static $_search_fields;

    /**
     * 新增同步搜索数据 （队列）
     *
     * @param array $row 所有字段必选, 见 self::$_search_fields 定义
     * @throws \Exception
     * @return bool
     */
    public static function addSearch(array $row): bool
    {
        $data = static::checkFields($row, true);

        $ret = Queue::add(static::$_search_queue, [
            'type' => 'add',
            'data' => $data
        ]);

        return $ret;
    }

    /**
     * 新增直接同步搜索数据
     *
     * @param array $row 所有字段必选, 见 self::$_search_fields 定义
     * @throws \Exception
     * @return bool
     */
    public static function addSearchDirect(array $row): bool
    {
        $data = static::checkFields($row, true);

        $ret = static::add($data);

        return $ret;
    }

    /**
     * 更新同步搜索数据 （队列）
     *
     * @param int   $id  ID
     * @param array $row 任选至少一个数据, 见 self::$_search_fields 定义
     * @return bool
     */
    public static function updateSearch(int $id, array $row): bool
    {
        $data = static::checkFields($row);

        $ret = true;
        if (count($data) > 0) {
            $data[static::INDEX_KEY] = $id;
            $ret = Queue::add(static::$_search_queue, [
                'type' => 'update',
                'data' => $data
            ]);
        }

        return $ret;
    }

    /**
     * 更新直接同步搜索数据
     *
     * @param int   $id  ID
     * @param array $row 任选至少一个数据, 见 self::$_search_fields 定义
     * @return bool
     */
    public static function updateSearchDirect(int $id, array $row): bool
    {
        $data = static::checkFields($row);

        $ret = true;
        if (count($data) > 0) {
            $data[static::INDEX_KEY] = $id;
            $ret = static::update($data);
        }

        return $ret;
    }

    /**
     * 执行搜索
     *
     * @param $query
     * @return mixed
     */
    public static function query($query)
    {
        $es = static::instance();

        return $es->query(static::$_search_index . '_search', $es::HTTP_POST, $query);
    }

    /**
     * 搜索结果返回
     *
     * @param       $ret
     * @param array $fields
     * @return array
     */
    public static function result($ret, $fields = [])
    {
        $total = empty($ret['hits']['total']) ? 0 : $ret['hits']['total'];
        $data = [];
        if ($ret !== false) {
            $hits = $ret['hits']['hits'];
            $only_id = count($fields) == 1 && key($fields) == static::$_primary_key;
            foreach ($hits as $result) {
                if ($only_id) {
                    $data[$result[static::INDEX_KEY]] = $result[static::INDEX_KEY];
                } else {
                    $items[static::$_primary_key] = $result[static::INDEX_KEY];
                    foreach ($fields as $field => $key) {
                        if (isset($result['_source'][$key])) {
                            $items[$field] = $result['_source'][$key];
                        }
                    }
                    $data[] = $items;
                }


            }
        }

        return ['total' => $total, 'data' => $data];
    }

    /**
     * 返回字段
     *
     * @param array|string $fields
     * @return array
     */
    public static function fields($fields = [])
    {
        if (empty($fields)) {
            $fields = [static::$_primary_key => static::INDEX_KEY];
        } elseif ($fields == '*') {
            $fields = array_map(function ($val) {
                return $val[1];
            }, static::$_search_fields);
        } elseif (is_string($fields)) {
            $fields = explode(',', $fields);
            $fields_map = array_map(function ($val) {
                return $val[1];
            }, static::$_search_fields);
            $fields = array_intersect($fields_map, $fields);
        }

        return $fields;
    }

    /**
     * 排序
     *
     * @param array $sort
     * @return array
     */
    public static function sort($sort = [])
    {
        $order = [];
        if (empty($sort)) {
            $sort['_score'] = 'desc';
        }
        foreach ($sort as $k => $v) {
            $order[] = [
                $k => [
                    "order" => $v
                ]
            ];
        }

        return $order;
    }

    /**
     * 添加 （直接|队列使用）
     *
     * @param $data
     * @return array|bool
     */
    public static function add($data)
    {
        $query = static::$_search_index . $data[static::INDEX_KEY];
        $id = $data[static::INDEX_KEY];
        unset($data[static::INDEX_KEY]);
        $es = static::instance();
        $ret = $es->query($query, $es::HTTP_PUT, $data);
        if ($ret === false) {
            logs([
                'query' => $query,
                'data' => $data,
                'id' => $id
            ], 'Search/SearchBook');
        }

        return $ret;
    }

    /**
     * 更新 （直接|队列使用）
     *
     * @param $data
     * @return array|bool
     */
    public static function update($data)
    {
        $query = static::$_search_index . $data[static::INDEX_KEY] . '/_update';
        $id = $data[static::INDEX_KEY];
        unset($data[static::INDEX_KEY]);
        $es = static::instance();
        $ret = $es->query($query, $es::HTTP_POST, ['doc' => $data]);
        if ($ret === false) {
            logs([
                'query' => $query,
                'data' => $data,
                'id' => $id
            ], 'Search/SearchBook');
        }

        return $ret;
    }

    /**
     * 删除
     *
     * @return mixed
     */
    public static function delete()
    {
        $query = static::$_search_index_prefix;
        $es = static::instance();

        return $es->query($query, $es::HTTP_DELETE);
    }

    /**
     * 新建搜索数据
     *
     * @param        $data
     * @param string $ver
     */
    public static function buildSearch($data, $ver = '')
    {
        $head = "{\"index\":{\"_index\":\"" . static::$_search_index_prefix . '_' . $ver . "\",\"_type\":\"list\",\"_id\":\"{" . static::INDEX_KEY . "}\"}}";
        $query = "";
        foreach ($data as $row) {
            $t_head = str_replace('{' . static::INDEX_KEY . '}', $row[static::$_primary_key], $head);
            $query .= $t_head . "\n";
            if (empty($row['UpdateTime']) || $row['UpdateTime'] == '0000-00-00 00:00:00') {
                $row['UpdateTime'] = $row['CreateTime'] ?? date('Y-m-d H:i:s');
            }
            $t_body = static::checkFields($row);
            unset($t_body[static::INDEX_KEY]);

            $query .= json_encode($t_body) . "\n";
        }

        $es = static::instance();
        $ret = $es->query("_bulk", $es::HTTP_POST, $query);
        if ($ret === false) {
            echo json_encode($es->getLastError()) . "\n";
        }
    }

    /**
     * 检查字段
     *
     * @param      $data
     * @param bool $must 必须全部满足
     * @return array
     * @throws \Exception
     */
    public static function checkFields($data, $must = false)
    {
        if ($must) {
            $diff = array_diff_key(static::$_search_fields, $data);
            if (count($diff) > 0) {
                throw new \Exception('Fields miss ' . implode(',', $diff));
            }
        }
        $return = [];
        foreach (static::$_search_fields as $k => $v) {
            if (isset($data[$k])) {
                $t = $data[$k];
                switch ($v[0]) {
                    case static::T_INT:
                        $t = (int)$t;
                        break;
                    case static::T_FLOAT:
                        $t = (float)$t;
                        break;
                    case static::T_STRING:
                        $t = (string)$t;
                        break;
                    case static::T_STRTOTIME:
                        $t = empty($t) ? 0 : strtotime($t);
                        break;
                }
                $return[$v[1]] = $t;
            }
        }

        return $return;
    }
}