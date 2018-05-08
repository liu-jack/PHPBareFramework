<?php
/**
 * 二维码
 *
 * @author: hjh <hjh@jf.com>
 *
 * Date: 2017/11/29
 * Time: 19:15
 */

namespace lib\plugins\xcx;

use lib\plugins\weixin\WeiXinTrait;
use lib\util\Request;

class QRCode
{
    use WeiXinTrait;

    public static function getWXACodeUnLimit($accessToken, $scene, $page = false, $width = 430, $autoColor = false, $lineColor = [])
    {
        $postData = [
            'scene'      => $scene,
            'width'      => $width,
            'auto_color' => $autoColor,
            'line_color' => $lineColor
        ];
        if ($page) {
            $postData['page'] = $page;
        }
        if ($lineColor) {
            $postData['line_color'] = $lineColor;
        }
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$accessToken}";

        $res = Request::postJsonData($url, json_encode($postData));

        return self::getWeChatApiResult($res);
    }
}
