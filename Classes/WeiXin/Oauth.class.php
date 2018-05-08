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

use Common\Bridge;
use lib\util\Request;

/**
 * Class Oauth
 * oauth 授权封装类.
 *
 * @author  tianming <keyed.cn@gmail.com>
 */
class Oauth
{
    use WeiXinTrait;
    const WEIXIN_API_HOST = 'https://api.weixin.qq.com/cgi-bin/';
    const WEIXIN_ACCESS_TOKEN_CACHE = 'weixin:token:app:id:%s';

    /**
     * 依赖APPID与SECRET获取当前公众号ACCESS TOKEN.
     *
     * @param string $appId
     * @param string $secret
     *
     * @return array
     */

    private static function getAccessToken($appId, $secret)
    {
        $result = Request::get(self::WEIXIN_API_HOST . "token?grant_type=client_credential&appid=$appId&secret=$secret");
        debug_log("appId: {$appId}");
        debug_log(json_encode($result));

        return self::getWeChatApiResult($result);
    }

    /**
     * 通过缓存获取 access token
     * @param $appId
     * @param $secret
     * @return mixed|string
     */
    public static function getAccessTokenWithCache($appId, $secret)
    {
        $tokenCacheKey = sprintf(self::WEIXIN_ACCESS_TOKEN_CACHE, $appId);
        debug_log($tokenCacheKey);
        $mc = Bridge::memcache(Bridge::MEMCACHE_WEIXIN);
        $token         = $mc->get($tokenCacheKey);
        if (!$token) {
            $ret   = self::getAccessToken($appId, $secret);
            $token = $ret['access_token'];
            if ($token) {
                $mc->set($tokenCacheKey, $token, 1200);
            }
        }
        debug_log($token);
        return $token;
    }

    /**
     * 删除缓存
     * @param $appId
     * @return bool
     */
    public static function removeAccessTokenCache($appId)
    {
        $tokenCacheKey = sprintf(self::WEIXIN_ACCESS_TOKEN_CACHE, $appId);
        return Bridge::memcache(Bridge::MEMCACHE_WEIXIN)->delete($tokenCacheKey);
    }

    /**
     * 获取第三方平台component_access_token.
     *
     * @param component_appid         =第三方平台appid
     * @param component_appsecret     =第三方平台appsecret
     * @param component_verify_ticket =微信后台推送的ticket，此ticket会定时推送   `````````````````````````````````````````
     *
     * @return array
     */
    public static function getComAccessToken($component_appid, $component_appsecret, $component_verify_ticket)
    {
        $postData = [
            'component_appid'         => $component_appid,
            'component_appsecret'     => $component_appsecret,
            'component_verify_ticket' => $component_verify_ticket,
        ];
        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . 'component/api_component_token', json_encode($postData));

        return self::getWeChatApiResult($result);
    }

    /**
     * 该API用于获取预授权码。预授权码用于公众号授权时的第三方平台方安全验证。
     *
     * @param component_appid        = 第三方平台APPID
     * @param component_access_token = 第三方平台令牌
     *
     * @return array
     * @author
     */
    public static function getPreauthCode($component_appid, $component_access_token)
    {
        $postData = [
            'component_appid' => $component_appid,
        ];
        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . 'component/api_create_preauthcode?component_access_token=' . $component_access_token, json_encode($postData));

        return self::getWeChatApiResult($result);
    }

    /**
     * 该API用于使用授权码换取授权公众号的授权信息，并换取authorizer_access_token和authorizer_refresh_token。
     *
     * @param component_appid        = 第三方平台APPID
     * @param component_access_token = 第三方平台令牌
     * @param authorization_code     = 预授权码
     *
     * @return array
     */
    public static function getQueryAuth($component_appid, $component_access_token, $authorization_code)
    {
        $postData = [
            'component_appid'    => $component_appid,
            'authorization_code' => $authorization_code,
        ];
        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . 'component/api_query_auth?component_access_token=' . $component_access_token, json_encode($postData));

        return self::getWeChatApiResult($result);
    }

    /**
     * 该API用于在授权方令牌（authorizer_access_token）失效时，可用刷新令牌（authorizer_refresh_token）获取新的令牌。
     *
     * @param component_appid          = 第三方平台APPID
     * @param component_access_token   = 第三方平台令牌
     * @param authorizer_appid         = 授权方公众号的appid
     * @param authorizer_refresh_token =刷新令牌，当公众号授权第三方时会提供
     *
     * @return array
     */
    public static function getAuthOrizerToken($component_appid, $component_access_token, $authorizer_appid, $authorizer_refresh_token)
    {
        $postData = [
            'component_appid'          => $component_appid,
            'authorizer_appid'         => $authorizer_appid,
            'authorizer_refresh_token' => $authorizer_refresh_token,
        ];
        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . 'component/api_authorizer_token?component_access_token=' . $component_access_token, json_encode($postData));

        return self::getWeChatApiResult($result);
    }

    /**
     * 该API用于获取授权方的公众号基本信息，包括头像、昵称、帐号类型、认证类型、微信号、原始ID和二维码图片URL。
     *
     * @param component_appid        = 第三方平台APPID
     * @param component_access_token = 第三方平台令牌
     * @param authorizer_appid       = 授权方公众号的appid
     *
     * @return array
     */
    public static function getAuthOrizerInfo($component_appid, $component_access_token, $authorizer_appid)
    {
        $postData = [
            'component_appid'  => $component_appid,
            'authorizer_appid' => $authorizer_appid,
        ];
        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . 'component/api_get_authorizer_info?component_access_token=' . $component_access_token, json_encode($postData));

        return self::getWeChatApiResult($result);
    }

    /**
     * 该API用于获取授权方的公众号的选项设置信息，如：地理位置上报，语音识别开关，多客服开关。注意，获取各项选项设置信息，需要有授权方的授权.
     *
     * @param component_appid        = 第三方平台APPID
     * @param component_access_token = 第三方平台令牌
     * @param authorizer_appid       = 授权方公众号的appid
     * @param option_name            = 选项名称
     *
     * @return array
     */
    public static function getOptionNameInfo($component_appid, $component_access_token, $authorizer_appid, $option_name)
    {
        $postData = [
            'component_appid'  => $component_appid,
            'authorizer_appid' => $authorizer_appid,
            'option_name'      => $option_name,
        ];
        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . 'component/api_get_authorizer_info?component_access_token=' . $component_access_token, json_encode($postData));

        return self::getWeChatApiResult($result);
    }

    /**
     * 该API用于设置授权方的公众号的选项信息，如：地理位置上报，语音识别开关，多客服开关。注意，设置各项选项设置信息，需要有授权方的授权.
     *
     * @param component_appid        = 第三方平台APPID
     * @param component_access_token = 第三方平台令牌
     * @param authorizer_appid       = 授权方公众号的appid
     * @param option_name            = 选项名称
     * @param $option_value          = 选项值
     *
     * @return array
     */
    public static function setOptionNameInfo($component_appid, $component_access_token, $authorizer_appid, $option_name, $option_value)
    {
        $postData = [
            'component_appid'  => $component_appid,
            'authorizer_appid' => $authorizer_appid,
            'option_name'      => $option_name,
            'option_value'     => $option_value,
        ];
        $result = Request::httpWeixinPost(self::WEIXIN_API_HOST . 'component/ api_set_authorizer_option?component_access_token=' . $component_access_token, json_encode($postData));

        return self::getWeChatApiResult($result);
    }

    /**
     * 获得用户信息，没关注则不能获得.
     *
     * @param $accessToken
     * @param $openId
     *
     * @return array|mixed
     */
    public static function getUserInfo($accessToken, $openId)
    {
        $userInfo = Request::request(self::WEIXIN_API_HOST . 'user/info?access_token=' . $accessToken . '&openid=' . $openId . '&lang=zh_CN');

        return self::getWeChatApiResult($userInfo);
    }

    /**
     * 获取授权url.
     *
     * @param        $app_id
     * @param        $redirect_url
     * @param        $response_type
     * @param string $scope
     * @param string $state
     *
     * @return string
     */
    public static function getAuthUrl($app_id, $redirect_url, $response_type, $scope = 'snsapi_base', $state = '')
    {
        return sprintf('%s/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=%s&scope=%s&state=%s#wechat_redirect', 'https://open.weixin.qq.com', $app_id, urlencode($redirect_url), $response_type, $scope, $state);
    }

    /**
     * 通过code获取accessToken, 获取openid.
     *
     * @param        $code
     * @param        $appid
     * @param        $secret
     * @param string $grant_type
     *
     * @return array|mixed
     */
    public static function getAccessTokenByCode($code, $appid, $secret, $grant_type = 'authorization_code')
    {
        $req_url = sprintf('%s/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=%s', 'https://api.weixin.qq.com/sns', $appid, $secret, $code, $grant_type);
        $res = Request::request($req_url);

        return self::getWeChatApiResult($res);
    }

    /**
     * https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN.
     * @param        $accessToken 网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
     * @param        $openId      用户的唯一标识
     * @param string $lang        返回国家地区语言版本，zh_CN 简体，zh_TW 繁体，en 英语
     *
     * @return array|mixed
     */
    public static function getSnsUserInfo($accessToken, $openId, $lang = 'zh_CN')
    {
        $req_url = sprintf('%s/userinfo?access_token=%s&openid=%s&lang=%s', 'https://api.weixin.qq.com/sns', $accessToken, $openId, $lang);
        $res = Request::request($req_url);

        return self::getWeChatApiResult($res);
    }

    /**
     * https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code.
     * @param        $appId     应用唯一标识，在微信开放平台提交应用审核通过后获得
     * @param        $secret    应用密钥AppSecret，在微信开放平台提交应用审核通过后获得
     * @param        $code      填写第一步获取的code参数
     * @param String $grantType 填authorization_code
     *
     * @return array|mixed
     */
    public static function getSnsAccessToken($appId, $secret, $code, $grantType = 'authorization_code')
    {
        $req_url = sprintf('%s/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=%s', 'https://api.weixin.qq.com/sns', $appId, $secret, $code, $grantType);
        $res = Request::request($req_url);

        return self::getWeChatApiResult($res);
    }
}
