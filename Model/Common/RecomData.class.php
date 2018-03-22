<?php

/**
 * 推荐数据集
 *
 * @author suning <snsnsky@gmail.com>
 *
 * $Id$
 */

namespace Model\Mobile;

use Bare\DB;
use Config\RDConfig;

class RecomData extends RDConfig
{
    // MC KEY 推荐数据
    const MC_KEY = 'RecomData_';
    //表名
    const DB_TABLE_NAME = 'RecomData';
    // redis 配置
    const REDIS_DB_W = DB::REDIS_DEFAULT_W;
    const REDIS_DB_R = DB::REDIS_DEFAULT_R;
    const REDIS_DB_INDEX = 10;
    const REDIS_KEY = 'RecomData:';

    /**
     * 按key获取数据
     *
     * @param string|array $keys KEY见self::KEY_CONFIG
     * @return array             ['key1' => [], 'key2' => [], ...]
     *
     */
    public static function getData($keys)
    {
        $mc_key = [];
        $keys = is_array($keys) ? $keys : [$keys];
        foreach ($keys as $v) {
            $mc_key[$v] = self::MC_KEY . $v;
        }

        $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
        $mc_data = $mc->get($mc_key);

        $data = [];
        $no_cache_key = [];
        foreach ($mc_key as $k => $v) {
            if (isset($mc_data[$v])) {
                $data[$k] = $mc_data[$v];
            } else {
                $no_cache_key[] = $k;
            }
        }

        if (count($no_cache_key) > 0) {
            $pdo = DB::pdo(DB::DB_MOBILE_R);
            $no_cache_data = $pdo->find(self::DB_TABLE_NAME, [
                'RecomType IN' => $no_cache_key
            ], 'RecomType, Content');

            if (is_array($no_cache_data)) {
                foreach ($no_cache_data as $v) {
                    $cont = isset($v['Content']) ? unserialize($v['Content']) : [];
                    $data[$v['RecomType']] = $cont;
                    $mc->set(self::MC_KEY . $v['RecomType'], $cont);
                }
            }
        }

        return $data;
    }

    /**
     * 保存数据
     *
     * @param string $key    要设置的key,见self::KEY_CONFIG
     * @param array  $data   要设置的数据, 不同key数据自定义, 见self::KEY_CONFIG
     * @param string $prefix 要设置的key 前缀
     * @return bool
     */
    public static function setData($key, $data, $prefix = '')
    {
        $conf = self::KEY_CONFIG;
        if (!isset($conf[$key]) && !isset($conf[$prefix])) {
            return false;
        }

        $now = date("Y-m-d H:i:s");
        $pdo = DB::pdo(DB::DB_MOBILE_W);
        $rowcount = $pdo->upsert(self::DB_TABLE_NAME, [
            'RecomType' => $key,
            'Content' => serialize($data),
            'UpdateTime' => $now,
            'CreateTime' => $now
        ], ['Content', 'UpdateTime']);

        if ($rowcount !== false && $rowcount > 0) {
            $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
            if (empty($data)) {
                $mc->delete(self::MC_KEY . $key);
            }
            $mc->set(self::MC_KEY . $key, $data);

            return true;
        }

        return false;
    }

    /**
     * 按key获取数据
     *
     * @param string|array $key KEY见self::KEY_CONFIG
     * @return array             ['key1' => [], 'key2' => [], ...]
     *
     */
    public static function getRedisData($key)
    {
        $data = [];
        $keys = is_array($key) ? $key : [$key];
        foreach ($keys as $v) {
            $data[$v] = self::getRedis()->get(self::REDIS_KEY . $v);
        }
        foreach ($data as &$v) {
            $v = unserialize($v) != false ? unserialize($v) : $v;
        }

        return is_array($key) ? $data : $data[$key];
    }

    /**
     * 保存数据
     *
     * @param string       $key    要设置的key,见self::KEY_CONFIG
     * @param array|string $data   要设置的数据, 不同key数据自定义, 见self::KEY_CONFIG
     * @param string       $prefix 要设置的key 前缀
     * @return bool
     */
    public static function setRedisData($key, $data, $prefix = '')
    {
        $conf = self::KEY_CONFIG;
        if (!isset($conf[$key]) && !isset($conf[$prefix])) {
            return false;
        }
        $data = is_array($data) ? serialize($data) : $data;

        return self::getRedis(true)->set(self::REDIS_KEY . $key, $data);
    }

    private static function getRedis($w = true)
    {
        static $redis_w, $redis_r;
        if ($w) {
            if (empty($redis_w)) {
                $redis_w = DB::redis(self::REDIS_DB_W, self::REDIS_DB_INDEX);
            }

            return $redis_w;
        } else {
            if (empty($redis_r)) {
                $redis_r = DB::redis(self::REDIS_DB_R, self::REDIS_DB_INDEX);
            }

            return $redis_r;
        }
    }
}