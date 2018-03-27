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

trait WeiXinTrait
{

    /***
     * 微信数据处理
     *
     * @param $data
     *
     * @return array|mixed
     * @author  tianming <keyed.cn@gmail.com>
     */
    protected static function getWeChatApiResult($data)
    {
        $data = (is_array($data)) ? $data : json_decode($data, true);

        if (isset($data['errcode']) && $data['errcode'] != 0) {
            throw new \RuntimeException('WeChat API errmsg: ' . $data['errmsg'] . ' errcode:' . $data['errcode'], $data['errcode']);
        }

        return $data;
    }
}
