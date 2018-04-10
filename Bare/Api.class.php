<?php
/**
 * API调用共用基类
 *
 * @package    Bare
 * @author     周剑锋 <camfee@foxmail.com>
 */

namespace Bare;

class Api
{
    // 接口请求地址
    public static $apiurl = 'http://api.bare.com';
    // appid
    public static $appid = 10;
    //apptype 0:web 1:wap 2:android 3:ios 4:xcx
    public static $apptype = 0;
    // 接口版本
    public static $verid;
    // 渠道来源
    public static $channel = 'default';
    // 设备id
    public static $deviceid = 0;
    // 接口appkey
    protected static $appkey;
    // rsa公钥
    protected static $rsakey;


    /**
     * 组装请求接口的url
     *
     * @param string $api_method 要请求的方法名
     * @param array  $get        get方式传输的参数数组
     * @return string        请求地址
     */
    public static function getUrl($api_method, $get = [])
    {
        self::$appkey = self::getAppKey();
        self::$verid = self::getVerId();
        $time = time();
        //hash=md5(APPKEY + 版本号 + 模块名/类名/方法 + ksort($_GET));
        $hash_str = self::$appkey . self::$verid . $api_method;
        $query = $get;
        if (defined('URL_MODE') && URL_MODE == 1) {
            $url = self::$apiurl . '/' . $api_method . '/' . self::$verid;
        } else {
            $url = self::$apiurl . '/' . $api_method;
            $query[API_VAR] = self::$verid;
        }
        $query['_t'] = self::$apptype;
        $query['appid'] = self::$appid;
        $query['channel'] = self::$channel;
        $query['deviceid'] = self::$deviceid;
        $query['time'] = $time;
        ksort($query);
        if (!empty($query)) {
            foreach ($query as $k => $v) {
                if ($k != API_VAR && $k != 'hash' && $v !== '') {
                    $hash_str .= $v;
                }
            }
        }
        $query['hash'] = md5($hash_str);

        return $url . '?' . http_build_query($query);
    }

    /**
     * 发送请求
     *
     * @param string $url     请求的地址
     * @param array  $data    需要post的数组
     * @param int    $timeout 超时时间
     * @return array       结果数组
     */
    public static function request($url, $data = [], $timeout = 15)
    {
        $time_start = microtime(true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($data['header'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $data['header']); // 加入header
            unset($data['header']);
        }
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true); // enable posting
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // post files
        }
        $res = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($res, true);
        if (empty($res['Code']) || !is_array($res)) {
            $log['url'] = $url;
            if (!empty($data)) {
                unset($data['password'], $data['old_password']);
                $log['post'] = $data;
            }
            $log['return'] = $res;
            logs($log, 'Api/api_request_error');
        }
        $time_end = microtime(true);
        $time_out = $time_end - $time_start;
        if ($time_out > 5) {
            $log = [
                'url' => $url,
                'time_start' => $time_start,
                'time_end' => $time_end,
                'time_out' => $time_out,
                'return' => $res,
            ];
            if (!empty($data)) {
                unset($data['password'], $data['old_password']);
                $log['post'] = $data;
            }
            logs($log, 'Api/api_request_timeout');
        }

        return self::checkRes($res);
    }

    /**
     * 重新组装接口返回数据
     *
     * @param array $res 接口返回的json数据
     * @return array     重组后的数据
     */
    public static function checkRes($res)
    {
        $return = [];
        if ($res['Code'] == 200) {
            if (isset($res['Msg']) && !isset($res['Data'])) {
                $return['code'] = 200;
                $return['msg'] = $res['Msg'];
            } else {
                $return = (array)$res['Data'];
            }
        } elseif (!empty($res['Code'])) {
            $return['code'] = $res['Code'];
            if (isset($res['Msg'])) {
                $return['msg'] = $res['Msg'];
            }
            if (isset($res['Data'])) {
                $return['data'] = $res['Data'];
            }
        }

        return $return;
    }

    /**
     * 获取verid
     *
     * @return string 接口verid
     */
    protected static function getVerId()
    {
        if (empty(self::$verid)) {
            self::$verid = config('api/config' . self::$appid)['verid'];
        }

        return self::$verid;
    }

    /**
     * 获取appkey
     *
     * @return string 接口appkey
     */
    protected static function getAppKey()
    {
        if (empty(self::$appkey)) {
            self::$appkey = config('api/config' . self::$appid)['appkey'];
        }

        return self::$appkey;
    }

    /**
     * 获取rsa公钥
     *
     * @return string rsa公钥
     */
    protected static function getRsaKey()
    {
        if (empty(self::$rsakey)) {
            self::$rsakey = config('api/config' . self::$appid)['rsakey'];
        }

        return self::$rsakey;
    }
}
