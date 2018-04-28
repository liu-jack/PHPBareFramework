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
require_once '../../../common.inc.php';


use Common\Bridge;
use Queue\Queue;

/**
 * 购物袋微信消
 *
 *
 * @author  wds
 */
class notify extends lib\core\Action
{
    //默认页面
    public function doDefault()
    {
        //签名数据验证
        $this->checkSignature();

        //事件处理
        $this->responseNotify();


    }

    public function doTest()
    {
        $d = $this->getRedis()->get('dddd');

        echo 'json11111111=' . $d;

    }

    private function getRedis($write = false)
    {
        return Bridge::redis($write ? Bridge::REDIS_OTHER_W : Bridge::REDIS_OTHER_R);
    }


    /**
     *
     */
    private function responseNotify()
    {
        //接收数据
        $xml = file_get_contents('php://input');

        //xml 转 array
        $data = $this->xml2array($xml);

        //事件类型
        if ($data['MsgType'] == 'event') {

            //关注或扫描事件
            if ($data['Event'] == 'subscribe' || $data['Event'] == 'SCAN') {

                //区别公众号
                $WechatId = (isset($_GET['wechatId']) ? $_GET['wechatId'] : '');

                $data['WechatId'] = $WechatId;

                $this->responseEvent($data);

                $ret = array_merge($data, $_GET);

                $this->getRedis(true)->set('dddd', json_encode($ret), 300);


            }

        }

    }

    /**
     * 关注事件信息回复
     *
     * @param $data
     */
    protected function responseEvent($data)
    {

        $data['EventKey'] = str_replace('qrscene_', '', $data['EventKey']);

        $this->responseMsg($data['ToUserName'], $data['FromUserName'], '欢迎你，给收银员看这条消息即可免费获得购物袋1个');

        Queue::add('WxShoppingBags', $data);

    }

    /**
     * 关注发送消息消息
     */
    protected function responseMsg($fromUser, $toUser, $text)
    {

        $xml = '<xml>';

        $xml .= '<ToUserName><![CDATA[' . $toUser . ']]></ToUserName>';

        $xml .= '<FromUserName><![CDATA[' . $fromUser . ']]></FromUserName>';

        $xml .= '<CreateTime>' . time() . '</CreateTime>';

        $xml .= '<MsgType><![CDATA[text]]></MsgType>';

        $xml .= '<Content><![CDATA[' . $text . ']]></Content>';

        $xml .= '</xml>';

        echo $xml;

    }

    //xml 转 array
    protected function xml2array($xml)
    {

        if (empty($xml)) {
            return array();
        }

        libxml_disable_entity_loader(true);

        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }


    /**
     * 微信签名验证
     */

    private function checkSignature()
    {

        /*$token = "wxshoppingbags";

        $id = $_GET['id'];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $tmpArr = array($token, $timestamp, $nonce, $id);

        sort($tmpArr);

        $tmpStr = implode($tmpArr);

        $tmpStr = sha1($tmpStr);

        if ($tmpStr != $signature) {
            die('签名错误');
        }*/

        if (isset($_GET["echostr"])) {
            die($_GET["echostr"]);
        }
    }
}

$app->run();
