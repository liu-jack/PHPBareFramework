<?php
/**
 * Project: story-server
 * File Created at 2017-02-23
 * Copyright 2014 qbaoting.cn All right reserved. This software is the
 * confidential and proprietary information of qbaoting.cn ("Confidential
 * Information"). You shall not disclose such Confidential Information and shall
 * use it only in accordance with the terms of the license agreement you entered
 * into with qbaoting.cn .
 */

namespace lib\plugins\weixin;

use lib\util\Request;

class PushMessage
{
    use WeiXinTrait;
    const WEIXIN_API_HOST = 'https://api.weixin.qq.com/cgi-bin/';

    /**
     * 根据模版发送消息 https://mp.weixin.qq.com/wiki
     *
     * @param string $accessToken 授权access_token
     * @param string $templateId  模板id
     * @param string $toUser      接收人openId
     * @param array  $data        消息体内容
     * @param string $url         文本链接
     *
     * @author tianming <keyec.cn@gmail.com>
     * @return array|mixed
     */
    public static function sendTemplateMessage($accessToken, $templateId, $toUser, $data, $url, $miniprogram = false)
    {
        $sendData = [
            'template_id' => $templateId,
            'touser'      => $toUser,
            'data'        => $data,
            'url'         => $url
        ];
        if ($miniprogram) {
            $sendData['miniprogram'] = $miniprogram;
        }
        $url      = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $accessToken;
        $params   = json_encode($sendData, JSON_UNESCAPED_UNICODE);
        $result   = Request::request($url, $params, true);
        return self::getWeChatApiResult($result);
    }

    /**
     * 客户消息接口
     *
     * @param  string $accessToken 授权access_token
     * @param  string $toUser      接收人openId
     * @param  string $msgType     消息类型
     * @param  string|array $content     消息内容
     *
     * @author tianming <keyed.cn@gmail.com>
     * @return array|mixed
     */
    public static function customSend($accessToken, $toUser, $msgType, $content)
    {
        $sendData = [
            'touser'  => $toUser,
            'msgtype' => $msgType,
        ];

        $url         = sprintf(self::WEIXIN_API_HOST . 'message/custom/send?access_token=' . $accessToken);
        $messageData = self::generateCustomMessage($msgType, $content);
        $customData  = array_merge($sendData, $messageData);
        $params      = json_encode($customData, JSON_UNESCAPED_UNICODE);

        $result = Request::request($url, $params, true);
        return self::getWeChatApiResult($result);
    }

    /**
     * 创建客服  https://api.weixin.qq.com/customservice/kfaccount/add?access_token=ACCESS_TOKEN  客户接口
     *
     * @param string $accessToken 授权access_token
     * @param        $data        客服信息
     *
     * @author tianming <keyec.cn@gmail.com>
     * @return array
     */
    public static function addKFAccount($accessToken, $data)
    {
        $url = sprintf('https://api.weixin.qq.com/customservice/kfaccount/add?access_token=' . $accessToken);

        $data = Request::postJsonData($url, $data);
        return $data;
    }

    private static function generateCustomMessage($msgType, $data)
    {
        $result = [];
        switch ($msgType) {
            case 'text':
                $result = [
                    'text' => [
                        'content' => $data
                    ]
                ];
                break;
            case 'image':
                $result = [
                    'image' => ['media_id' => $data]
                ];
                break;
            case 'voice':
                $result = [
                    'voice' => ['media_id' => $data]
                ];
                break;
            case 'music':
                $result = [
                    'music' => [
                        'title'          => $data['title'],//音乐标题
                        'description'    => $data['title'],//音乐描述
                        'musicurl'       => $data['title'],//音乐链接
                        'hqmusicurl'     => $data['title'],//高品质音乐链接，wifi环境优先使用该链接播放音乐
                        'thumb_media_id' => $data['title'], //缩略图的媒体ID
                    ]
                ];
                break;
            case 'video':
                $result = [
                    'video' => [
                        'media_id'       => $data, //发送的视频的媒体ID
                        'thumb_media_id' => $data, //缩略图的媒体ID
                        'title'          => $data, //视频消息的标题
                        'description'    => $data, //视频消息的描述

                    ]
                ];
                break;
            case 'news':
                $articles = [];
                foreach ($data as $key => $row) {
                    $check = array_keys($row);
                    sort($check);
                    $md5 = md5(json_encode($check, JSON_UNESCAPED_UNICODE));
                    if ($md5 === 'a966ed3782507e828ad12e8d5f0c1c16') {
                        $articles[] = $row;
                    }
                }
                $result = [
                    'news' => [
                        'articles' => $articles
                    ]
                ];
                break;
            case 'miniprogrampage':
                $result = [
                    'miniprogrampage' => [
                        'title' => $data['title'],
                        'appid' => $data['appid'],
                        'pagepath' => $data['pagepath'],
                        'thumb_media_id' => $data['thumb_media_id'],
                    ]
                ];
                break;
            default:
                break;
        }
        return $result;
    }

    /**
     * @param string $toUser   发送人
     * @param string $fromUser 接收人
     * @param string $pushType 消息类型
     * @param mixed  $pushData 消息体
     *
     * @return null|xml|mixed
     * @author  tianming <keyed.cn@gmail.com>
     */
    public static function generateMessage($toUser, $fromUser, $pushType, $pushData)
    {
        $xml     = '<xml>
        <ToUserName><![CDATA[' . $toUser . ']]></ToUserName>
        <FromUserName><![CDATA[' . $fromUser . ']]></FromUserName>
        <CreateTime>' . time() . '</CreateTime>
        <MsgType><![CDATA[' . $pushType . ']]></MsgType>
        ';
        $content = '';
        switch ($pushType) {
            case 'text':
                $content = '<Content><![CDATA[' . $pushData . ']]></Content>';
                break;
            case 'image':
                $content = '<Image><MediaId><![CDATA[' . $pushData . ']]></MediaId></Image>';
                break;
            case 'voice':
                $content = '<Voice><MediaId><![CDATA[' . $pushData . ']]></MediaId></Voice>';
                break;
            case 'video':
                if (is_array($pushData) && isset($pushData['media_id']) && isset($pushData['title'])
                    && isset($pushData['description'])
                ) {
                    $content = '<Video>
                <MediaId><![CDATA[' . $pushData['media_id'] . ']]></MediaId>
                <Title><![CDATA[' . $pushData['title'] . ']]></Title>
                <Description><![CDATA[' . $pushData['description'] . ']]></Description>
                </Video> ';
                }
                break;
            case 'music':
                if (is_array($pushData) && isset($pushData['title']) && isset($pushData['description'])
                    && isset($pushData['HQMusicUrl']) && isset($pushData['thumbMediaId']) && isset($pushData['MusicUrl'])
                ) {
                    $content = '<Music>
                <ThumbMediaId><![CDATA[' . $pushData['media_id'] . ']]></ThumbMediaId>
                <Title><![CDATA[' . $pushData['title'] . ']]></Title>
                <Description><![CDATA[' . $pushData['description'] . ']]></Description>
                <HQMusicUrl><![CDATA[' . $pushData['HQMusicUrl'] . ']]></HQMusicUrl>
                <MusicUrl><![CDATA[' . $pushData['MusicUrl'] . ']]></MusicUrl>
                </Music> ';
                }
                break;
            case 'news':
                if (is_array($pushData) && count($pushData) > 0) {
                    $itemInfo = self::generateNewsItem($pushData);
                    $content  = ($itemInfo['itemTotal'] > 0) ?
                        '<ArticleCount>' . $itemInfo['itemTotal'] . '</ArticleCount><Articles>' . $itemInfo['item'] . '</Articles>' : null;
                }
                break;
        }

        return empty($content) ? $content : $xml . $content . '</xml>';
    }

    private static function generateNewsItem($newsData)
    {
        $item      = '';
        $itemTotal = 0;
        foreach ($newsData as $key => $row) {
            if (isset($row['title']) && isset($row['description']) && isset($row['picUrl']) && isset($row['url'])) {
                $item .= '<item>
            <Title><![CDATA[' . $row['title'] . ']]></Title>
            <Description><![CDATA[' . $row['description'] . ']]></Description>
            <PicUrl><![CDATA[' . $row['picUrl'] . ']]></PicUrl>
            <Url><![CDATA[' . $row['url'] . ']]></Url>
            </item>';
                ++$itemTotal;
            }
        }

        return ['item' => $item, 'itemTotal' => $itemTotal];
    }
}
