<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * API服务端配置 appid >100为其他外部应用
 */
$config = [
    10 => [ // web
        'appid' => 10,
        'verid' => 'v1.0.0',
        'appkey' => 'fe411dc7de70fb26cdc18e860b5f03aa',
        'rsakey' => '',
    ],
    30 => [ // wap
        'appid' => 30,
        'verid' => 'v1.0.0',
        'appkey' => '68992dea816835d48918c56bb60872b7',
        'rsakey' => '',
    ],
    50 => [ // android
        'appid' => 50,
        'verid' => 'v1.0.0',
        'appkey' => 'e543ae2b86821b4e85bfe94088293366',
        'rsakey' => '',
    ],
    70 => [ // ios
        'appid' => 70,
        'verid' => 'v1.0.0',
        'appkey' => 'c4491d446bb9fbd9cc504228466fd939',
        'rsakey' => '',
    ],
];

return $config;