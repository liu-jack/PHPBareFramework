<?php

/**
 * 推送
 *
 * @author 周剑锋 <camfee@foxmail.com>
 *
 */

namespace Model\Mobile;

use Bare\Queue;

class AppPush
{
    const QUEUE_NAME = 'AppPush';

    const PUSH_TYPE_MSG = 1;             // 启动APP
    const PUSH_TYPE_URL = 2;             // 协议URL


    //传入参数字段
    const VAR_CONT = 'cont'; // url
    const VAR_FEEDID = 'feedId';
    const VAR_USERID = 'memberId';

    /**
     * 允许的推送类型
     *
     * @see http://git.20hn.cn/scale/server-meite/wikis/api-doc-scheme
     * @var array
     */
    private static $type = [
        self::PUSH_TYPE_MSG => 'empty',
        self::PUSH_TYPE_URL => 'string',
    ];

    /**
     * 类型url
     *
     * @see http://git.20hn.cn/scale/server-meite/wikis/api-doc-scheme
     * @var array
     */
    private static $typeurl = [
        self::PUSH_TYPE_MSG => '',
        self::PUSH_TYPE_URL => '',
    ];

    // 标签前缀
    const TAG_TEST_PREFIX = 'STAGE_TEST'; // 标签前缀
    const TAG_LOGIN_OUT = 'STAGE0'; // 未登录用户标签
    const TAG_LOGIN = 'STAGE1'; // 已登录用户标签

    /**
     * 按用户ID推送一条消息
     *
     * @param int    $uid  用户ID
     * @param int    $type 消息类型  详见 PUSH_TYPE_*
     * @param string $msg  推送显示文本
     * @param array  $data 推送数据
     * @return bool
     */
    public static function pushByUserId($uid, $type, $msg, $data = [])
    {
        $token = Device::getTokenByUserId($uid);

        if (!empty($token['ios'])) {
            self::iOSPushOne($token['ios'], $type, $msg, $data);
        }
        if (!empty($token['android'])) {
            self::androidPushOne($token['android'], $type, $msg, $data);
        }

        return true;
    }

    /**
     * iOS 单条消息推送
     *
     * @param string $token 设备ID
     * @param int    $type  消息类型   详见 PUSH_TYPE_*
     * @param string $msg   推送显示文本
     * @param array  $data  推送数据
     *
     * @return bool
     */
    public static function iOSPushOne($token, $type, $msg, $data = [])
    {
        $scheme = self::getTypeUrl($type, $data);

        return Queue::add(self::getQueueName(), [
            'App' => ['ios'],
            'Cid' => $token,
            'Type' => $type,
            'Msg' => $msg,
            'Data' => $scheme,
        ]);
    }

    /**
     * Android 单条消息推送
     *
     * @param string $token 设备ID
     * @param int    $type  消息类型    详见 PUSH_TYPE_*
     * @param string $msg   推送显示文本
     * @param array  $data  推送数据
     * @return bool
     */
    public static function androidPushOne($token, $type, $msg, $data = [])
    {
        $scheme = self::getTypeUrl($type, $data);

        return Queue::add(self::getQueueName(), [
            'App' => ['android'],
            'Cid' => $token,
            'Type' => $type,
            'Msg' => $msg,
            'Data' => $scheme,
        ]);
    }

    /**
     * 向所有人推送消息
     *
     * @param int    $type     消息类型    详见 PUSH_TYPE_*
     * @param string $msg      推送显示文本
     * @param array  $data     推送数据
     * @param array  $platform 推送平台, 可选, 默认全部 ['android', 'ios']
     * @return bool
     */
    public static function pushAll($type, $msg, $data = [], $platform = ['android', 'ios'])
    {
        $scheme = self::getTypeUrl($type, $data);
        Queue::add(self::getQueueName(), [
            'App' => $platform,
            'Cid' => 'ALL',
            'Type' => $type,
            'Msg' => $msg,
            'Data' => $scheme,
        ]);

        return true;
    }

    /**
     * 按用户类型推送
     *
     * @param string $tag      用户类型标签，多个用逗号（,）分隔
     * @param int    $type     消息类型    详见 PUSH_TYPE_*
     * @param string $msg      推送显示文本
     * @param array  $data     推送数据
     * @param array  $platform 推送平台, 可选, 默认全部 ['android', 'ios']
     * @return bool
     */
    public static function pushTag($tag, $type, $msg, $data = [], $platform = ['android', 'ios'])
    {
        if (empty($tag)) {
            return false;
        }
        $scheme = self::getTypeUrl($type, $data);

        return Queue::add(self::getQueueName(), [
            'App' => $platform,
            'Cid' => $tag,
            'Type' => $type,
            'Msg' => $msg,
            'Data' => $scheme,
        ]);
    }

    /**
     * 检查推送类型
     *
     * @param int $type 类型   详见 PUSH_TYPE_*
     * @return bool|string
     */
    public static function checkType($type)
    {
        if (isset(self::$type[$type])) {
            return self::$type[$type];
        }

        return false;
    }

    /**
     * 获得推送类型url
     *
     * @param int   $type  推送类型  详见 PUSH_TYPE_*
     * @param array $extra 详见 VAR_*
     * @return bool|string
     */
    public static function getTypeUrl($type, $extra = [])
    {
        $typeurl = self::$typeurl;
        if (isset($typeurl[$type])) {
            $scheme = $typeurl[$type];
            switch ($type) {
                case self::PUSH_TYPE_URL:
                    $scheme = SchemeConstant::getH5Scheme($extra[self::VAR_CONT]);
                    break;
            }

            return $scheme;
        }

        return false;
    }

    /**
     * 根据不同站点返回不同的队列名称
     *
     * @return string
     */
    private static function getQueueName()
    {
        return self::QUEUE_NAME;
    }
}