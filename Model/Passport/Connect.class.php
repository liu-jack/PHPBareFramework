<?php

/**
 * 第三方连接
 *
 * @author suning <snsnsky@gmail.com>
 *
 * $Id$
 */

namespace Model\Passport;

use Bare\Model;
use Bare\DB;

class Connect extends Model implements ISiteType
{
    const MC_KEY_OPENID = 'PACO_';
    const MC_KEY_UNIONID = 'PACUN_';
    const MC_KEY_USER = 'PACI_';
    const MC_KEY_USER_LIST = 'PACL_';

    protected static $_conf = [
        'db' => [
            'w' => DB::DB_PASSPORT_W,
            'r' => DB::DB_PASSPORT_R
        ],
        'table' => 'Connect',
        'fields' => [
            'ConnectId' => self::VAR_TYPE_KEY,
            'UserId' => self::VAR_TYPE_INT,
            'PlatformId' => self::VAR_TYPE_INT,
            'SiteId' => self::VAR_TYPE_INT,
            'OpenId' => self::VAR_TYPE_STRING,
            'UnionId' => self::VAR_TYPE_STRING,
            'CreateTime' => self::VAR_TYPE_STRING,
        ],
        'mc' => '',
        'mckey' => '',
        'mctime' => 0
    ];

    /**
     * 添加第三方注册用户
     *
     * @param  int    $siteid      站点ID, 见self::SITE_LISTS
     * @param  int    $uid         用户ID
     * @param  int    $platform_id 平台ID, 见self::PLATFORM_LISTS
     * @param  string $openid      OpenId
     * @param string  $unionid     可选, UnionId
     * @return bool
     */
    public static function addUser($siteid, $uid, $platform_id, $openid, $unionid = '')
    {
        $sites = self::SITE_LISTS;
        if (!isset($sites[$siteid])) {
            return false;
        }

        $platforms = self::PLATFORM_LISTS;
        if (!isset($platforms[$platform_id])) {
            return false;
        }

        $data = [
            'UserId' => $uid,
            'PlatformId' => $platform_id,
            'SiteId' => $siteid,
            'OpenId' => $openid,
            'UnionId' => $unionid,
            'CreateTime' => date("Y-m-d H:i:s")
        ];

        $ret = self::addData($data, true);

        if ($ret > 0) {
            $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
            $key = self::MC_KEY_USER_LIST . "{$siteid}:{$uid}";
            $mc->delete($key);

            return true;
        }

        logs($data, "PassportApi/Connect/AddFail");

        return false;
    }

    /**
     * 根据OpenId获取信息
     *
     * @param int    $siteid      站点ID, 见self::SITE_LISTS
     * @param string $openid      OpenId
     * @param int    $platform_id 平台ID, 见self::PLATFORM_LISTS
     * @return array 无数据时, 返回[]
     * @throws \Exception
     */
    public static function getInfoByOpenId($siteid, $openid, $platform_id)
    {
        $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
        $key = self::MC_KEY_OPENID . "{$siteid}:{$openid}:{$platform_id}";
        $data = $mc->get($key);
        if (empty($data)) {
            $ret = self::getDataByFields([
                'OpenId' => $openid,
                'SiteId' => $siteid,
                'PlatformId' => $platform_id
            ], [
                'type' => parent::MOD_TYPE_DB,
                'fields' => '*',
                'get_count' => 0,
                'get_result' => 1
            ]);

            if (is_array($ret['data'][0])) {
                $data = $ret['data'][0];
                $mc->set($key, $data, 86400);
            }
        }

        return is_array($data) ? $data : [];
    }

    /**
     * 根据UnionId获取信息
     *
     * @param string $unionid UnionId
     * @return array 无数据时, 返回[]
     * @throws \Exception
     */
    public static function getInfoByUnionId($unionid)
    {
        $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
        $key = self::MC_KEY_UNIONID . "{$unionid}";
        $data = $mc->get($key);
        if (empty($data)) {
            $ret = self::getDataByFields([
                'UnionId' => $unionid,
            ], [
                'type' => parent::MOD_TYPE_DB,
                'fields' => '*',
                'get_count' => 0,
                'get_result' => 1
            ]);

            if (is_array($ret['data'][0])) {
                $data = $ret['data'][0];
                $mc->set($key, $data, 86400);
            }
        }

        return is_array($data) ? $data : [];
    }

    /**
     * 根据用户ID获取信息
     *
     * @param int $siteid      站点ID, 见self::SITE_LISTS
     * @param int $uid         用户ID
     * @param int $platform_id 平台ID, 见self::PLATFORM_LISTS
     * @return array 无数据时, 返回[]
     * @throws \Exception
     */
    public static function getInfoByUserId($siteid, $uid, $platform_id)
    {
        $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
        $key = self::MC_KEY_USER . "{$siteid}:{$uid}:{$platform_id}";
        $data = $mc->get($key);
        if (empty($data)) {
            $ret = self::getDataByFields([
                'UserId' => $uid,
                'SiteId' => $siteid,
                'PlatformId' => $platform_id
            ], [
                'type' => parent::MOD_TYPE_DB,
                'fields' => '*',
                'get_count' => 0,
                'get_result' => 1
            ]);

            if (is_array($ret['data'][0])) {
                $data = $ret['data'][0];
                $mc->set($key, $data, 86400);
            }
        }

        return is_array($data) ? $data : [];
    }

    /**
     * 根据用户ID获取绑定的第三方信息
     *
     * @param int $siteid 站点ID, 见self::SITE_LISTS
     * @param int $uid    用户ID
     * @return array 无数据时, 返回[]
     * @throws \Exception
     */
    public static function getBindList($siteid, $uid)
    {
        $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
        $key = self::MC_KEY_USER_LIST . "{$siteid}:{$uid}";
        $data = $mc->get($key);
        if (empty($data)) {
            $ret = self::getDataByFields([
                'UserId' => $uid,
                'SiteId' => $siteid
            ], [
                'type' => parent::MOD_TYPE_DB,
                'fields' => '*',
                'get_count' => 0,
                'get_result' => 1
            ]);
            if (is_array($ret['data'])) {
                $data = $ret['data'];
                $mc->set($key, $data, 86400);
            }
        }

        return is_array($data) ? $data : [];
    }

    /**
     * 已登录用户解除绑定第三方帐号
     *
     * @param  int $siteid      站点ID, 见self::SITE_LISTS
     * @param  int $uid         用户ID
     * @param  int $platform_id 平台ID, 见self::PLATFORM_LISTS
     * @return bool
     */
    public static function unbindUser($siteid, $uid, $platform_id)
    {
        $sites = self::SITE_LISTS;
        if (!isset($sites[$siteid])) {
            return false;
        }

        $platforms = self::PLATFORM_LISTS;
        if (!isset($platforms[$platform_id])) {
            return false;
        }

        $data = self::getInfoByUserId($siteid, $uid, $platform_id);
        $id = $data['ConnectId'];
        $openid = $data['OpenId'];
        $unionid = !empty($data['UnionId']) ? $data['UnionId'] : '';

        $ret = self::delData($id);

        if ($ret !== false) {
            $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
            $key = self::MC_KEY_USER_LIST . "{$siteid}:{$uid}";
            $key2 = self::MC_KEY_USER . "{$siteid}:{$uid}:{$platform_id}";
            $key3 = self::MC_KEY_OPENID . "{$siteid}:{$openid}:{$platform_id}";
            $mc->delete($key);
            $mc->delete($key2);
            $mc->delete($key3);
            if (!empty($unionid)) {
                $key4 = self::MC_KEY_UNIONID . "{$unionid}";
                $mc->delete($key4);
            }

            return true;
        }

        logs($data, "PassportApi/Connect/AddFail");

        return false;
    }
}