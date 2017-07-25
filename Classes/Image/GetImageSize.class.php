<?php
/**
 * 快速获取远程图片尺寸
 *
 */

namespace Classes\Image;

class GetImageSize
{
    /**
     * 获取远程图片的宽高
     *
     * @param string $url url 地址
     * @return array|bool 成功结果同 getimagesize
     */
    public static function getUrlImageSize($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RANGE, '0-1024');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (empty($data) || strpos($info['content_type'], 'image') !== 0) {
            return false;
        }

        $info = self::getSize($info['content_type'], $data);

        if ($info == false) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RANGE, '1025-25000');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data2 = curl_exec($ch);
            curl_close($ch);

            $info = self::getSize($info['content_type'], $data . $data2);
        }

        return $info;

    }

    private static function getSize($type, $data)
    {
        return getimagesize("data://$type;base64," . base64_encode($data));
    }
}