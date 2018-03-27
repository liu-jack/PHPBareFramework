<?php
/**
 * 微信公众号推送入口
 *
 * @author: hjh <hjh@jf.com>
 *
 * Date: 2017/4/7
 * Time: 09:08
 */

define("NO_CHECK", true);
require_once '../../common.inc.php';

use Act\WxGroupActivity;
use Act\WxGroupTopic;
use Common\ResourcePathManager;
use Weixin\Material;
use lib\plugins\weixin\CustomMessage;

class pushentry extends lib\core\Action
{
    public function doDefault()
    {
        $appId = isset($_GET['id']) ? $_GET['id'] : null;
        if (empty($appId)) {
            debug_log("appid params not found {$appId}", JF_LOG_INFO);
            exit('success');
        }

        $mpEntryConf = loadconf("act/wxmpentry");
        if (!isset($mpEntryConf[$appId])) {
            debug_log("{$appId} not in mp entry config");
            exit('success');
        }

        $mpEntryConf = $mpEntryConf[$appId];
        $token = $mpEntryConf['Token'];

        if (!$this->check_sign($token)) {
            return false;
        }

        $xml = file_get_contents('php://input');
        @libxml_disable_entity_loader(true);
        $msg = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        debug_log('weixin notify error, status: ' . json_encode($msg, JSON_UNESCAPED_UNICODE), JF_LOG_INFO);
        $openId = $msg['FromUserName'];

        if ($msg['MsgType'] == 'event') {
            if ($msg['Event'] == 'subscribe') {
                $this->subscribeEventMsg($openId, $mpEntryConf, $msg['ToUserName']);
            }
        }
        exit('success');
    }

    private function subscribeEventMsg($openId, $mpEntryConf, $mpWxId)
    {
        $topicId = 41;
        if (isset($msg['EventKey'])) {
            $event_key = $msg['EventKey'];
            if (!empty($event_key)) {
                $event_keys = explode('_', $event_key);
                if (count($event_keys) > 1) {
                    $topicId = intval([1]);
                }
            }
        }
        //推送二维码
        $topicInfo = WxGroupTopic::getGroupTopic($topicId);
        $activityInfo = WxGroupActivity::addWxGroupMember($openId, $topicInfo, '127.0.0.1', 'mp');
        if (empty($activityInfo)) {
            return;
        }
        $appId = $mpEntryConf['AppId'];
        try {
            CustomMessage::sendMsg($appId, $mpEntryConf['AppSecret'], $openId, 'text', $topicInfo['MpReplyContent']);
        } catch (\RuntimeException $e) {
            exit();
        }

        debug_log($activityInfo, JF_LOG_INFO);

        $head_file = ResourcePathManager::getWxGroupTopicHeadImagePath() . $topicInfo['HeadImage'];
        $qr_file = ResourcePathManager::getWxGroupTopicHeadImagePath() . $activityInfo['QRCodeSource'];
        $result_file = ResourcePathManager::getWxGroupTopicHeadImagePath() . 'mid-' . $activityInfo['QRCodeSource'];

        if (!file_exists($result_file)) {
            if (!\Common\PhotoImage::mergeInCenter($head_file, $qr_file, $result_file)) {
                $result_file = $qr_file;
            }
        }

        $push_permanent = Material::pushTempMaterial($appId, $mpEntryConf['AppSecret'], 'image', $result_file);
        if (empty($push_permanent)) {
            exit();
        }
        $media_id = $push_permanent['media_id'];
        $xml = \lib\plugins\weixin\PushMessage::generateMessage($openId, $mpWxId, 'image', $media_id);
        exit($xml);
    }

    private function check_sign($token)
    {
        if (isset($_GET['signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']) && isset($_GET['echostr'])) {
            $signature = $_GET['signature'];
            $timestamp = $_GET['timestamp'];
            $nonce     = $_GET['nonce'];
            $echostr   = $_GET['echostr'];
            $tmpArr    = [$token, $timestamp, $nonce];
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode($tmpArr);
            $tmpStr = sha1($tmpStr);
            if ($tmpStr == $signature) {
                exit($echostr);
            } else {
                debug_log('check sign failed', JF_LOG_ERROR);
                return false;
            }
        } else {
            // 不是接入验证 返回成功
            return true;
        }
    }
}

$app->run();
