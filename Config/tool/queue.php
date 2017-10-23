<?php defined('ROOT_PATH') or exit('Access deny');
/**
 *队列列表
 */

return [
    0 => [
        'id' => 0,
        'name' => '推送标签管理',
        'queue_name' => 'AppPushTag',
    ],
    1 => [
        'id' => 1,
        'name' => 'App推送',
        'queue_name' => 'AppPush',
    ],
];