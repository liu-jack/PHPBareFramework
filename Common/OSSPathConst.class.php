<?php
/**
 *
 * 资源路径管理
 *
 */

namespace Common;

class OSSPathConst
{
    const OSS_IMAGE_URL = 'http://xxxxxx';
    const OSS_TEST_IMAGE_URL = 'http://meitetest.oss-cn-hangzhou.aliyuncs.com/';

    public static function getOssImageUrl()
    {
        if (__ENV__ != 'ONLINE') {
            return self::OSS_TEST_IMAGE_URL;
        }

        return self::OSS_IMAGE_URL;
    }

    public static function getUserHeadSavePath()
    {
        return 'head/';
    }

    public static function getShareConfigSavePath()
    {
        return 'config/share/';
    }

    public static function getActivityConfigSavePath()
    {
        return 'config/ac/';
    }

    public static function getAdvertisingSavePath()
    {
        return 'config/ad/';
    }

    public static function getIndexFeedBannerSavePath()
    {
        return 'config/banner/';
    }

    public static function getIndexFeedSkipSavePath()
    {
        return 'config/skip/';
    }

    public static function getMatterActivitySavePath()
    {
        return 'activity/matter/';
    }

    public static function getEcommerceConfigSavePath()
    {
        return 'config/ecomm_ad/';
    }

    public static function getTagChildConfigSavePath()
    {
        return 'config/tag_child/';
    }

    public static function getUserMemberSavePath()
    {
        return 'member/';
    }

    public static function getAppScreenImageSavePath()
    {
        return 'screen/';
    }

    public static function getTeMaiCatePicSavePath()
    {
        return 'temai/cate/';
    }
}
