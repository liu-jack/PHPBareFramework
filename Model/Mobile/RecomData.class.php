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

class RecomData
{
    const APP_ACTIVE_BANNER = 'app_active_banner';
    const APP_TEMAI_SKIP = 'app_temai_skip';
    // 支持的访问key数据
    const KEY_CONFIG = [
        self::APP_ACTIVE_BANNER => 'app活动banner图',
        self::APP_TEMAI_SKIP => 'app特卖入口跳转图'
    ];

    // MC KEY 推荐数据
    const MC_KEY = 'RecomData_';
    //表名
    const DB_TABLE_NAME = 'RecomData';

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
}