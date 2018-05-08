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
 * 数据统计类
 * Class DataCube
 *
 * @package lib\plugins\weixin
 * @author  tianming <keyed.cn@gmail.com>
 */
class DataCube
{
    use WeiXinTrait;

    /**
     * 获取图文群发每日 数据,限一天内的，故begin_date = end_date 如：2016-05-08.
     *
     * @param string $accessToken
     * @param string $beginDate
     * @param string $endDate
     *
     * @return array
     */
    public function getArticleSummary($accessToken, $beginDate, $endDate)
    {
        $postData = [
            'begin_date' => $beginDate,
            'end_date'   => $endDate,
        ];
        $result   = Request::request("https://api.weixin.qq.com/datacube/getarticlesummary?access_token={$accessToken}", json_encode($postData), true);

        return self::getWeChatApiResult($result);
    }

    /**
     * 获取获取图文群发总数据,限一天内的，故begin_date = end_date 如：2016-05-08.
     *
     * @param string $accessToken
     * @param string $beginDate
     * @param string $endDate
     *
     * @return array
     */
    public function getArticleTotal($accessToken, $beginDate, $endDate)
    {
        $postData = [
            'begin_date' => $beginDate,
            'end_date'   => $endDate,
        ];
        $result   = Request::httpWeixinPost("https://api.weixin.qq.com/datacube/getarticletotal?access_token={$accessToken}", json_encode($postData));

        return self::getWeChatApiResult($result);
    }

    /**
     * 获取图文统计数据,限 3 天内的.
     *
     * @param string $accessToken
     * @param string $beginDate
     * @param string $endDate
     *
     * @return array
     */
    public function getUserRead($accessToken, $beginDate, $endDate)
    {
        $postData = [
            'begin_date' => $beginDate,
            'end_date'   => $endDate,
        ];
        $result   = Request::request("https://api.weixin.qq.com/datacube/getuserread?access_token{$accessToken}", json_encode($postData));

        return self::getWeChatApiResult($result);
    }

    /**
     * 获取图文统计分时数据,限 1 天内的，故begin_date = end_date 如：2016-05-08.
     *
     * @param string $accessToken
     * @param string $beginDate
     * @param string $endDate
     *
     * @return array
     */
    public function getUserReadHour($accessToken, $beginDate, $endDate)
    {
        $postData = [
            'begin_date' => $beginDate,
            'end_date'   => $endDate,
        ];
        $result   = Request::request("https://api.weixin.qq.com/datacube/getuserreadhour?access_token={$accessToken}", json_encode($postData));

        return self::getWeChatApiResult($result);
    }

    /**
     * 获取图文分享转发数据,限 7 天内的.
     *
     * @param string $accessToken
     * @param string $beginDate
     * @param string $endDate
     *
     * @return array
     */
    public function getUserShare($accessToken, $beginDate, $endDate)
    {
        $postData = [
            'begin_date' => $beginDate,
            'end_date'   => $endDate,
        ];
        $result   = Request::request("https://api.weixin.qq.com/datacube/getusershare?access_token={$accessToken}", json_encode($postData), true);

        return self::getWeChatApiResult($result);
    }

    /**
     * 获取图文分享转发分时数据,限 1 天内的，故begin_date = end_date 如：2016-05-08.
     *
     * @param string $accessToken
     * @param string $beginDate
     * @param string $endDate
     *
     * @return array
     */
    public function getUserShareHour($accessToken, $beginDate, $endDate)
    {
        $postData = [
            'begin_date' => $beginDate,
            'end_date'   => $endDate,
        ];
        $result   = Request::request("https://api.weixin.qq.com/datacube/getusersharehour?access_token={$accessToken}", json_encode($postData));

        return self::getWeChatApiResult($result);
    }
}
