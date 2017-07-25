<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * url地址映射(重写)配置
 */
$config = [
    'DEV' => [
        [   // 接口文档地址重写
            'pos' => 'apidoc', // url替换起始位置 为空或者没有时不重写
            'rules' => [
                '@^apidoc/phpdoc(\.html)?$@i' => 'tool/apidoc/phpdoc',
                '@^apidoc/?(index)?(\.html)?$@i' => 'tool/apidoc/index',
                '@^apidoc/lists/([^/]+)(\.html)?$@i' => 'tool/apidoc/lists/module/$1',
                '@^apidoc/methods/([^/_]+)_([^/_]+)(\.html)?$@i' => 'tool/apidoc/methods/module/$1/class/$2',
                '@^apidoc/info/([^/_]+)_([^/_]+)_([^/_]+)(\.html)?$@i' => 'tool/apidoc/info/module/$1/class/$2/method/$3',
            ]
        ],
        [
            'pos' => 'book/',
            'rules' => [
                '@^book/type_(\d+)$@i' => 'book/index/type/tid/$1',
                '@^book/(\d+)_(\d+)$@i' => 'book/index/column/fid/$1/bid/$2',
                '@^book/(\d+)_(\d+)/(\d+)$@i' => 'book/index/content/fid/$1/bid/$2/cid/$3',
            ]
        ],
    ],
    'TEST' => [

    ],
    'ONLINE' => [
        [   // 接口文档地址重写
            'pos' => 'apidoc', // url替换起始位置  为空或者没有时不重写
            'rules' => [
                '@^apidoc/phpdoc(\.html)?$@i' => 'tool/apidoc/phpdoc',
                '@^apidoc/?(index)?(\.html)?$@i' => 'tool/apidoc/index',
                '@^apidoc/lists/([^/]+)(\.html)?$@i' => 'tool/apidoc/lists/module/$1',
                '@^apidoc/methods/([^/_]+)_([^/_]+)(\.html)?$@i' => 'tool/apidoc/methods/module/$1/class/$2',
                '@^apidoc/info/([^/_]+)_([^/_]+)_([^/_]+)(\.html)?$@i' => 'tool/apidoc/info/module/$1/class/$2/method/$3',
            ]
        ],
        [
            //'pos' => 'book/',
            'rules' => [
                '@^book/type_(\d+)$@i' => 'book/index/type/tid/$1',
                '@^book/(\d+)_(\d+)$@i' => 'book/index/column/fid/$1/bid/$2',
                '@^book/(\d+)_(\d+)/(\d+)$@i' => 'book/index/content/fid/$1/bid/$2/cid/$3',
            ]
        ],
    ]
];

return $config[__ENV__];
