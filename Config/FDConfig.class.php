<?php

/**
 * FDConfig.php 文件缓存键值配置
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-5-8 下午3:12
 *
 */

namespace Config;

defined('ROOT_PATH') or exit('Access deny');

class FDConfig
{
    // 文件缓存路径
    const PATH_DEFAULT = 'default';
    // 缓存文件名
    const KEY_TEST = 'test';
    const KEY_SET = 'set';
    // 映射数组
    const KEY_CONFIG = [
        self::KEY_TEST => self::PATH_DEFAULT,
        self::KEY_SET => self::PATH_DEFAULT,
    ];
}
