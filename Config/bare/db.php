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
                'w' => ['host' => __REDIS_IP__, 'port' => 6380, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6380, 'auth' => ''],
            ],
            'passport' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6381, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6381, 'auth' => ''],
            ],
            'account' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6382, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6382, 'auth' => ''],
            ],
            'queue' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6383, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6383, 'auth' => '']
            ],
            'mobile' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6384, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6384, 'auth' => '']
            ],
            'notice' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6385, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6385, 'auth' => '']
            ],
            'other' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6386, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6386, 'auth' => '']
            ],
            'cache' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6387, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6387, 'auth' => '']
            ],
            'payment' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6388, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6388, 'auth' => '']
            ]
        ],
        'search' => [
            'default' => ['host' => __SEARCH_IP__, 'port' => 9200],
        ],
        'mongodb' => [
            'default' => ['host' => __MONGODB_IP__, 'port' => 27017, 'user' => 'camfee', 'password' => '']
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
                'w' => ['host' => __REDIS_IP__, 'port' => 6380, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6380, 'auth' => ''],
            ],
            'passport' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6381, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6381, 'auth' => ''],
            ],
            'account' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6382, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6382, 'auth' => ''],
            ],
            'queue' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6383, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6383, 'auth' => '']
            ],
            'mobile' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6384, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6384, 'auth' => '']
            ],
            'notice' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6385, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6385, 'auth' => '']
            ],
            'other' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6386, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6386, 'auth' => '']
            ],
            'cache' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6387, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6387, 'auth' => '']
            ],
            'payment' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6388, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6388, 'auth' => '']
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
                'w' => ['host' => __REDIS_IP__, 'port' => 6380, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6380, 'auth' => ''],
            ],
            'passport' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6381, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6381, 'auth' => ''],
            ],
            'account' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6382, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6382, 'auth' => ''],
            ],
            'queue' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6383, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6383, 'auth' => '']
            ],
            'mobile' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6384, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6384, 'auth' => '']
            ],
            'notice' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6385, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6385, 'auth' => '']
            ],
            'other' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6386, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6386, 'auth' => '']
            ],
            'cache' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6387, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6387, 'auth' => '']
            ],
            'payment' => [
                'w' => ['host' => __REDIS_IP__, 'port' => 6388, 'auth' => ''],
                'r' => ['host' => __REDIS_IP__, 'port' => 6388, 'auth' => '']
            ]
        ],
        'search' => [
            'default' => ['host' => __SEARCH_IP__, 'port' => 9200],
        ],
        'mongodb' => [
            'default' => ['host' => __MONGODB_IP__, 'port' => 27017, 'user' => 'camfee', 'password' => 'camfee29']
        ]
    ]
];

return $config[__ENV__];
