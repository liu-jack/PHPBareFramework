<?php
/**
 * Created by PhpStorm.
 * User: huliren<huliren0516@163.com>
 * Date: 2017/12/1
 * Time: 15:27
 */
define("NO_CHECK", true);
require_once '../../common.inc.php';

use lib\core\Action;
use Weixin\Material;
use lib\plugins\weixin\PushMessage;
use Model\Common\RecomData;

class numbergamePushmp extends Action
{

    public function doDefault()
    {
        
        $this->checkSignature();

        $mpEntryConf = loadconf('act/numbergame');
        $xml = file_get_contents('php://input');//获取参数

        //解析
        @libxml_disable_entity_loader(true);
        $msg = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        debug_log('numbergamePushmp notify, status: ' . json_encode($msg, JSON_UNESCAPED_UNICODE));

        if ($msg['MsgType'] == 'event') {
            $event = $msg['Event'];
            if ($event == 'user_enter_tempsession') {//进入客服会话
                //                $images = [
                //                    __DIR__  . '/numbergame_resource/code.jpg',  //二维码本地地址
                //                ];
                //                $imageCount = count($images);
                //                $imageFile = $images[rand(0, $imageCount) % $imageCount];
                //
                //                $mId_res  = Material::pushTempMaterial($mpEntryConf['AppId'], $mpEntryConf['AppSecret'], 'image', $imageFile,86400);
                //                debug_log($mId_res, JF_LOG_INFO);
                //                $mId      = $mId_res['media_id'];

                $content = self::getConfig();
                $type = 'link';

                if (!empty($content)) {
                    $toUser = $msg['FromUserName'];

                    //$res = \lib\plugins\weixin\CustomMessage::sendMsg($mpEntryConf['AppId'], $mpEntryConf['AppSecret'], $toUser, $type, $content);
                    //debug_log($res);

                    $customData = [
                        'AppId' => $mpEntryConf['AppId'],
                        'AppSecret' => $mpEntryConf['AppSecret'],
                        'ToUser' => $toUser,
                        'Type' => $type,
                        'Data' => $content
                    ];

                    \Tools\RedisListQueue::instance(\Tools\RedisListQueue::TYPE_WEIXIN_CUSTOM_MESSAGE)->push($customData);
                }
            }
        }elseif (in_array($msg['MsgType'], ['image', 'text'])) {
            $toUser = $msg['FromUserName'];
            $fromUser = $msg['ToUserName'];
            $obj = [
                'ToUserName' => $toUser,
                'FromUserName' => $fromUser,
            ];

            $xml = self::transmitService($obj);
            echo $xml;
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

    private static function getConfig(){
        $customers = RecomData::getData(RecomData::XCC_NGGAME_CUSTOMER);
        $data = [];
        if(!empty($customers[RecomData::XCC_NGGAME_CUSTOMER])){
            $customers = $customers[RecomData::XCC_NGGAME_CUSTOMER];
            foreach ($customers as $v){
                if($v['Status'] == 0){
                    unset($v['Status']);
                    $data[] = $v;
                }
            }
        }
        $count = count($data)-1;

        if($count>=0){
            return $data[rand(0,$count)];
        }else{
            $mpEntryConf = loadconf('act/numbergame');
            return [
                'title' => $mpEntryConf['ServiceTitle'],
                'description' => $mpEntryConf['ServiceDescription'],
                'url' => $mpEntryConf['ServiceUrl'],
                'thumb_url' => $mpEntryConf['ServiceThumbUrl'],
            ];
        }
    }
}

$app->run();