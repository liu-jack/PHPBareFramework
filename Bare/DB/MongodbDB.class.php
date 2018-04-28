<?php
/**
 * MongodbDB.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/7/4 15:08
 *
 */

namespace Bare\DB;

use MongoDB\Client;

include_once(LIB_PATH . 'MongoDB/functions.php');

class MongodbDB extends Client
{
    /**
     * 默认选项
     *
     * @var array
     */
    private $options = [
        'typeMap' => [
            'array' => 'array',
            'document' => 'array',
            'root' => 'array',
        ]
    ];

    /**
     * Mongodb constructor.
     *
     * @param array $params  链接参数
     * @param array $options 自定义选项
     */
    public function __construct(array $params, array $options = [])
    {
        $uri = 'mongodb://' . $params['host'] . ':' . $params['port'];
        $uri_options = [];
        if (!empty($params['password'])) {
            $uri_options = [
                'username' => $params['user'],
                'password' => $params['password'],
                'authSource' => 'admin',
            ];
        }

        $this->options = array_merge($this->options, $options);
        parent::__construct($uri, $uri_options, $this->options);
    }
}
