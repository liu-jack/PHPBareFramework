<?php

/**
 * Project: story-server
 * File Created at 2017-02-23
 * Copyright 2014 keyed.cn All right reserved. This software is the
 * confidential and proprietary information of keyed.cn ("Confidential
 * Information"). You shall not disclose such Confidential Information and shall
 * use it only in accordance with the terms of the license agreement you entered
 * into with keyed.cn .
 */
define('NO_SESSION', true);
require_once '../../common.inc.php';
loadconf('mobileapi/base');

use lib\plugins\weixin\PushMessage;
use Weixin\MessageAi;
use Weixin\Oauth;

/**
 * 微信消息回复
 * Class Message.
 *
 * @author  tianming <keyed.cn@gmail.com>
 */
class pushmsg extends lib\core\Action
{
    /**
     * 内容消息回复.
     */
    public function doDefault()
    {
        $type = (int) (isset($_GET['type']) ? $_GET['type'] : 0);
        self::checkSign();
        $xml = file_get_contents('php://input');
        //        $xml = '<xml><ToUserName><![CDATA[gh_2098e3d3e089]]></ToUserName>
        //<FromUserName><![CDATA[oPLsVt1Oo5EB4TM9IFhqvcL7GmyY]]></FromUserName>
        //<CreateTime>1487843900</CreateTime>
        //<MsgType><![CDATA[voice]]></MsgType>
        //<MediaId><![CDATA[lfd9hwQhs_0XnphczA67Vp9Of9A0UDOTEELrpErQuc13qlATQKDnPqCjWOlda6CY]]></MediaId>
        //<Format><![CDATA[amr]]></Format>
        //<MsgId>6390240892053094400</MsgId>
        //<Recognition><![CDATA[白雪公主]]></Recognition>
        //</xml>';
        @libxml_disable_entity_loader(true);
        $text = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        debug_log('weixin notify error, status: ' . json_encode($text, JSON_UNESCAPED_UNICODE), JF_LOG_INFO);
        $recordInfo = [];

        if (in_array($text['MsgType'], ['voice', 'text'])) {
            if ($text['MsgType'] == 'voice') {
                $content = $text['Recognition'];
            } elseif ($text['MsgType'] == 'text') {
                $content = $text['Content'];
            } else {
                $content = '';
            }

            $toUser                 = $text['FromUserName'];
            $recordInfo['OpenId']   = $toUser;
            $recordInfo['FromData'] = $content;
            $recordInfo['FromType'] = ($text['MsgType'] == 'voice') ? 1 : 5;

            $pushInfo = MessageAi::sceneInfo($content, $toUser);
            $pushType = $pushInfo['type'];
            $pushData = $pushInfo['data'];
            $id       = $pushInfo['id'];
            debug_log('weixin notify error, pushInfo: ' . json_encode($pushInfo, JSON_UNESCAPED_UNICODE), JF_LOG_INFO);
            $fromUser = $text['ToUserName'];
            $xml      = PushMessage::generateMessage($toUser, $fromUser, $pushType, $pushData);
            if (!empty($xml)) {
                $jsonData                 = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                $jsonData['id']           = $id;
                $recordInfo['RevertData'] = json_encode($jsonData, JSON_UNESCAPED_UNICODE);
            } else {
                $recordInfo['RevertData'] = json_encode([], JSON_UNESCAPED_UNICODE);
            }
            \Admin\WxMessageRecords::addRecords($recordInfo);
            echo $xml;
        } elseif ($text['MsgType'] == 'event' && $text['Event'] == 'subscribe') {
            $toUser   = $text['FromUserName'];
            $fromUser = $text['ToUserName'];
            /* if($type == 1){
                $openId = $toUser;
                $weixinMp = loadconf("act/wxmpentry");
                $weixinMp = $weixinMp['wx11ff07c6b1fdb9fb'];
                $oauth = Oauth::getAccessTokenByAppId($weixinMp['AppId'], $weixinMp['AppSecret']);
                $userInfo = Oauth::getUserInfo($toUser);
                $sex = $userInfo['sex'];//用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
                //TODO 
                if($sex == 1) { //男
                    $material = \Admin\WxMaterial::getMaterialById(41);
                } elseif ($sex == 2) { //女
                    $material = \Admin\WxMaterial::getMaterialById(41);
                } else { //未知
                    $material = \Admin\WxMaterial::getMaterialById(41);
                }
            } */
            $material = \Admin\WxMaterial::getMaterialById(41);

            if (!empty($material)) {
                $pushData = \Admin\WxSceneMsg::translationMaterialData($material);
                $xml      = PushMessage::generateMessage($toUser, $fromUser, $pushData['pushType'], $pushData['pushData']);

                echo $xml;
            }
        }

        die;
    }

    /**
     * 微信签名验证
     */
    private static function checkSign()
    {
        if (isset($_GET['signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']) && isset($_GET['echostr'])) {
            $signature = $_GET['signature'];
            $timestamp = $_GET['timestamp'];
            $nonce     = $_GET['nonce'];
            $echostr   = $_GET['echostr'];
            $token     = 'qbaotingtingting';
            $tmpArr    = [$token, $timestamp, $nonce];
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode($tmpArr);
            $tmpStr = sha1($tmpStr);
            if ($tmpStr == $signature) {
                echo $echostr;
            } else {
                echo $echostr;
            }
            die;
        } else {
            return false;
        }
    }
}

$app->run();
