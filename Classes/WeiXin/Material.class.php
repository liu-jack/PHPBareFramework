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
 * Class Media
 * 微信素材管理接口
 *
 * @package lib\plugins\weixin
 * @author  tianming <keyed.cn@gmail.com>
 */
class Material
{
    use WeiXinTrait;
    const WEIXIN_API_HOST = 'https://api.weixin.qq.com/cgi-bin/';

    /**
     * 上传临时素材,注意5.5前和5.5后版本的不同处理.
     *
     * @param string $accessToken 调用接口凭证
     * @param string $type        分别有图片（image）、语音（voice）、视频（video）和缩略图（thumb）
     * @param string $mediaUrl    要传递相对于服务器的绝对地址
     *
     * @link http://mp.weixin.qq.com/wiki/5/963fc70b80dc75483a271298a76a8d59.html
     * @return array
     */
    public static function uploadTempMaterial($accessToken, $type, $mediaUrl)
    {
        if (version_compare(phpversion(), '5.5.0') >= 0) {
            $postData = ['media' => new \CURLFile($mediaUrl)];
        } else {
            $postData = ['media' => '@' . $mediaUrl];
        }
        $result = Request::request(self::WEIXIN_API_HOST . "media/upload?access_token={$accessToken}&type={$type}", $postData, true);

        return self::getWeChatApiResult($result);
    }

    /**
     * 获取临时素材，依赖media_id,注意视频时返回的是视频地址，其它类型返回为数据流
     *
     * @param string $accessToken 调用接口凭证
     * @param string $mediaId     媒体文件ID
     *
     * @link http://mp.weixin.qq.com/wiki/11/07b6b76a6b6e8848e855a435d5e34a5f.html
     * @return mixed
     */
    public static function getTempMaterial($accessToken, $mediaId)
    {
        $result = Request::request(self::WEIXIN_API_HOST . "media/get?access_token={$accessToken}&media_id={$mediaId}");

        return self::getWeChatApiResult($result);
    }

    /**
     * 新增永久素材.
     *
     * @param string $accessToken  调用接口凭证
     * @param string $type         媒体文件类型，分别有,图片（image）、语音（voice）、视频（video）和缩略图（thumb）,视频时还要带下TITLE和描述
     * @param string $localPath    要传递相对于服务器的绝对地址
     * @param string $title        标题
     * @param string $introduction 图文消息的摘要，仅有单图文消息才有摘要，多图文此处为空
     *
     * @link http://mp.weixin.qq.com/wiki/14/7e6c03263063f4813141c3e17dd4350a.html
     *       {
     *       "media_id":MEDIA_ID,
     *       "url":URL
     *       }
     * @return array|mixed
     */
    public static function uploadPermanentMaterial($accessToken, $type, $localPath, $title = '', $introduction = '')
    {
        if (version_compare(phpversion(), '5.5.0') >= 0) {
            $postData = ['media' => new \CURLFile($localPath)];
        } else {
            $postData = ['media' => '@' . $localPath];
        }
        $data = ['status' => false];

        if (trim($type) === 'video' || trim($type) === 'music') {
            if (empty($title) || empty($introduction)) {
                return $data;
            }
            $postData['description'] = ['title' => $title, 'introduction' => $introduction];
        }

        $postData = array_merge($postData, ['type' => $type]);

        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . "material/add_material?access_token={$accessToken}", $postData);

        return self::getWeChatApiResult($result);
    }

    /**
     * 获得永久素材.
     *
     * @param string $accessToken 调用接口凭证
     * @param string $mediaId
     *
     * @return bool|mixed
     */
    public static function getPermanentMedia($accessToken, $mediaId)
    {
        $postData = ['media_id' => $mediaId];
        $postData=json_encode($postData);
        $result   = Request::request(self::WEIXIN_API_HOST . "material/get_material?access_token={$accessToken}", $postData, true);

        return self::getWeChatApiResult($result);
    }

    /**
     * @param $accessToken
     * @param $mediaId
     * @return mixed     获取图片文件
     */
    public static function getPermanentMediaByImage($accessToken, $mediaId){
        $postData = json_encode(['media_id' => $mediaId]);
        $result = Request::request(self::WEIXIN_API_HOST . "material/get_material?access_token={$accessToken}", $postData, true);

        return $result;
    }

    /**
     * 删除永久素材.
     *
     * @param string $accessToken 调用接口凭证
     * @param string $mediaId
     *
     * @return array
     */
    public static function deletePermanentMaterial($accessToken, $mediaId)
    {
        $postData = ['media_id' => $mediaId];
        $result   = Request::request(self::WEIXIN_API_HOST . "material/del_material?access_token={$accessToken}", $postData, true);

        return self::getWeChatApiResult($result);
    }


    /**
     * 获取素材总数.
     *
     * @param string $accessToken 调用接口凭证
     *
     * @return mixed
     */
    public static function getMediaAccount($accessToken)
    {
        $result = Request::request(self::WEIXIN_API_HOST . "material/get_materialcount?access_token={$accessToken}");

        return self::getWeChatApiResult($result);
    }

    /**
     * 获取素材列表.
     *
     * @param string $accessToken 调用接口凭证
     * @param string $type        =素材的类型，图片（image）、视频（video）、语音 （voice）、图文（news）
     * @param int    $offset
     * @param int    $count
     *
     * @return bool|mixed
     */
    public static function getMediaList($accessToken, $type, $offset, $count)
    {
        $postData = [
            'type'   => $type,
            'offset' => $offset,
            'count'  => $count,
        ];
        $result   = Request::request(self::WEIXIN_API_HOST . "material/batchget_material?access_token={$accessToken}", json_encode($postData), true);

        return self::getWeChatApiResult($result);
    }

    /**
     * 新增图文消息.
     *
     * @param string $accessToken 调用接口凭证
     * @param string $articles
     *
     * @link http://mp.weixin.qq.com/wiki/14/7e6c03263063f4813141c3e17dd4350a.html
     * @return mixed
     */
    public static function updateNews($accessToken, $articles)
    {
        $result = Request::httpWeixinJson(self::WEIXIN_API_HOST . "material/add_news?access_token={$accessToken}", $articles);

        return self::getWeChatApiResult($result);
    }

    /**
     * 图片转化，但是仅仅是转化成腾迅内部的URL，因为外部图片URL不能在图文中正常显示.
     *
     * @param string $accessToken 调用接口凭证
     * @param string $imgUrl
     *
     * @return array
     * @author
     */
    public static function changeImage($accessToken, $imgUrl)
    {
        if (version_compare(phpversion(), '5.5.0') >= 0) {
            $postData = ['media' => new \CURLFile($imgUrl)];
        } else {
            $postData = ['media' => '@' . $imgUrl];
        }
        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . "media/uploadimg?access_token={$accessToken}", $postData);

        return self::getWeChatApiResult($result);
    }

    /**
     * 修改图文,.
     *
     * @param string $accessToken 调用接口凭证
     * @param string $mediaId
     * @param string $index       图文组第几个图文，$articles=图文内容
     * @param string $articles
     *
     * @return bool
     */
    public static function modifyMaterial($accessToken, $mediaId, $index, $articles)
    {
        $postData = [
            'media_id' => $mediaId,
            'index'    => $index,
            'articles' => $articles,
        ];
        $result   = Request::request(self::WEIXIN_API_HOST . "material/del_material?access_token={$accessToken}", $postData, true);

        return self::getWeChatApiResult($result);
    }
}
