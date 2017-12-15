<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * 数据库配置
 */

define('__MYSQL_IP__', __IP__);
define('__MEMCACHE_IP__', __IP__);
define('__REDIS_IP__', __IP__);
define('__SEARCH_IP__', __IP__);
define('__MONGODB_IP__', __IP__);

$config = [
    'DEV' => [
        'mysql' => [
            'create' => [
                'db' => ['host' => __MYSQL_IP__, 'port' => 3306, 'user' => 'camfee', 'password' => 'camfee29']
            ],
            'default' => [
                'w' => ['host' => __MYSQL_IP__, 'user' => 'camfee', 'password' => 'camfee29'],
                'r' => ['host' => __MYSQL_IP__, 'user' => 'camfee', 'password' => 'camfee29'],
            ],
        ],
        'memcache' => [
            'default' => [
                ['host' => __MEMCACHE_IP__, 'port' => 11211]
            ]
        ],
        'redis' => [
            'default' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 7380],
                'r' => ['host' => __REDIS_IP__, 'port' => 7380],
            ],
            'passport' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 7381],
                'r' => ['host' => __REDIS_IP__, 'port' => 7381],
            ],
            'account' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 7382],
                'r' => ['host' => __REDIS_IP__, 'port' => 7382],
            ],
            'queue' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 7383],
                'r' => ['host' => __REDIS_IP__, 'port' => 7383]
            ],
            'mobile' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 7384],
                'r' => ['host' => __REDIS_IP__, 'port' => 7384]
            ],
            'notice' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 7385],
                'r' => ['host' => __REDIS_IP__, 'port' => 7385]
            ],
            'other' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 7386],
                'r' => ['host' => __REDIS_IP__, 'port' => 7386]
            ]
        ],
        'search' => [
            'default' => ['host' => __SEARCH_IP__, 'port' => 9200],
        ],
        'mongodb' => [
            'default' => ['host' => __MONGODB_IP__, 'port' => 27017, 'user' => 'camfee', 'password' => 'camfee29']
        ]
    ],
    'TEST' => [
        'mysql' => [
            'create' => [
                'db' => ['host' => __MYSQL_IP__, 'port' => 3306, 'user' => 'camfee', 'password' => 'camfee29']
            ],
            'default' => [
                'w' => ['host' => __MYSQL_IP__, 'user' => 'camfee', 'password' => 'camfee29'],
                'r' => ['host' => __MYSQL_IP__, 'user' => 'camfee', 'password' => 'camfee29'],
            ],
        ],
        'memcache' => [
            'default' => [
                ['host' => __MEMCACHE_IP__, 'port' => 11211]
            ]
        ],
        'redis' => [
            'default' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6379],
                'r' => ['host' => __REDIS_IP__, 'port' => 6379],
            ]
        ],
        'search' => [
            'default' => ['host' => __SEARCH_IP__, 'port' => 9200],
        ],
        'mongodb' => [
            'default' => ['host' => __MONGODB_IP__, 'port' => 27017, 'user' => 'camfee', 'password' => 'camfee29']
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
