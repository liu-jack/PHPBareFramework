<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * 数据库配置
 */
$config = [
    'DEV' => [
        'mysql' => [
            'create' => [
                'db' => ['host' => '10.7.10.253', 'port' => 3306, 'user' => 'camfee', 'password' => 'camfee29']
            ],
            'default' => [
                'w' => ['host' => '10.7.10.253', 'user' => 'camfee', 'password' => 'camfee29'],
                'r' => ['host' => '10.7.10.253', 'user' => 'camfee', 'password' => 'camfee29'],
            ],
        ],
        'memcache' => [
            'default' => [
                ['host' => '10.7.10.253', 'port' => 11211]
            ]
        ],
        'redis' => [
            'default' => [
                'w' => ['host' => '10.7.10.253', 'port' => 6379],
                'r' => ['host' => '10.7.10.253', 'port' => 6379],
            ]
        ],
        'search' => [
            'default' => ['host' => '10.7.10.253', 'port' => 9200],
        ],
        'mongodb' => [
            'default' => ['host' => '10.7.10.253', 'port' => 27017, 'user' => 'camfee', 'password' => 'camfee29']
        ]
    ],
    'TEST' => [
        'mysql' => [
            'create' => [
                'db' => ['host' => '192.168.1.102', 'port' => 3306, 'user' => 'camfee', 'password' => 'camfee29']
            ],
            'default' => [
                'w' => ['host' => '192.168.1.102', 'user' => 'camfee', 'password' => 'camfee29'],
                'r' => ['host' => '192.168.1.102', 'user' => 'camfee', 'password' => 'camfee29'],
            ],
        ],
        'memcache' => [
            'default' => [
                ['host' => '192.168.1.102', 'port' => 11211]
            ]
        ],
        'redis' => [
            'default' => [
                'w' => ['host' => '192.168.1.102', 'port' => 6379],
                'r' => ['host' => '192.168.1.102', 'port' => 6379],
            ]
        ],
        'search' => [
            'default' => ['host' => '192.168.1.102', 'port' => 9200],
        ],
        'mongodb' => [
            'default' => ['host' => '192.168.1.102', 'port' => 27017, 'user' => 'camfee', 'password' => 'camfee29']
        ]
    ],
    'ONLINE' => [
        'mysql' => [
            'create' => [
                'db' => ['host' => '192.168.1.105', 'port' => 3306, 'user' => 'camfee', 'password' => 'camfee29']
            ],
            'default' => [
                'w' => ['host' => '192.168.1.105', 'user' => 'camfee', 'password' => 'camfee29'],
                'r' => ['host' => '192.168.1.105', 'user' => 'camfee', 'password' => 'camfee29']
            ],
            'other' => [
                'w' => ['host' => '192.168.1.111', 'user' => 'camfee', 'password' => 'camfee29'],
                'r' => ['host' => '192.168.1.111', 'user' => 'camfee', 'password' => 'camfee29']
            ],
        ],
        'memcache' => [
            'default' => [
                ['host' => '192.168.1.105', 'port' => 11211]
            ],
            'other' => [
                ['host' => '192.168.1.111', 'port' => 11211]
            ],
        ],
        'redis' => [
            'default' => [
                'w' => ['host' => '192.168.1.105', 'port' => 6379],
                'r' => ['host' => '192.168.1.105', 'port' => 6379]
            ],
            'other' => [
                'w' => ['host' => '192.168.1.111', 'port' => 6379],
                'r' => ['host' => '192.168.1.111', 'port' => 6379]
            ],
        ],
        'search' => [
            'default' => ['host' => '192.168.1.105', 'port' => 9200],
        ],
        'mongodb' => [
            'default' => ['host' => '192.168.1.105', 'port' => 27017, 'user' => 'camfee', 'password' => 'camfee29']
        ]
    ]
];

return $config[__ENV__];
