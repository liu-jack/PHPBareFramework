<?php

/**
 * 添加设备推送标签
 *
 * @author 周剑锋 <camfee@foxmail.com>
 *
 * $Id$
 */

namespace Model\Mobile;

use Bare\Queue;

class AddPushTag
{
    // 队列名称
    const QUEUE_NAME = 'AddPushTag';
    // 标签前缀
    const TAG_TEST_PREFIX = 'STAGE_TEST'; // 标签前缀
    const TAG_LOGIN_OUT = 'STAGE0'; // 未登录用户标签
    const TAG_LOGIN = 'STAGE1'; // 已登录用户标签

    /**
     * 允许标签数组
     *
     * @see http://git.20hn.cn/personalized/server/wikis/push-group
     * @var array
     */
    private static $tags = [
        self::TAG_LOGIN_OUT => self::TAG_LOGIN_OUT, // 未登录用户标签
        self::TAG_LOGIN => self::TAG_LOGIN, // 已登录用户标签
        self::TAG_TEST_PREFIX => self::TAG_TEST_PREFIX, // 标签前缀
    ];

    /**
     * 为设备添加推送标签
     *
     * @param string       $token 设备注册ID
     * @param string|array $tag   标签
     * @return bool
     */
    public static function addTag($token, $tag = '')
    {
        return self::updateTag($token, $tag, 'add');
    }

    /**
     * 为设备删除推送标签
     *
     * @param string       $token 设备注册ID
     * @param string|array $tag   标签
     * @return bool
     */
    public static function removeTag($token, $tag = '')
    {
        return self::updateTag($token, $tag, 'remove');
    }

    /**
     * 删除应用推送标签
     *
     * @param string|array $tag 标签
     * @return bool
     */
    public static function removeAppTag($tag = '')
    {
        if (empty($tag)) {
            return false;
        }
        if (strpos($tag, self::TAG_TEST_PREFIX) !== 0) {
            return false;
        }

        return Queue::add(self::getQueueName(), [
            'Tag' => $tag,
            'Handle' => 'removeAppTag'
        ]);
    }

    /**
     * 为设备更新推送标签
     *
     * @param string       $token  设备注册ID
     * @param string|array $tag    标签
     * @param string       $handle 操作类型 add|remove
     * @return bool
     */
    public static function updateTag($token, $tag = '', $handle = 'add')
    {
        if (empty($token)) {
            return false;
        }
        if (!is_array($tag)) {
            $tag = [$tag];
        }
        $tags = self::$tags;
        foreach ($tag as $k => $v) {
            if (!isset($tags[$v]) && strpos($v, self::TAG_TEST_PREFIX) === false) {
                unset($tag[$k]);
            }
        }
        if (empty($tag)) {
            return false;
        }

        return Queue::add(self::getQueueName(), [
            'Cid' => $token,
            'Tag' => $tag,
            'Handle' => $handle
        ]);
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