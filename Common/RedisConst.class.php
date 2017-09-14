<?php
/**
 * RedisConst.class.php redis配置
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-9-14 上午9:38
 *
 */

namespace Common;

use Config\DBConfig;

class RedisConst
{
    // 队列
    const QUEUE_DB_R = DBConfig::REDIS_QUEUE_R;
    const QUEUE_DB_W = DBConfig::REDIS_QUEUE_W;
    const QUEUE_DB_INDEX = 15;
    // 用户
    const ACCOUNT_DB_R = DBConfig::REDIS_ACCOUNT_R;
    const ACCOUNT_DB_W = DBConfig::REDIS_ACCOUNT_W;
    const ACCOUNT_DB_INDEX = 10;
    // 通行证
    const PASSPORT_DB_R = DBConfig::REDIS_PASSPORT_R;
    const PASSPORT_DB_W = DBConfig::REDIS_PASSPORT_W;
    const PASSPORT_DB_INDEX = 11;
    // 书本
    const BOOK_DB_R = DBConfig::REDIS_DEFAULT_R;
    const BOOK_DB_W = DBConfig::REDIS_DEFAULT_W;
    const BOOK_DB_INDEX = 9;
    // 手机app
    const MOBILE_DB_R = DBConfig::REDIS_MOBILE_R;
    const MOBILE_DB_W = DBConfig::REDIS_MOBILE_W;
    const MOBILE_DB_INDEX = 14;
    // fast interface 手机信息 版本 启动图等
    const FAST_DB_R = DBConfig::REDIS_OTHER_R;
    const FAST_DB_W = DBConfig::REDIS_OTHER_W;
    const FAST_DB_INDEX = 13;
}