<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * 公共配置
 */
$GLOBALS['_M'] = 'Book';
$GLOBALS['_C'] = 'Index';
$GLOBALS['_A'] = 'index';

// 接口配置
define('API_VERSION', 'v1.0.0'); // 接口版本
define('API_STOP', false);       // 接口停服
define('APP_TYPE_WEB', 0);
define('APP_TYPE_WAP', 1);
define('APP_TYPE_ADR', 2);
define('APP_TYPE_IOS', 3);
$GLOBALS['g_app_types'] = [
    APP_TYPE_WEB => APP_TYPE_WEB,
    APP_TYPE_WAP => APP_TYPE_WAP,
    APP_TYPE_ADR => APP_TYPE_ADR,
    APP_TYPE_IOS => APP_TYPE_IOS,
];