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
    // 搜索位置
    protected static $_search_index = '29shu_book/list/';
    // 搜索名称
    protected static $_search_index_prefix = '29shu_book';
    // 搜索队列名称
    protected static $_search_queue = 'SearchBook';

    const PRIMARY_KEY = 'id';
    const T_INT = 'int';
    const T_STRING = 'string';
    const T_STRTOTIME = 'strtotime';
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
            $data[static::PRIMARY_KEY] = $id;
            $ret = Queue::add(static::$_search_queue, [
                'type' => 'update',
                'data' => $data
            ]);
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
        $es = DB::search(DB::SEARCH_DEFAULT);

        return $es->query(static::$_search_index . '_search', $es::HTTP_POST, $query);
    }

    /**
     * 添加
     *
     * @param $data
     */
    public static function add($data)
    {
        $query = static::$_search_index . $data[static::PRIMARY_KEY];
        $id = $data[static::PRIMARY_KEY];
        unset($data[static::PRIMARY_KEY]);
        $es = DB::search(DB::SEARCH_DEFAULT);
        $ret = $es->query($query, $es::HTTP_PUT, $data);
        if ($ret === false) {
            logs([
                'query' => $query,
                'data' => $data,
                'id' => $id
            ], 'Search/SearchBook');
        }
    }

    /**
     * 更新
     *
     * @param $data
     */
    public static function update($data)
    {
        $query = static::$_search_index . $data[static::PRIMARY_KEY] . '/_update';
        $id = $data[static::PRIMARY_KEY];
        unset($data[static::PRIMARY_KEY]);
        $es = DB::search(DB::SEARCH_DEFAULT);
        $ret = $es->query($query, $es::HTTP_POST, ['doc' => $data]);
        if ($ret === false) {
            logs([
                'query' => $query,
                'data' => $data,
                'id' => $id
            ], 'Search/SearchBook');
        }
    }

    /**
     * 删除
     *
     * @return mixed
     */
    public static function delete()
    {
        $query = static::$_search_index_prefix;
        $es = DB::search(DB::SEARCH_DEFAULT);

        return $es->query($query, $es::HTTP_DELETE);
    }

    /**
     * 新建搜索数据
     *
     * @param        $data
     * @param string $primary
     * @param string $ver
     */
    public static function buildSearch($data, $primary = 'Id', $ver = '')
    {
        $head = "{\"index\":{\"_index\":\"" . static::$_search_index_prefix . $ver . "\",\"_type\":\"list\",\"_id\":\"{" . static::PRIMARY_KEY . "}\"}}";
        $query = "";
        foreach ($data as $row) {
            $t_head = str_replace('{' . static::PRIMARY_KEY . '}', $row[$primary], $head);
            $query .= $t_head . "\n";
            if (empty($row['UpdateTime']) || $row['UpdateTime'] == '0000-00-00 00:00:00') {
                $row['UpdateTime'] = $row['CreateTime'] ?? date('Y-m-d H:i:s');
            }
            $t_body = static::checkFields($row);

            $query .= json_encode($t_body) . "\n";
        }

        $es = DB::search(DB::SEARCH_DEFAULT);
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