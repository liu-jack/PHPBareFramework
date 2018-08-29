<?php
/**
 * 基类数据模型
 *
 * @author camfee<camfee@yeah.net>
 * @since  v1.0 2016.09.25
 */

namespace Bare\M;

use Bare\DB;

defined('ROOT_PATH') or exit('Access deny');

abstract class Model
{
    const FIELD_VAR_TYPE = 'var_type';
    // 配置变量名称
    const CF_DB = 'db';
    const CF_DB_W = 'w';
    const CF_DB_R = 'r';
    const CF_TABLE = 'table';
    const CF_FIELDS = 'fields';
    const CF_MC = 'mc';
    const CF_MC_KEY = 'mc_key';
    const CF_MC_TIME = 'mc_expire';
    const CF_RD = 'redis';
    const CF_RD_INDEX = 'redis_index';
    const CF_RD_TIME = 'redis_expire';
    const CF_RD_KEY = 'redis_key';
    const CF_PRIMARY_KEY = '_primary_key';
    const CF_FIELDS_ARRAY = '_fields_array';
    const CF_FIELDS_JSON = '_fields_json';
    const CF_USE_MC = '_use_memcache';
    const CF_USE_RD = '_use_redis';

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
        self::CF_MC_TIME => 86400,
        // 可选, redis (来自DB配置), w: 写, r: 读
        self::CF_RD => [
            self::CF_DB_W => '',
            self::CF_DB_R => '',
            self::CF_RD_INDEX => 0,
            self::CF_RD_TIME => 86400,
            self::CF_RD_KEY => '', // 可选, redis KEY, "KeyName:%d", %d会用主键ID替代
        ],
    ];

    /**
     * @see \Bare\M\Model::add() 新增
     * @see \Bare\M\Model::update() 更新
     * @see \Bare\M\Model::getInfoByIds() 按主键id查询
     * @see \Bare\M\Model::getList() 条件查询
     * @see \Bare\M\Model::delete() 删除
     */

    // 主键/字段类型
    const VAR_TYPE_KEY = 'PRIMARY KEY';
    const VAR_TYPE_INT = 'int';
    const VAR_TYPE_FLOAT = 'float';
    const VAR_TYPE_STRING = 'string';
    const VAR_TYPE_ARRAY = 'array';
    const VAR_TYPE_JSON = 'json';
    const VAR_TYPE_PASSWORD = 'password';
    const VAR_TYPE_HIDDEN = 'hidden';
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
    const EXTRA_MC_KEY = 'mc_key';
    const EXTRA_MC_TIME = 'mc_expire';
    const EXTRA_LIST_KEY = 'list_key_field';
    const EXTRA_LIST_VAL = 'list_val_field';
    const EXTRA_RD_KEY = 'redis_key';
    const EXTRA_RD_TIME = 'redis_expire';

    // 模块类型, MC模式
    const MOD_TYPE_MEMCACHE = 1;
    // 模块类型, DB模式
    const MOD_TYPE_DB = 2;
    // 模块类型, REDIS模式
    const MOD_TYPE_REDIS = 3;

    //{{{ curd基础方法

    // 列表缓存数组 [list_key{field1}{field2} => [type => 1, fields => [field1,field2]]] 0:mc 1:redis 默认0
    const CACHE_LIST_TYPE = 'type';
    const CACHE_LIST_TYPE_MC = 0;
    const CACHE_LIST_TYPE_REDIS = 1;
    const CACHE_LIST_FIELDS = 'fields';
    const UPDATE_DEL_CACHE_LIST = false; // 更新是否清除列表缓存
    protected static $_cache_list_keys;
    const MC_LIST_DEMO_1 = 'MC_LIST_DEMO_1';
    const MC_LIST_DEMO_2 = 'MC_LIST_DEMO_2:{Id}';
    const MC_LIST_DEMO_3 = 'MC_LIST_DEMO_3:{Id}:{UserId}';
    private static $_cache_list_keys_demo = [
        self::MC_LIST_DEMO_1 => self::CACHE_LIST_TYPE_MC,
        self::MC_LIST_DEMO_2 => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_MC,
            self::CACHE_LIST_FIELDS => 'Id',
        ],
        self::MC_LIST_DEMO_3 => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_MC,
            self::CACHE_LIST_FIELDS => ['Id', 'UserId']
        ],
    ];
    // 新增必须字段 field => 1
    protected static $_add_must_fields;
    // 不可修改字段 field => 1
    protected static $_un_modify_fields = [
        'Id' => 1,
    ];
    protected static $_suffix_field;

    /**
     * 获取列表
     *
     * @param $id
     * @return array
     */
    protected static function getDemoList($id)
    {
        $where = [
            'Id' => $id,
            'Status' => 1
        ];
        $extra = [
            self::EXTRA_MOD_TYPE => self::MOD_TYPE_MEMCACHE,
            self::EXTRA_MC_KEY => self::MC_LIST_DEMO_2,
            self::EXTRA_MC_TIME => 86400,
            self::EXTRA_FIELDS => '*',
            self::EXTRA_LIST_KEY => 'Id',
            self::EXTRA_LIST_VAL => '',
            self::EXTRA_ORDER => '',
        ];
        try {
            $data = self::getDataByFields($where, $extra);
        } catch (\Exception $e) {
            $cnt = $e->getCode() . ':' . $e->getMessage();
            IS_ONLINE ? error_logs($cnt) : exit($cnt);
        }

        return $data ?? [];
    }

    /**
     * 获取Id列表
     *
     * @param     $uid
     * @param int $offset
     * @param int $limit
     * @return array
     */
    protected static function getDemoIdList($uid, $offset = 0, $limit = 10)
    {
        $where = [
            'Id' => $uid,
            'Status' => 1
        ];
        $extra = [
            self::EXTRA_MOD_TYPE => self::MOD_TYPE_MEMCACHE,
            self::EXTRA_MC_KEY => self::MC_LIST_DEMO_2,
            self::EXTRA_MC_TIME => 86400,
            self::EXTRA_ORDER => '',
        ];
        try {
            $data = self::getDataByFields($where, $extra);
        } catch (\Exception $e) {
            $cnt = $e->getCode() . ':' . $e->getMessage();
            IS_ONLINE ? error_logs($cnt) : exit($cnt);
        }

        if (!empty($data['data'])) {
            $data['data'] = array_slice($data['data'], $offset, $limit);
        }

        return $data;
    }

    /**
     * 添加
     *
     * @param      $data
     * @param bool $ignore
     * @return bool|int|string
     */
    public static function add($data, $ignore = true)
    {
        if (!empty(static::$_add_must_fields) && count(array_diff_key(static::$_add_must_fields, $data)) > 0) {
            return false;
        }
        $ret = false;
        if (!empty($data)) {
            if (empty($data[0]) && empty($data['CreateTime'])) {
                $data['CreateTime'] = date('Y-m-d H:i:s');
            }
            $ret = static::addData($data, $ignore, table($data[static::$_suffix_field] ?? ''));
            if (!empty(static::$_cache_list_keys) && $ret !== false) {
                if (empty($data[0])) {
                    $data[static::$_conf[static::CF_PRIMARY_KEY]] = $ret;
                } else {
                    $data = $data[0];
                }
                static::delListCache($data);
            }
        }

        return $ret;
    }

    /**
     * 更新
     *
     * @param      $id
     * @param      $data
     * @param bool $filter true：直接过滤不可修改字段 false：中断修改
     * @return bool
     */
    public static function update($id, $data, $filter = true)
    {
        $ret = false;
        $suffix = table($data[static::$_suffix_field] ?? '');
        if (!empty(static::$_un_modify_fields)) {
            if ($filter) {
                $data = array_diff_key($data, static::$_un_modify_fields);
            } else {
                $intersect_key = array_intersect_key($data, static::$_un_modify_fields);
                if (count($intersect_key) > 0) {
                    return false;
                }
            }
        }
        if ($id > 0 && !empty($data)) {
            $ret = static::updateData($id, $data, $suffix);
            if (!empty(static::$_cache_list_keys) && $ret !== false && !empty(static::UPDATE_DEL_CACHE_LIST)) {
                $info = static::getInfoByIds($id);
                if (!empty($info)) {
                    static::delListCache($info);
                }
            }
        }

        return $ret;
    }

    /**
     * 根据id获取
     *
     * @param        $ids
     * @param array  $extra
     * @param string $suffix
     * @return array
     */
    public static function getInfoByIds($ids, $extra = [], $suffix = '')
    {
        if (empty($ids)) {
            return [];
        }
        try {
            $data = static::getDataById($ids, $extra, $suffix);
        } catch (\Exception $e) {
            $cnt = $e->getCode() . ':' . $e->getMessage();
            IS_ONLINE ? error_logs($cnt) : exit($cnt);
        }

        return $data ?? [];
    }

    /**
     * 获取列表
     *
     * @param array  $where
     * @param int    $offset
     * @param int    $limit
     * @param string $fields
     * @param string $order
     * @param string $suffix
     * @return array
     */
    public static function getList($where = [], $offset = 0, $limit = 0, $fields = '*', $order = '', $suffix = '')
    {
        $extra = [
            static::EXTRA_FIELDS => $fields,
            static::EXTRA_OFFSET => $offset,
            static::EXTRA_LIMIT => $limit,
            static::EXTRA_ORDER => $order,
        ];
        try {
            $data = static::getDataByFields($where, $extra, $suffix);
        } catch (\Exception $e) {
            $cnt = $e->getCode() . ':' . $e->getMessage();
            IS_ONLINE ? error_logs($cnt) : exit($cnt);
        }

        return $data ?? [];
    }

    /**
     * 删除
     *
     * @param        $id
     * @param string $suffix
     * @return bool
     */
    public static function delete($id, $suffix = '')
    {
        $ret = false;
        if ($id > 0) {
            if (!empty(static::$_cache_list_keys)) {
                $info = static::getInfoByIds($id);
            }
            try {
                $ret = static::delData($id, $suffix);
            } catch (\Exception $e) {
                $cnt = $e->getCode() . ':' . $e->getMessage();
                IS_ONLINE ? error_logs($cnt) : exit($cnt);
            }
            if ($ret !== false && !empty($info)) {
                static::delListCache($info);
            }
        }

        return $ret;
    }

    /**
     * 清除列表缓存
     *
     * @param array $info
     */
    protected static function delListCache($info = [])
    {
        if (!empty(static::$_cache_list_keys)) {
            $mc_key = $rd_key = [];
            foreach (static::$_cache_list_keys as $k => $v) {
                if (!is_array($v)) {
                    if ($v == static::CACHE_LIST_TYPE_REDIS) {
                        $rd_key[] = $k;
                    } else {
                        $mc_key[] = $k;
                    }
                } else {
                    if (!is_array($v[static::CACHE_LIST_FIELDS])) {
                        $field = $v[static::CACHE_LIST_FIELDS];
                        $key = str_replace('{' . $field . '}', $info[$field], $k);
                    } else {
                        $search = $replace = [];
                        foreach ($v[static::CACHE_LIST_FIELDS] as $vv) {
                            $search[] = '{' . $vv . '}';
                            $replace[] = $info[$vv];
                        }
                        $key = str_replace($search, $replace, $k);
                    }
                    if (isset($v[static::CACHE_LIST_TYPE]) && $v[static::CACHE_LIST_TYPE] == static::CACHE_LIST_TYPE_REDIS) {
                        $rd_key[] = $key;
                    } else {
                        $mc_key[] = $key;
                    }
                }
            }
            if (!empty($mc_key)) {
                static::getMC()->delete($mc_key);
            }
            if (!empty($rd_key)) {
                static::getRedis(true)->delete($rd_key);
            }
        }
    }

    //}}}

    /**
     * 添加一条或多条数据
     *
     * @param array  $rows   要写入的数组(支持多行写入), 单个[data], 多个[[data1],[data2],...]
     * @param bool   $ignore 是否使用IGNORE, 默认不使用
     * @param string $suffix 分表后缀名称
     * @return bool|int|string 成功返回LastInsertId, 未插入数据返回0, 插入失败返回false
     */
    protected static function addData($rows, $ignore = false, $suffix = '')
    {
        try {
            $rows = static::checkParams($rows);
        } catch (\Exception $e) {
            $cnt = $e->getCode() . ':' . $e->getMessage();
            IS_ONLINE ? error_logs($cnt) : exit($cnt);
        }
        $options = $ignore ? ['ignore' => true] : [];
        $pdo = static::getPdo(true);
        $ret = $pdo->insert(static::$_conf[static::CF_TABLE] . $suffix, $rows, $options);
        if ($ret !== false) {
            if ($ret > 0) {
                $id = $pdo->lastInsertId();
            } else {
                $id = 0;
            }
        }
        //$pdo->close();
        //DB::pdo(static::$_conf[static::CF_DB][static::CF_DB_W], 'force_close');
        $pdo = null;

        return isset($id) ? $id : false;

    }

    /**
     * 获得一条或多条数据
     *
     * @param int|array $id     主键ID
     * @param array     $extra  见 self::EXTRA_*
     * @param string    $suffix 分表后缀名称
     * @return array 单条返回['feild1'=>'value1',..], 多条返回['主键ID1' => [...],]
     */
    protected static function getDataById($id, $extra = [], $suffix = '')
    {
        try {
            static::checkParams();
        } catch (\Exception $e) {
            $cnt = $e->getCode() . ':' . $e->getMessage();
            IS_ONLINE ? error_logs($cnt) : exit($cnt);
        }
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
        if ((!static::$_conf[static::CF_USE_RD] && !static::$_conf[static::CF_USE_MC]) || !empty($extra[self::EXTRA_NO_CACHE])) {
            $data = static::getFromDb($ids, $db, $suffix);
            $data = array_column($data, null, static::$_conf[static::CF_PRIMARY_KEY]);
            $order_data = [];
            foreach ($ids as $v) {
                if (isset($data[$v]) && $data[$v][static::$_conf[static::CF_PRIMARY_KEY]] > 0) {
                    $order_data[$v] = $data[$v];
                }
            }
        } else {
            $mc_ids = $nocache_ids = [];
            if (static::$_conf[static::CF_USE_RD]) {
                foreach ($ids as $v) {
                    $mc_ids[$v] = sprintf(static::$_conf[static::CF_RD][static::CF_RD_KEY], $v);
                    $nocache_ids[$v] = $v;
                }
                $mc = static::getRedis(true);
                $data = $mc->mget($mc_ids);
            } else {
                foreach ($ids as $v) {
                    $mc_ids[$v] = sprintf(static::$_conf[static::CF_MC_KEY], $v);
                    $nocache_ids[$v] = $v;
                }
                $mc = static::getMC();
                $data = $mc->get($mc_ids);
            }
            $data_cache = [];
            if (is_array($data) && count($data) > 0) {
                foreach ($data as $v) {
                    if (static::$_conf[static::CF_USE_RD]) {
                        $v = unserialize($v);
                    }
                    $data_cache[$v[static::$_conf[static::CF_PRIMARY_KEY]]] = $v;
                    unset($nocache_ids[$v[static::$_conf[static::CF_PRIMARY_KEY]]]);
                }
            }
            if (count($nocache_ids) > 0) {
                $data = static::getFromDb($nocache_ids, $db, $suffix);
                foreach ($data as $v) {
                    if (static::$_conf[static::CF_USE_RD]) {
                        $mc->set(sprintf(static::$_conf[static::CF_RD][static::CF_RD_KEY], $v[static::$_conf[static::CF_PRIMARY_KEY]]), serialize($v), static::$_conf[static::CF_RD][static::CF_RD_TIME]);
                    } else {
                        $mc->set(sprintf(static::$_conf[static::CF_MC_KEY], $v[static::$_conf[static::CF_PRIMARY_KEY]]), $v, static::$_conf[static::CF_MC_TIME]);
                    }

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
     */
    protected static function updateData($id, $rows = [], $suffix = '')
    {
        $id = (int)$id;
        try {
            $rows = static::checkParams($rows);
        } catch (\Exception $e) {
            $cnt = $e->getCode() . ':' . $e->getMessage();
            IS_ONLINE ? error_logs($cnt) : exit($cnt);
        }
        // 主键不支持更新
        unset($rows[static::$_conf[static::CF_PRIMARY_KEY]]);
        if ($id > 0) {
            $pdo = static::getPdo(true);
            $ret = $pdo->update(static::$_conf[static::CF_TABLE] . $suffix, $rows, [
                static::$_conf[static::CF_PRIMARY_KEY] => $id
            ]);
            if ($ret !== false) {
                if (!empty(static::$_conf[static::CF_MC_KEY])) {
                    $mc = static::getMC();
                    $mc->delete(sprintf(static::$_conf[static::CF_MC_KEY], $id));
                }
                if (!empty(static::$_conf[static::CF_RD][static::CF_RD_KEY])) {
                    $redis = static::getRedis(true);
                    $redis->delete(sprintf(static::$_conf[static::CF_RD][static::CF_RD_KEY], $id));
                }
            }
            //$pdo->close();
            //DB::pdo(static::$_conf[static::CF_DB][static::CF_DB_W], 'force_close');
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
     */
    protected static function delData($id, $suffix = '')
    {
        $id = (int)$id;
        try {
            static::checkParams();
        } catch (\Exception $e) {
            $cnt = $e->getCode() . ':' . $e->getMessage();
            IS_ONLINE ? error_logs($cnt) : exit($cnt);
        }
        if ($id > 0) {
            $pdo = static::getPdo(true);
            $ret = $pdo->delete(static::$_conf[static::CF_TABLE] . $suffix, [
                static::$_conf[static::CF_PRIMARY_KEY] => $id
            ]);
            if ($ret !== false) {
                if (!empty(static::$_conf[static::CF_MC_KEY])) {
                    $mc = static::getMC();
                    $mc->delete(sprintf(static::$_conf[static::CF_MC_KEY], $id));
                }
                if (!empty(static::$_conf[static::CF_RD][static::CF_RD_KEY])) {
                    $redis = static::getRedis(true);
                    $redis->delete(sprintf(static::$_conf[static::CF_RD][static::CF_RD_KEY], $id));
                }
            }
            //$pdo->close();
            //DB::pdo(static::$_conf[static::CF_DB][static::CF_DB_W], 'force_close');
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
     *                     REDIS模式:
     *                     type: static::MOD_TYPE_REDIS, REDIS模式, 只返回主键ID
     *                     rediskey: 必选, XXX_{Uid}:{TestId}, 字段只能在传入条件中, 不存在替换为0
     *                     redistime: 可选, 0, 过期时间, 默认不过期
     *                     redis: 可选, redis连接, 默认用static::$_conf['redis']['r'] > redis_default
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
     */
    protected static function getDataByFields($where, $extra = [], $suffix = '')
    {
        try {
            static::checkParams();
        } catch (\Exception $e) {
            $cnt = $e->getCode() . ':' . $e->getMessage();
            IS_ONLINE ? error_logs($cnt) : exit($cnt);
        }
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
        if ($extra[static::EXTRA_MOD_TYPE] == static::MOD_TYPE_REDIS) {
            if (empty($extra[static::EXTRA_RD_KEY])) {
                $cnt = "MOD_TYPE_REDIS: redis_key is empty!";
                IS_ONLINE ? error_logs($cnt) : exit($cnt);
            }
            $extra[static::EXTRA_RD_TIME] = $extra[static::EXTRA_RD_TIME] > 0 ? $extra[static::EXTRA_RD_TIME] : 86400;
            $key = preg_replace_callback("/\{(\w+)\}/", function ($matchs) use ($where_normal) {
                return $where_normal[$matchs[1]] ?? 0;
            }, $extra[static::EXTRA_RD_KEY]);
            $redis = static::getRedis(true);
            $data = $redis->getS($key);
            if (!is_array($data)) {
                $pdo = DB::pdo($extra[static::EXTRA_DB]);
                $ret = $pdo->find(static::$_conf[static::CF_TABLE] . $suffix, $where, static::$_conf[static::CF_PRIMARY_KEY], $extra[static::EXTRA_ORDER]);
                $data = [];
                if (is_array($ret)) {
                    if (!empty($extra[self::EXTRA_LIST_KEY])) {
                        if (!empty($extra[self::EXTRA_LIST_VAL])) {
                            if (is_array($extra[self::EXTRA_LIST_VAL])) {
                                foreach ($ret as $v) {
                                    foreach ($extra[self::EXTRA_LIST_VAL] as $lv) {
                                        $data[$v[$extra[self::EXTRA_LIST_KEY]]][$lv] = $v[$lv];
                                    }
                                }
                            } else {
                                foreach ($ret as $v) {
                                    $data[$v[$extra[self::EXTRA_LIST_KEY]]] = $v[$extra[self::EXTRA_LIST_VAL]];
                                }
                            }
                        } else {
                            foreach ($ret as $v) {
                                $data[$v[$extra[self::EXTRA_LIST_KEY]]] = $v;
                            }
                        }
                    } else {
                        foreach ($ret as $v) {
                            $data[$v[static::$_conf[static::CF_PRIMARY_KEY]]] = $v[static::$_conf[static::CF_PRIMARY_KEY]];
                        }
                    }
                    $redis->setS($key, $data, $extra[static::EXTRA_RD_TIME]);
                }
            }
            $count = count($data);
            if ($extra[static::EXTRA_LIMIT] > 0) {
                $data = array_slice($data, $extra[static::EXTRA_OFFSET], $extra[static::EXTRA_LIMIT], true);
            }
        } elseif ($extra[static::EXTRA_MOD_TYPE] == static::MOD_TYPE_MEMCACHE) {
            if (empty($extra[static::EXTRA_MC_KEY])) {
                $cnt = "MOD_TYPE_MEMCACHE: mckey is empty!";
                IS_ONLINE ? error_logs($cnt) : exit($cnt);
            }
            $extra[static::EXTRA_MC_TIME] = $extra[static::EXTRA_MC_TIME] > 0 ? $extra[static::EXTRA_MC_TIME] : 0;
            $extra[static::EXTRA_MC] = empty($extra[static::EXTRA_MC]) ? (static::$_conf[static::CF_MC] ? static::$_conf[static::CF_MC] : DB::MEMCACHE_DEFAULT) : $extra[static::EXTRA_MC];
            $key = preg_replace_callback("/\{(\w+)\}/", function ($matchs) use ($where_normal) {
                return $where_normal[$matchs[1]] ?? 0;
            }, $extra[static::EXTRA_MC_KEY]);
            $mc = static::getMC($extra[static::EXTRA_MC]);
            $data = $mc->get($key);
            if (!is_array($data)) {
                $pdo = DB::pdo($extra[static::EXTRA_DB]);
                $ret = $pdo->find(static::$_conf[static::CF_TABLE] . $suffix, $where, static::$_conf[static::CF_PRIMARY_KEY], $extra[static::EXTRA_ORDER]);
                $data = [];
                if (is_array($ret)) {
                    if (!empty($extra[self::EXTRA_LIST_KEY])) {
                        if (!empty($extra[self::EXTRA_LIST_VAL])) {
                            if (is_array($extra[self::EXTRA_LIST_VAL])) {
                                foreach ($ret as $v) {
                                    foreach ($extra[self::EXTRA_LIST_VAL] as $lv) {
                                        $data[$v[$extra[self::EXTRA_LIST_KEY]]][$lv] = $v[$lv];
                                    }
                                }
                            } else {
                                foreach ($ret as $v) {
                                    $data[$v[$extra[self::EXTRA_LIST_KEY]]] = $v[$extra[self::EXTRA_LIST_VAL]];
                                }
                            }
                        } else {
                            foreach ($ret as $v) {
                                $data[$v[$extra[self::EXTRA_LIST_KEY]]] = $v;
                            }
                        }
                    } else {
                        foreach ($ret as $v) {
                            $data[$v[static::$_conf[static::CF_PRIMARY_KEY]]] = $v[static::$_conf[static::CF_PRIMARY_KEY]];
                        }
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
                $data = $pdo->find(static::$_conf[static::CF_TABLE] . $suffix, $where, $extra[static::EXTRA_FIELDS], $extra[static::EXTRA_ORDER], $limit);
            }
        }
        if (!empty($pdo)) {
            $pdo->close();
            DB::pdo($extra[static::EXTRA_DB], 'force_close');
            $pdo = null;
        }

        return ['count' => $count, 'data' => $data];
    }

    /**
     * 获取pdo实例
     *
     * @param bool $w
     * @return \Bare\D\PDODriver|bool
     */
    protected static function getPdo($w = false)
    {
        static $pdo_w, $pdo_r;
        if ($w) {
            if (empty($pdo_w)) {
                $pdo_w = DB::pdo(static::$_conf[self::CF_DB][self::CF_DB_W]);
            }

            return $pdo_w;
        } else {
            if (empty($pdo_r)) {
                $pdo_r = DB::pdo(static::$_conf[self::CF_DB][self::CF_DB_R]);
            }

            return $pdo_r;
        }
    }

    /**
     * 获取redis实例
     *
     * @param bool $w
     * @return \Bare\D\RedisDriver
     */
    protected static function getRedis($w = false)
    {
        static $redis_w, $redis_r;
        if ($w) {
            if (empty($redis_w)) {
                $redis_w = DB::redis(static::$_conf[self::CF_RD][self::CF_DB_W], static::$_conf[self::CF_RD][self::CF_RD_INDEX]);
            }

            return $redis_w;
        } else {
            if (empty($redis_r)) {
                $redis_r = DB::redis(static::$_conf[self::CF_RD][self::CF_DB_R], static::$_conf[self::CF_RD][self::CF_RD_INDEX]);
            }

            return $redis_r;
        }
    }

    /**
     * 获取mc实例
     *
     * @param null $option
     * @return \Bare\D\MemcacheDriver
     */
    protected static function getMC($option = null)
    {
        static $mc;
        if (empty($mc)) {
            if (empty($option)) {
                $option = static::$_conf[static::CF_MC];
            }
            $mc = DB::memcache($option);
        }

        return $mc;
    }

    /**
     * 从数据库查询
     *
     * @param        $ids
     * @param        $db
     * @param string $suffix
     * @return array
     */
    private static function getFromDb($ids, $db, $suffix = '')
    {
        $pdo = DB::pdo($db);
        $ret = $pdo->find(static::$_conf[static::CF_TABLE] . $suffix, [
            static::$_conf[static::CF_PRIMARY_KEY] . ' IN' => $ids
        ]);
        $pdo->close();
        DB::pdo($db, 'force_close');
        $pdo = null;
        if (is_array($ret) && count($ret) > 0) {
            foreach ($ret as & $v) {
                foreach (static::$_conf[static::CF_FIELDS_ARRAY] as $x) {
                    if (!empty($v[$x])) {
                        $v[$x] = unserialize($v[$x]);
                    }
                }
                foreach (static::$_conf[static::CF_FIELDS_JSON] as $x) {
                    if (!empty($v[$x])) {
                        $v[$x] = json_decode($v[$x], true);
                    }
                }
            }

            return $ret;
        }

        return [];
    }

    /**
     * 检查参数配置
     *
     * @param array $rows
     * @return array
     * @throws \Exception
     */
    private static function checkParams($rows = [])
    {
        $conf = &static::$_conf;
        if (!isset($conf[static::CF_PRIMARY_KEY]) || $conf[static::CF_PRIMARY_KEY] == '') {
            $conf[static::CF_FIELDS_ARRAY] = [];
            $conf[static::CF_FIELDS_JSON] = [];
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
                } elseif ($v == static::VAR_TYPE_JSON) {
                    $conf[static::CF_FIELDS_JSON][$k] = $k;
                }
            }
            $conf[static::CF_USE_MC] = !empty($conf[static::CF_MC]) && !empty($conf[static::CF_MC_KEY]);
            $conf[static::CF_USE_RD] = !empty($conf[static::CF_RD][static::CF_DB_W]) && !empty($conf[static::CF_RD][static::CF_RD_KEY]);
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

    /**
     * 字段类型验证
     *
     * @param array $rows
     * @return array
     */
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
                            $v = is_array($v) ? $v : intval($v);
                            break;
                        case static::VAR_TYPE_FLOAT:
                            $v = is_array($v) ? $v : floatval($v);
                            break;
                        case static::VAR_TYPE_ARRAY:
                            $v = is_array($v) ? serialize($v) : $v;
                            break;
                        case static::VAR_TYPE_JSON:
                            $v = is_array($v) ? json_encode($v) : $v;
                            break;
                        case static::VAR_TYPE_PASSWORD:
                            $v = !empty($v) ? password_hash($v, PASSWORD_DEFAULT) : $v;
                            break;
                        case static::VAR_TYPE_HIDDEN:
                            if (isset($rows[$k])) {
                                unset($rows[$k]);
                            }
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