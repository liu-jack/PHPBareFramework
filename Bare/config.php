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
define('DATA_PATH', ROOT_PATH . 'Data/');
define('LOG_PATH', DATA_PATH . 'log/');
define('FONT_PATH', DATA_PATH . 'font/');
define('CACHE_PATH', DATA_PATH . 'cache/');
define('CACHE_TEMPLATE_PATH', DATA_PATH . 'cache/template/');
define('UPLOAD_PATH', ROOT_PATH . 'Public/upload/');
define('UPLOAD_URI', '/Public/upload/');
define('HTTP_HOST', 'http://' . (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''));
define('JS_PATH', HTTP_HOST . '/Public/js/');
define('CSS_PATH', HTTP_HOST . '/Public/css/');
define('IMAGES_PATH', HTTP_HOST . '/Public/images/');
define('STATICS_PATH', HTTP_HOST . '/Public/statics/');
define('STATICS_URI', '/Public/statics/');

define('EXT', '.php');
define('CEXT', '.class.php');
define('VEXT', '.html');
define('URL_MODE', 1); //0 一般模式 1 rewrite模式

define('V_WEB', 0); // web访问
define('V_API', 1); // 接口访问
define('V_ADMIN', 2); // 后台访问

if (php_sapi_name() !== 'cli') {
    $search = ['/index.php', '/admin.php', '/api.php', VEXT];
    define('PATH_INFO', trim(str_replace($search, '', $_SERVER['DOCUMENT_URI']), '/'));
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

$env = get_cfg_var('environment');
if ($env === 'DEV') {
    // 定义开发环境  
    define('__ENV__', 'DEV');
    define('__KEY__', '86f64532553eeb9111cf66233d6726df');
} elseif ($env === 'TEST') {
    // 定义测试环境
    define('__ENV__', 'TEST');
    define('__KEY__', '6facf75d3bac75b1cdfde6d94ee0aaec');
} else {
    // 定义线上环境
    define('__ENV__', 'ONLINE');
    define('__KEY__', '1be811d9b9a37d91893b3588e270d519');
}