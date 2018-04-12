<?php
/**
 * GroupBuyList.class.php
 * 团购成员
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-11 下午5:34
 *
 */

namespace Model\Application;

use Bare\Model;
use Config\DBConfig;

class GroupBuyList extends Model
{
    /**
     * 基础配置文件
     *
     * @var array
     */
    protected static $_conf = [
        // 必选, 数据库代码 (来自DB配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DBConfig::DB_APPLICATION_W,
            self::CF_DB_R => DBConfig::DB_APPLICATION_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => 'GroupBuyList',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'Id' => self::VAR_TYPE_KEY,
            'GroupId' => self::VAR_TYPE_INT,
            'UserId' => self::VAR_TYPE_INT,
            'Type' => self::VAR_TYPE_INT,
            'PayState' => self::VAR_TYPE_INT,
            'PayTime' => self::VAR_TYPE_STRING,
            'CreateTime' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        self::CF_MC => DBConfig::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => 'GroupBuyList:%d',
        // 可选, 超时时间, 默认不过期
        self::CF_MC_TIME => 86400,
        // 可选, redis (来自DB配置), w: 写, r: 读
        self::CF_RD => [
            self::CF_DB_W => '',
            self::CF_DB_R => '',
            self::CF_RD_INDEX => 0,
            self::CF_RD_TIME => 86400,
            self::CF_RD_KEY => '', // 可选, redis KEY, "KeyName:%d", %d会用主键ID替代
        ],
    ];

    /**
     * @see \Bare\Model::add() 新增
     * @see \Bare\Model::update() 更新
     * @see \Bare\Model::getInfoByIds() 按主键id查询
     * @see \Bare\Model::getList() 条件查询
     * @see \Bare\Model::delete() 删除
     */

    const MC_LIST_USER_GROUP = 'MC_LIST_USER_GROUP:{UserId}';
    const MC_LIST_GROUP_LIST = 'MC_LIST_GROUP_LIST:{GroupId}';
    protected static $_cache_list_keys = [
        self::MC_LIST_USER_GROUP => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_MC,
            self::CACHE_LIST_FIELDS => 'UserId',
        ],
        self::MC_LIST_GROUP_LIST => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_MC,
            self::CACHE_LIST_FIELDS => 'GroupId',
        ],
    ];

    protected static $_add_must_fields = [
        'GroupId' => 1,
        'UserId' => 1,
        'Type' => 1,
    ];

    const MEMBER_PAY_TIME = 900; // 成员支付时间

    const TYPE_LEADER = 1;//团长
    const TYPE_NORMAL = 2;//成员

    const PAY_WAIT = 0;//未支付
    const PAY_SUCCESS = 1;//已支付

    /**
     * 获取用户团购列表
     *
     * @param $uid
     * @return array
     */
    public static function getUserGroup($uid)
    {
        $where = [
            'UserId' => $uid,
        ];
        $extra = [
            self::EXTRA_MOD_TYPE => self::MOD_TYPE_MEMCACHE,
            self::EXTRA_MC_KEY => self::MC_LIST_USER_GROUP,
            self::EXTRA_MC_TIME => 86400,
            self::EXTRA_FIELDS => 'Id',
            self::EXTRA_LIST_KEY => 'Id',
            self::EXTRA_LIST_VAL => 'Id',
            self::EXTRA_ORDER => '',
            self::EXTRA_LIMIT => 99999,
        ];

        return self::getDataByFields($where, $extra);
    }

    /**
     * 是否加入团购
     *
     * @param $uid
     * @param $gid
     * @return bool
     */
    public static function isJoinGroup($uid, $gid)
    {
        $group_list = self::getGroupList($gid);
        $members = self::getInfoByIds($group_list['data']);
        $users = array_column($members, 'UserId');

        return in_array($uid, $users) ? true : false;
    }

    /**
     * 获取团购列表
     *
     * @param $group_id
     * @return array
     */
    public static function getGroupList($group_id)
    {
        $where = [
            'GroupId' => $group_id,
        ];
        $extra = [
            self::EXTRA_MOD_TYPE => self::MOD_TYPE_MEMCACHE,
            self::EXTRA_MC_KEY => self::MC_LIST_GROUP_LIST,
            self::EXTRA_MC_TIME => 86400,
            self::EXTRA_FIELDS => '*',
            self::EXTRA_LIST_KEY => 'Id',
            self::EXTRA_LIST_VAL => 'Id',
            self::EXTRA_ORDER => '',
        ];

        return self::getDataByFields($where, $extra);
    }

    /**
     * 查询用户最后加入的团
     *
     * @param $uid
     * @return array|bool
     */
    public static function getLastGroupByUid($uid)
    {
        $where = [
            'UserId' => $uid,
            'PayState' => self::PAY_SUCCESS,
        ];
        $member = self::getList($where, 0, 1, '*');
        if (!empty($member) && ($member['Type'] == self::TYPE_LEADER || strtotime($member['CreateTime']) > time() - self::MEMBER_PAY_TIME)) {
            return $member;
        }

        return false;
    }

    /**
     * 查询支付超时订单
     *
     * @param int $offset
     * @param int $limit
     * @return array|bool
     */
    public static function getTimeoutPay($offset = 0, $limit = 100)
    {
        $timeout_ts = time() - self::MEMBER_PAY_TIME;
        $timeout_date = date('Y-m-d H:i:s', $timeout_ts);

        $where = [
            'PayState' => self::PAY_WAIT,
            'CreateTime <' => $timeout_date
        ];

        return self::getList($where, $offset, $limit);
    }

    /**
     * 删除支付超时成员
     *
     * @param $id
     * @return bool
     */
    public static function deleteTimeoutPay($id)
    {
        $member = self::getInfoByIds($id);
        if (empty($member) || $member['PayState'] != self::PAY_WAIT) {
            return false;
        }

        return self::delete($id);
    }

    /**
     * 添加团长
     *
     * @param $group_id
     * @param $uid
     * @return bool|int|string
     */
    public static function addLeader($group_id, $uid)
    {
        return self::addMember($group_id, $uid, self::TYPE_LEADER, self::PAY_SUCCESS);
    }

    /**
     * 加入团
     *
     * @param     $group_id
     * @param     $uid
     * @param int $type
     * @param int $pay_state
     * @return bool|int|string
     */
    public static function addMember($group_id, $uid, $type = self::TYPE_NORMAL, $pay_state = self::PAY_WAIT)
    {
        if (self::isJoinGroup($uid, $group_id)) {
            return true;
        }
        $now = date('Y-m-d H:i:s');
        $add = [
            'GroupId' => $group_id,
            'UserId' => $uid,
            'Type' => $type,
            'PayState' => $pay_state,
            'CreateTime' => $now,
        ];

        return self::add($add);
    }

    /**
     * 更新已支付
     *
     * @param $group_id
     * @param $uid
     * @return bool|int|string
     */
    public static function updatePay($group_id, $uid)
    {
        $member = self::getList([
            'GroupId' => $group_id,
            'UserId' => $uid,
        ], 0, 1);

        $now = date('Y-m-d H:i:s');
        if (empty($member)) {
            $add = [
                'GroupId' => $group_id,
                'UserId' => $uid,
                'Type' => self::TYPE_NORMAL,
                'PayState' => self::PAY_SUCCESS,
                'PayTime' => $now,
                'CreateTime' => $now
            ];
            $ret = self::add($add);
        } else {
            $update = [
                'PayState' => self::PAY_SUCCESS,
                'PayTime' => $now,
            ];
            $ret = self::update($member['Id'], $update);
        }

        return $ret;
    }
}