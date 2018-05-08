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
    const QUEUE_DB_INDEX = 0;
    // 用户
    const ACCOUNT_DB_R = DBConfig::REDIS_ACCOUNT_R;
    const ACCOUNT_DB_W = DBConfig::REDIS_ACCOUNT_W;
    const ACCOUNT_DB_INDEX = 0;
    // 通行证
    const PASSPORT_DB_R = DBConfig::REDIS_PASSPORT_R;
    const PASSPORT_DB_W = DBConfig::REDIS_PASSPORT_W;
    const PASSPORT_DB_INDEX = 0;
    // 书本
    const BOOK_DB_R = DBConfig::REDIS_DEFAULT_R;
    const BOOK_DB_W = DBConfig::REDIS_DEFAULT_W;
    const BOOK_DB_INDEX = 0;
    // 团购缓存
    const GROUP_DB_R = DBConfig::REDIS_DEFAULT_R;
    const GROUP_DB_W = DBConfig::REDIS_DEFAULT_W;
    const GROUP_DB_INDEX = 1;
    // 手机app
    const MOBILE_DB_R = DBConfig::REDIS_MOBILE_R;
    const MOBILE_DB_W = DBConfig::REDIS_MOBILE_W;
    const MOBILE_DB_INDEX = 0;
    // fast interface 手机信息 版本 启动图等
    const FAST_DB_R = DBConfig::REDIS_OTHER_R;
    const FAST_DB_W = DBConfig::REDIS_OTHER_W;
    const FAST_DB_INDEX = 0;
    // 数据表同步队列
    const SYNC_DB_R = DBConfig::REDIS_SYNC_EVENT_R;
    const SYNC_DB_W = DBConfig::REDIS_SYNC_EVENT_W;
    const SYNC_DB_INDEX = 15;
    // 数据表缓存
    const CACHE_DB_R = DBConfig::REDIS_DB_CACHE_R;
    const CACHE_DB_W = DBConfig::REDIS_DB_CACHE_W;
    const PASSPORT_INDEX = 0; // 通行证
    const GROUP_BUY_INDEX = 10; // 团购
    // 支付平台
    const PAYMENT_DB_R = DBConfig::REDIS_DB_PAYMENT_R;
    const PAYMENT_DB_W = DBConfig::REDIS_DB_PAYMENT_W;
    const PAYMENT_INDEX = 0; // 支付平台
}