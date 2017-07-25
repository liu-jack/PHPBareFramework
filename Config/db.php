<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * 数据库配置
 */
$config = [
    'DEV' => [
        'mysql' => [
            'default' => [
                'w' => ['host' => '10.7.10.250', 'user' => 'root', 'password' => '123456'],
                'r' => ['host' => '10.7.10.250', 'user' => 'root', 'password' => '123456'],
            ],
        ],
        'memcache' => [
            'default' => [
                ['host' => '10.7.10.250', 'port' => 11211]
            ]
        ],
        'redis' => [
            'default' => [
                'w' => ['host' => '10.7.10.250', 'port' => 6379],
                'r' => ['host' => '10.7.10.250', 'port' => 6379],
            ]
        ],
        'search' => [
            'default' => ['host' => '10.7.10.250', 'port' => 9200],
        ],
        'mongodb' => [
            'default' => ['host' => '10.7.10.250', 'port' => 27017, 'user' => 'camfee', 'password' => 'camfee29']
        ]
    ],
    'TEST' => [
        'mysql' => [
            'default' => [
                'w' => ['host' => '192.168.1.105', 'user' => 'proxy_w', 'password' => 'proxy'],
                'r' => ['host' => '192.168.1.105', 'user' => 'proxy_r', 'password' => 'proxy'],
            ],
        ],
        'memcache' => [
            'default' => [
                ['host' => '192.168.1.105', 'port' => 11211]
            ]
        ],
        'redis' => [
            'default' => [
                'w' => ['host' => '192.168.1.105', 'port' => 6379],
                'r' => ['host' => '192.168.1.105', 'port' => 6379],
            ]
        ],
        'search' => [
            'default' => ['host' => '192.168.1.105', 'port' => 9200],
        ],
        'mongodb' => [
            'default' => ['host' => '192.168.1.105', 'port' => 27017, 'user' => 'camfee', 'password' => 'camfee29']
        ]
    ],
    'ONLINE' => [
        'mysql' => [
            'default' => [
                'w' => ['host' => '192.168.1.111', 'user' => 'camfee', 'password' => 'camfee29'],
                'r' => ['host' => '192.168.1.111', 'user' => 'camfee', 'password' => 'camfee29']
            ],
            'other' => [
                'w' => ['host' => '192.168.1.105', 'user' => 'camfee', 'password' => 'camfee29'],
                'r' => ['host' => '192.168.1.105', 'user' => 'camfee', 'password' => 'camfee29']
            ],
        ],
        'memcache' => [
            'default' => [
                ['host' => '192.168.1.111', 'port' => 11211]
            ],
            'other' => [
                ['host' => '192.168.1.105', 'port' => 11211]
            ],
        ],
        'redis' => [
            'default' => [
                'w' => ['host' => '192.168.1.111', 'port' => 6379],
                'r' => ['host' => '192.168.1.111', 'port' => 6379]
            ],
            'other' => [
                'w' => ['host' => '192.168.1.105', 'port' => 6379],
                'r' => ['host' => '192.168.1.105', 'port' => 6379]
            ],
        ],
        'search' => [
            'default' => ['host' => '192.168.1.111', 'port' => 9200],
        ],
        'mongodb' => [
            'default' => ['host' => '192.168.1.111', 'port' => 27017, 'user' => 'camfee', 'password' => 'camfee29']
        ]
    ]
];

return $config[__ENV__];
