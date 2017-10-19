<?php
/**
 * ImgConst.class.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-9-13 下午5:41
 *
 */

namespace Common;

class PathConst
{
    const IMG_EXT_JPG = 'jpg';
    const IMG_EXT_PNG = 'png';
    const IMG_EXT_GIF = 'gif';
    const IMG_EXT_WEBP = 'webp';
    const IMG_SIZE_0 = 0;
    const IMG_SIZE_450 = 450;
    const IMG_SIZE_290 = 290;
    const IMG_EXTRA_HEIGHT = 'height';
    const IMG_EXTRA_QUALITY = 'quality'; // 1-100 default:85
    const IMG_EXTRA_POSITION = 'position'; // top|middle|bottom default:middle
    const IMG_EXTRA_WATERMARK = 'watermark'; // true|false default:false
    // 测试
    const IMG_TEST = 'test';
    // 启动图
    const IMG_APP_SCREEN = 'app/screen';
    const IMG_APP_SCREEN_SIZE = self::IMG_SIZE_0;
    // 标签
    const IMG_TAG_ICON = 'tag/icon';
    const IMG_TAG_ICON_SIZE = self::IMG_SIZE_0;
    const IMG_TAG_COVER = 'tag/cover';
    const IMG_TAG_COVER_SIZE = self::IMG_SIZE_450;
    const IMG_TAG_BANNER = 'tag/banner';
    const IMG_TAG_BANNER_SIZE = self::IMG_SIZE_450;
    const IMG_TAG_BANNER_EXTRA = [
        self::IMG_EXTRA_HEIGHT => [
            self::IMG_TAG_BANNER_SIZE => self::IMG_SIZE_290
        ]
    ];
    // 相册
    const IMG_ATLAS_COVER = 'atlas/cover';
    const IMG_ATLAS_COVER_SIZE = self::IMG_SIZE_0;
    const IMG_ATLAS_PHOTO = 'atlas/photo';
    const IMG_ATLAS_PHOTO_SIZE = self::IMG_SIZE_0;
}