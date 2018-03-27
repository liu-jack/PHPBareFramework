<?php
namespace lib\plugins\xcx;

use lib\plugins\weixin\WeiXinTrait;
use lib\util\Request;

class XcxOauth
{
    use WeiXinTrait;

    public static function getXcxSessionKey($appId, $secret, $code, $grantType = 'authorization_code')
    {
        //        https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code
        $requestUrl = sprintf('%s/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=%s', 'https://api.weixin.qq.com/sns', $appId, $secret, $code, $grantType);
        $res        = Request::request($requestUrl);
        return self::getWeChatApiResult($res);
    }
}