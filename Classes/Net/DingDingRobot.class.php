<?php
/**
 * DingDingRobot.class.php 钉钉自定义机器人消息推送
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-10-22 上午9:20
 *
 */

namespace Classes\Net;

class DingDingRobot
{
    // 秤异常报警机器人
    const DEFAULT_URL = 'https://oapi.dingtalk.com/robot/send?access_token=25476c63bd78cf6b8284e76acc4bccc99cd800b87ee5647dcb2809a783e354e0';
    // 测试机器人
    const TEST_URL = 'https://oapi.dingtalk.com/robot/send?access_token=e6ffe5883ae951cd05f046c2d3cafa64864cd84abe0d5975adcac282de02f6d2';

    /**
     * 推送地址
     *
     * @var string
     */
    private static $send_url = self::DEFAULT_URL;

    /**
     * 获取实例
     *
     * @return \Classes\Net\DingDingRobot
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * 设置推送地址
     *
     * @param $url
     * @return \Classes\Net\DingDingRobot
     */
    public static function setSendUrl($url)
    {
        self::$send_url = $url;

        return self::instance();
    }

    /**
     * 推送文本消息
     *
     * @param       $content
     * @param array $at_mobiles
     * @param bool  $is_at_all
     * @return array|string
     */
    public static function sendText($content, $at_mobiles = [], $is_at_all = false)
    {
        $msg_data = [
            'msgtype' => 'text',
            'text' => ['content' => $content],
            'at' => ['atMobiles' => $at_mobiles, 'isAtAll' => $is_at_all]
        ];

        return self::send($msg_data);
    }

    /**
     * 推送链接
     *
     * @param        $title
     * @param        $content
     * @param        $url
     * @param string $pic_url
     * @return array|string
     */
    public static function sendLink($title, $content, $url, $pic_url = '')
    {
        $msg_data = [
            'msgtype' => 'link',
            'link' => [
                'title' => $title,
                'text' => $content,
                'messageUrl' => $url,
                'picUrl' => $pic_url,
            ]
        ];

        return self::send($msg_data);
    }

    /**
     * 推送markdown
     *
     * @param       $title
     * @param       $content
     * @param array $at_mobiles
     * @param bool  $is_at_all
     * @return array|string
     */
    public static function sendMarkdown($title, $content, $at_mobiles = [], $is_at_all = false)
    {
        $msg_data = [
            'msgtype' => 'markdown',
            'markdown' => [
                'title' => $title,
                'text' => $content,
            ],
            'at' => ['atMobiles' => $at_mobiles, 'isAtAll' => $is_at_all]
        ];

        return self::send($msg_data);
    }

    /**
     * 推送ActionCard
     *
     * @param              $title
     * @param              $content
     * @param string|array $singleTitle array [['title'=>'','actionURL'=>''],...]
     * @param string       $singleURL
     * @param int          $hideAvatar
     * @param int          $btnOrientation
     * @return array|string
     */
    public static function sendActionCard(
        $title,
        $content,
        $singleTitle = '',
        $singleURL = '',
        $hideAvatar = 0,
        $btnOrientation = 0
    ) {
        if (is_string($singleTitle)) {
            $msg_data = [
                'msgtype' => 'actionCard',
                'actionCard' => [
                    "title" => $title,
                    "text" => $content,
                    "singleTitle" => $singleTitle,
                    "singleURL" => $singleURL,
                    "hideAvatar" => $hideAvatar,
                    "btnOrientation" => $btnOrientation,
                ]
            ];
        } else {
            $msg_data = [
                'msgtype' => 'actionCard',
                'actionCard' => [
                    "title" => $title,
                    "text" => $content,
                    "btns" => $singleTitle,
                    "hideAvatar" => $hideAvatar,
                    "btnOrientation" => $btnOrientation,
                ]
            ];
        }

        return self::send($msg_data);
    }

    /**
     * 推送FeedCard
     *
     * @param array $links [['title'=>'','messageURL' =>'','picURL'=>''],...]
     * @return array|string
     */
    public static function sendFeedCard($links)
    {
        $msg_data = [
            'msgtype' => 'feedCard',
            'feedCard' => [
                'links' => $links
            ]
        ];

        return self::send($msg_data);
    }

    /**
     * 发送请求
     *
     * @param array $data    需要post的数组
     * @param int   $timeout 超时时间
     * @return string|array   结果数组
     */
    public static function send($data = [], $timeout = 10)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_URL, self::$send_url);
        // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
        //curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, true); // enable posting
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data)); // post files
        }
        $header = [
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:53.0) Gecko/20100101 Firefox/53.0',
            'Content-Type: application/json;charset=utf-8',
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }
}