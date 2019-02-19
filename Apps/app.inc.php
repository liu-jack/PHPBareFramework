<?php

/**
 * 应用初始化程序
 *
 * @author  camfee
 *
 */

// 检测PHP环境
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    die('require PHP >= 7.0.0 !');
}
// 程序根目录
define('ROOT_PATH', dirname(__DIR__) . '/');
// 加载系统配置
require ROOT_PATH . 'Bare/config.php';
// 共用配置
require COMMON_PATH . 'config.php';
// 加载系统函数
require CORE_PATH . 'function.php';
// 共用函数库
require COMMON_PATH . 'common.php';
// 应用类
require CORE_PATH . 'App.class.php';

if (defined('SESSION') && SESSION) {
    session_start();
}

/**
 * smarty 配置
 */
// 定义HTTP协议
define("HTTP_SCHEME", (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') ? 'https' : 'http');
// 网站起始路径
define('ROOT_URL', auto_host(HTTP_SCHEME . '://zf.bare.com/'));
// 初始化配置变量
$cfg = [
    'path' => [
        'conf' => CONFIG_PATH,
        'root' => ROOT_PATH,
        'lib' => LIB_PATH,
        'class' => CLASSES_PATH,
        'common' => COMMON_PATH,
        'cache' => CACHE_PATH,
        'upload' => UPLOAD_PATH,
        'fonts' => FONT_PATH,
        'temp' => TEMP_PATH,
        'module' => MODEL_PATH,
        'current' => dirname($_SERVER['SCRIPT_FILENAME']) . '/', // 运行脚本所在目录
    ],
    'url' => [
        // 网站首页
        'root' => ROOT_URL,
        'js' => ROOT_URL . 'public/js/',
        'css' => ROOT_URL . 'public/css/',
        'swf' => ROOT_URL . 'public/swf/',
        'images' => ROOT_URL . 'public/images/',
        'statics' => ROOT_URL . 'public/statics/',
        'public' => ROOT_URL . 'public/',
    ],
    'cache' => [
        'root' => CACHE_PATH,  // engine=memcached 时为服务器地址
        'engine' => 'file', // file|memcached
        'port' => 11211, // engine=memcached 时才有意义
        'timeout' => 60, // engine=memcached 时才有意义
    ],
    // 网站简称全称
    'site' => [
        'title' => '29书',
        'name' => '29书',
    ],
];
//页面信息
$cfg['page'] = [
    'charset' => 'UTF-8',
    'contentType' => 'text/html',
    'title' => '',
    'cached' => true,
    'engine' => 'smarty',
    'adminEngine' => 'adminSmarty',
    'css' => [],
    'js' => [],
];
// Smarty tpl与缓存
$cfg['smarty'] = [
    'template_dir' => VIEW_PATH . 'Apps/',
    'compile_dir' => $cfg['path']['cache'] . 'smarty/',
];

/**
 * 对Smarty中的html标记添加引号的过滤
 *
 * @param String $str
 * @return String
 */
function _htmlspecialchars($str)
{
    return htmlspecialchars($str, ENT_QUOTES);
}

// 初始化application
$app = new \Bare\App();
