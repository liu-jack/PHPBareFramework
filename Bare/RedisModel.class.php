<?php
/**
 * RedisModel.class.php
 * redis缓存数据模型基类
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-8 下午5:47
 *
 */

namespace Bare;

abstract class RedisModel
{
    /**
     * @return \Model\RedisDB\RedisCache
     */
    abstract protected static function redisCache();

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
        // 可选, 数据表分表前缀 User_%s
        self::CF_PREFIX_TABLE => '',
    ];

    /**
     * @see \Bare\RedisModel::add() 新增
     * @see \Bare\RedisModel::update() 更新
     * @see \Bare\RedisModel::updateCount() 更新计数
     * @see \Bare\RedisModel::getInfoByIds() 按主键id查询
     * @see \Bare\RedisModel::getList() 条件查询
     * @see \Bare\RedisModel::delete() 删除
     */

    const CF_DB = 'db';
    const CF_DB_W = 'w';
    const CF_DB_R = 'r';
    const CF_TABLE = 'table';
    const CF_PREFIX_TABLE = 'prefix_table';
    const CF_FIELDS = 'fields';
    const CF_MC = 'mc';
    const CF_MC_KEY = 'mc_key';
    const CF_MC_TIME = 'mc_expire';
    const CF_RD = 'redis';
    const CF_RD_INDEX = 'redis_index';
    const CF_RD_TIME = 'redis_expire';
    const CF_RD_KEY = 'redis_key';
    const FIELD_VAR_TYPE = 'var_type';
    const CF_PRIMARY_KEY = '_primary_key';
    const CF_FIELDS_ARRAY = '_fields_array';
    const CF_FIELDS_JSON = '_fields_json';
    // 主键/字段类型
    const VAR_TYPE_KEY = 'PRIMARY KEY';
    const VAR_TYPE_INT = 'int';
    const VAR_TYPE_FLOAT = 'float';
    const VAR_TYPE_STRING = 'string';
    const VAR_TYPE_ARRAY = 'array';
    const VAR_TYPE_JSON = 'json';
    const VAR_TYPE_PASSWORD = 'password';
    const VAR_TYPE_HIDDEN = 'hidden';

    // 列表缓存数组 [list_key{field1}{field2} => [type => 1, fields => [field1,field2]]] 0:mc 1:redis 默认0
    const CACHE_LIST_TYPE = 'type';
    const CACHE_LIST_TYPE_MC = 0;
    const CACHE_LIST_TYPE_REDIS = 1;
    const CACHE_LIST_FIELDS = 'fields';
    const UPDATE_DEL_CACHE_LIST = false; // 更新是否清除列表缓存
    protected static $_cache_list_keys;
    const REDIS_LIST_DEMO_1 = 'REDIS_LIST_DEMO_1';
    const REDIS_LIST_DEMO_2 = 'REDIS_LIST_DEMO_2:{Id}';
    const REDIS_LIST_DEMO_3 = 'REDIS_LIST_DEMO_3:{Id}:{UserId}';
    private static $_cache_list_keys_demo = [
        self::REDIS_LIST_DEMO_1 => self::CACHE_LIST_TYPE_REDIS,
        self::REDIS_LIST_DEMO_2 => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_REDIS,
            self::CACHE_LIST_FIELDS => 'Id',
        ],
        self::REDIS_LIST_DEMO_3 => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_REDIS,
            self::CACHE_LIST_FIELDS => ['Id', 'UserId']
        ],
    ];
    // 新增必须字段 field => 1
    protected static $_add_must_fields;
    // 不可修改字段 field => 1
    protected static $_un_modify_fields = [
        'Id' => 1,
    ];

    /**
     * 根据主键id获取多个数据
     *
     * @param array|int $ids
     * @return array
     */
    public static function getInfoByIds($ids)
    {
        static::checkParams();
        $id_arr = !is_array($ids) ? [$ids] : $ids;
        $redis_key = [];
        foreach ($id_arr as $id) {
            $redis_key[$id] = static::redisCache()->getKey($id);
        }
        $redis_data = static::redisCache()->loads($redis_key);
        $_cache = $nocache_ids = [];
        foreach ($redis_key as $id => $v) {
            if (empty($redis_data[$id])) {
                $nocache_ids[$id] = $v;
            } else {
                $_cache[$id] = $redis_data[$id];
            }
        }
        if (!empty($nocache_ids)) {
            $lists = static::getPdo()->select('*')->from(static::tableName($ids))->where([static::$_conf[static::CF_PRIMARY_KEY] . ' IN' => array_keys($nocache_ids)])->getAll();
            if (!empty($lists)) {
                foreach ($lists as $v) {
                    $_cache[$v[static::$_conf[static::CF_PRIMARY_KEY]]] = $v;
                    static::redisCache()->save($nocache_ids[$v[static::$_conf[static::CF_PRIMARY_KEY]]], $v);
                }
            }
        }
        $data = [];
        foreach ($id_arr as $id) {
            if (isset($_cache[$id])) {
                $data[$id] = $_cache[$id];
            }
        }
        if (is_numeric($ids)) {
            $data = isset($data[$ids]) ? $data[$ids] : [];
        }

        return $data;
    }

    /**
     * 获取列表数据
     *
     * @param array  $where
     * @param int    $offset
     * @param int    $limit
     * @param string $fields
     * @param string $order
     * @return mixed
     */
    public static function getList($where = [], $offset = 0, $limit = 0, $fields = '', $order = '')
    {
        static::checkParams();
        if (empty($fields)) {
            $fields = static::$_conf[static::CF_PRIMARY_KEY];
        }
        if (empty($order)) {
            $order = static::$_conf[static::CF_PRIMARY_KEY] . ' DESC';
        }
        $offset_limit = '';
        if ($limit > 0) {
            $offset_limit = [$offset, $limit];
        }
        $count = static::getPdo()->select('COUNT(*)')->from(static::tableName())->where($where)->getValue();
        $list = static::getPdo()->find(static::tableName(), $where, $fields, $order, $offset_limit);
        $data = [];
        if (!empty($list) && $fields == static::$_conf[static::CF_PRIMARY_KEY]) {
            foreach ($list as $v) {
                $data[$v[static::$_conf[static::CF_PRIMARY_KEY]]] = $v[static::$_conf[static::CF_PRIMARY_KEY]];
            }
        } else {
            $data = $list;
        }

        return ['count' => $count, 'data' => $data];
    }

    /**
     * 新增
     *
     * @param      $rows
     * @param bool $ignore
     * @return bool|int|string
     */
    public static function add($rows, $ignore = true)
    {
        if (!empty(static::$_add_must_fields) && count(array_diff_key(static::$_add_must_fields, $rows)) > 0) {
            return false;
        }
        if (empty($rows[0]) && empty($rows['CreateTime'])) {
            $rows['CreateTime'] = date('Y-m-d H:i:s');
        }
        $rows = static::checkParams($rows);
        $ret = self::getPdo(true)->insert(static::tableName(), $rows, ['ignore' => $ignore]);
        if ($ret > 0) {
            $id = self::getPdo(true)->lastInsertId();
            if (!empty(static::$_cache_list_keys) && $ret !== false) {
                if (empty($rows[0])) {
                    $rows[static::$_conf[static::CF_PRIMARY_KEY]] = $ret;
                } else {
                    $rows = $rows[0];
                }
                static::delListCache($rows);
            }
        } else {
            $id = 0;
        }

        return $id;
    }

    /**
     * 更新
     *
     * @param      $id
     * @param      $rows
     * @param bool $filter
     * @return bool
     */
    public static function update($id, $rows, $filter = true)
    {
        if (!empty(static::$_un_modify_fields)) {
            if ($filter) {
                $rows = array_diff_key($rows, static::$_un_modify_fields);
            } else {
                $intersect_key = array_intersect_key($rows, static::$_un_modify_fields);
                if (count($intersect_key) > 0) {
                    return false;
                }
            }
        }
        $rows = static::checkParams($rows);
        $redis_key = static::redisCache()->getKey($id);
        $ret = static::redisCache()->save($redis_key, $rows);
        static::redisCache()->async($redis_key, $id, array_keys($rows));
        if (empty($ret)) {
            return false;
        }
        if (!empty(static::$_cache_list_keys) && $ret !== false && !empty(static::UPDATE_DEL_CACHE_LIST)) {
            $info = static::getInfoByIds($id);
            if (!empty($info)) {
                static::delListCache($info);
            }
        }

        return true;
    }

    /**
     * 更新计数
     *
     * @param      $id
     * @param      $rows
     * @param bool $filter
     * @return bool
     */
    public static function updateCount($id, $rows, $filter = true)
    {
        if (!empty(static::$_un_modify_fields)) {
            if ($filter) {
                $rows = array_diff_key($rows, static::$_un_modify_fields);
            } else {
                $intersect_key = array_intersect_key($rows, static::$_un_modify_fields);
                if (count($intersect_key) > 0) {
                    return false;
                }
            }
        }
        $rows = static::checkParams($rows);
        $redis_key = static::redisCache()->getKey($id);
        $ret = false;
        foreach ($rows as $field => $inc) {
            $ret = static::redisCache()->hIncrBy($redis_key, $field, $inc);
        }
        static::redisCache()->async($redis_key, $id, array_keys($rows));
        if ($ret === false) {
            return false;
        }
        if (!empty(static::$_cache_list_keys) && $ret !== false && !empty(static::UPDATE_DEL_CACHE_LIST)) {
            $info = static::getInfoByIds($id);
            if (!empty($info)) {
                static::delListCache($info);
            }
        }

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
        static::checkParams();
        if (!empty(static::$_cache_list_keys)) {
            $info = static::getInfoByIds($id);
        }
        $res = static::redisCache()->del(static::redisCache()->getKey($id));
        $ret = self::getPdo(true)->delete(static::tableName($id), [
            static::$_conf[static::CF_PRIMARY_KEY] => $id
        ]);
        if ($res !== false && $ret) {
            if (!empty($info)) {
                static::delListCache($info);
            }

            return true;
        }

        return false;
    }

    /**
     * 获取数据表名称
     *
     * @param int $id
     * @return string
     */
    protected static function tableName($id = 0)
    {
        if (!empty(static::$_conf[static::CF_PREFIX_TABLE]) && !empty($id) && is_numeric($id)) {
            $table = sprintf(static::$_conf[static::CF_PREFIX_TABLE], $id % 256);
        } else {
            $table = static::$_conf[static::CF_TABLE];
        }

        return $table;
    }

    /**
     * 获取pdo实例
     *
     * @param bool $w
     * @return DB\PDODB|bool
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
     * @return \Redis
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
     * @param string $option
     * @return \Bare\DB\MemcacheDB
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