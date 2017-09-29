<?php
/**
 * API接口入口
 *
 * @author camfee <camfee@yeah.net>
 */

define('ROOT_PATH', __DIR__ . '/');

$GLOBALS['_M'] = 'Test';
$GLOBALS['_C'] = 'Index';
$GLOBALS['_A'] = 'getIndex';

require ROOT_PATH . 'Bare/bare.php';
