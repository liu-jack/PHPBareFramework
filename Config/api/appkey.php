<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * appkey.php
 * 应用版本类型appkey管理
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-5-10 上午9:55
 *
 */

$config = [
    'DEV' => [
        APP_APPID_ADR => [
            'v1.0.1' => '53370d36c455030becc572b34a38d8da',
            'v1.0.2' => 'd456132db0866de16358a7c452544702',
        ],
        APP_APPID_IOS => [
            'v1.0.1' => '4e9755958c2e201a51fc3c914951aa1b',
            'v1.0.2' => '1db24c55c295ce080bc2d0858001759a',
        ],
        APP_APPID_XCX => [
            'v1.0.1' => 'e0068b46e5d0bbfe1f39020dbf811f37',
        ],
        APP_APPID_WAP => [
            'v1.0.1' => '3d2ea9d7e9a9ce4fc50ad4521939d538',
        ],
        APP_APPID_WEB => [
            'v1.0.1' => '76aef518f59c4f9190843f3a14801a96',
        ]
    ],
    'TEST' => [],
    'ONLINE' => []
];

return $config[__ENV__];