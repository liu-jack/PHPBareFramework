<?php

/**
 * APP基础信息(版本/升级/启动图等)
 */

namespace Model\Mobile;

use Bare\RedisFastInterface as Fast;
use Bare\DB;
use Common\ImgPath;

class AppInfo
{
    // 缓存字段
    const CACHE_VERSION = 'Version_';
    const CACHE_APP_SCREEN = 'AppScreen_';

    // 缓存key
    const APPINFO_KEY = 'all';

    /**
     * 缓存表
     *
     * @var array
     */
    private static $cache = [
        self::CACHE_VERSION => 'getVersion',
        self::CACHE_APP_SCREEN => 'getStartImg',
    ];

    /**
     * 获取启动图信息
     *
     * @param int $appid AppId
     * @return array
     */
    public static function getStartImg($appid = 1)
    {
        $time = date("Y-m-d");
        $base = [
            'Id' => '',
            'Channel' => '',
            'StartTime' => '',
            'EndTime' => '',
            'ImgUrl' => '',
            'ClickUrl' => ''
        ];
        $fast = new Fast(Fast::TYPE_APP_INFO);
        $field = self::CACHE_APP_SCREEN . $appid;
        $data = $fast->get(self::APPINFO_KEY, $field);
        $data = unserialize($data[self::APPINFO_KEY][$field]);
        if (empty($data)) {
            $pdo = DB::pdo(DB::DB_MOBILE_R);
            $query = $pdo->prepare("select Id,Channel,Url as ClickUrl,StartTime,EndTime from AppScreenImage where AppId=:appid and Status=1 and EndTime>=:time ORDER BY Id DESC limit 1");
            $query->bindParam(':appid', $appid, \PDO::PARAM_INT);
            $query->bindParam(':time', $time, \PDO::PARAM_STR);
            $query->execute();
            $data = $query->fetch();
            if (empty($data)) {
                $data = $base;
            } else {
                $data['ImgUrl'] = getSavePath(ImgPath::IMG_APP_SCREEN, $data['Id'])['url'];
            }
            $fast->set(self::APPINFO_KEY, [$field => serialize($data)]);
        }

        if ($data['Id'] <= 0 || $data['EndTime'] < $time) {
            $data = $base;
        }

        return $data;
    }

    /**
     * 获取版本信息
     *
     * @param int $appid AppId
     * @return array
     */
    public static function getVersion($appid = 1)
    {
        $fast = new Fast(Fast::TYPE_APP_INFO);

        $field = self::CACHE_VERSION . $appid;
        $data = $fast->get(self::APPINFO_KEY, $field);
        $data = unserialize($data[self::APPINFO_KEY][$field]);

        if (empty($data)) {
            $pdo = DB::pdo(DB::DB_MOBILE_R);
            $query = $pdo->prepare("select VersionCode as VerCode, Intro as Feature, DownUrl as Url from AppVersion where AppId=:appid ORDER BY Id DESC limit 1");
            $query->bindParam(':appid', $appid);
            $query->execute();
            $data = $query->fetch();

            if (empty($data)) {
                $data = [
                    'VerCode' => '',
                    'Feature' => '',
                    'Url' => ''
                ];
            } else {
                $data['Feature'] = str_replace("\r\n", "\n", $data['Feature']);
            }

            $fast->set(self::APPINFO_KEY, [$field => serialize($data)]);
        }

        return $data;
    }

    /**
     * 一次获取所有基础信息
     *
     * @param int $appid  AppId
     * @param int $width  屏幕尺寸(宽)
     * @param int $height 屏幕尺寸(高)
     * @return array
     */
    public static function getAllInfo($appid, $width, $height)
    {
        $fast = new Fast(Fast::TYPE_APP_INFO);
        $data = $fast->get(self::APPINFO_KEY, '*');

        foreach (self::$cache as $k => $method) {
            $key = $k . $appid;
            if (empty($data[self::APPINFO_KEY][$key])) {
                $data[self::APPINFO_KEY][$key] = self::$method($appid, $width, $height);
            }

            if ($k == self::CACHE_APP_SCREEN) {
                $data[self::APPINFO_KEY][$key] = is_array($data[self::APPINFO_KEY][$key]) ? $data[self::APPINFO_KEY][$key] : unserialize($data[self::APPINFO_KEY][$key]);
            }
        }

        return $data;
    }

    /**
     * 删除缓存
     *
     * @param string $type  类型, 见self::CACHE_*
     * @param int    $appid APPID (1,3)
     * @return bool
     */
    public static function removeCache($type, $appid)
    {
        if (isset(self::$cache[$type])) {
            $fast = new Fast(Fast::TYPE_APP_INFO);
            $feild = [$type . $appid => ''];

            return $fast->set(self::APPINFO_KEY, $feild);
        }

        return false;
    }
}