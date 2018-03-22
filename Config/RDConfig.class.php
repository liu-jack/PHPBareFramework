<?php
/**
 * RDConfig.class.php 推荐数据key配置
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-3-3 下午3:17
 *
 */

namespace Config;

defined('ROOT_PATH') or exit('Access deny');

class RDConfig
{
    // 数据保存获取key mysql memcache
    const APP_TEST = 'app_test';
    // 数据保存获取key redis
    const REDIS_APP_TEST = 'redis_app_test';
    // 支持的访问key数据
    const KEY_CONFIG = [
        self::APP_TEST => 'app test',
        self::REDIS_APP_TEST => 'redis app test',
    ];
}