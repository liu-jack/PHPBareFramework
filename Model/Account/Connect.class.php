<?php

/**
 * 第三方链接
 *
 * @author suning <snsnsky@gmail.com>
 *
 */

namespace Model\Account;

use Bare\DB;

class Connect
{
    const SITEID_WEIBO = 20;   // SINA 微博
    const SITEID_QQ = 22;      // QQ
    const SITEID_WEIXIN = 26;  // 微信

    /**
     * 站点配置
     *
     * @var array
     */
    private static $site = [
        self::SITEID_WEIXIN => [
            'cfg' => 'weixin',
            'url' => 'https://api.weixin.qq.com/sns/',
            'get_user_info' => [
                'url' => 'userinfo?access_token={ACCESS_TOKEN}&openid={OPENID}',
                'fields' => [
                    'nick' => 'nickname',
                    'headurl' => 'headimgurl',
                    'unionid' => 'unionid'
                ]
            ]
        ],
        self::SITEID_QQ => [
            'cfg' => 'qq',
            'url' => 'https://graph.qq.com/user/',
            'get_user_info' => [
                'url' => 'get_user_info?access_token={ACCESS_TOKEN}&oauth_consumer_key={APPID}&openid={OPENID}&format=json',
                'fields' => [
                    'nick' => 'nickname',
                    'headurl' => 'figureurl_qq_2',
                ],
            ],
        ],
        self::SITEID_WEIBO => [
            'cfg' => 'weibo',
            'url' => 'https://api.weibo.com/2/',
            'get_user_info' => [
                'url' => 'users/show.json?access_token={ACCESS_TOKEN}&source={APPID}&uid={OPENID}',
                'fields' => [
                    'nick' => 'name',
                    'headurl' => 'avatar_large',
                ]
            ],
        ]
    ];

    /**
     * 可更新的字段
     *
     * @var array
     */
    private static $update_field = [
        'AccessToken',
        'RefreshToken',
        'UnionId',
        'NickName',
        'ExpiredAt',
        'UpdatedAt',
        'OpenId'
    ];

    /**
     * 根据OPENID 获取用户信息
     *
     * @param string $openid OPENID
     * @param int    $siteid 站点ID
     * @param int    $db     数据读/写
     * @return array
     */
    public static function getUserByOpenId($openid, $siteid = self::SITEID_WEIXIN, $db = DB::DB_ACCOUNT_R)
    {
        $pdo = DB::pdo($db);
        $pdo->prepare('SELECT UserId,ExpiredAt,AccessToken,RefreshToken FROM Connect WHERE OpenId=:openid AND SiteId=:siteid limit 1');
        $pdo->bindValue(':openid', $openid);
        $pdo->bindValue(':siteid', $siteid);
        $pdo->execute();

        $ret = $pdo->fetch();

        if (empty($ret)) {
            return [];
        }

        return $ret;
    }

    /**
     * 根据UserId 获取用户信息
     *
     * @param int $userid 用户ID
     * @param int $db     数据读/写
     * @return array
     */
    public static function getConnectByUserId($userid, $db = DB::DB_ACCOUNT_R)
    {
        $pdo = DB::pdo($db);
        $pdo->prepare('SELECT SiteId,OpenId,NickName FROM Connect WHERE UserId=:userid');
        $pdo->bindValue(':userid', $userid);
        $pdo->execute();

        $ret = $pdo->fetchAll();
        $user = [];

        if (is_array($ret)) {
            foreach ($ret as $v) {
                $user[$v['SiteId']] = [
                    'NickName' => $v['NickName'],
                    'OpenId' => $v['OpenId']
                ];
            }
        }

        return $user;
    }

    /**
     * 添加一个用户
     *
     * @param int    $siteid 站点ID
     * @param int    $userid 用户ID
     * @param string $openid OPENID
     * @param array  $data   数据, [AccessToken, RefreshToken, ExpiredAt, (UnionId, NickName)]
     * @return bool|array
     */
    public static function addUser($siteid, $userid, $openid, $data)
    {
        $userid = (int)$userid;

        if (!isset(self::$site[$siteid])) {
            return ['status' => 201, '站点ID类型不正确'];
        }
        if (empty($userid) || empty($openid) || empty($data['AccessToken']) || empty($data['ExpiredAt'])) {
            return ['status' => 202, '必选参数不能为空'];
        }

        $data = [
            'UserId' => $userid,
            'SiteId' => $siteid,
            'AccessToken' => $data['AccessToken'],
            'RefreshToken' => isset($data['RefreshToken']) ? $data['RefreshToken'] : '',
            'OpenId' => $openid,
            'CreateTime' => date("Y-m-d H:i:s"),
            'UpdatedAt' => date("Y-m-d H:i:s"),
            'ExpiredAt' => date("Y-m-d H:i:s", time() + $data['ExpiredAt']),
            'UnionId' => isset($data['UnionId']) ? $data['UnionId'] : '',
            'NickName' => $data['NickName']
        ];
        $pdo = DB::pdo(DB::DB_ACCOUNT_W);
        $count = $pdo->insert('Connect', $data, ['ignore' => true]);

        if ($count > 0) {
            return true;
        }

        return false;
    }

    /**
     * 更新用户新
     *
     * @param int   $userid 用户ID
     * @param int   $siteid 站点ID
     * @param array $data   数据
     * @return bool|array
     */
    public static function updateUser($userid, $siteid, $data)
    {
        $userid = (int)$userid;

        if (empty($data)) {
            return false;
        }
        if (!isset(self::$site[$siteid])) {
            return ['status' => 201, '站点ID类型不正确'];
        }

        foreach ($data as $key => $val) {
            if (!in_array($key, self::$update_field)) {
                return ['status' => 202, "$key 不在更新字段授权中"];
            }
        }

        $pdo = DB::pdo(DB::DB_ACCOUNT_W);
        $count = $pdo->update('Connect', $data, ['UserId' => $userid, 'SiteId' => $siteid]);
        if ($count > 0) {
            return true;
        }

        return false;
    }

    /**
     * 通过Token 获取用户信息
     *
     * @param int    $siteid 站点ID
     * @param string $token  Token
     * @param string $openid OPENID
     * @return bool|array        失败false, 成功 [openid, nickname, headimgurl, unionid]
     */
    public static function getUserInfoByToken($siteid, $token, $openid)
    {
        $info = self::_getcfg($siteid);

        $key = ['{ACCESS_TOKEN}', '{OPENID}', '{APPID}'];
        $val = [$token, $openid, $info['appid']];
        $url = str_replace($key, $val, self::$site[$siteid]['get_user_info']['url']);

        $ret = self::_curl($siteid, $url);
        $fields = self::$site[$siteid]['get_user_info']['fields'];
        if (is_array($ret) && !empty($ret[$fields['nick']])) {

            $data = [
                'openid' => $openid,
                'nickname' => $ret[$fields['nick']],
                'headimgurl' => $ret[$fields['headurl']],
                'unionid' => isset($ret[$fields['unionid']]) ? $ret[$fields['unionid']] : ''
            ];

            return $data;
        }

        return false;
    }

    /**
     * 检查站点ID正确性
     *
     * @param int $siteid 站点ID
     * @return bool
     */
    public static function checkSiteId($siteid)
    {
        if (!isset(self::$site[$siteid])) {
            return false;
        }

        return true;
    }

    /**
     * 获取站点配置
     *
     * @param int $siteid 站点ID
     * @return array
     */
    public static function getCfgInfo($siteid = self::SITEID_WEIXIN)
    {
        self::_getcfg($siteid);

        return self::$site;
    }

    private static function _getcfg($siteid)
    {
        if (isset(self::$site[$siteid]['appid'])) {
            return self::$site[$siteid];
        }

        $cfg = config('passport/connect');
        foreach (self::$site as $k => & $v) {
            $v['appid'] = $cfg[$v['cfg']]['AppID'];
            $v['appsecret'] = $cfg[$v['cfg']]['AppSecret'];
            $v['name'] = $cfg[$v['cfg']]['Name'];
        }

        return self::$site[$siteid];
    }

    private static function _curl($siteid, $url, $body = [], $header = [], $method = "GET")
    {
        $url = self::$site[$siteid]['url'] . $url;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        switch ($method) {
            case "GET" :
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case "PUT" :
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
        }

        curl_setopt($ch, CURLOPT_USERAGENT, 'ZheYue JK/1.0');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        $ret = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        if ($errno || $info['http_code'] != 200) {
            logs([
                "status" => 'http_fail',
                "errno" => $errno,
                "error" => $error,
                "url" => $url,
                "body" => serialize($body),
                'ret' => $ret,
            ], "Passport/Connect");
        }

        return json_decode($ret, true);
    }
}