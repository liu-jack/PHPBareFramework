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
        self::CF_DB => [
            self::CF_DB_W => '',
            self::CF_DB_R => ''
        ],
        // 必选, 数据表名
        self::CF_TABLE => '',
        // 必选, 字段信息
        self::CF_FIELDS => [],
        // 可选, MC连接参数
        self::CF_MC => '',
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => '',
        // 可选, 超时时间, 默认不过期
        self::CF_MC_TIME => 86400
    ];

    // 配置变量名称
    const CF_DB = 'db';
    const CF_DB_W = 'w';
    const CF_DB_R = 'r';
    const CF_TABLE = 'table';
    const CF_FIELDS = 'fields';
    const CF_MC = 'mc';
    const CF_MC_KEY = 'mckey';
    const CF_MC_TIME = 'mctime';
    const CF_PRIMARY_KEY = '_primary_key';
    const CF_FIELDS_ARRAY = '_fields_array';
    const CF_USE_MC = '_use_memcache';

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

    const EXTRA_DB = 'db';
    const EXTRA_FIELDS = 'fields';
    const EXTRA_OFFSET = 'offset';
    const EXTRA_LIMIT = 'limit';
    const EXTRA_ORDER = 'order';
    const EXTRA_GET_COUNT = 'get_count';
    const EXTRA_GET_RET = 'get_result';
    const EXTRA_MOD_TYPE = 'type';
    const EXTRA_MC = 'mc';
    const EXTRA_MC_KEY = 'mckey';
    const EXTRA_MC_TIME = 'mctime';

    // 模块类型, MC模式
    const MOD_TYPE_MEMCACHE = 1;
    // 模块类型, DB模式
    const MOD_TYPE_DB = 2;

    /**
     * 添加一条或多条数据
     *
     * @param array  $rows   要写入的数组(支持多行写入), 单个[data], 多个[[data1],[data2],...]
     * @param bool   $ignore 是否使用IGNORE, 默认不使用
     * @param string $suffix 分表后缀名称
     * @return bool|int|string 成功返回LastInsertId, 未插入数据返回0, 插入失败返回false
     * @throws \Exception
     */
    public static function addData($rows, $ignore = false, $suffix = '')
    {
        $rows = static::checkParams($rows);
        $options = $ignore ? ['ignore' => true] : [];

        $pdo = DB::pdo(static::$_conf[static::CF_DB][static::CF_DB_W]);
        $ret = $pdo->insert(static::$_conf[static::CF_TABLE] . $suffix, $rows, $options);
        if ($ret !== false) {
            if ($ret > 0) {
                return $pdo->lastInsertId();
            } else {
                return 0;
            }
        }

        $pdo->close();
        $pdo = null;

        return false;

    }

    /**
     * 获得一条或多条数据
     *
     * @param int|array $id     主键ID
     * @param array     $extra  见 self::EXTRA_*
     * @param string    $suffix 分表后缀名称
     * @return array 单条返回['feild1'=>'value1',..], 多条返回['主键ID1' => [...],]
     * @throws \Exception
     */
    public static function getDataById($id, $extra = [], $suffix = '')
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

        $db = !empty($extra[self::EXTRA_FROM_W]) ? static::$_conf[static::CF_DB][static::CF_DB_W] : static::$_conf[static::CF_DB][static::CF_DB_R];

        if (!static::$_conf[static::CF_USE_MC] || !empty($extra[self::EXTRA_NO_CACHE])) {
            $data = static::getFromDb($ids, $db, $suffix);
            $data = array_column($data, null, static::$_conf[static::CF_PRIMARY_KEY]);

            $order_data = [];
            foreach ($ids as $v) {
                if (isset($data[$v]) && $data[$v][static::$_conf[static::CF_PRIMARY_KEY]] > 0) {
                    $order_data[$v] = $data[$v];
                }
            }
        } else {
            $mc_ids = [];
            $nocache_ids = [];
            foreach ($ids as $v) {
                $mc_ids[$v] = sprintf(static::$_conf[static::CF_MC_KEY], $v);
                $nocache_ids[$v] = $v;
            }

            $mc = DB::memcache(static::$_conf[static::CF_MC]);
            $data = $mc->get($mc_ids);

            $data_cache = [];
            if (is_array($data) && count($data) > 0) {
                foreach ($data as $v) {
                    $data_cache[$v[static::$_conf[static::CF_PRIMARY_KEY]]] = $v;
                    unset($nocache_ids[$v[static::$_conf[static::CF_PRIMARY_KEY]]]);
                }
            }

            if (count($nocache_ids) > 0) {
                $data = static::getFromDb($nocache_ids, $db, $suffix);
                foreach ($data as $v) {
                    $mc->set(sprintf(static::$_conf[static::CF_MC_KEY], $v[static::$_conf[static::CF_PRIMARY_KEY]]), $v,
                        static::$_conf[static::CF_MC_TIME]);
                    $data_cache[$v[static::$_conf[static::CF_PRIMARY_KEY]]] = $v;
                }
            }

            $order_data = [];
            foreach ($mc_ids as $k => $v) {
                if (isset($data_cache[$k]) && $data_cache[$k][static::$_conf[static::CF_PRIMARY_KEY]] > 0) {
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
     * @param array  $rows   见static::$_conf['_fields_array'], 不支持修改主键ID
     * @param string $suffix 分表后缀名称
     * @return bool
     * @throws \Exception
     */
    public static function updateData($id, $rows = [], $suffix = '')
    {
        $id = (int)$id;
        $rows = static::checkParams($rows);
        // 主键不支持更新
        unset($rows[static::$_conf[static::CF_PRIMARY_KEY]]);

        if ($id > 0) {
            $pdo = DB::pdo(static::$_conf[static::CF_DB][static::CF_DB_W]);
            $ret = $pdo->update(static::$_conf[static::CF_TABLE] . $suffix, $rows, [
                static::$_conf[static::CF_PRIMARY_KEY] => $id
            ]);
            if ($ret !== false && !empty(static::$_conf[static::CF_MC_KEY])) {
                $mc = DB::memcache(static::$_conf[static::CF_MC]);
                $mc->delete(sprintf(static::$_conf[static::CF_MC_KEY], $id));
            }

            $pdo->close();
            $pdo = null;

            return true;
        }

        return false;
    }

    /**
     * 物理删除一条数据
     *
     * @param int    $id     主键ID
     * @param string $suffix 分表后缀名称
     * @return bool
     * @throws \Exception
     */
    public static function delData($id, $suffix = '')
    {
        $id = (int)$id;
        static::checkParams();

        if ($id > 0) {
            $pdo = DB::pdo(static::$_conf[static::CF_DB][static::CF_DB_W]);
            $ret = $pdo->delete(static::$_conf[static::CF_TABLE] . $suffix, [
                static::$_conf[static::CF_PRIMARY_KEY] => $id
            ]);

            if ($ret !== false && !empty(static::$_conf[static::CF_MC_KEY])) {
                $mc = DB::memcache(static::$_conf[static::CF_MC]);
                $mc->delete(sprintf(static::$_conf[static::CF_MC_KEY], $id));
            }

            $pdo->close();
            $pdo = null;

            return true;
        }

        return false;
    }

    /**
     * 根据条件查询数据集合 (支持普通分页查询或者MC查询, 但只能用其中一种)
     *
     * @param array  $where  一个或多个查询条件, 支持格式参考 PDOQuery 中的where支持
     * @param array  $extra  可选额外参数, 分页查询和MC不能同时使用, 优先使用分页停用MC
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
     * @param string $suffix 分表后缀名称
     * @return array ['count' => 总数, 'data' => [查询的数据]]
     * @throws \Exception
     */
    public static function getDataByFields($where, $extra = [], $suffix = '')
    {
        static::checkParams();

        $data = [];

        $where_normal = [];
        foreach ($where as $k => $v) {
            $p = strpos($k, ' ');
            $q = $p === false ? $k : substr($k, 0, $p);
            if (!isset(static::$_conf[static::CF_FIELDS][$q])) {
                unset($where[$k]);
            } else {
                $where_normal[$q] = $v;
            }
        }

        $extra[static::EXTRA_DB] = $extra[static::EXTRA_DB] == static::$_conf[static::CF_DB][static::CF_DB_W] ? static::$_conf[static::CF_DB][static::CF_DB_W] : static::$_conf[static::CF_DB][static::CF_DB_R];
        $extra[static::EXTRA_OFFSET] = empty($extra[static::EXTRA_OFFSET]) ? 0 : max(0, $extra[static::EXTRA_OFFSET]);
        $extra[static::EXTRA_LIMIT] = empty($extra[static::EXTRA_LIMIT]) ? 0 : $extra[static::EXTRA_LIMIT];
        $extra[static::EXTRA_ORDER] = empty($extra[static::EXTRA_ORDER]) ? static::$_conf[static::CF_PRIMARY_KEY] . " DESC" : $extra[static::EXTRA_ORDER];
        $extra[static::EXTRA_GET_COUNT] = $extra[static::EXTRA_GET_COUNT] === 0 ? 0 : 1;
        $extra[static::EXTRA_GET_RET] = $extra[static::EXTRA_GET_RET] === 0 ? 0 : 1;

        if ($extra[static::EXTRA_MOD_TYPE] == static::MOD_TYPE_MEMCACHE) {
            if (empty($extra[static::EXTRA_MC_KEY])) {
                throw new \Exception("MOD_TYPE_MEMCACHE: mckey is empty!");
            }
            $extra[static::EXTRA_MC_TIME] = $extra[static::EXTRA_MC_TIME] > 0 ? $extra[static::EXTRA_MC_TIME] : 0;
            $extra[static::EXTRA_MC] = empty($extra[static::EXTRA_MC]) ? (static::$_conf[static::CF_MC] ? static::$_conf[static::CF_MC] : DB::MEMCACHE_DEFAULT) : $extra[static::EXTRA_MC];

            $key = preg_replace_callback("/\{(\w+)\}/", function ($matchs) use ($where_normal) {
                return $where_normal[$matchs[1]] ?? 0;
            }, $extra[static::EXTRA_MC_KEY]);

            $mc = DB::memcache($extra[static::EXTRA_MC]);
            $data = $mc->get($key);
            if (!is_array($data)) {
                $pdo = DB::pdo($extra[static::EXTRA_DB]);
                $ret = $pdo->find(static::$_conf[static::CF_TABLE] . $suffix, $where,
                    static::$_conf[static::CF_PRIMARY_KEY], $extra[static::EXTRA_ORDER]);
                $data = [];
                if (is_array($ret)) {
                    foreach ($ret as $v) {
                        $data[$v[static::$_conf[static::CF_PRIMARY_KEY]]] = $v[static::$_conf[static::CF_PRIMARY_KEY]];
                    }
                    $mc->set($key, $data, $extra[static::EXTRA_MC_TIME]);
                }
            }
            $count = count($data);
            if ($extra[static::EXTRA_LIMIT] > 0) {
                $data = array_slice($data, $extra[static::EXTRA_OFFSET], $extra[static::EXTRA_LIMIT], true);
            }
        } else {
            $extra[static::EXTRA_FIELDS] = empty($extra[static::EXTRA_FIELDS]) ? static::$_conf[static::CF_PRIMARY_KEY] : $extra[static::EXTRA_FIELDS];
            $limit = null;
            if ($extra[static::EXTRA_LIMIT] > 0) {
                $limit = [$extra[static::EXTRA_OFFSET], $extra[static::EXTRA_LIMIT]];
            }

            $pdo = DB::pdo($extra[static::EXTRA_DB]);
            $count = -1;
            if ($extra[static::EXTRA_GET_COUNT] == 1) {
                $count = $pdo->select('COUNT(' . static::$_conf[static::CF_PRIMARY_KEY] . ')')->from(static::$_conf[static::CF_TABLE] . $suffix)->where($where)->getValue();
            }

            if ($extra[static::EXTRA_GET_RET] == 1 && ($count == -1 || $count > 0)) {
                $data = $pdo->find(static::$_conf[static::CF_TABLE] . $suffix, $where, $extra[static::EXTRA_FIELDS],
                    $extra[static::EXTRA_ORDER], $limit);
            }
        }

        if (!empty($pdo)) {
            $pdo->close();
            $pdo = null;
        }

        return ['count' => $count, 'data' => $data];
    }

    private static function getFromDb($ids, $db, $suffix = '')
    {
        $pdo = DB::pdo($db);
        $ret = $pdo->find(static::$_conf[static::CF_TABLE] . $suffix, [
            static::$_conf[static::CF_PRIMARY_KEY] . ' IN' => $ids
        ]);

        $pdo->close();
        $pdo = null;

        if (is_array($ret) && count($ret) > 0) {
            foreach ($ret as & $v) {
                foreach (static::$_conf[static::CF_FIELDS_ARRAY] as $x) {
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
        if (!isset($conf[static::CF_PRIMARY_KEY]) || $conf[static::CF_PRIMARY_KEY] == '') {
            $conf[static::CF_FIELDS_ARRAY] = [];
            $conf[static::CF_MC_TIME] = empty($conf[static::CF_MC_TIME]) ? 0 : $conf[static::CF_MC_TIME];
            $flag = true;

            foreach ($conf[static::CF_FIELDS] as $k => $v) {
                if (is_array($v)) {
                    $v = $v[static::FIELD_VAR_TYPE];
                }
                if ($v == static::VAR_TYPE_KEY) {
                    $conf[static::CF_PRIMARY_KEY] = $k;
                    $flag = false;
                } elseif ($v == static::VAR_TYPE_ARRAY) {
                    $conf[static::CF_FIELDS_ARRAY][$k] = $k;
                }
            }

            $conf[static::CF_USE_MC] = !empty($conf[static::CF_MC]) && !empty($conf[static::CF_MC_KEY]);

            if ($flag) {
                throw new \Exception('primary_key not set');
            }

            if (empty($conf[static::CF_DB][static::CF_DB_W]) || empty($conf[static::CF_DB][static::CF_DB_R])) {
                throw new \Exception('db is empty!');
            }

            if (empty($conf[static::CF_TABLE])) {
                throw new \Exception('tablename is empty!');
            }

            if (count($conf[static::CF_FIELDS]) == 0) {
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
                if (!isset(static::$_conf[static::CF_FIELDS][$k])) {
                    unset($rows[$k]);
                } else {
                    if (is_array(static::$_conf[static::CF_FIELDS][$k])) {
                        $type = static::$_conf[static::CF_FIELDS][$k][static::FIELD_VAR_TYPE];
                    } else {
                        $type = static::$_conf[static::CF_FIELDS][$k];
                    }
                    switch ($type) {
                        case static::VAR_TYPE_KEY:
                        case static::VAR_TYPE_INT:
                            $v = (int)$v;
                            break;
                        case static::VAR_TYPE_ARRAY:
                            $v = is_array($v) ? serialize($v) : $v;
                            break;
                        case static::VAR_TYPE_PASSWORD:
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