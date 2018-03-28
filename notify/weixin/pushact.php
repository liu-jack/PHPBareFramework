<?php
/**
 * 微信活动关注推送入口
 *
 * @author 周剑锋<camfee@foxmail.com>
 * @data   2017-07-21 16:39
 *
 */

define("NO_CHECK", true);
require_once '../../common.inc.php';

use lib\plugins\weixin\PushMessage;
use Act\WxActUserCourse as UCourse;

class pushact extends lib\core\Action
{
    private static $host = 'study.qbaoting.com'; // todo url

    public function doDefault()
    {
        $appId = isset($_GET['id']) ? $_GET['id'] : 'wx75c21b290403ffd2';
        if (empty($appId)) {
            debug_log("appid params not found {$appId}", JF_LOG_INFO);
            exit('success');
        }

        $mpEntryConf = loadconf("act/wxmpentry");
        if (!isset($mpEntryConf[$appId])) {
            debug_log("{$appId} not in weixin entry config");
            exit('success');
        }
        $xml = file_get_contents('php://input');
        debug_log($xml, JF_LOG_ERROR);
        $mpEntryConf = $mpEntryConf[$appId];
        $token = $mpEntryConf['Token'];

        if (!$this->check_sign($token)) {
            return false;
        }

        @libxml_disable_entity_loader(true);
        $msg = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        debug_log('weixin notify error, status: ' . json_encode($msg, JSON_UNESCAPED_UNICODE), JF_LOG_INFO);
        $openId = $msg['FromUserName'];

        if ($msg['MsgType'] == 'event') {
            if ($msg['Event'] == 'subscribe') {
                $userinfo = UCourse::getUserCourseByOpenId($openId);
                if (!empty($userinfo)) {
                    $this->subscribeMsg($openId, 1, $msg['ToUserName']);
                    $this->subscribeMsg($openId, 2, $msg['ToUserName']);
                    $this->subscribeMsg($openId, 3, $msg['ToUserName'], $userinfo);
                }
            }
        }
        exit('success');
    }

    /**
     * 发送关注事件推送
     *
     * @param $openId
     * @param $type
     * @param $mpWxId
     * @param $user
     */
    private function subscribeMsg($openId, $type, $mpWxId, $user = [])
    {
        $content = '';
        switch ($type) {
            case 1:
                $content = "欢迎加入【小学生成语水平提升计划】！\n我们将为你量身定制属于你的成语提升课程，深刻理解成语背后的故事及含义，快速提升写作水平及语言表达能力，实现“出口成章、笔下生花”的梦想。";
                break;
            case 2:
                $url = autohost("http://" . self::$host . "/select");
                $content = "你已完成【小学生成语计划】测试部分，系统已为你生成定制课程。请选择报名方式：\n【方式一】\n99元购买课程<a href='{$url}'>点击购买</a>\n【方式二】\n分享下方邀请卡给好友，3人扫码完成关注并测试，你即成为志愿者，免费入学";
                break;
            case 3:
                $url = autohost("http://" . self::$host . "/invite");
                $content = "<a href='{$url}'>点我获得邀请卡</a>";
                break;
        }
        if (!empty($content)) {
            $accessToken = UCourse::getAccessToken();
            $xml = PushMessage::customSend($accessToken, $openId, 'text', $content);
            echo $xml;
        }
    }

    /**
     * 签名验证
     *
     * @param $token
     * @return bool
     */
    private function check_sign($token)
    {
        if (isset($_GET['signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']) && isset($_GET['echostr'])) {
            $signature = $_GET['signature'];
            $timestamp = $_GET['timestamp'];
            $nonce = $_GET['nonce'];
            $echostr = $_GET['echostr'];
            $tmpArr = [$token, $timestamp, $nonce];
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
