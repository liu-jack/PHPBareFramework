<?php defined('ROOT_PATH') or exit('Access deny');

/**
 *
 * 接口版本适配配置文件
 *
 * @author camfee<camfee@foxmail.com>
 *
 */

return [
    // 全等, 适配一个版本
    '=' => [],
    // 向下范围, 适配多个版本, 务必将小的版本号排列在前
    '<=' => [
        'v1.1.0' => [
            'Test.Index' => 1,
        ]
    ]
];