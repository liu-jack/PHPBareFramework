<?php
/**
 * 基类数据模型
 *
 * @author camfee<camfee@yeah.net>
 * @since  v1.0 2016.09.25
 */

namespace Bare;

abstract class Model
{
    /**
     * 基础配置文件
     *
     * @var array
     */
    protected static $_conf = [
        // 必选, 数据库代码 (来自DB配置), w: 写, r: 读
        'db' => [
            'w' => '',
            'r' => ''
        ],
        // 必选, 数据表名
        'table' => '',
        // 必选, 字段信息
        'fields' => [],
        // 可选, MC连接参数
        'mc' => '',
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        'mckey' => '',
        // 可选, 超时时间, 默认不过期
        'mctime' => 86400
    ];

    // 主键/字段类型
    const VAR_TYPE_KEY = 'PRIMARY KEY';
    const VAR_TYPE_INT = 'int';
    const VAR_TYPE_STRING = 'string';
    const VAR_TYPE_ARRAY = 'array';
    const VAR_TYPE_PASSWORD = 'password';

    // 强制不用缓存
    const EXTRA_NO_CACHE = 'no_cache';
    // 从写库读取数据
    const EXTRA_FROM_W = 'from_w';

    // 模块类型, MC模式
    const MOD_TYPE_MEMCACHE = 1;
    // 模块类型, DB模式
    const MOD_TYPE_DB = 2;

    /**
     * 添加一条或多条数据
     *
     * @param array  $rows   要写入的数组(支持多行写入), 单个[data], 多个[[data1],[data2],...]
     * @param bool   $ignore 是否使用IGNORE, 默认不使用
     * @param string $suff   分表后缀名称
     * @return bool|int|string 成功返回LastInsertId, 未插入数据返回0, 插入失败返回false
     * @throws \Exception
     */
    public static function addData($rows, $ignore = false, $suff = '')
    {
        $rows = static::checkParams($rows);
        $options = $ignore ? ['ignore' => true] : [];

        $pdo = DB::pdo(static::$_conf['db']['w']);
        $ret = $pdo->insert(static::$_conf['table'] . $suff, $rows, $options);
        if ($ret !== false) {
            if ($ret > 0) {
                return $pdo->lastInsertId();
            } else {
                return 0;
            }
        }

        $pdo->close();
        DB::pdo(static::$_conf['db']['w'], 'force_close');
        $pdo = null;

        return false;

    }

    /**
     * 获得一条或多条数据
     *
     * @param int|array $id    主键ID
     * @param array     $extra 见 self::EXTRA_*
     * @param string    $suff  分表后缀名称
     * @return array 单条返回['feild1'=>'value1',..], 多条返回['主键ID1' => [...],]
     * @throws \Exception
     */
    public static function getDataById($id, $extra = [], $suff = '')
    {
        static::checkParams();

        $tmp_ids = is_array($id) ? $id : [$id];
        $ids = [];
        foreach ($tmp_ids as $v) {
            $v = (int)$v;
            if ($v > 0) {
                $ids[$v] = $v;
            }
        }

        if (empty($ids)) {
            return [];
        }

        $db = !empty($extra[self::EXTRA_FROM_W]) ? static::$_conf['db']['w'] : static::$_conf['db']['r'];

        if (!static::$_conf['_use_memcache'] || !empty($extra[self::EXTRA_NO_CACHE])) {
            $data = static::getFromDb($ids, $db, $suff);
            $data = array_column($data, null, static::$_conf['_primary_key']);

            $order_data = [];
            foreach ($ids as $v) {
                if (isset($data[$v]) && $data[$v][static::$_conf['_primary_key']] > 0) {
                    $order_data[$v] = $data[$v];
                }
            }
        } else {
            $mc_ids = [];
            $nocache_ids = [];
            foreach ($ids as $v) {
                $mc_ids[$v] = sprintf(static::$_conf['mckey'], $v);
                $nocache_ids[$v] = $v;
            }

            $mc = DB::memcache(static::$_conf['mc']);
            $data = $mc->get($mc_ids);

            $data_cache = [];
            if (is_array($data) && count($data) > 0) {
                foreach ($data as $v) {
                    $data_cache[$v[static::$_conf['_primary_key']]] = $v;
                    unset($nocache_ids[$v[static::$_conf['_primary_key']]]);
                }
            }

            if (count($nocache_ids) > 0) {
                $data = static::getFromDb($nocache_ids, $db, $suff);
                foreach ($data as $v) {
                    $mc->set(sprintf(static::$_conf['mckey'], $v[static::$_conf['_primary_key']]), $v, static::$_conf['mctime']);
                    $data_cache[$v[static::$_conf['_primary_key']]] = $v;
                }
            }

            $order_data = [];
            foreach ($mc_ids as $k => $v) {
                if (isset($data_cache[$k]) && $data_cache[$k][static::$_conf['_primary_key']] > 0) {
                    $order_data[$k] = $data_cache[$k];
                }
            }
        }

        if (is_numeric($id)) {
            $order_data = current($order_data);

            return is_array($order_data) ? $order_data : [];
        }

        return $order_data;
    }

    /**
     * 更新一条数据
     *
     * @param int    $id
     * @param array  $rows 见static::$_conf['_fields_array'], 不支持修改主键ID
     * @param string $suff 分表后缀名称
     * @return bool
     * @throws \Exception
     */
    public static function updateData($id, $rows = [], $suff = '')
    {
        $id = (int)$id;
        $rows = static::checkParams($rows);
        // 主键不支持更新
        unset($rows[static::$_conf['_primary_key']]);

        if ($id > 0) {
            $pdo = DB::pdo(static::$_conf['db']['w']);
            $ret = $pdo->update(static::$_conf['table'] . $suff, $rows, [
                static::$_conf['_primary_key'] => $id
            ]);
            if ($ret !== false && !empty(static::$_conf['mckey'])) {
                $mc = DB::memcache(static::$_conf['mc']);
                $mc->delete(sprintf(static::$_conf['mckey'], $id));
            }

            $pdo->close();
            DB::pdo(static::$_conf['db']['w'], 'force_close');
            $pdo = null;

            return true;
        }

        return false;
    }

    /**
     * 物理删除一条数据
     *
     * @param int    $id   主键ID
     * @param string $suff 分表后缀名称
     * @return bool
     * @throws \Exception
     */
    public static function delData($id, $suff = '')
    {
        $id = (int)$id;
        static::checkParams();

        if ($id > 0) {
            $pdo = DB::pdo(static::$_conf['db']['w']);
            $ret = $pdo->delete(static::$_conf['table'] . $suff, [
                static::$_conf['_primary_key'] => $id
            ]);

            if ($ret !== false && !empty(static::$_conf['mckey'])) {
                $mc = DB::memcache(static::$_conf['mc']);
                $mc->delete(sprintf(static::$_conf['mckey'], $id));
            }

            $pdo->close();
            DB::pdo(static::$_conf['db']['w'], 'force_close');
            $pdo = null;

            return true;
        }

        return false;
    }

    /**
     * 根据条件查询数据集合 (支持普通分页查询或者MC查询, 但只能用其中一种)
     *
     * @param array  $where 一个或多个查询条件, 支持格式参考 PDOQuery 中的where支持
     * @param array  $extra 可选额外参数, 分页查询和MC不能同时使用, 优先使用分页停用MC
     *
     *                     MC模式:
     *                     type: static::MOD_TYPE_MEMCACHE, MC模式, 只返回主键ID
     *                     mckey: 必选, XXX_{Uid}:{TestId}, 字段只能在传入条件中, 不存在替换为0
     *                     mctime: 可选, 0, 过期时间, 默认不过期
     *                     mc: 可选, MC连接, 默认用static::$_conf['mc'] > memcache_default
     *
     *                     DB模式
     *                     type: static::MOD_TYPE_DB, 数据模式, 返回查询的具体数据
     *                     fields: 可选, 字段, 逗号分隔, 默认仅为主键ID
     *                     get_count: 可选, 1:返回总数, 0 不返回总数(此时count返回-1), 默认1
     *                     get_result: 可选, 1: 返回结果, 0 不返回结果, 默认1
     *
     *                     公用参数:
     *                     db: 可选, static::$_conf['db']['r'] (默认), static::$_conf['db']['w']
     *                     offset: 可选, 偏移量, 默认0
     *                     limit:  可选, 每页数 ,默认0, 表示返回所有数据
     *                     order:  可选, 排序, 默认按主键降序
     *
     * @param string $suff  分表后缀名称
     * @return array ['count' => 总数, 'data' => [查询的数据]]
     * @throws \Exception
     */
    public static function getDataByFields($where, $extra = [], $suff = '')
    {
        static::checkParams();

        $data = [];

        $where_normal = [];
        foreach ($where as $k => $v) {
            $p = strpos($k, ' ');
            $q = $p === false ? $k : substr($k, 0, $p);
            if (!isset(static::$_conf['fields'][$q])) {
                unset($where[$k]);
            } else {
                $where_normal[$q] = $v;
            }
        }

        $extra['db'] = $extra['db'] == static::$_conf['db']['w'] ? static::$_conf['db']['w'] : static::$_conf['db']['r'];
        $extra['offset'] = empty($extra['offset']) ? 0 : max(0, $extra['offset']);
        $extra['limit'] = empty($extra['limit']) ? 0 : $extra['limit'];
        $extra['order'] = empty($extra['order']) ? static::$_conf['_primary_key'] . " DESC" : $extra['order'];
        $extra['get_count'] = $extra['get_count'] === 0 ? 0 : 1;
        $extra['get_result'] = $extra['get_result'] === 0 ? 0 : 1;

        if ($extra['type'] == static::MOD_TYPE_MEMCACHE) {
            if (empty($extra['mckey'])) {
                throw new \Exception("MOD_TYPE_MEMCACHE: mckey is empty!");
            }
            $extra['mctime'] = $extra['mctime'] > 0 ? $extra['mctime'] : 0;
            $extra['mc'] = empty($extra['mc']) ? (static::$_conf['mc'] ? static::$_conf['mc'] : DB::MEMCACHE_DEFAULT) : $extra['mc'];

            $key = preg_replace_callback("/\{(\w+)\}/", function ($matchs) use ($where_normal) {
                return $where_normal[$matchs[1]] ?? 0;
            }, $extra['mckey']);

            $mc = DB::memcache($extra['mc']);
            $data = $mc->get($key);
            if (!is_array($data)) {
                $pdo = DB::pdo($extra['db']);
                $ret = $pdo->find(static::$_conf['table'] . $suff, $where, static::$_conf['_primary_key'],
                    $extra['order']);
                $data = [];
                if (is_array($ret)) {
                    foreach ($ret as $v) {
                        $data[$v[static::$_conf['_primary_key']]] = $v[static::$_conf['_primary_key']];
                    }
                    $mc->set($key, $data, $extra['mctime']);
                }
            }
            $count = count($data);
            if ($extra['limit'] > 0) {
                $data = array_slice($data, $extra['offset'], $extra['limit'], true);
            }
        } else {
            $extra['fields'] = empty($extra['fields']) ? static::$_conf['_primary_key'] : $extra['fields'];
            $limit = null;
            if ($extra['limit'] > 0) {
                $limit = [$extra['offset'], $extra['limit']];
            }

            $pdo = DB::pdo($extra['db']);
            $count = -1;
            if ($extra['get_count'] == 1) {
                $count = $pdo->select('COUNT(' . static::$_conf['_primary_key'] . ')')->from(static::$_conf['table'] . $suff)->where($where)->getValue();
            }

            if ($extra['get_result'] == 1 && ($count == -1 || $count > 0)) {
                $data = $pdo->find(static::$_conf['table'] . $suff, $where, $extra['fields'], $extra['order'], $limit);
            }
        }

        if (!empty($pdo)) {
            $pdo->close();
            DB::pdo(static::$_conf['db']['w'], 'force_close');
            $pdo = null;
        }

        return ['count' => $count, 'data' => $data];
    }

    private static function getFromDb($ids, $db, $suff = '')
    {
        $pdo = DB::pdo($db);
        $ret = $pdo->find(static::$_conf['table'] . $suff, [
            static::$_conf['_primary_key'] . ' IN' => $ids
        ]);

        $pdo->close();
        DB::pdo(static::$_conf['db']['w'], 'force_close');
        $pdo = null;

        if (is_array($ret) && count($ret) > 0) {
            foreach ($ret as & $v) {
                foreach (static::$_conf['_fields_array'] as $x) {
                    if (!empty($v[$x])) {
                        $v[$x] = unserialize($v[$x]);
                    }
                }
            }

            return $ret;
        }

        return [];
    }

    private static function checkParams($rows = [])
    {
        $conf = &static::$_conf;
        if (!isset($conf['_primary_key']) || $conf['_primary_key'] == '') {
            $conf['_fields_array'] = [];
            $conf['mctime'] = empty($conf['mctime']) ? 0 : $conf['mctime'];
            $flag = true;

            foreach ($conf['fields'] as $k => $v) {
                if ($v == static::VAR_TYPE_KEY) {
                    $conf['_primary_key'] = $k;
                    $flag = false;
                } elseif ($v == static::VAR_TYPE_ARRAY) {
                    $conf['_fields_array'][$k] = $k;
                }
            }
            
            $conf['_use_memcache'] = !empty($conf['mc']) && !empty($conf['mckey']);

            if ($flag) {
                throw new \Exception('primary_key not set');
            }

            if (empty($conf['db']['w']) || empty($conf['db']['r'])) {
                throw new \Exception('db is empty!');
            }

            if (empty($conf['table'])) {
                throw new \Exception('tablename is empty!');
            }

            if (count($conf['fields']) == 0) {
                throw new \Exception('fields is empty!');
            }
        }

        return static::checkFields($rows);
    }

    private static function checkFields($rows = [])
    {
        foreach ($rows as $k => &$v) {
            if (is_numeric($k)) {
                $v = static::checkFields($v);
            } else {
                if (!isset(static::$_conf['fields'][$k])) {
                    unset($rows[$k]);
                } else {
                    switch (static::$_conf['fields'][$k]) {
                        case self::VAR_TYPE_KEY:
                        case self::VAR_TYPE_INT:
                            $v = (int)$v;
                            break;
                        case self::VAR_TYPE_ARRAY:
                            $v = is_array($v) ? serialize($v) : $v;
                            break;
                        case self::VAR_TYPE_PASSWORD:
                            $v = !empty($v) ? password_hash($v, PASSWORD_DEFAULT) : $v;
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