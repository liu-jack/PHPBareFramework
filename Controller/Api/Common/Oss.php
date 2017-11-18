<?php
/**
 * 阿里云获取授权接口
 *
 * @author  周剑锋 <camee@foxmail.com>
 * @since   1.0.4 2017-10-24
 */

namespace controls\MobileApi\Common;

use Bare\Controller;

/**
 * 阿里云获取临时token接口
 *
 * @package Common
 * @author  周剑锋 <camee@foxmail.com>
 * @since   1.0.4 2017-10-24
 */
class Oss extends Controller
{
    /**
     * 获取阿里云oss上传授权信息
     *
     * <pre>
     * GET:
     *      expire: 可选, 过期时间, 30-3600 (单位秒), 默认3600
     * </pre>
     *
     * @return void|String
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *         "AccessKeyId": "LTAI4FSEBwPFQ2BA",                 // 授权id
     *         "UploadHost": "http://meitetest.oss.aliyuncs.com/", // 上传与访问域名
     *         "Policy": "eyJleHBpiMjAxNzEwXC8yNFwvIl1dfQ==",     // 授权设置
     *         "Signature": "/lyVSL5/FV/shy08/GSMwF8w=",          // 授权签名
     *         "Expire": 1508832076,                              // 授权过期时间
     *         "PrefixDir": "201710/24/"                          // 上传目录前缀
     *     }
     * }
     * 获取授权后使用：
     * 详见@see https://help.aliyun.com/document_detail/31988.html?spm=5176.doc31988.6.880.84KOmP
     * 请求地址：Result.UploadHost
     * POST:
     *  OSSAccessKeyId: Result.AccessKeyId 授权id, 必须
     *  policy: Result.Policy 授权设置, 必须
     *  Signature: Result.Signature 授权签名, 必须
     *  key: Result.PrefixDir + 自定义文件路径名称(如:test/1.jpg), 必须
     *  file: 要上传的文件, 必须
     * 注意：
     * 1.上传到oss成功后默认返回的http_code是204
     * 2.上传成功后访问路径为 Result.UploadHost + key(http://meitetest.oss.aliyuncs.com/ + 201710/24/  + test/1.jpg)
     *
     */
    public function getUploadToken()
    {
        $uid = $this->isLogin(false);
        $expire = intval($_GET['expire']);
        if ($expire < 1) {
            $expire = 3600;
        } else {
            $expire = min(3600, max(30, $expire));
        }

        //        $id = 'LTAI7tNstUI0zU3L';
        //        $key = 'cEPMvtQrFn2ZqJzIuhpIFRSWfqEvqm';
        //        $host = 'http://zftestimages.oss-cn-shenzhen.aliyuncs.com';
        $oss = config('oss/oss');
        $id = $oss['accessKeyId'];
        $key = $oss['accessSecret'];
        $end_point = rtrim($oss['endPoint'], '/') . '/';
        $host = str_replace('://', '://' . $oss['bucket'] . '.', $end_point);

        $end = time() + $expire;
        $expiration = self::gmt_iso8601($end);
        if ($uid > 0) {
            $dir = sprintf('%.2f', $uid % 256) . '/' . sprintf('%.2f', $uid % 255) . '/';
        } else {
            $dir = date('Ym') . '/' . date('d') . '/';
        }

        //最大文件大小.用户可以自己设置
        $size_range = [0 => 'content-length-range', 1 => 0, 2 => 104857600]; // 100Mb
        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = [0 => 'starts-with', 1 => '$key', 2 => $dir];
        $conditions = [
            'expiration' => $expiration,
            'conditions' => [
                $size_range,
                $start
            ]
        ];
        $policy = json_encode($conditions);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $data['AccessKeyId'] = $id;
        $data['UploadHost'] = $host;
        $data['Policy'] = $base64_policy;
        $data['Signature'] = $signature;
        $data['Expire'] = $end;
        //这个参数是设置用户上传指定的前缀
        $data['PrefixDir'] = $dir;
        $this->output(200, $data);
    }

    private static function gmt_iso8601($time)
    {
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);

        return $expiration . "Z";
    }
}