<?php
/**
 * 数据库连接配置
 */

namespace Config;

defined('ROOT_PATH') or exit('Access deny');

class DBConfig
{
    /**
     * 数据库连接配置
     */
    const DB_BARE_R = 0; // %2余0 read 余1 write
    const DB_BARE_W = 1;
    const DB_TEST_R = 2;
    const DB_TEST_W = 3;
    const DB_29SHU_R = 4;
    const DB_29SHU_W = 5;
    const DB_29SHU_CONTENT_R = 6;
    const DB_29SHU_CONTENT_W = 7;
    const DB_PASSPORT_R = 8;
    const DB_PASSPORT_W = 9;
    const DB_ACCOUNT_R = 10;
    const DB_ACCOUNT_W = 11;
    const DB_FAVORITE_R = 12;
    const DB_FAVORITE_W = 13;
    const DB_APPLICATION_R = 14;
    const DB_APPLICATION_W = 15;
    const DB_DEVICE_R = 16;
    const DB_DEVICE_W = 17;
    const DB_COMMENT_R = 18;
    const DB_COMMENT_W = 19;
    const DB_TAG_R = 20;
    const DB_TAG_W = 21;
    const DB_ADMIN_R = 22;
    const DB_ADMIN_W = 23;
    const DB_COLLECT_R = 24;
    const DB_COLLECT_W = 25;
    const DB_MOBILE_R = 26;
    const DB_MOBILE_W = 27;
    const DB_PICTURE_R = 28;
    const DB_PICTURE_W = 29;

    protected static $_db_cfgs = [
        self::DB_BARE_R => ['name' => 'default', 'db' => 'bare'],
        self::DB_BARE_W => ['name' => 'default', 'db' => 'bare'],
        self::DB_TEST_R => ['name' => 'default', 'db' => 'test'],
        self::DB_TEST_W => ['name' => 'default', 'db' => 'test'],
        self::DB_29SHU_R => ['name' => 'default', 'db' => '29shu_book'],
        self::DB_29SHU_W => ['name' => 'default', 'db' => '29shu_book'],
        self::DB_29SHU_CONTENT_R => ['name' => 'default', 'db' => '29shu_content'],
        self::DB_29SHU_CONTENT_W => ['name' => 'default', 'db' => '29shu_content'],
        self::DB_PASSPORT_R => ['name' => 'default', 'db' => 'zf_passport'],
        self::DB_PASSPORT_W => ['name' => 'default', 'db' => 'zf_passport'],
        self::DB_ACCOUNT_R => ['name' => 'default', 'db' => 'zf_account'],
        self::DB_ACCOUNT_W => ['name' => 'default', 'db' => 'zf_account'],
        self::DB_FAVORITE_R => ['name' => 'default', 'db' => 'zf_favorite'],
        self::DB_FAVORITE_W => ['name' => 'default', 'db' => 'zf_favorite'],
        self::DB_APPLICATION_R => ['name' => 'default', 'db' => 'zf_application'],
        self::DB_APPLICATION_W => ['name' => 'default', 'db' => 'zf_application'],
        self::DB_DEVICE_R => ['name' => 'default', 'db' => 'zf_device'],
        self::DB_DEVICE_W => ['name' => 'default', 'db' => 'zf_device'],
        self::DB_COMMENT_R => ['name' => 'default', 'db' => 'zf_comment'],
        self::DB_COMMENT_W => ['name' => 'default', 'db' => 'zf_comment'],
        self::DB_TAG_R => ['name' => 'default', 'db' => 'zf_tag'],
        self::DB_TAG_W => ['name' => 'default', 'db' => 'zf_tag'],
        self::DB_ADMIN_R => ['name' => 'default', 'db' => 'zf_admin'],
        self::DB_ADMIN_W => ['name' => 'default', 'db' => 'zf_admin'],
        self::DB_COLLECT_R => ['name' => 'default', 'db' => 'zf_collect'],
        self::DB_COLLECT_W => ['name' => 'default', 'db' => 'zf_collect'],
        self::DB_MOBILE_R => ['name' => 'default', 'db' => 'zf_mobile'],
        self::DB_MOBILE_W => ['name' => 'default', 'db' => 'zf_mobile'],
        self::DB_PICTURE_R => ['name' => 'default', 'db' => 'zf_picture'],
        self::DB_PICTURE_W => ['name' => 'default', 'db' => 'zf_picture'],
    ];

    /**
     * redis连接配置
     */
    const REDIS_DEFAULT_R = 0;
    const REDIS_DEFAULT_W = 1;
    const REDIS_PASSPORT_R = 2;
    const REDIS_PASSPORT_W = 3;
    const REDIS_ACCOUNT_R = 4;
    const REDIS_ACCOUNT_W = 5;
    const REDIS_OTHER_R = 6;
    const REDIS_OTHER_W = 7;
    const REDIS_QUEUE_R = 8;
    const REDIS_QUEUE_W = 9;
    const REDIS_MOBILE_R = 10;
    const REDIS_MOBILE_W = 11;
    const REDIS_NOTICE_R = 12;
    const REDIS_NOTICE_W = 13;
    const REDIS_SYNC_EVENT_R = 14;
    const REDIS_SYNC_EVENT_W = 15;
    const REDIS_DB_CACHE_R = 16;
    const REDIS_DB_CACHE_W = 17;

    protected static $_redis_cfgs = [
        self::REDIS_DEFAULT_R => 'default',
        self::REDIS_DEFAULT_W => 'default',
        self::REDIS_PASSPORT_R => 'passport',
        self::REDIS_PASSPORT_W => 'passport',
        self::REDIS_ACCOUNT_R => 'account',
        self::REDIS_ACCOUNT_W => 'account',
        self::REDIS_OTHER_R => 'other',
        self::REDIS_OTHER_W => 'other',
        self::REDIS_QUEUE_R => 'queue',
        self::REDIS_QUEUE_W => 'queue',
        self::REDIS_MOBILE_R => 'mobile',
        self::REDIS_MOBILE_W => 'mobile',
        self::REDIS_NOTICE_R => 'notice',
        self::REDIS_NOTICE_W => 'notice',
        self::REDIS_SYNC_EVENT_R => 'queue',
        self::REDIS_SYNC_EVENT_W => 'queue',
        self::REDIS_DB_CACHE_R => 'cache',
        self::REDIS_DB_CACHE_W => 'cache',
    ];

    /**
     * memcache连接配置
     */
    const MEMCACHE_DEFAULT = 0;
    const MEMCACHE_OTHER = 1;

    protected static $_memcache_cfgs = [
        self::MEMCACHE_DEFAULT => 'default',
        self::MEMCACHE_OTHER => 'other',
    ];

    /**
     * elasticsearch连接配置
     */
    const SEARCH_DEFAULT = 0;

    protected static $_search_cfgs = [
        self::SEARCH_DEFAULT => 'default',
    ];

    /**
     * mongodb连接配置
     */
    const MONGODB_DEFAULT = 0;

    protected static $_mongodb_cfgs = [
        self::MONGODB_DEFAULT => ['name' => 'default', 'db' => 'user']
    ];
}
