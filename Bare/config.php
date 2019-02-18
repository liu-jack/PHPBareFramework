<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * 系统配置
 */
define('CORE_PATH', ROOT_PATH . 'Bare/');
define('CONTROLLER_PATH', ROOT_PATH . 'Controller/');
define('MODEL_PATH', ROOT_PATH . 'Model/');
define('CLASSES_PATH', ROOT_PATH . 'Classes/');
define('LIB_PATH', ROOT_PATH . 'Lib/');
define('VIEW_PATH', ROOT_PATH . 'View/');
define('CONFIG_PATH', ROOT_PATH . 'Config/');
define('COMMON_PATH', ROOT_PATH . 'Common/');
define('APPS_PATH', ROOT_PATH . 'Apps/');
define('DATA_PATH', ROOT_PATH . 'Data/');
define('LOG_PATH', DATA_PATH . 'logs/');
define('FONT_PATH', DATA_PATH . 'font/');
define('TEMP_PATH', DATA_PATH . 'temp/');
define('CACHE_PATH', DATA_PATH . 'cache/');
define('CACHE_TEMPLATE_PATH', CACHE_PATH . 'template/');
define('FILE_CACHE_PATH', CACHE_PATH . 'fileCache/');
define('UPLOAD_PATH', ROOT_PATH . 'Public/upload/');
define('UPLOAD_URI', '/Public/upload/');
define('CURRENT_HOST', !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '29shu.iok.la');
define('CURRENT_PROTOCOL', empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off' ? 'http://' : 'https://');
define('HOST_DOMAIN', 'zf.bare.com');
define('IMG_HOST', '');
define('STATICS_HOST', '');
define('HTTP_HOST', CURRENT_PROTOCOL . HOST_DOMAIN);
define('CURRENT_HTTP_HOST', CURRENT_PROTOCOL . CURRENT_HOST);
define('JS_PATH', STATICS_HOST . '/Public/js/');
define('CSS_PATH', STATICS_HOST . '/Public/css/');
define('IMAGES_PATH', STATICS_HOST . '/Public/images/');
define('STATICS_PATH', STATICS_HOST . '/Public/statics/');
define('STATICS_URI', '/Public/statics/');

define('EXT', '.php');
define('CEXT', '.class.php');
define('VEXT', '.html');
define('URL_MODE', 1); //0 一般模式 1 rewrite模式

define('V_WEB', 0); // web访问
define('V_API', 1); // 接口访问
define('V_ADMIN', 2); // 后台访问

if (php_sapi_name() !== 'cli') {
    $path_info = str_replace(VEXT, '', $_SERVER['DOCUMENT_URI'] ?? $_SERVER['PATH_INFO']);
    $pos = stripos($path_info, '.php');
    $path_info = $pos === false ? $path_info : substr($path_info, $pos + 4);
    define('PATH_INFO', trim($path_info, '/'));
    define('IS_CLI', false);
} else {
    define('PATH_INFO', trim($argv[1], '/'));
    define('IS_CLI', true);
    $_GET['argv'] = array_slice($argv, 1);
}
if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
    define('IS_AJAX', true);
} else {
    define('IS_AJAX', false);
};

// 实时日志开关配置
define('RUNTIME_LOG', false);
defined('JF_LOG_DEBUG') or define('JF_LOG_DEBUG', 0);
defined('JF_LOG_INFO') or define('JF_LOG_INFO', 1);
defined('JF_LOG_NOTICE') or define('JF_LOG_NOTICE', 2);
defined('JF_LOG_WARNING') or define('JF_LOG_WARNING', 3);
defined('JF_LOG_ERROR') or define('JF_LOG_ERROR', 4);
defined('JF_LOG_CRITICAL') or define('JF_LOG_CRITICAL', 5);

$env = strtoupper(get_cfg_var('environment'));
define('DEV', 'DEV');
define('TEST', 'TEST');
define('ONLINE', 'ONLINE');
if ($env === 'DEV') {
    // 定义开发环境  
    define('__ENV__', DEV);
    define('__KEY__', '86f64532553eeb9111cf66233d6726df');
    define('IS_ONLINE', false);
    define('__LOG_LEVEL__', JF_LOG_DEBUG);
    define('__IP__', '127.0.0.1');
} elseif ($env === 'TEST') {
    // 定义测试环境
    define('__ENV__', TEST);
    define('__KEY__', '6facf75d3bac75b1cdfde6d94ee0aaec');
    define('IS_ONLINE', false);
    define('__LOG_LEVEL__', JF_LOG_DEBUG);
    define('__IP__', '127.0.0.1');
} else {
    // 定义线上环境
    define('__ENV__', ONLINE);
    define('__KEY__', '1be811d9b9a37d91893b3588e270d519');
    define('IS_ONLINE', true);
    define('__LOG_LEVEL__', JF_LOG_INFO);
    define('__IP__', '127.0.0.1');
}
