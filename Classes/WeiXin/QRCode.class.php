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

/**
 * Class QRCode
 * 微信二维码授权类.
 *
 * @author  tianming <keyed.cn@gmail.com>
 */
class QRCode
{
    use WeiXinTrait;
    const WEIXIN_API_HOST = 'https://api.weixin.qq.com/cgi-bin/';

    /**
     * 生成临时二维码场景id ,用于微信登录时与用户OPEN_ID绑定.
     *
     * @param  $length 场景ID位长
     *
     * @return 32位临时二维码场景ID
     */
    public static function makeTempCard($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; ++$i) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }

    /**
     * 生成二维码图片数据.
     *
     * @param string $accesstken
     * @param int $sceneId 场景ID值
     *
     * @return 图片数据流
     */
    public static function makeCardImg($accessToken, $sceneId, $url = true)
    {
        $postData = [
            'expire_seconds' => 2592000,
            'action_name' => 'QR_SCENE',
            'action_info' => [
                'scene' => [
                    'scene_id' => $sceneId,
                ],
            ],
        ];

        //var_dump($accessToken);die();
        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . 'qrcode/create?access_token='.$accessToken, json_encode($postData));
        $resultArray = json_decode($result, true);
        if (empty($resultArray['ticket'])) {
            return false;
        }
        if ($url == true) {
            return 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . $resultArray['ticket'];
        }
        $img = Request::request('https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$resultArray['ticket']);

        return $img;
    }

    /**
     * 生成字符串形式二维码图片数据
     *
     * @param      $accessToken
     * @param      $sceneStr
     * @param bool $url
     * @return bool|mixed|string
     */
    public static function makeCardImgStr($accessToken, $sceneStr, $url = true)
    {
        $postData = [
            'expire_seconds' => 2592000,
            'action_name' => 'QR_STR_SCENE',
            'action_info' => [
                'scene' => [
                    'scene_str' => $sceneStr,
                ],
            ],
        ];

        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . 'qrcode/create?access_token='.$accessToken, json_encode($postData));
        //debug_log($result, JF_LOG_INFO);
        $resultArray = json_decode($result, true);
        if (empty($resultArray['ticket'])) {
            return false;
        }
        if ($url == true) {
            return 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . $resultArray['ticket'];
        }
        $img = Request::request('https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$resultArray['ticket']);

        return $img;
    }

    /**
     * 获取用永久二维码链接
     *
     * @param $accessToken
     * @param $sceneStr
     * @return mixed
     */
    public static function makePermanentQrCodeUrl($accessToken, $sceneStr)
    {
        $postData = [
            'action_name' => 'QR_LIMIT_STR_SCENE',
            'action_info' => [
                'scene' => [
                    'scene_str' => $sceneStr,
                ],
            ],
        ];
        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . 'qrcode/create?access_token='.$accessToken, json_encode($postData));
        return $result['url'];
    }
}
