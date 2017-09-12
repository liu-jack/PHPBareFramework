<?php

namespace Bare;

use Config\DBConfig;
use Bare\DB\{
    PDODB, MemcacheDB, RedisDB, ElasticSearch as ESDB, MongodbDB, FileDB
};

/**
 * 数据库操作基类
 * @author camfee<camfee@yeah.net>
 * @since v1.0 2016.09.25
 */
class DB extends DBConfig
{
    /**
     * 返回PDO数据库连接实例
     *
     * @param mixed $params 连接参数
     * @param mixed $options 选项
     * @return bool|PDODB
     */
    public static function pdo($params, $options = null)
    {
        static $_static = [];

        if ($options === 'force_close') {
            $keys = array_keys($_static);

            foreach ($keys as $key) {
                unset($_static[$key]);
            }

            return true;
        }

        $key = md5(json_encode([$params, $options]));

        if (!isset($_static[$key])) {
            if (is_numeric($params)) {
                $configs = self::getMysqlConfig();
                if (isset($configs[$params])) {
                    $params = $configs[$params];
                }
            }

            self::_checkConnectionParams([$params], 'mysql');

            $_static[$key] = new PDODB($params, $options, function () use (&$_static, $key) {
                unset($_static[$key]);
            });
        } else {
            if (method_exists($_static[$key], 'clear')) {
                $_static[$key]->clear();
            }
        }

        return $_static[$key];
    }

    /**
     * 获取 MySQL 服务的配置
     *
     * @return array
     */
    public static function getMysqlConfig()
    {
        static $_static = [];

        if (empty($_static)) {
            $configs = self::_loadConfigFile('mysql');

            foreach (self::$_db_cfgs as $key => $meta) {
                $name = $meta['name'];

                if (isset($configs[$name])) {
                    $conn = $configs[$name];

                    $cfg = $conn[($key % 2) ? 'r' : 'w'];

                    $cfg['name'] = $meta['db'];
                    $cfg['driver'] = 'mysql';
                    if (!isset($cfg['port'])) {
                        $cfg['port'] = 3306;
                    }

                    $_static[$key] = $cfg;
                }
            }
        }

        return $_static;
    }

    protected static function _loadConfigFile($type)
    {
        static $_static = [];

        if (empty($_static)) {
            $_static = config('db');
        }

        return $_static[$type];
    }

    /**
     * 检查连接参数
     *
     * @param array $param_arr
     * @param string $type
     * @return bool
     * @throws \Exception
     */
    protected static function _checkConnectionParams($param_arr, $type)
    {
        static $_filters = [
            'host' => [
                'filter' => FILTER_VALIDATE_IP,
            ],
            'port' => [
                'filter' => FILTER_VALIDATE_INT,
            ],
            'db' => [
                'filter' => FILTER_VALIDATE_REGEXP,
                'options' => [
                    'regexp' => '/^[a-z0-9_]{1,64}$/i',
                ],
            ],
            'index' => [
                'filter' => FILTER_VALIDATE_INT,
            ],
        ];

        $valid_params = false;

        foreach ($param_arr as $params) {
            $valid_params = false;

            if (is_array($params)) {
                $valid_params = true;
                $params = filter_var_array($params, $_filters);
                foreach ($params as $param) {
                    if ($param === false) {
                        $valid_params = false;
                        break;
                    }
                }
            }

            if (!$valid_params) {
                break;
            }
        }

        if (!$valid_params) {
            throw new \Exception("Invalid Connection Parameters Of {$type}");
        }

        return true;
    }

    /**
     * 返回Redis连接实例
     *
     * @param mixed $params 连接参数
     * @param integer $dbindex 库号
     * @param integer $timeout 超时时间
     * @return \Redis
     */
    public static function redis($params, $dbindex = 0, $timeout = 5)
    {
        static $_static = [];

        $dbindex = (is_numeric($dbindex) && ($dbindex = (int)$dbindex) > 0) ? $dbindex : 0;
        $key = md5(json_encode([$params, $dbindex]));

        if (!isset($_static[$key])) {
            if (is_numeric($params)) {
                $configs = self::getRedisConfig();
                if (isset($configs[$params])) {
                    $params = $configs[$params];
                }
            }

            self::_checkConnectionParams([$params], 'redis');

            $_static[$key] = new RedisDB($params, $dbindex, $timeout);
        }

        return $_static[$key];
    }

    /**
     * 获取 Redis 服务的配置
     *
     * @return array
     */
    public static function getRedisConfig()
    {
        static $_static = [];

        if (empty($_static)) {
            $configs = self::_loadConfigFile('redis');

            foreach (self::$_redis_cfgs as $key => $name) {
                if (isset($configs[$name])) {
                    $conn = $configs[$name];

                    $_static[$key] = $conn[($key % 2) ? 'r' : 'w'];
                }
            }
        }

        return $_static;
    }

    /**
     * 返回Memcache连接实例
     *
     * @param mixed $params 连接参数
     * @param integer $timeout 超时时间
     * @return MemcacheDB
     */
    public static function memcache($params = self::MEMCACHE_DEFAULT, $timeout = 5)
    {
        static $_static = [];

        $key = md5(json_encode([$params]));
        if (!isset($_static[$key])) {
            if (is_numeric($params)) {
                $configs = self::getMemcacheConfig();
                if (isset($configs[$params])) {
                    $params = $configs[$params];
                }
            }

            self::_checkConnectionParams($params, 'memcache');

            $_static[$key] = new MemcacheDB($params, $timeout, $key);
        }

        return $_static[$key];
    }

    /**
     * 获取 Memcached 服务的配置
     *
     * @return array
     */
    public static function getMemcacheConfig()
    {
        static $_static = [];

        if (empty($_static)) {
            $configs = self::_loadConfigFile('memcache');

            foreach (self::$_memcache_cfgs as $key => $val) {
                if (isset($configs[$val])) {
                    $_static[$key] = $configs[$val];
                }
            }
        }

        return $_static;
    }

    /**
     * 返回搜索连接实例
     *
     * @param mixed $params 连接参数
     * @param integer $timeout 超时时间
     * @return ESDB
     */
    public static function search($params = self::SEARCH_DEFAULT, $timeout = 2)
    {
        static $_static = [], $included = false;

        $key = md5(json_encode([$params]));
        if (!isset($_static[$key])) {
            if (!$included) {
                $included = true;
            }

            if (is_numeric($params)) {
                $configs = self::getSearchConfig();
                if (isset($configs[$params])) {
                    $params = $configs[$params];
                }
            }

            $params = (array)$params;
            if (isset($params['servers'])) {
                $servers = $params['servers'];
            } else {
                $sub_param = current($params);

                if (is_array($sub_param) && isset($sub_param['host'])) {
                    $servers = $params;
                    $params = [
                        'servers' => $servers,
                    ];
                } else {
                    $servers = [$params];
                }
            }

            self::_checkConnectionParams($servers, 'search');

            $params['timeout'] = (is_numeric($timeout) && ($timeout = (int)$timeout) > 0) ? $timeout : 2;

            $_static[$key] = new ESDB($params);
        }

        return $_static[$key];
    }

    /**
     * 获取搜索服务的配置
     *
     * @return array
     */
    public static function getSearchConfig()
    {
        static $_static = [];

        if (empty($_static)) {
            $configs = self::_loadConfigFile('search');

            foreach (self::$_search_cfgs as $key => $val) {
                if (isset($configs[$val])) {
                    $_static[$key] = $configs[$val];
                }
            }
        }

        return $_static;
    }

    /**
     * 返回文件缓存实例
     *
     * @param string $path 缓存路径
     * @return FileDB
     */
    public static function filecache($path)
    {
        static $_static = [];

        $key = md5($path);
        if (!isset($_static[$key])) {
            $_static[$key] = new FileDB($path);
        }

        return $_static[$key];
    }

    /**
     * 返回MongoDB连接实例
     *
     * @param mixed $params 连接参数
     * @param array $options 额外参数
     * @return \MongoDB\Client
     */
    public static function mongodb($params = self::MONGODB_DEFAULT, $options = [])
    {
        static $_static = [];

        $key = md5(json_encode([$params, $options]));
        if (!isset($_static[$key])) {
            if (is_numeric($params)) {
                $configs = self::getMongoDBConfig();
                if (isset($configs[$params])) {
                    $params = $configs[$params];
                }
            }

            $_static[$key] = new MongodbDB($params, $options);
        }

        return $_static[$key];
    }

    /**
     * 获取 MongoDB 服务的配置
     *
     * @return array
     */
    public static function getMongoDBConfig()
    {
        static $_static = [];

        if (empty($_static)) {
            $configs = self::_loadConfigFile('mongodb');

            foreach (self::$_mongodb_cfgs as $key => $val) {
                if (isset($configs[$val['name']])) {
                    $cfg = $configs[$val['name']];
                    $cfg['db'] = $val['db'];
                    $_static[$key] = $cfg;
                }
            }
        }

        return $_static;
    }
}