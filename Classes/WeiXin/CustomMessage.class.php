<?php
/**
 * 客服消息接口
 *
 * @author: hjh <hjh@jf.com>
 *
 * Date: 2017/4/7
 * Time: 10:33
 */

namespace lib\plugins\weixin;

use lib\util\Request;
use Weixin\Oauth;

class CustomMessage
{
    use WeiXinTrait;

    public static function sendMsg($appId, $appSecret, $toUser, $msgType, $content)
    {
        $msg = [
            'touser' => $toUser,
            'msgtype' => $msgType,
        ];
        switch ($msgType) {
            case 'text':
                $msg[$msgType] = [
                    "content" => $content
                ];
                break;
            case 'image':
            case 'voice':
                $msg[$msgType] = [
                    'media_id' => $content
                ];
                break;
            case 'video':
                $msg[$msgType] = [
                    'media_id' => $content['media_id'],
                    'thumb_media_id' => $content['thumb_media_id'],
                    'title' => $content['title'],
                    'description' => $content['description']
                ];
                break;
            case 'music':
                $msg[$msgType] = [
                    "title" => $content['title'],
                    "description" => $content[''],
                    "musicurl" => $content[''],
                    "hqmusicurl" => $content[''],
                    "thumb_media_id" => $content[''],
                ];
                break;
            case 'link':
                $msg[$msgType] = [
                    'title' => $content['title'],
                    'description' => $content['description'],
                    'url' => $content['url'],
                    'thumb_url' => $content['thumb_url'],
                ];
                break;
                //TODO
            default:
                throw new \RuntimeException("not implement the msg type [{$msgType}]");
        }

        $token = Oauth::getAccessTokenByAppId($appId, $appSecret);
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?&body=0&access_token={$token}";
        $result = Request::httpWeixinPost($url, json_encode($msg, JSON_UNESCAPED_UNICODE));
        return self::getWeChatApiResult($result);
    }
}