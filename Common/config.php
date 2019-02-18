<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * 公共配置
 */
// 默认访问方法
$GLOBALS['_M'] = $GLOBALS['_M'] ?? 'Game';
$GLOBALS['_C'] = $GLOBALS['_C'] ?? 'H5';
$GLOBALS['_A'] = $GLOBALS['_A'] ?? 'index';
// 接口访问配置
define('API_PATH', 'Api');       // Api路径名
define('API_VAR', '_v');         // 接口访问区分变量名 $_GET['_v']
// 后台访问配置
define('ADMIN_PATH', 'Admin');   // 后台路径名
define('ADMIN_VAR', '_b');       // 后台访问区分变量名 $_GET['_b']
define('SUPER_ADMIN_GROUP', 29); // 超级管理员分组

define('PAGE_SIZE', 15);         // 分页
define('PAGE_VAR', 'p');         // 页码参数名

// 接口配置
define('API_VERSION', 'v1.0.0'); // 接口版本
define('API_STOP', false);       // 接口停服
define('APP_TYPE_WEB', 0);       // web
define('APP_TYPE_WAP', 1);       // wap
define('APP_TYPE_ADR', 2);       // android
define('APP_TYPE_IOS', 3);       // ISO
define('APP_TYPE_XCX', 4);       // 小程序
define('APP_APPID_WEB', 10);     // web appid
define('APP_APPID_WAP', 30);     // wap appid
define('APP_APPID_ADR', 50);     // android appid
define('APP_APPID_IOS', 70);     // ISO appid
define('APP_APPID_XCX', 90);     // 小程序

define('G_TYPES', 'g_app_types');
define('G_APP_ID', 'g_appid');
define('G_APP_TYPE', 'g_apptype');
define('G_VER', 'g_ver');
define('G_CHANNEL', 'g_channel');
define('G_DEVICE_ID', 'g_deviceid');

$GLOBALS[G_TYPES] = [
    APP_TYPE_WEB => APP_TYPE_WEB,
    APP_TYPE_WAP => APP_TYPE_WAP,
    APP_TYPE_ADR => APP_TYPE_ADR,
    APP_TYPE_IOS => APP_TYPE_IOS,
    APP_TYPE_XCX => APP_TYPE_XCX,
];