<?php
/**
 * FileData.class.php 文件缓存使用类
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-5-8 下午3:18
 *
 */

namespace Common;

use Bare\DB;
use Config\FDConfig;

class FileData extends FDConfig
{
    /**
     * 保存数据
     *
     * @param string       $key    文件名
     * @param array|string $val    数据
     * @param int          $expire 0：不过期 1 - 31536000
     * @return bool
     */
    public static function set($key, $val, $expire = 0)
    {
        if (!isset(self::KEY_CONFIG[$key])) {
            return false;
        }

        return DB::fileCache(self::KEY_CONFIG[$key])->set($key, $val, $expire);
    }

    /**
     * 获取数据
     *
     * @param string $key 文件名
     * @return bool|mixed
     */
    public static function get($key)
    {
        if (!isset(self::KEY_CONFIG[$key])) {
            return false;
        }

        return DB::fileCache(self::KEY_CONFIG[$key])->get($key);
    }

    /**
     * 缓存缓存文件
     *
     * @param string $key 文件名
     * @return bool
     */
    public static function delete($key)
    {
        if (!isset(self::KEY_CONFIG[$key])) {
            return false;
        }

        return DB::fileCache(self::KEY_CONFIG[$key])->delete($key);
    }

    /**
     * 清空路径下所有数据
     *
     * @param string $path 路径
     * @return bool
     */
    public static function flush($path)
    {
        return DB::fileCache($path)->flush();
    }
}