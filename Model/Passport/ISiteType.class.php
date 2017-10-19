<?php

/**
 * 站点类型接口
 *
 */

namespace Model\Passport;

interface ISiteType
{
    // 站点分类 - 亲信
    const SITE_QINXIN = 1;
    // 站点分类 - 亲宝听
    const SITE_QBAOTING = 2;
    // 站点分类 - 亲亲宝贝
    const SITE_QBAOBEI = 10;
    // 站点分类 - 亲宝头条
    const SITE_QBTOUTIAO = 20;
    // 站点分类 - 美特
    const SITE_MEITE = 30;

    // 站点分类 - 列表
    const SITE_LISTS = [
        self::SITE_QINXIN => self::SITE_QINXIN,
        self::SITE_QBAOTING => self::SITE_QBAOTING,
        self::SITE_QBAOBEI => self::SITE_QBAOBEI,
        self::SITE_QBTOUTIAO => self::SITE_QBTOUTIAO,
        self::SITE_MEITE => self::SITE_MEITE
    ];

    // 第三方 新浪微博
    const PLATFORM_WEIBO = 20;
    // 第三方 QQ
    const PLATFORM_QQ = 22;
    // 第三方 微信
    const PLATFORM_WEIXIN = 26;
    // 第三方 网页微信
    const PLATFORM_WEIXIN_WEB = 27;
    // 第三方 - 列表
    const PLATFORM_LISTS = [
        self::PLATFORM_WEIBO => self::PLATFORM_WEIBO,
        self::PLATFORM_QQ => self::PLATFORM_QQ,
        self::PLATFORM_WEIXIN => self::PLATFORM_WEIXIN,
        self::PLATFORM_WEIXIN_WEB => self::PLATFORM_WEIXIN_WEB
    ];
}