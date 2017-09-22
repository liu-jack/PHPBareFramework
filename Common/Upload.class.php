<?php
/**
 * Upload.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-9-14 下午3:18
 *
 */

namespace Common;

use Classes\Image\PhotoImage;

class Upload
{
    /**
     * @param string    $path         PathConst
     * @param array     $image_status PhotoImage::checkImage|PhotoImage::checkImageByUrl
     * @param int|array $size         裁剪尺寸    0:原图
     * @param int       $itemid       项目id     0:按时间生成路径
     * @param array     $extra        quality   裁剪质量 0 - 100
     *                                position  位置 top|middle|bottom
     *                                height    限制高度
     *                                watermark 水印 true|false
     * @return array|bool
     */
    public static function saveImg($path, $image_status, $size = 0, $itemid = 0, $extra = [])
    {
        $path_url = getSavePath($path, $itemid, $image_status['image_type'], $size);
        if (!is_numeric(key($path_url))) {
            $path_url = [$path_url];
        }
        foreach ($path_url as $k => $v) {
            $image_cfg[$k] = [
                'width' => $k,
                'position' => isset($extra['position']) ? $extra['position'] : 'middle',
                'quality' => isset($extra['quality']) ? $extra['quality'] : 85,
                'watermark' => isset($extra['watermark']) ? $extra['watermark'] : false,
                'path' => $v['path'],
                'url' => $v['url']
            ];
        }
        if (!empty($extra['height'])) {
            foreach ($extra['height'] as $k => $v) {
                $image_cfg[$k]['height'] = $v;
            }
        }
        if (!empty($image_cfg)) {
            $ret = PhotoImage::imageResize($image_cfg, $image_status);

            return $ret;
        }

        return false;
    }
}