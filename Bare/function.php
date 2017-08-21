<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * 系统函数库
 *
 * @package Bare
 * @author  camfee <camfee@yeah.net>
 * @since   v1.0 2016.09.12
 */

/**
 * [自动加载函数]
 *
 * @param  [string] $class [命名空间 + 类名 如：\Controller\Home\Index ]
 */
spl_autoload_register(function ($class) {
    static $class_map = [];
    $class = trim(strtr($class, '\\', '/'), '/');
    if (!isset($class_map[$class])) {
        $class_dirs = [
            'Bare/' => 1,
            'Classes/' => 1,
            'Config/' => 1,
            'Controller/' => 1,
            'Model/' => 1,
            'Common/' => 1
        ];
        $class_prefix = substr($class, 0, strpos($class, '/') + 1);
        if (!empty($class_dirs[$class_prefix])) {
            if (strpos($class, 'Controller/') === 0) {
                $class_file = ROOT_PATH . $class . '.php';
            } else {
                $class_file = ROOT_PATH . $class . '.class.php';
            }
        } else {
            $class_file = LIB_PATH . $class . '.php';
            $class_file2 = LIB_PATH . $class . '.class.php';
        }

        if (is_file($class_file)) {
            include($class_file);
        } elseif (!empty($class_file2) && is_file($class_file2)) {
            include($class_file2);
        } else {
            throw new Exception("include file {$class_file} error");
        }
        // 无论成功失败, 自动加载只进行一次
        $class_map[$class] = $class;
    }
});

/**
 * html模板include其他模板函数 模板页面使用
 *
 * @param string $path [模板的路径 默认为
 *                     ROOT_PATH/View/模块名(module)/控制器名(controller)/方法名(action)]
 * @param string $ext  后缀名
 */
function view($path = '', $ext = VEXT)
{
    if (!empty($path)) {
        if (isset($_GET[ADMIN_VAR])) {
            $path = ADMIN_PATH . '/' . $path;
        }
        $view_path = VIEW_PATH . $path . $ext;
    } else {
        $view_path = VIEW_PATH . $GLOBALS['_PATH'] . $ext;
    }
    $view_path = parseTemplate($view_path);
    include_once $view_path;
}

/**
 * 简单的模板解析
 *
 * @param $path
 * @return string
 */
function parseTemplate($path)
{
    $cmp = filemtime($path); // md5_file($path)
    $md5 = md5($path);
    $c_path = CACHE_TEMPLATE_PATH;
    if (isset($_GET[ADMIN_VAR])) {
        $c_path .= strtolower(ADMIN_PATH) . '/';
    } else {
        $c_path .= strtolower($GLOBALS['_M']) . '/';
    }
    $cache_path = $c_path . $md5 . EXT;
    $cmp_path = $c_path . $md5;
    if (is_file($cache_path) && is_file($cmp_path)) {
        $cmp_cache = file_get_contents($cmp_path);
        if (strcmp($cmp, $cmp_cache) === 0) {
            return $cache_path;
        }
    }
    $pattern = [
        // {:view('add')}
        '@\{:([\w_]+\([\w/_"\',\[\]=>\$\s\.\x{4e00}-\x{9fa5}]*\))\}@isuU',
        // {@view('admin/public/header')}
        '@\{\@([\w_]+\([\w/_"\',\[\]=>\$\s\.\x{4e00}-\x{9fa5}]*\))\}@isuU',
        // {foreach ($group as $v)}{if(xx)}{elseif(xx)}
        '@\{(foreach|if|elseif)\s*(\([^}]*\))\}@isU',
        // {else}
        '@\{(else)\}@isU',
        // {/foreach}{/if}
        '@\{/(foreach|if)\}@isU',
        // {$a.b.c}
        '@\{(\$[\w_]+)\.([\w_]+)\.([\w_]+)\}@isU',
        // {$a.b}
        '@\{(\$[\w_]+)\.([\w_]+)\}@isU',
        // {$a} {$a['b']} {$a[$b['c']]}
        '@\{(\$[^}]+)\}@isU',
        // {STATICS_JS}
        '@\{([A-Z_]+)\}@isU',
        // {url('add')}
        '@\{([\w_]+\([\w/_"\',\[\]=>\$\s\.\x{4e00}-\x{9fa5}]*\))\}@isuU',
    ];
    $replace = [
        '<?php $this->$1?>',
        '<?php $1?>',
        "<?php $1$2:?>",
        "<?php $1:?>",
        "<?php end$1?>",
        "<?php echo $1['$2']['$3']?>",
        "<?php echo $1['$2']?>",
        '<?php echo $1?>',
        '<?php echo $1?>',
        "<?php echo $1?>",
    ];
    $content = file_get_contents($path);
    $content = preg_replace($pattern, $replace, $content);
    if (!is_dir(dirname($cache_path))) {
        mkdir(dirname($cache_path), 0777, true);
    }
    file_put_contents($cache_path, $content);
    file_put_contents($cmp_path, $cmp);
    unset($content);

    return $cache_path;
}

/**
 * url生成函数
 *
 * @param string       $url    module/controller/action
 * @param array|string $vars   get参数 id/55
 * @param  string|bool $domain 网站域名
 * @param  string      $suffix 静态后缀
 * @return  string url地址
 */
function url($url = '', $vars = '', $domain = '', $suffix = VEXT)
{
    if ($domain && !is_string($domain)) {
        $http = $_SERVER['HTTPS'] == "on" ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $port = $_SERVER['SERVER_PORT'] == 80 ? '' : ':' . $_SERVER['SERVER_PORT'];
        $domain = $http . $host . $port;
    }
    if (URL_MODE == 1) {
        $domain .= '/';
    } else {
        $domain .= '/index.php/';
    }
    if (empty($url)) {
        $temp = explode('/', $GLOBALS['_URL']);
        $temp = array_map('lcfirst', $temp);
        $url = implode('/', $temp);
    } elseif (substr_count($url, '/') == 0) {
        $temp = explode('/', $GLOBALS['_MURL']);
        $temp = array_map('lcfirst', $temp);
        $url = implode('/', $temp) . '/' . $url;
    } elseif (substr_count($url, '/') == 1) {
        $url = lcfirst($GLOBALS['_M']) . '/' . $url;
    }
    $params = $query = '';
    if (!empty($vars)) {
        if (is_array($vars)) {
            if (isset($vars[ADMIN_VAR])) {
                unset($vars[ADMIN_VAR]);
            }
            foreach ($vars as $k => $v) {
                $params .= '/' . $k . '/' . $v;

            }
        } else {
            $query = $vars;
        }
    }
    $urls = $domain . $url . $params . $suffix;
    if (isset($_GET[ADMIN_VAR])) {
        $urls .= '?' . ADMIN_VAR . '=' . $_GET[ADMIN_VAR];
        if (!empty($query)) {
            $urls .= '&' . $query;
        }
    } elseif (!empty($query)) {
        $urls .= '?' . $query;
    }

    return $urls;
}

/**
 * 列表页及详细页url映射（重写）地址生成
 *
 * @param string     $module   模块名称
 * @param string|int $itemid   项目id
 * @param string|int $columnid 内容id
 * @return bool|string
 */
function uri($module, $itemid, $columnid = 0)
{
    if (empty($module) || empty($itemid)) {
        return false;
    }
    $uri = '/' . $module . '/' . $itemid;
    if (!empty($columnid)) {
        $uri .= '/' . $columnid . VEXT;
    } else {
        $uri .= VEXT;
    }

    return $uri;
}

/**
 * 读取配置文件
 *
 * @param string $path 路径, 如 mobileapi/base
 * @param string $ext  扩展名, 默认 .php
 * @return array       不存在时返回空数组
 */
function config($path, $ext = '.php')
{
    static $confs = [];
    $name = $path . $ext;
    $path = CONFIG_PATH . $name;

    if (!isset($confs[$name])) {
        if (is_file($path)) {
            $conf = include($path);
            $confs[$name] = $conf;
        }
    }

    return is_array($confs[$name]) ? $confs[$name] : [];
}

/**
 * 文件日志
 *
 * @param string|array $content  内容
 * @param string       $name     文件名
 * @param string       $log_path 路径
 */
function logs($content, $name = '', $log_path = LOG_PATH)
{
    if (!empty($content)) {
        if (empty($name)) {
            if (IS_CLI) {
                $name = $_GET['argv'][0];
            } else {
                $name = trim($_SERVER['REQUEST_URI'], '/');
                if (empty($name)) {
                    $name = trim(str_replace(['.php', '.'], ['', '_'], $_SERVER['SCRIPT_NAME']), '/');
                }
            }
        }
        if (is_array($content)) {
            if (is_numeric(key($content))) {
                $content = json_encode($content);
            } else {
                $temp = '';
                foreach ($content as $k => $v) {
                    if (is_array($v)) {
                        $v = json_encode($v);
                    }
                    $temp .= $k . ': ' . $v . '  ';
                }
                $content = $temp;
            }
        }
        $dir = trim(dirname($name) != '.' ? dirname($name) : '', '/');
        $base = basename($name);
        $file = $log_path . $dir . '/' . date('Ym') . '/' . $base . '_' . date('d') . '.log';
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
        $content = $content . ' time: ' . date('Y-m-d H:i:s') . PHP_EOL;
        error_log($content, 3, $file);
    }
}

/**
 * 必须cli模式运行
 *
 * @param string $msg     退出信息
 * @param int    $memory  程序运行内存
 * @param int    $timeout 程序超时时间
 */
function need_cli($msg = 'Must CLI Mode', $memory = 0, $timeout = 0)
{
    IS_CLI ?: exit($msg);
    set_time_limit($timeout);
    if (!empty($memory)) {
        $memory = intval($memory);
        ini_set('memory_limit', $memory . 'M');
    }
}

/**
 * 输出带PRE的调试信息
 *
 * @param mixed $extra 多个变量
 * @return void
 */
function pre(...$extra)
{
    $args = func_get_args();
    echo '<pre>' . PHP_EOL;
    foreach ($args as $v) {
        print_r($v);
        echo PHP_EOL;
    }
    echo '</pre>' . PHP_EOL;
}

/**
 * 返回客户端IP
 *
 * @return string
 */
function ip()
{
    static $ip = null;
    if (!is_null($ip)) {
        return $ip;
    }
    $real_ip = $_SERVER['HTTP_X_REAL_IP'];
    $ip = $_SERVER['REMOTE_ADDR'];
    if (filter_var($real_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false) {
            $ip = $real_ip;
        }
    }
    if (!preg_match('/[\d\.]{7,15}/', $ip)) {
        $ip = '0.0.0.0';
    }

    return $ip;
}

/**
 * 页面重定向
 *
 * @param string $url     重定向目标URL
 * @param int    $mode    重定向模式, 值意义如下:
 *                        0 通过PHP的header()函数实现
 *                        1 通过JavaScript的Location实现
 *                        2 通过JavaScript的Location.replace实现
 *                        return void
 */
function redirect($url = '', $mode = 1)
{
    if (empty($url)) {
        $url = referer();
    }
    switch ($mode) {
        case 1:
            echo '<script type="text/javascript">location="' . $url . '";</script>';
            break;
        case 2:
            echo '<script type="text/javascript">location.replace("' . $url . '");</script>';
            break;
        default:
            header('Location: ' . $url);
            break;
    }
    exit;
}

/**
 * 获取跳转地址
 *
 * @param String $defualt 默认地址
 * @return String referer 跳转地址
 */
function referer($defualt = '/')
{
    if (!isset($_GET['referer']) && !isset($_SERVER['HTTP_REFERER'])) {
        return $defualt;
    }
    if (isset($_GET['referer'])) {
        $referer = trim($_GET['referer']);
    } else {
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = trim($_SERVER['HTTP_REFERER']);
        }
    }
    if (empty($referer)) {
        return $defualt;
    } else {
        return strip_tags($referer);
    }
}

/**
 * 加密 Cookie
 *
 * @param mixed $data 待加密的数据
 * @return string     返回一个包含初始向量(IV)、加密数据的字符串，均以十六进制表示，前 16 位为IV
 */
function cookie_encode($data)
{
    $checksum = sprintf("%u", crc32($_SERVER['HTTP_USER_AGENT']));
    $serialized = $checksum . '|' . json_encode($data);

    return \Classes\Encrypt\Blowfish::encode($serialized);
}

/**
 * 解密 Cookie
 *
 * @param string $encoded_hex 已加密数据
 * @return mixed              原始数据
 */
function cookie_decode($encoded_hex)
{
    $encoded_hex = trim($encoded_hex);
    $serialized = \Classes\Encrypt\Blowfish::decode($encoded_hex);
    if ($serialized === false) {
        return false;
    }
    $arr = explode('|', $serialized);
    if (count($arr) != 2) {
        return false;
    }
    $checksum = sprintf("%u", crc32($_SERVER['HTTP_USER_AGENT']));
    if ($checksum != $arr[0]) {
        return false;
    }

    return json_decode($arr[1], true);
}

/**
 * 数据输出
 *
 * @param int          $code 返回码 200：成功
 * @param array|string $data 接口输出的数据
 */
function output($code = 200, $data = [])
{
    $result['code'] = $code;
    if (is_string($data)) {
        $result['msg'] = $data;
    } else {
        $result['data'] = $data;
    }
    if (isset($_GET['callback'])) {
        if (!headers_sent()) {
            header("Cache-Control:maxage=1");
            header("Content-type: text/javascript; charset=UTF-8");
        }
        $jsonp = $_GET['callback'];
        exit($jsonp . '(' . json_encode($data) . ')');
    } else {
        exit(json_encode($result));
    }
}