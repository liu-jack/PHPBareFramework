<?php defined('ROOT_PATH') or exit('Access deny');

/**
 * 基础应用类
 *
 * @package Bare
 * @author  camfee <camfee@yeah.net>
 * @since   v1.0 2016.09.12
 */
class Bare
{
    /**
     * 初始化运行 路由解析
     */
    public static function init()
    {
        $path_info = self::urlMap(PATH_INFO);
        $url_param = explode('/', $path_info);
        if (!empty($url_param[0]) || !empty($_GET['m'])) {
            $GLOBALS['_M'] = !empty($url_param[0]) ? ucfirst($url_param[0]) : ucfirst($_GET['m']);
        }
        if (!empty($url_param[1]) || !empty($_GET['c'])) {
            $GLOBALS['_C'] = !empty($url_param[1]) ? ucfirst($url_param[1]) : ucfirst($_GET['c']);
        }
        if (!empty($url_param[2]) || !empty($_GET['a'])) {
            $GLOBALS['_A'] = !empty($url_param[2]) ? $url_param[2] : $_GET['a'];
        }
        $url_param = count($url_param) > 3 ? array_slice($url_param, 3) : [];
        if (!empty($url_param)) {
            // 用于把参数分为两两一组key=>value
            $kv = 0;
            // 获取地址栏参数的数组
            $get = [];
            foreach ($url_param as $k => $v) {
                if ($kv % 2 == 0) {
                    if (!empty($v)) {
                        if (empty($url_param[$k + 1])) {
                            //防止缺省值
                            $url_param[$k + 1] = '';
                        }
                        //Key=>Value 
                        $get[$v] = $url_param[$k + 1];
                    }
                }
                $kv++;
            }
            $_GET = array_merge($_GET, $get);
        }

        self::start();
    }

    /**
     * 开始访问
     */
    private static function start()
    {
        if (defined('API_VAR') && !empty($_GET[API_VAR])) {
            //接口访问
            self::visitApi();
            define('VISIT_TYPE', VISIT_TYPE_API);
        } elseif (defined('ADMIN_VAR') && isset($_GET[ADMIN_VAR])) {
            //后台访问
            self::visitWebAdmin();
            define('VISIT_TYPE', VISIT_TYPE_ADMIN);
        } else {
            //网站访问
            self::visitWeb();
            define('VISIT_TYPE', VISIT_TYPE_WEB);
        }

        $GLOBALS['_URL'] = $GLOBALS['_M'] . '/' . $GLOBALS['_C'] . '/' . $GLOBALS['_A'];
        $GLOBALS['_MURL'] = $GLOBALS['_M'] . '/' . $GLOBALS['_C'];

        // 开始访问
        if (!empty($GLOBALS['_ADAPTER_NAMESPACE'])) {
            $controller = '\\Controller' . $GLOBALS['_ADAPTER_NAMESPACE'];
        } else {
            $controller = '\\Controller' . $GLOBALS['_NAMESPACE'];
        }
        try {
            $bare = new $controller;
            $action = $GLOBALS['_A'];
            $bare->$action();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 接口访问
     */
    private static function visitApi()
    {
        $ver = trim($_GET[API_VAR]);
        $appid = intval($_GET['appid']);
        $hash = trim($_GET['hash']);
        $deviceid = trim($_GET['deviceid']);
        $app_type = intval($_GET['_t']);
        $time = trim($_GET['time']);
        $method = $GLOBALS['_M'] . '/' . $GLOBALS['_C'] . '/' . $GLOBALS['_A'];
        self::apiCheckParam($ver, $appid, $method, $hash, $app_type, $deviceid, $time);
        // 强行停止服务
        if (API_STOP === true) {
            self::apiStopServer($method, '抱歉, 服务器升级中, 请稍后访问');
        }
        $low_ver = config('api/apiconfig')[$appid]['verid'];
        if (version_compare($ver, $low_ver, '<')) {
            if ($method != 'Common/Init/start' && $method != 'Common/Init/checkVersion') {
                self::apiStopServer($method, '抱歉，当前版本已停用，请升级！');
            }
        }
        // 过滤EMOJI
        if (isset($_POST)) {
            foreach ($_POST as &$v) {
                if (is_string($v)) {
                    $v = \Classes\Tool\Emoji::removeEmoji($v);
                }
            }
        }
        // 适配版本
        if (API_VERSION != $ver) {
            $adapter_ver = self::apiVerAdapter($ver, $GLOBALS['_M'], $GLOBALS['_C']);
            if (!empty($adapter_ver)) {
                $GLOBALS['_ADAPTER_NAMESPACE'] = '\\' . API_PATH . '\\Adapter\\' . $adapter_ver . '\\' . $GLOBALS['_M'] . '\\' . $GLOBALS['_C'];
            }
        }

        $GLOBALS['_PATH'] = API_PATH . '/' . $GLOBALS['_M'] . '/' . $GLOBALS['_C'] . '/' . $GLOBALS['_A'];
        $GLOBALS['_MPATH'] = API_PATH . '/' . $GLOBALS['_M'] . '/' . $GLOBALS['_C'];
        $GLOBALS['_NAMESPACE'] = '\\' . API_PATH . '\\' . $GLOBALS['_M'] . '\\' . $GLOBALS['_C'];
    }

    /**
     * 后台访问
     */
    private static function visitWebAdmin()
    {
        $GLOBALS['_PATH'] = ADMIN_PATH . '/' . $GLOBALS['_M'] . '/' . $GLOBALS['_C'] . '/' . $GLOBALS['_A'];
        $GLOBALS['_MPATH'] = ADMIN_PATH . '/' . $GLOBALS['_M'] . '/' . $GLOBALS['_C'];
        $GLOBALS['_NAMESPACE'] = '\\' . ADMIN_PATH . '\\' . $GLOBALS['_M'] . '\\' . $GLOBALS['_C'];
        // 静态文件路径定义
        $s_path = HTTP_HOST . STATICS_URI . strtolower(ADMIN_PATH);
        if (is_dir(ROOT_PATH . STATICS_URI . strtolower(ADMIN_PATH) . '/' . strtolower($GLOBALS['_M']) . '/' . strtolower($GLOBALS['_C']) . '/' . strtolower($GLOBALS['_A']))) {
            $s_path .= '/' . strtolower($GLOBALS['_C']) . '/' . strtolower($GLOBALS['_A']);
        } elseif (is_dir(ROOT_PATH . STATICS_URI . strtolower(ADMIN_PATH) . '/' . strtolower($GLOBALS['_M']) . '/' . strtolower($GLOBALS['_C']))) {
            $s_path .= '/' . strtolower($GLOBALS['_C']);
        } elseif (is_dir(ROOT_PATH . STATICS_URI . strtolower(ADMIN_PATH) . '/' . strtolower($GLOBALS['_M']))) {
            $s_path .= '/' . strtolower($GLOBALS['_M']);
        }
        define('STATICS_URL', $s_path . '/');
        define('STATICS_JS', $s_path . '/js/');
        define('STATICS_CSS', $s_path . '/css/');
        define('STATICS_IMG', $s_path . '/images/');
    }

    /**
     * 网站访问
     */
    private static function visitWeb()
    {
        $GLOBALS['_PATH'] = $GLOBALS['_M'] . '/' . $GLOBALS['_C'] . '/' . $GLOBALS['_A'];
        $GLOBALS['_MPATH'] = $GLOBALS['_M'] . '/' . $GLOBALS['_C'];
        $GLOBALS['_NAMESPACE'] = '\\' . $GLOBALS['_M'] . '\\' . $GLOBALS['_C'];

        // 静态文件路径定义
        $s_path = HTTP_HOST . STATICS_URI . strtolower($GLOBALS['_M']);
        if (is_dir(ROOT_PATH . STATICS_URI . strtolower($GLOBALS['_M']) . '/' . strtolower($GLOBALS['_C']) . '/' . strtolower($GLOBALS['_A']))) {
            $s_path .= '/' . strtolower($GLOBALS['_C']) . '/' . strtolower($GLOBALS['_A']);
        } elseif (is_dir(ROOT_PATH . STATICS_URI . strtolower($GLOBALS['_M']) . '/' . strtolower($GLOBALS['_C']))) {
            $s_path .= '/' . strtolower($GLOBALS['_C']);
        }
        define('STATICS_URL', $s_path . '/');
        define('STATICS_JS', $s_path . '/js/');
        define('STATICS_CSS', $s_path . '/css/');
        define('STATICS_IMG', $s_path . '/images/');
    }

    /**
     * url地址映射（重写）
     *
     * @param string $path_info url参数
     * @return mixed
     */
    private static function urlMap($path_info)
    {
        $map = config('urlmap');
        if (!empty($map)) {
            foreach ($map as $v) {
                if (!empty($v['pos']) && !empty($v['rules'])) {
                    if (stripos($path_info, $v['pos']) === 0) {
                        $path_info = preg_replace(array_keys($v['rules']), array_values($v['rules']), $path_info);
                        break;
                    }
                }
            }
        }

        return $path_info;
    }

    /**
     * @var array 错误代码
     */
    private static $api_errno = [
        500 => '缺少必选参数：%s',
        501 => '调用方法不存在',
        502 => '请求失效,请检查本机时间',
        503 => '未知错误, 代码：%d',
        504 => 'AppId不存在',
        505 => 'HASH值错误',
        506 => '请求URL格式不正确',
        507 => '仅公测版本可用',
        508 => '服务器维护中，暂停服务',
    ];

    /**
     * 检查参数
     *
     * @param string  $ver      版本
     * @param integer $appid    APPID
     * @param string  $method   方法路径
     * @param string  $hash     MD5 Hash
     * @param string  $app_type 应用类型
     * @param string  $deviceid 设备ID
     * @param integer $time     时间戳
     * @return bool
     */
    private static function apiCheckParam($ver, $appid, $method, $hash, $app_type, $deviceid, $time)
    {
        // 检查参数
        $fields = [
            'deviceid' => $deviceid,
            'appid' => $appid,
            'hash' => $hash,
            'app_type' => $app_type,
            'version' => $ver,
            'time' => $time
        ];
        if (in_array($app_type, [0, 1])) {
            unset($fields['deviceid']);
        }

        foreach ($fields as $k => $v) {
            if (empty($v) && $v !== 0) {
                if ($k == 'deviceid' && $method == 'Common/Init/start') {
                    continue;
                }
                // 缺少必选参数：%s
                self::apiError(500, $k);
            }
        }

        $appconfig = config('api/apiconfig')[$appid];
        if (!empty($appconfig)) {
            $key = $appconfig['appkey'];
        } else {
            // AppId不存在
            self::apiError(504);
            exit;
        }

        if (!isset($GLOBALS['g_app_types'][$app_type])) {
            // app_type不存在
            self::apiError(500, '_t');
            exit;
        }

        // 注册全局基础信息
        $GLOBALS['g_appid'] = $appid;
        $GLOBALS['g_apptype'] = $app_type;
        $GLOBALS['g_ver'] = $ver;
        $GLOBALS['g_deviceid'] = $deviceid;

        // 本地环境特殊处理
        if (defined("__ENV__")) {
            if ((__ENV__ == 'TEST' || __ENV__ == 'DEV') && $_GET['hash'] === 'test') {
                return true;
            }
        }

        // 判断hash
        $hash1 = $key . $ver . $method;
        foreach ($_GET as $k => $v) {
            if ($k != '_v' && $k != 'hash' && $v !== '') {
                $hash1 .= $v;
            }
        }
        $real_hash = md5($hash1);
        if ($real_hash != $hash) {
            // HASH值错误
            self::apiError(505);
        }

        // 判断时间
        if ($method != 'Common/Init/start') {
            $now_time = time();
            $sub_time = $now_time - $time;
            if ($sub_time > 600 || $sub_time < -600) {
                // 请求失效,请检查本机时间
                self::apiError(502);
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
    private static function apiError($errno, $str = '')
    {
        $json = [];
        $json['Code'] = $errno;
        $json['Msg'] = self::$api_errno[$errno];
        if (!empty($str)) {
            $json['Msg'] = sprintf($json['Msg'], $str);
        }
        if ($errno == '502') {
            $json['Data']['ServerTime'] = time();
        }
        header('Content-type: application/json');
        exit(json_encode($json));
    }

    /**
     * 停服
     *
     * @param string $method 方法
     * @param string $info   停服信息
     */
    private static function apiStopServer($method, $info)
    {
        $json = ['Code' => 508, 'Msg' => $info];
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
                'Code' => 200,
                'Data' => $result
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
    private static function apiVerAdapter($ver, $dir_name, $class_name)
    {
        $adapter = config('api/adapter');
        $name = $dir_name . '.' . $class_name;

        foreach ($adapter['='] as $k => $v) {
            if ($k == $ver && isset($v[$name])) {
                return self::apiVerFormat($k);
            }
        }

        foreach ($adapter['<='] as $k => $v) {
            if (version_compare($ver, $k, '<=') && isset($v[$name])) {
                return self::apiVerFormat($k);
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
    private static function apiVerFormat($ver)
    {
        return str_replace('.', '', $ver);
    }
}
