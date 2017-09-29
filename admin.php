<?php
/**
 * 网站后台入口
 *
 * @author camfee <camfee@yeah.net>
 */

define('ROOT_PATH', __DIR__ . '/');

$GLOBALS['_M'] = 'Admin';
$GLOBALS['_C'] = 'Index';
$GLOBALS['_A'] = 'index';
$_GET['_b'] = 1;

require ROOT_PATH . 'Bare/bare.php';
