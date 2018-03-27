<?php

define("NO_CHECK", true);
require_once '../../common.inc.php';

use Act\WxGroupActivity;
use Act\WxGroupTopic;
use Common\ResourcePathManager;
use Weixin\Material;
use lib\plugins\weixin\CustomMessage;
use lib\plugins\weixin\PushMessage;
use Weixin\MessageAi;

class pushmp extends lib\core\Action
{
    public function doDefault()
    {
        $appId = isset($_GET['id']) ? $_GET['id'] : null;
        if (empty($appId)) {
            debug_log("appid params not found {$appId}", JF_LOG_INFO);
            exit('success');
        }
        //        [media_id] => foL3wM3oy0FT8hastTDQGZJJqWFjdDhoi8v8YNMbrak
        //    [url] => http://mmbiz.qpic.cn/mmbiz_jpg/JqJOCpPfR3ImbBbmOe4kbHb0nFDw5LfcEYia5zbwWHQlh2oXk4VWLAo8GyibOm61zkfYGIPEhYSpQzLeNI61SgNg/0?wx_fmt=jpeg

        $mpEntryConf = loadconf("weixin/plugins");
        if (!isset($mpEntryConf[$appId])) {
            debug_log("{$appId} not in mp entry config");
            exit('success');
        }
        $xml = file_get_contents('php://input');
        debug_log($xml, JF_LOG_ERROR);
        $mpEntryConf = $mpEntryConf[$appId];
        $token       = $mpEntryConf['Token'];

        if (!$this->check_sign($token)) {
            return false;
        }

        @libxml_disable_entity_loader(true);
        $msg = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        debug_log('weixin notify, status: ' . json_encode($msg, JSON_UNESCAPED_UNICODE), JF_LOG_INFO);
        $openId = $msg['FromUserName'];

        if (in_array($msg['MsgType'], ['voice', 'text'])) {
            if ($msg['MsgType'] == 'voice') {
                $content = $msg['Recognition'];
            } elseif ($msg['MsgType'] == 'text') {
                $content = $msg['Content'];
            } else {
                $content = '';
            }

            $toUser                 = $msg['FromUserName'];
            $recordInfo['OpenId']   = $toUser;
            $recordInfo['FromData'] = $content;
            $recordInfo['FromType'] = ($msg['MsgType'] == 'voice') ? 1 : 5;

            $accessToken = \Weixin\Oauth::getAccessTokenByAppId($appId, $mpEntryConf['AppSecret']);

            /*
            $ret = $this->redpackage($appId, $accessToken, $content, $toUser, $mpEntryConf);
            if ($ret !== false) {
                $pushInfo = $ret;
            }
            if ($ret === false) {
                $ret = $this->newYearGiftPackage($appId, $accessToken, $content, $toUser, $mpEntryConf);
                if ($ret !== false) {
                    $pushInfo = $ret;
                }
            }
            if ($ret === false) {
                $ret = $this->yuanxiaoGiftPcakge($appId, $accessToken, $content, $toUser, $mpEntryConf);
                if ($ret !== false) {
                    $pushInfo = $ret;
                }
            }

            $this->keywordNewYear($appId, $accessToken, $content, $toUser, $mpEntryConf);
            $this->keywordVipMember($appId, $accessToken, $content, $toUser, $mpEntryConf);
            */

            //TODO 关闭搜索功能 非宝宝好故事
            if ($appId !== 'wx5b79c22a5bdd5bef') {
                $pushInfo = [
                    'id'   => 0,
                    'type' => '',
                ];
            }

            if (empty($pushInfo)) {
                $pushInfo = MessageAi::sceneInfo($content, $toUser, $accessToken);
            }
            $pushType = $pushInfo['type'];
            $pushData = $pushInfo['data'];
            $id       = $pushInfo['id'];
            debug_log('weixin notify, pushInfo: ' . json_encode($pushInfo, JSON_UNESCAPED_UNICODE), JF_LOG_INFO);
            $fromUser = $msg['ToUserName'];
            $xml      = PushMessage::generateMessage($toUser, $fromUser, $pushType, $pushData);
            if (!empty($xml)) {
                $jsonData                 = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                $jsonData['id']           = $id;
                $recordInfo['RevertData'] = json_encode($jsonData, JSON_UNESCAPED_UNICODE);
            } else {
                $recordInfo['RevertData'] = json_encode([], JSON_UNESCAPED_UNICODE);
            }
            if ($pushInfo['type'] === false) {
                $xml = 'success';
            }
            \Admin\WxMessageRecords::addRecords($recordInfo);
            \Weixin\MpFollower::updateTime($appId, $openId);

            debug_log($xml, JF_LOG_INFO);
            echo $xml;
        } elseif ($msg['MsgType'] == 'event') {
            $sceneId     = isset($msg['EventKey']) ? str_replace("qrscene_", '', $msg['EventKey']) : ''; //事件KEY值，是一个32位无符号整数，即创建二维码时的二维码scene_id
            $eventTicket = isset($msg['Ticket']) ? $msg['Ticket'] : ''; //二维码的ticket，可用来换取二维码图片
            $event       = $msg['Event'];

            if ($event == 'CLICK') { //点击菜单跳转链接时的事件推送，点击菜单弹出子菜单，不会产生上报。
                \Weixin\MpFollower::updateTime($appId, $openId);
                $toUser   = $msg['FromUserName'];
                $fromUser = $msg['ToUserName'];
                if ($sceneId == 'zhaogushi') {
                    $xml = PushMessage::generateMessage($toUser, $fromUser, 'image', 'foL3wM3oy0FT8hastTDQGQXd62PyQGE6ewL6KnmXeVQ');
                    echo $xml;
                    die;
                }
            } elseif ($event == 'subscribe' && !empty($sceneId) && isset($msg['Ticket']) && !empty($eventTicket)) {  //扫描带参数二维码事件 1. 用户未关注时，进行关注后的事件推送
                $info = [
                    'AppId'  => $appId,
                    'OpenId' => $openId,
                ];
                debug_log("weixin notify, new user subscribe, sceneId: {$sceneId}, appId: {$appId}, openId: {$openId}", JF_LOG_INFO);
                \Weixin\MpFollower::add($info, $sceneId);
                $this->subscribeEventMsg($openId, $mpEntryConf, $msg['ToUserName']);
                if (stripos($sceneId, 'qbShareAlbum_') !== false) {
                    $this->scanEventMsg($openId, $sceneId, $mpEntryConf);
                }
            } elseif ($event == 'SCAN' && !empty($sceneId) && isset($msg['Ticket']) && !empty($eventTicket)) {  //扫描带参数二维码事件 1.用户已关注时的事件推送
                debug_log("weixin notify, old user enter, sceneId: {$sceneId}, appId: {$appId}, openId: {$openId}", JF_LOG_INFO);
                if (stripos($sceneId, 'qbShareAlbum_') !== false) {
                    $this->scanEventMsg($openId, $sceneId, $mpEntryConf);
                } else {
                    \Weixin\MpPushMessage::sendEventFollowerMessage($sceneId, $openId, false, $appId);
                }
                \Weixin\MpFollower::updateTime($appId, $openId);
            } elseif ($event == 'subscribe') {//关注
                $info = [
                    'AppId'  => $appId,
                    'OpenId' => $openId,
                ];
                \Weixin\MpFollower::add($info);
                $this->subscribeEventMsg($openId, $mpEntryConf, $msg['ToUserName']);
                if (!empty($sceneId) && stripos($sceneId, 'qbShareAlbum_') !== false) {
                    $this->scanEventMsg($openId, $sceneId, $mpEntryConf);
                }
            } elseif ($event == 'unsubscribe') {//取消关注
                //TODO
                \Weixin\MpFollower::remove($appId, $openId);
            }
        }
        exit('');
    }

    // 扫描亲宝故事小程序带参数专辑
    private function scanEventMsg($openId, $sceneId, $mpEntryConf)
    {
        list(/**/,$userInviteKey, $albumId) = explode('_', $sceneId);
        if (empty($userInviteKey) || empty($albumId)) {
            debug_log("scanEventMsg failed, userInviteKey: {$userInviteKey}, albumId: {$albumId}", JF_LOG_ERROR);
            return false;
        }
        $inviteUserId = \Center\User::getUserIdByInviteKey($userInviteKey);
        $albumInfo = \Center\Album::getStoryAlbumById($albumId);
        if (empty($albumInfo) || empty($inviteUserId)) {
            debug_log("scanEventMsg failed, inviteUserId: {$inviteUserId}, albumId: {$albumId}", JF_LOG_ERROR);
            return false;
        }
        if (in_array($albumInfo['AlbumType'], [
            \Center\Album::ALBUM_TYPE_PAYMENT,
            \Center\Album::ALBUM_TYPE_INVITE,
            \Center\Album::ALBUM_TYPE_PAYMENT_INVITE
        ])) {
            if ($albumInfo['AlbumContentType'] == \Center\Album::ALBUM_CONTENT_TYPE_VIDEO) {
                $pages = '/pages/videoalbum?aid=' . $albumInfo['AlbumId'] . '&uid=' . $inviteUserId;
            } else {
                $pages = '/pages/storyalbum?aid=' . $albumInfo['AlbumId'] . '&uid=' . $inviteUserId;
            }
        } else {
            $pages = '/pages/freealbum?aid=' . $albumInfo['AlbumId'] . '&uid=' . $inviteUserId;
        }

        $appId = $mpEntryConf['AppId'];
        debug_log("scanEventMsg push, inviteUserId: {$inviteUserId}, albumId: {$albumId}");
        $userInfo = \Center\User::getUserById($inviteUserId);
        $userNick = !empty($userInfo['UserNick']) ? $userInfo['UserNick'] : '';
        $miniText = '你的好友' . $userNick . '推荐你收听《' . $albumInfo['Title'] . '》';
        $img = empty($albumInfo['Bg']) ? $albumInfo['Cover'] : $albumInfo['Bg'];
        $img_path = self::getTmpPic($img);
        if (empty($img_path)) {
            debug_log("scanEventMsg failed, inviteUserId: {$inviteUserId}, albumId: {$albumId}, img: {$img}, ", JF_LOG_ERROR);
            return false;
        }
        $materialInfo = Material::pushTempMaterial($appId, $mpEntryConf['AppSecret'], 'image', $img_path);
        unlinkFile($img_path);
        if (isset($materialInfo['media_id'])) {
            $accessToken = \Weixin\Oauth::getAccessTokenByAppId($appId, $mpEntryConf['AppSecret']);
            PushMessage::customSend($accessToken, $openId, 'miniprogrampage', [
                'title' => $miniText,
                'appid' => 'wxe2a33360a65260b8',
                'pagepath' => $pages,
                'thumb_media_id' => $materialInfo['media_id']
            ]);
        }
    }

    private function subscribeEventMsg($openId, $mpEntryConf, $mpWxId)
    {
        $appId   = $mpEntryConf['AppId'];
        $content = $mpEntryConf['TextContent'];

        debug_log("subscribeEvent: openId: {$openId}, appId: {$appId}, mpWxId: {$mpWxId}", JF_LOG_INFO);
        $mpAppNames = \Weixin\WeixinMp::getList();
        foreach ($mpAppNames as $appInfo) {
            if ($appId == $appInfo[\Weixin\WeixinMp::FIELD_APP_ID]) {
                if (!empty($appInfo[\Weixin\WeixinMp::FIELD_REVERT])) {
                    $data = json_decode($appInfo[\Weixin\WeixinMp::FIELD_REVERT], true);
                    if (!empty($data) && $data[\Weixin\WeixinMp::FIELD_REVERT_TYPE] == \Weixin\WeixinMp::FIELD_REVERT_TYPE_TEXT) {
                        if (!empty($data[\Weixin\WeixinMp::FIELD_REVERT_CONTENT])) {
                            $content = $data[\Weixin\WeixinMp::FIELD_REVERT_CONTENT];
                            debug_log("subscribeEvent: reset reply content: " . $content, JF_LOG_INFO);
                        }
                    }
                }
                break;
            }
        }

        $xml = \lib\plugins\weixin\PushMessage::generateMessage($openId, $mpWxId, 'text', $content);
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
                exit($echostr);
            }
        } else {
            // 不是接入验证 返回成功
            return true;
        }
    }

    private function redpackage($appId, $accessToken, $content, $toUser, $mpEntryConf)
    {
        if ($appId == 'wx3d3ea257ad52ba06' && strpos($content,'测试红包') !== FALSE) {
            $data = \MinApp\QBStory\QBPrizeCode::getSendCode($toUser);
            if (!empty($data['data'])) {
                if ($data['code'] == 200) {
                    $pages = '/pages/my';
                    PushMessage::customSend($accessToken, $toUser, 'text', $data['data']['Code']);
                    PushMessage::customSend($accessToken, $toUser, 'text', '恭喜您领到了88元新人代金券，复制上方兑换码，点击下方小程序卡片，在【我的】-【兑换码】页面兑换奖励');
                    $miniText = '恭喜您领到了88元新人代金券';
                } else {
                    $pages = '/pages/coupons';
                    PushMessage::customSend($accessToken, $toUser, 'text', '您已经领取过88元新人代金券，请点击下方小程序卡片，在【我的】-【代金券】页面查看红包');
                    $miniText = '您已经领取过88元新人代金券，点击使用';
                }
                $materialInfo = Material::pushTempMaterial($appId, $mpEntryConf['AppSecret'], 'image', 'qbstory/qbstory1.png');
                if (isset($materialInfo['media_id'])) {
                    PushMessage::customSend($accessToken, $toUser, 'miniprogrampage', [
                        'title' => $miniText,
                        'appid' => 'wxe2a33360a65260b8',
                        'pagepath' => $pages,
                        'thumb_media_id' => $materialInfo['media_id']
                    ]);
                }
                $pushInfo = [
                    'id' => 0,
                    'type' => '',
                ];
            } else {
                debug_log("weixin notify error, getSendCode failed " . json_encode($data, JSON_UNESCAPED_UNICODE), JF_LOG_WARNING);
            }
        }
        if ($appId == 'wx3d3ea257ad52ba06' && trim($content) == '红包') {
            $pushInfo = [
                'id' => 0,
                'type' => '',
            ];

            $data = \MinApp\QBStory\QBPrizeCode::getSendCode($toUser);
            if (!empty($data['data'])) {
                if ($data['code'] == 200) {
                    $pages = '/pages/my';
                    PushMessage::customSend($accessToken, $toUser, 'text', $data['data']['Code']);
                    PushMessage::customSend($accessToken, $toUser, 'text', '恭喜您领到了88元新人代金券，复制上方兑换码，点击下方小程序卡片，在【我的】-【兑换码】页面兑换奖励');
                    $miniText = '恭喜您领到了88元新人代金券';
                } else {
                    $pages = '/pages/coupons';
                    PushMessage::customSend($accessToken, $toUser, 'text', '您已经领取过88元新人代金券，请点击下方小程序卡片，在【我的】-【代金券】页面查看红包');
                    $miniText = '您已经领取过88元新人代金券，点击使用';
                }
                $materialInfo = Material::pushTempMaterial($appId, $mpEntryConf['AppSecret'], 'image', 'qbstory/qbstory1.png');
                if (isset($materialInfo['media_id'])) {
                    PushMessage::customSend($accessToken, $toUser, 'miniprogrampage', [
                        'title' => $miniText,
                        'appid' => 'wxe2a33360a65260b8',
                        'pagepath' => $pages,
                        'thumb_media_id' => $materialInfo['media_id']
                    ]);
                }
            } else {
                debug_log("weixin notify error, getSendCode failed " . json_encode($data, JSON_UNESCAPED_UNICODE), JF_LOG_WARNING);
            }
            return $pushInfo;
        }
        return false;
    }

    private function newYearGiftPackage($appId, $accessToken, $content, $toUser, $mpEntryConf)
    {
        if ($appId == 'wx3d3ea257ad52ba06' && strpos($content,'新年礼包') !== FALSE) {
            $data = \MinApp\QBStory\QBPrizeCode::getSendCode($toUser, \Tools\RedisKeyCache::getQBNewGiftPackId());
            $pushInfo = [
                'id' => 0,
                'type' => '',
            ];

            if (!empty($data['data'])) {
                if ($data['code'] == 200) {
                    $pages = '/pages/my';
                    PushMessage::customSend($accessToken, $toUser, 'text', $data['data']['Code']);
                    PushMessage::customSend($accessToken, $toUser, 'text', "恭喜你获得亲宝故事168元新年现金礼！\n".
                        "海量好课程陪孩子充实过寒假！更有亲子教育课程助您轻松育儿~\n\n".
                        "复制上方兑换码，点击下方小程序卡片，\n".
                        "进入【亲宝故事】小程序，在【我的】-【兑换码】兑换礼包\n".
                        "（代金券过期作废，请尽快使用哦）");
                    $miniText = '您已获得168元新年现金礼，马上为孩子选购一个好课程吧';
                } else {
                    $pages = '/pages/coupons';
                    PushMessage::customSend($accessToken, $toUser, 'text', '您已领取过168元新年现金礼，请点击下方小程序卡片，在【我的】-【代金券】页面进行查看');
                    $miniText = '您已领取过168元新年现金礼，马上点击使用吧！';
                }
                $materialInfo = Material::pushTempMaterial($appId, $mpEntryConf['AppSecret'], 'image', 'qbstory/newyear.png');
                if (isset($materialInfo['media_id'])) {
                    PushMessage::customSend($accessToken, $toUser, 'miniprogrampage', [
                        'title' => $miniText,
                        'appid' => 'wxe2a33360a65260b8',
                        'pagepath' => $pages,
                        'thumb_media_id' => $materialInfo['media_id']
                    ]);
                }
            } else {
                debug_log("weixin notify error, getSendCode failed " . json_encode($data, JSON_UNESCAPED_UNICODE), JF_LOG_WARNING);
            }
            return $pushInfo;
        }
        return false;
    }

    private function yuanxiaoGiftPcakge($appId, $accessToken, $content, $toUser, $mpEntryConf)
    {
        if ($appId == 'wx3d3ea257ad52ba06' && strpos($content,'元宵礼包') !== FALSE) {
            $pushInfo = [
                'id' => 0,
                'type' => '',
            ];
            $data = \MinApp\QBStory\QBPrizeCode::getSendCode($toUser, \Tools\RedisKeyCache::getYuanXiaoGiftPackId());
            if (!empty($data['data'])) {
                if ($data['code'] == 200) {
                    $pages = '/pages/my';
                    PushMessage::customSend($accessToken, $toUser, 'text', $data['data']['Code']);
                    PushMessage::customSend($accessToken, $toUser, 'text',
                        "恭喜你获得亲宝故事128元元宵现金礼！\n".
                        "新学期新气象，海量好课程助孩子成绩稳步上升！\n".
                        "更有高质量亲子教育课程，助您轻松养出好孩子~\n\n".
                        "复制上方兑换码，点击下方小程序卡片，\n".
                        "进入【亲宝故事】小程序，在【我的】-【兑换码】兑换礼包\n".
                        "（代金券过期作废，请尽快使用哦~）");
                    $miniText = '您已获得128元元宵现金礼，马上为孩子选购一个好课程吧！';
                } else {
                    $pages = '/pages/coupons';
                    PushMessage::customSend($accessToken, $toUser, 'text', '您已领取过128元元宵现金礼，请点击下方小程序卡片，在【我的】-【代金券】页面进行查看');
                    $miniText = '您已领取过128元元宵现金礼，马上点击使用吧！';
                }
                $materialInfo = Material::pushTempMaterial($appId, $mpEntryConf['AppSecret'], 'image', 'qbstory/yuanxiao.png');
                if (isset($materialInfo['media_id'])) {
                    PushMessage::customSend($accessToken, $toUser, 'miniprogrampage', [
                        'title' => $miniText,
                        'appid' => 'wxe2a33360a65260b8',
                        'pagepath' => $pages,
                        'thumb_media_id' => $materialInfo['media_id']
                    ]);
                }
            } else {
                debug_log("weixin notify error, getSendCode failed " . json_encode($data, JSON_UNESCAPED_UNICODE), JF_LOG_WARNING);
            }
            return $pushInfo;
        }
        return false;
    }

    private function keywordNewYear($appId, $accessToken, $content, $toUser, $mpEntryConf)
    {
        if ($appId == 'wx3d3ea257ad52ba06' && strpos($content, '过年好') !== false) {
            PushMessage::customSend($accessToken, $toUser, 'text', "新年新愿“旺”，168元现金礼奉上！\n\n" .
            "请您按照以下3个步骤完成任务，即可获得【跟着淘淘过大年】特价购买资格！更有海量好课程都可超低价购买哦~\n\n" .
            "第一步：保存下图，下图转发到3个50人以上的班级群/家长交流群+朋友圈，并截图\n\n第二步：请将微信群截图发到这里（注意，是分享到3个群和朋友圈的截图哦~）\n\n" .
            "第三步：请回复关键词☞ “新年礼包”，小编核实截图后，下发168元现金礼包\n\n" .
            "（人力有限，或许不能及时回复您，但一定会在12小时内审核并下发链接，请耐心等候~）");

            $materialInfo = Material::pushTempMaterial($appId, $mpEntryConf['AppSecret'], 'image', 'qbstory/guonianhao.png');
            if (isset($materialInfo['media_id'])) {
                PushMessage::customSend($accessToken, $toUser, 'image', $materialInfo['media_id']);
            }
        }

        return false;
    }

    private function keywordVipMember($appId, $accessToken, $content, $toUser, $mpEntryConf)
    {
        if ($appId == 'wx3d3ea257ad52ba06' && strpos($content, '会员') !== false) {
            PushMessage::customSend($accessToken, $toUser, 'text', "千万名师服务一个家庭！【亲宝故事】 2018年招募亲子会员啦~\n\n" .
                "请您按照以下3个步骤完成任务，即可【免费】获得原价36元的亲子会员月卡！前1000名还可获得168元新年现金礼哦~\n\n" .
                "第一步：保存下图，下图转发到3个50人以上的班级群/家长交流群+朋友圈集赞3个，并截图\n\n" .
                "第二步：请将微信群截图发到这里（注意，是分享到3个群和朋友圈，一共4张截图哦~）\n\n" .
                "第三步：发完截图后，请继续回复关键词☞ “新年礼包”，小编核实截图后，下发亲子月卡0元购资格，前1000名还将下发168元新年现金礼\n\n" .
                "（人力有限，或许不能及时回复您，但一定会在12小时内审核并下发链接，请耐心等候~）");

            $materialInfo = Material::pushTempMaterial($appId, $mpEntryConf['AppSecret'], 'image', 'qbstory/vip-member.png');
            if (isset($materialInfo['media_id'])) {
                PushMessage::customSend($accessToken, $toUser, 'image', $materialInfo['media_id']);
            }
        }
    }

    private static function getTmpPic($url)
    {
        $temp = tempnam('/tmp', 'qbstory_pic_');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        $img = curl_exec($ch);
        curl_close($ch);
        if (!empty($img)) {
            $fp = fopen($temp, 'a');
            fwrite($fp, $img);
            fclose($fp);
            $ext_type = exif_imagetype($temp);
            $ext_arr = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_GIF => 'gif',
                IMAGETYPE_PNG => 'png',
            ];
            $ext = isset($ext_arr[$ext_type]) ? $ext_arr[$ext_type] : 'jpg';
            $temp2 = $temp . '.' . $ext;

            rename($temp, $temp2);

            return $temp2;
        }
        @unlink($temp);

        return false;
    }
}

$app->run();
