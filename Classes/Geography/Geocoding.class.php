<?php

/**
 * 百度LBS GeoCoding相关基类
 *
 * @subpackage Geography
 *
 * $Id$
 */

namespace Classes\Geography;

use Bare\DB;

class Geocoding
{

    /**
     * MC 缓存前缀
     */
    const MC_LBS_KEY = 'MC_LBS:';

    /**
     * MC 缓存时间
     */
    const MC_LBS_TIME = 86400;

    /**
     * 百度LBS请求地址
     */
    const BAIDU_LBS_URL = "http://api.map.baidu.com/geocoder/v2/?";

    /**
     * 百度AK保存redis键名
     */
    const BAIDU_AK_REDIS = "baidu-lbs-ak";

    /**
     * 百度AK超额后恢复时间
     */
    const BAIDU_AK_TIME = 86400;

    /**
     * AK取值失败后配额错误返回值（最小）
     */
    const BAIDU_RETURN_QUOTA_CODE = 299;

    /**
     * AK取值成功后返回值
     */
    const BAIDU_RESULT_CODE = 0;

    /**
     * 如遇失败，重试次数
     */
    const TRY_TIME = 2;

    /*
     * 百度AK值
    */
    protected static $_ak_value = [
        'A7021fe89b1accd83c0279779285e8d2' => 0,
        '414017e917535ff10f82df67e43d821a' => 0
    ];

    private function __construct()
    {
    }

    /**
     * 根据LBS获得周围地址信息
     *
     * @param string $lat LBS纬度
     * @param string $lng LBS经度
     * @param boolean $cache 是否取缓存信息
     * @return mixed
     *     array   - 成功返回周边地址信息数组
     *         ［'0' => ［'addr' => '地址'，'name' => '名称'，'lng' => 经度，'lat' => 纬度］...］
     *     boolean - 失败返回false
     */
    public static function getGeocoding($lat, $lng, $cache = true)
    {
        if (empty($lng) || empty($lat)) {
            return false;
        }
        $mc = DB::memcache(DB::MEMCACHE_MOBILE);
        $mckey = self::MC_LBS_KEY . $lng . "_" . $lat;
        if ($cache) {
            $res = $mc->get($mckey);
            if (is_array($res) && count($res) > 0) {
                return $res;
            }
        }
        $res = self::getData($lng, $lat);
        $result = false;
        if ($res['status'] == 0) {
            if (is_array($res['result']['pois']) && count($res['result']['pois']) > 0) {
                foreach ($res['result']['pois'] as $v) {
                    $result[] = [
                        'addr' => $v['addr'],
                        'name' => $v['name'],
                        'lng' => $v['point']['x'],
                        'lat' => $v['point']['y']
                    ];
                }
            }
            $mc->set($mckey, $result, self::MC_LBS_TIME);
        }
        return $result;
    }

    /**
     * 使用curl调用百度Geocoding API
     * @param  string $url 请求的地址
     * @param  array $param 请求的参数
     * @return string
     */
    private static function toCurl($url, $param = array())
    {
        $newurl = $url . http_build_query($param);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_URL, $newurl);
        $response = curl_exec($ch);
        if ($error = curl_error($ch)) {
            return false;
        }
        curl_close($ch);
        return $response;
    }

    /**
     * 根据地址获取周边数据
     * @param  string $longitude 经度
     * @param  string $latitude 纬度
     * @return array
     */
    private static function getData($longitude, $latitude)
    {
        $ak = self::getAk();
        if ($ak === false) {
            return false;
        }
        $param = [
            'ak' => $ak,
            'location' => implode(',', array($latitude, $longitude)),
            'pois' => 1,
            'output' => 'json'
        ];

        // 请求百度api
        $response = self::toCurl(self::BAIDU_LBS_URL, $param);
        $result = [];
        if ($response) {
            $result = json_decode($response, true);
        }
        if ($result['status'] > 0) {
            static $count = 1;
            $count++;
            self::getAk($ak, $result['status']);
            self::getData($longitude, $latitude);
            if ($count > self::TRY_TIME) {
                return false;
            }
        }
        $count = 1;
        return $result;
    }

    /**
     * 轮换模式调取百度AK值
     * @param string $oldak 过期AK值
     * @param int $status 过期状态值
     * @return bool|string 返回一个可以用的百度ＡＫ，如无可用的ＡＫ，则返回false
     */
    private static function getAk($oldak = null, $status = self::BAIDU_RESULT_CODE)
    {
        $redis = DB::redis(DB::REDIS_DEFAULT_W);
        $len = $redis->hLen(self::BAIDU_AK_REDIS);
        if ($len == 0) {
            $akarr = self::$_ak_value;
            foreach ($akarr as $k => $v) {
                $akarr[$k] = serialize([1 => time(), 2 => self::BAIDU_RESULT_CODE]);
            }
            $redis->hMset(self::BAIDU_AK_REDIS, $akarr);
        }

        if ($oldak != null) {
            $redis->hSet(self::BAIDU_AK_REDIS, $oldak, serialize([1 => time(), 2 => $status]));
        }

        $nowak = $redis->hGetAll(self::BAIDU_AK_REDIS);
        $time = strtotime(date('Y-m-d') . " 00:00:00");
        $flag = '';
        foreach ($nowak as $key => $val) {
            $temp = unserialize($val);
            if ($temp[2] == self::BAIDU_RESULT_CODE) {
                $flag = $key;
            }
            if (($time - $temp[1]) > self::BAIDU_AK_TIME && $temp[2] > self::BAIDU_RETURN_QUOTA_CODE) {
                $redis->hSet(self::BAIDU_AK_REDIS, $key, serialize([1 => time(), 2 => 0]));
                $flag = $key;
            }
        }
        if ($flag != '') {
            return $flag;
        } else {
            return false;
        }
    }
}
