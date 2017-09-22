<?php

namespace Model\Common;

/**
 *
 * OSS处理类
 *
 */
use OSS\OssClient;
use OSS\Core\OssException;

ini_set('memory_limit', '1024M');

class OSS
{
    /**
     * 日志路径定义
     */
    const LOG_FAIL_PATH = 'OSS/Fail';

    public static function getOssClient()
    {
        $oss = config('oss/oss');
        $accessKeyId = $oss['accessKeyId'];
        $accessKeySecret = $oss['accessSecret'];
        $endpoint = $oss['endPoint'];
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

        $ossClient->setTimeout(3600 /* seconds */);
        $ossClient->setConnectTimeout(10 /* seconds */);

        return $ossClient;
    }

    /**
     * 上传单个文件至OSS
     *
     * @param string $path     OSS服务器上路径及文件名
     * @param string $filePath 本地文件路径和文件名
     * @param string $bucket   OSS区块名称
     *
     * @return bool
     *
     */
    public static function PutFile($path, $filePath, $bucket)
    {
        $ossClient = self::getOssClient();

        try {
            $res = $ossClient->uploadFile($bucket, $path, $filePath);
        } catch (OssException $e) {
            $exception = $e->getMessage();
            logs(__METHOD__ . "files: {$filePath}, path: [" . $path . "], exception: {$exception} @ ",
                self::LOG_FAIL_PATH);
        }

        if (!empty($res['info']['http_code'])) {
            return true;
        }

        return false;
    }

}
