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
 * Class Menu
 * 自定义菜单栏接口封装类
 *
 * @package lib\plugins\weixin
 * @author  tianming <keyed.cn@gmail.com>
 */
class Menu
{
    use WeiXinTrait;
    const WEIXIN_API_HOST = 'https://api.weixin.qq.com/cgi-bin/';

    /**
     * 自定义菜单创建
     * apilink    http://mp.weixin.qq.com/wiki/13/43de8269be54a0a6f64413e4dfa94f39.html
     * errorCodeLink http://mp.weixin.qq.com/wiki/17/fa4e1434e57290788bde25603fa2fcbd.html
     *
     * @param string $accessToken
     * @param string $menuJsonData
     *
     * @return array|mixed
     */
    public static function create($accessToken, $menuJsonData)
    {
        $result = Request::request(self::WEIXIN_API_HOST . "menu/create?access_token={$accessToken}", $menuJsonData, true);

        return self::getWeChatApiResult($result);
    }

    /**
     * 查询自定义菜单
     * apilink   http://mp.weixin.qq.com/wiki/16/ff9b7b85220e1396ffa16794a9d95adc.html
     * errorCodeLink http://mp.weixin.qq.com/wiki/17/fa4e1434e57290788bde25603fa2fcbd.html
     *
     * @param string $accessToken
     *
     * @return array|mixed
     */
    public static function get($accessToken)
    {
        $result = Request::get(self::WEIXIN_API_HOST . "menu/get?access_token={$accessToken}");

        return self::getWeChatApiResult($result);
    }

    /**
     * 删除自定义菜单
     * apilink   http://mp.weixin.qq.com/wiki/16/8ed41ba931e4845844ad6d1eeb8060c8.html
     * errorCodeLink http://mp.weixin.qq.com/wiki/17/fa4e1434e57290788bde25603fa2fcbd.html
     *
     * @param string $accessToken
     *
     * @return array|mixed
     */
    public static function delete($accessToken)
    {
        $result = Request::get(self::WEIXIN_API_HOST . "menu/delete?access_token={$accessToken}");

        return self::getWeChatApiResult($result);
    }
}
