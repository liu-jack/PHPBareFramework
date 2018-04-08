<?php
/**
 * RedisModel.class.php
 * redis缓存数据模型基类
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-8 下午5:47
 *
 */

use Bare\DB;

abstract class RedisModel
{
    /**
     * @var \Model\RedisDB\RedisDB
     */
    protected static $redis_obj;
    protected static $primary_key = 'Id';
    protected static $table_name = 'Id';

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
    ];

    const CF_DB = 'db';
    const CF_DB_W = 'w';
    const CF_DB_R = 'r';
    const CF_TABLE = 'table';
    const CF_FIELDS = 'fields';

    /**
     * 获取多个数据
     *
     * @param array $ids
     * @return array
     */
    public static function getInfoByIds($ids)
    {
        $rdKey = [];
        foreach ($ids as $id) {
            $rdKey[$id] = static::$redis_obj::getKey($id);
        }
        $rdata = static::$redis_obj::instance()->loads($rdKey);
        $k = 0;
        $_cache = $nocache_ids = [];
        foreach ($rdKey as $id => $v) {
            if (empty($rdata[$k])) {
                $nocache_ids[$id] = $v;
            } else {
                $_cache[$id] = $rdata[$k];
            }
            $k++;
        }
        if (!empty($nocache_ids)) {
            $groups = static::getPdo()->select('*')->from(static::$table_name)->where([static::$primary_key . ' IN' => array_keys($nocache_ids)])->getAll();
            if (!empty($groups)) {
                foreach ($groups as $v) {
                    $_cache[$v[static::$primary_key]] = $v;
                    static::$redis_obj::instance()->save($nocache_ids[$v[static::$primary_key]], $v);
                }
            }
        }
        $data = [];
        foreach ($ids as $id) {
            if (isset($_cache[$id])) {
                $data[$id] = $_cache[$id];
            }
        }

        return $data;
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
}