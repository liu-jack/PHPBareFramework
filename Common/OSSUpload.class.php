<?php
/**
 * OSSUpload.php 文件上传
 *
 * @author 周剑锋 <camfee@foxmail.com>
 * @date   17-9-7 上午9:16
 *
 */

namespace Common;

use Classes\Image\PhotoImage;
use Classes\Common\OSS;

class OSSUpload
{
    /**
     * 保存文件
     *
     * @param string $filename 本地|临时 文件名
     * @param string $uri      保存路径
     * @param int    $itemid   项目id
     * @return bool|string
     */
    public static function saveFile($filename, $uri, $itemid = 0)
    {
        $path = PhotoImage::getImageHash($itemid);
        $imageType = PhotoImage::getImageType($filename);
        if (empty($itemid)) {
            $md5 = $itemid . '_' . substr(md5(__KEY__ . $itemid . ''), -6);
        } else {
            $md5 = rand(100, 999) . '_' . substr(md5(__KEY__ . microtime() . ''), -6);
        }
        $savePath = $uri . "{$path['hash1']}/{$path['hash2']}/{$md5}.$imageType";

        $res = OSS::PutFile($savePath, $filename, config('oss/oss')['bucket']);
        if (empty($res)) {
            logs("getActUrl failed,, {$filename}", __CLASS__);

            return false;
        }

        return OSSPathConst::getOssImageUrl() . $savePath;
    }
}