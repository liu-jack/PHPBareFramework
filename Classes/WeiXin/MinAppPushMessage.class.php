<?php
/**
 *
 * 小程序模板消息
 *
 * @author: hjh <hjh@jf.com>
 *
 * Date: 2017/11/30
 * Time: 19:36
 */

namespace lib\plugins\MinApp;

use lib\util\Request;
use lib\plugins\weixin\WeiXinTrait;
use Tools\RedisListQueue;

class MinAppPushMessage
{
    use WeiXinTrait;
    const WEIXIN_API_HOST = 'https://api.weixin.qq.com/cgi-bin/';

    /**
     * 根据模版发送消息 https://mp.weixin.qq.com/debug/wxadoc/dev/api/notice.html#发送模板消息
     *
     * @param        $accessToken
     * @param        $toUser
     * @param        $formId
     * @param        $templateId
     * @param        $data
     * @param        $page
     * @param string $emphasis_keyword
     *
     * @param bool   $sendDirectly
     *
     * @return array|mixed
     */
    public static function sendTemplateMessage($accessToken, $toUser, $formId, $templateId, $data, $page, $emphasis_keyword = '', $sendDirectly = false)
    {
        //TODO 等成功后再注释
        if ($sendDirectly) {
            return self::sendTemplateMessageDirectly($accessToken, $toUser, $formId, $templateId, $data, $page, $emphasis_keyword);
        } else {
            return self::sync($accessToken, $toUser, $formId, $templateId, $data, $page, $emphasis_keyword);
        }

        //return self::sendTemplateMessageDirectly($accessToken, $toUser, $formId, $templateId, $data, $page, $emphasis_keyword);
    }

    /**
     * @param        $accessToken
     * @param        $toUser
     * @param        $formId
     * @param        $templateId
     * @param        $data
     * @param        $page
     * @param string $emphasis_keyword
     *
     * @return array|bool|mixed
     */
    public static function sendTemplateMessageDirectly($accessToken, $toUser, $formId, $templateId, $data, $page, $emphasis_keyword = '')
    {
        $sendData = [
            'touser'      => $toUser,
            'template_id' => $templateId,
            'page'        => $page,
            'form_id'     => $formId,
            'data'        => $data,
        ];
        if (!empty($emphasis_keyword)) {
            $sendData['emphasis_keyword'] = $emphasis_keyword;
        }
        $url      = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $accessToken;

        debug_log("sendTemplateMessage: {$url}");

        $params   = json_encode($sendData, JSON_UNESCAPED_UNICODE);
        $result   = Request::request($url, $params, true);

        return self::getWeChatApiResult($result);
    }

    public static function sync($accessToken, $toUser, $formId, $templateId, $data, $page, $emphasis_keyword = '')
    {
        //TODO 可能在Token失效的瞬间 有一些发不出去
        $data = [
            'AccessToken' => $accessToken,
            'ToUser' => $toUser,
            'FormId' => $formId,
            'TemplateId' => $templateId,
            'Data' => $data,
            'Page' => $page,
            'EmphasisKeyword' => $emphasis_keyword
        ];

        return RedisListQueue::instance(RedisListQueue::TYPE_WEIXIN_MINAPP_NOTICE)->push($data);
    }
}
