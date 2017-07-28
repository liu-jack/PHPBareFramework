<?php
/**
 * API共用基类
 *
 * @package    Bare
 * @author     周剑锋 <camfee@foxmail.com>
 */

namespace Bare;

class Api
{
    // 接口请求地址
    public static $apiurl = 'http://29shu.iok.la';
    // appid
    public static $appid = 10;
    //apptype 0:web 1:wap 2:android 3:ios
    public static $apptype = 0;
    // 接口版本
    public static $verid;
    // 接口appkey
    protected static $appkey;
    // rsa公钥
    protected static $rsakey;


    /**
     * 组装请求接口的url
     *
     * @param string $api_method 要请求的方法名
     * @param array $get get方式传输的参数数组
     * @return string        请求地址
     */
    public static function getUrl($api_method, $get = [])
    {
        self::$appkey = self::getAppKey();
        self::$verid = self::getVerId();
        $time = time();
        //hash=md5(APPKEY + 版本号 + 模块名/类名/方法 + $_GET);
        $hash1 = self::$appkey . self::$verid . $api_method;
        if (!empty($get)) {
            foreach ($get as $k => $v) {
                if ($k != '_v' && $k != 'hash' && $v !== '') {
                    $hash1 .= $v;
                }
            }
        }
        $hash = md5($hash1 . self::$apptype . self::$appid . $time);
        $url = self::$apiurl . '/' . $api_method;
        $query = $get;
        $query['_v'] = self::$verid;
        $query['_t'] = self::$apptype;
        $query['appid'] = self::$appid;
        $query['time'] = $time;
        $query['hash'] = $hash;
        return $url . '?' . http_build_query($query);
    }

    /**
     * 发送请求
     *
     * @param string $url 请求的地址
     * @param array $data 需要post的数组
     * @param int $timeout 超时时间
     * @return array       结果数组
     */
    public static function request($url, $data = [], $timeout = 15)
    {
        $time_start = microtime(true);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, true); // enable posting
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // post files
        }
        $res = curl_exec($curl);
        curl_close($curl);
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
     * @param string $res 接口返回的json数据
     * @return array      重组后的数据
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
