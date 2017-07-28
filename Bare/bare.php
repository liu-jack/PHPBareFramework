<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * bare框架入口
 * @author camfee <camfee@yeah.net>
 * @since v1.0 2016.09.12
 */

// 检测PHP环境
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    die('require PHP >= 7.0.0 !');
}
// 加载系统配置
require 'config.php';
// 共用配置
require COMMON_PATH . 'config.php';
// 加载系统函数
require 'function.php';
// 共用函数库
require COMMON_PATH . 'common.php';
// url解析处理 初始化
require 'Bare.class.php';
Bare::init();