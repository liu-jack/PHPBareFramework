<?php
/**
 * Created by PhpStorm.
 * User: huliren<huliren0516@163.com>
 * Date: 2017/12/15
 * Time: 16:32
 */
define("NO_CHECK", true);
require_once '../../common.inc.php';

use lib\core\Action;
use lib\plugins\weixin\CustomMessage;
use lib\plugins\weixin\PushMessage;
use Weixin\Material;

class qbstoryPushmp extends Action
{
    public function doDefault()
    {
        $this->checkSignature();

        $mpEntryConf = loadconf('minapp/qbstory')['NewPrizeService'];

        $wxConf = loadconf('minapp/minapp')['QBStory'];
        $channel = isset($_GET['channel']) ? $_GET['channel'] : '';
        if (!empty($channel)) {
            $channelConfig = loadconf('minapp/qbstory_channel');
            if (isset($channelConfig[$channel])) {
                $wxConf = $channelConfig[$channel];
            }
        }

        $xml = file_get_contents('php://input');//获取参数

        //解析
        @libxml_disable_entity_loader(true);
        $msg = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        debug_log('qbstoryPushmp notify, status: ' . json_encode($msg, JSON_UNESCAPED_UNICODE), JF_LOG_INFO);

        $toUser = $msg['FromUserName'];

        if ($msg['MsgType'] == 'event') {
            $event = $msg['Event'];
            if ($event == 'user_enter_tempsession') {//进入客服会话

                /*$content = [
                    'title' => $mpEntryConf['ServiceTitle'],
                    'description' => $mpEntryConf['ServiceDescription'],
                    'url' => $mpEntryConf['ServiceUrl'],
                    'thumb_url' => $mpEntryConf['ServiceThumbUrl'],
                ];

                if (!empty($content)) {
                    $toUser = $msg['FromUserName'];
                    $res = \lib\plugins\weixin\CustomMessage::sendMsg($wxConf['AppId'], $wxConf['AppSecret'], $toUser,
                        'link', $content);

                    debug_log($res, JF_LOG_INFO);
                }*/
                //$this->sendMiniprogramCardMessage($wxConf['AppId'], $wxConf['AppSecret'], $msg['FromUserName']);
                //$this->sendImageMessage($wxConf['AppId'], $wxConf['AppSecret'], $msg['FromUserName']);
                //CustomMessage::sendMsg($wxConf['AppId'], $wxConf['AppSecret'], $toUser, 'text', '【邀请您领取2018新春福利】在微信对话框，立即回复“会员”，即可免费获得亲宝故事vip会员卡及168元新年现金礼，名额有限，先到先得。');

                CustomMessage::sendMsg($wxConf['AppId'], $wxConf['AppSecret'], $toUser, 'link', [
                    'title' => '点击进入小儿推拿学习群',
                    'description' => '儿科专家每天视频教学，简单易上手，宝宝少生病！',
                    'url' => 'http://mp.0098118.com/wq.php?t=fE5U',
                    'thumb_url' => 'http://img3.qbaoting.com/minApp/h5/qun_kefu.jpg',
                ]);
            }
        } elseif (in_array($msg['MsgType'], ['image', 'text'])) {
            $toUser = $msg['FromUserName'];
            $fromUser = $msg['ToUserName'];

            if ($this->memberActionMessage($msg['Content'], $wxConf['AppId'], $wxConf['AppSecret'], $toUser) == false) {
                $obj = [
                    'ToUserName'   => $toUser,
                    'FromUserName' => $fromUser,
                ];
                $xml = self::transmitService($obj);
                echo $xml;
            }
            die;
        }
        exit('success');
    }

    private function checkSignature()
    {
        if (isset($_GET['signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']) && isset($_GET['echostr'])) {
            $signature = $_GET["signature"];
            $timestamp = $_GET["timestamp"];
            $nonce = $_GET["nonce"];
            $echostr = $_GET['echostr'];

            $token = 'qbaoting';
            $tmpArr = array($token, $timestamp, $nonce);
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode($tmpArr);
            $tmpStr = sha1($tmpStr);

            if ($tmpStr == $signature) {
                exit($echostr);
            } else {
                return false;
            }
        }
        return false;
    }

    protected function memberActionMessage($content, $appId, $appSecret, $toUser)
    {
        if ($content !== '会员') {
            return false;
        }
        //$materialInfo = Material::pushTempMaterial($appId, $appSecret, 'image', 'qbstory/vip-spread.png');
        //if (isset($materialInfo['media_id'])) {
            $accessToken = \Weixin\Oauth::getAccessTokenByAppId($appId, $appSecret);
            //PushMessage::customSend($accessToken, $toUser, 'image', $materialInfo['media_id']);

            $data = \MinApp\QBStory\QBPrizeCode::getSendCode($toUser, \Tools\RedisKeyCache::getQBNewGiftPackId());
            if (isset($data['code']) && $data['code'] == 200) {
                PushMessage::customSend($accessToken, $toUser, 'text', $data['data']['Code']);
                PushMessage::customSend($accessToken, $toUser, 'text', '复制上方兑换码，长按上图识别二维码领取vip会员及168元现金礼，168元现金礼在【我的】-【兑换码】页面兑换');
            } else {
                PushMessage::customSend($accessToken, $toUser, 'text', '您已成功参与vip会员领取，长按上图识别二维码体验。168元现金礼在【我的】-【代金券】中查看。');
            }
            return true;
        //}
        //return false;
    }

    protected function sendImageMessage($appId, $appSecret, $toUser)
    {
        $materialInfo = Material::pushTempMaterial($appId, $appSecret, 'image', 'qbstory/vip-spread2.png');
        if (isset($materialInfo['media_id'])) {
            $accessToken = \Weixin\Oauth::getAccessTokenByAppId($appId, $appSecret);
            PushMessage::customSend($accessToken, $toUser, 'image', $materialInfo['media_id']);
            return true;
        }
        return false;
    }

    protected function sendMiniprogramCardMessage($appId, $appSecret, $toUser)
    {
        $materialInfo = Material::pushTempMaterial($appId, $appSecret, 'image', 'qbstory/mini_message1.png');
        if (isset($materialInfo['media_id'])) {
            $accessToken = \Weixin\Oauth::getAccessTokenByAppId($appId, $appSecret);
            $res = PushMessage::customSend($accessToken, $toUser, 'miniprogrampage', [
                'title' => '名额有限，先到先得',
                'appid' => $appId,
                'pagepath' => '/pages/newyear',
                'thumb_media_id' => $materialInfo['media_id']
            ]);
        }
    }

    /**
     * 转发客服消息
     */
    private static function transmitService($object)
    {
        $xmlTpl = '<xml>
                        <ToUserName><![CDATA[' . $object['ToUserName'] . ']]></ToUserName>
                        <FromUserName><![CDATA[' . $object['FromUserName'] . ']]></FromUserName>
                        <CreateTime>' . time() . '</CreateTime>
                        <MsgType><![CDATA[transfer_customer_service]]></MsgType>
                    </xml>';

        return $xmlTpl;
    }
}

$app->run();