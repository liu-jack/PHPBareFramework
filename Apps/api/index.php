<?php

/**
 *
 * 手机API接口
 *
 * @author suning <snsnsky@126.com>
 *
 *
 */

use Classes\Tool\Emoji;

define('SESSION', false);
define('API_VERSION', 'v1.0.0');
define('API_STOP', false);

require_once dirname(__DIR__) . '/app.inc.php';

class index
{
    /**
     * 入口
     */
    public function doDefault()
    {
        $ver = trim($_GET['_v']);
        $method = trim($_GET['_method']);
        $appid = intval($_GET['appid']);
        $hash = trim($_GET['hash']);

        $deviceid = trim($_GET['deviceid']);
        $channel = trim($_GET['channel']);
        $time = trim($_GET['time']);

        $dir_name = $class_name = $method_name = '';
        if (preg_match('/^(\w+)\/(\w+)\/(\w+)$/i', $method, $match)) {
            list(/**/, $dir_name, $class_name, $method_name) = $match;
        } else {
            // 请求URL格式不正确
            $this->_error(506);
        }

        $this->_checkParam($ver, $appid, $method, $hash, $channel, $deviceid, $time);

        // 强行停止服务
        if (API_STOP === true) {
            $this->_stopServer($method, '抱歉, 服务器升级中, 请稍后访问');
        }
        $low_ver = config('api/apiconfig')[$appid]['verid'];
        if (version_compare($ver, $low_ver, '<')) {
            if ($method != 'Common/Init/start' && $method != 'Common/Init/checkVersion') {
                $this->_stopServer($method, '抱歉，当前版本已停用，请升级！');
            }
        }

        // 加载模块
        $module_file = APPS_PATH . "controls/Api/" . $dir_name . '/' . $class_name . CEXT;
        if (is_file($module_file)) {
            include_once($module_file);
        } else {
            // 调用方法不存在
            $this->_error(501);
        }

        $api_module = "\\controls\\Api\\$dir_name\\$class_name";

        // 适配版本
        if (API_VERSION != $ver) {
            $adapter_ver = self::getVerAdapter($ver, $dir_name, $class_name);
            if ($adapter_ver) {
                $api_module = "\\controls\\Adapter\\$adapter_ver\\$dir_name\\$class_name";
                $adapter_module = APPS_PATH . 'controls/Api/Adapter/' . $adapter_ver . '/' . $dir_name . '/' . $class_name . CEXT;
                if (is_file($adapter_module)) {
                    include_once($adapter_module);
                } else {
                    $this->_error(501);
                }
            }
        }

        // 过滤EMOJI
        if (isset($_POST) && $method != 'Common/Comment/add') {
            foreach ($_POST as &$v) {
                if (is_string($v)) {
                    $v = self::_fastRemoveEmoji($v);
                }
            }
        }

        // 运行模块
        $api = new $api_module();
        if (method_exists($api, $method_name)) {
            $api->$method_name();
        } else {
            $this->_error(501);
        }

    }

    /**
     * 检查参数
     *
     * @param string  $ver      版本
     * @param integer $appid    APPID
     * @param string  $method   方法路径
     * @param string  $hash     MD5 Hash
     * @param string  $channel  渠道名
     * @param string  $deviceid 设备ID
     * @param integer $time     时间戳
     * @return bool
     */
    private function _checkParam($ver, $appid, $method, $hash, $channel, $deviceid, $time)
    {

        // 检查参数
        $fields = [
            'deviceid' => $deviceid,
            'appid' => $appid,
            'hash' => $hash,
            'channel' => $channel,
            'version number' => $ver,
            'time' => $time,
            'http header User-Agent' => $_SERVER["HTTP_USER_AGENT"]
        ];

        foreach ($fields as $k => $v) {
            if (empty($v)) {
                if ($k == 'deviceid' && ($method == 'Common/Init/start' || $method == 'Employee/Init/start')) {
                    continue;
                }
                // 缺少必选参数：%s
                $this->_error(500, $k);
            }
        }

        $key = version_app_key($appid, $ver);
        if (empty($key)) {
            $key = config('api/apiconfig')[$appid]['appkey'];
        }
        if (!empty($key)) {
            // AppId不存在
            $this->_error(504);
            exit;
        }

        // 注册全局基础信息
        $GLOBALS[G_APP_ID] = $appid;
        $GLOBALS[G_VER] = $ver;
        $GLOBALS[G_DEVICE_ID] = $deviceid;
        $GLOBALS[G_CHANNEL] = $channel;

        // 本地环境特殊处理
        if (defined("__ENV__")) {
            if ((__ENV__ == 'TEST' || __ENV__ == 'DEV') && $_GET['hash'] === 'test') {
                return true;
            }
        }

        // 判断hash
        $real_hash = md5($key . $ver . $method . $appid . $deviceid . $channel . $time);
        if ($real_hash != $hash) {
            // HASH值错误
            $this->_error(505);
        }

        // 判断时间
        if ($method != 'Common/Init/start' && $method != 'Employee/Init/start') {
            $now_time = time();
            $sub_time = $now_time - $time;
            if ($sub_time > 600 || $sub_time < -600) {
                // 请求失效,请检查本机时间
                $this->_error(502);
            }
        }

        return true;
    }

    /**
     * 输出错误 并结束
     *
     * @param integer $errno 错误代码
     * @param string  $str   自定义错误内容
     * @return void
     */
    private function _error($errno, $str = '')
    {
        $json = [];
        $json['Status'] = $errno;
        $json['Result'] = error_msg($errno);

        if (!empty($str)) {
            $json['Result'] = sprintf($json['Result'], $str);
        }

        $json['Result'] = [
            'ErrorMsg' => $json['Result']
        ];

        if ($errno == '502') {
            $json['Result']['ServerTime'] = time();
        }

        header('Content-type: application/json');
        echo json_encode($json);
        exit();
    }

    /**
     * 停服
     *
     * @param string $method 方法
     * @param string $info   停服信息
     */
    private function _stopServer($method, $info)
    {
        $json = ['Status' => 508, 'Result' => ['ErrorMsg' => $info]];
        if ($method == 'Common/Init/start') {
            $result = [
                'StopServer' => [
                    "Code" => 1,
                    "Msg" => $info
                ],
                'Update' => (object)[],
                'AppScreen' => (object)[],
                'IsLogin' => 0,
                'DeviceId' => "",
                'ServerTime' => time()
            ];

            $json = [
                'Status' => 200,
                'Result' => $result
            ];
        }

        header('Content-type: application/json');
        exit(json_encode($json));
    }

    /**
     * 获取适配版本信息
     *
     * @param string $ver        版本信息
     * @param string $dir_name   目录名
     * @param string $class_name 类名
     * @return bool|string
     */
    private static function getVerAdapter($ver, $dir_name, $class_name)
    {
        $adapter = config('api/adapter');
        $name = $dir_name . '.' . $class_name;

        foreach ($adapter['='] as $k => $v) {
            if ($k == $ver && isset($v[$name])) {
                return self::_getVerAdapter($k);
            }
        }

        foreach ($adapter['<='] as $k => $v) {
            if (version_compare($ver, $k, '<=') && isset($v[$name])) {
                return self::_getVerAdapter($k);
            }
        }

        return false;
    }

    /**
     * 格式化版本信息
     *
     * @param string $ver 版本信息
     * @return string
     */
    private static function _getVerAdapter($ver)
    {
        return str_replace('.', '', $ver);
    }

    /**
     * 去除emoji表情
     *
     * @param string $text emoji代码
     * @return string
     */
    private static function _fastRemoveEmoji($text)
    {
        return Emoji::removeEmoji($text);
    }
}

$app->run();
