<?php
/**
 * 拼图成员
 */

namespace MinApp\QBVip;

use Common\Bridge;
use MobileApi\Vip\GroupPurchase;

/**
 * Class QBGroupMember
 *
 * @package MinApp\QBVip
 */
class QBGroupMember
{
    /**
     * table
     */
    const TABLE_NAME = 'QBGroupMember';

    /**
     * redis
     */
    const REDIS_MEMBER_LIST_KEY = 'QBGroupMemberList:%d';
    const REDIS_TIMEOUT = 86400;
    const REDIS_MEMBER_USER_GROUP_LIST_KEY = 'QBGroupMemberUserGroupList:%d'; // userid

    /**
     * field
     */
    const FIELD_ID = 'Id';
    const FIELD_GROUP_ID = 'GroupId';
    const FIELD_USER_ID = 'UserId';
    const FIELD_MEMBER_TYPE = 'MemberType';
    const FIELD_PAYMENT_STATE = 'PaymentState';
    const FIELD_PAYMENT_TIME = 'PaymentTime';
    const FIELD_VIP_STATE = 'VipState';
    const FIELD_VIP_TIME = 'VipTime';
    const FIELD_CREATE_TIME = 'CreateTime';

    /**
     * member_type
     */
    const MEMBER_TYPE_LEADER = 1;//团长
    const MEMBER_TYPE_NORMAL = 0;//成员

    /**
     * @param $info
     * @return bool|string
     */
    public static function add($info)
    {
        if (empty($info[self::FIELD_GROUP_ID])) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $data = array_merge($info, [
            self::FIELD_VIP_STATE => 0,
            self::FIELD_CREATE_TIME => $now
        ]);
        $pdo = self::getPdo(true);
        $ret = $pdo->insert(self::TABLE_NAME, $data);
        if ($ret === false) {
            return false;
        }
        self::clearCache($info[self::FIELD_GROUP_ID]);
        self::clearUserCache($info[self::FIELD_USER_ID]);

        return true;
    }

    /**
     * 更新数据
     *
     * @param $where
     * @param $info
     * @param $groupId
     * @return bool|int
     */
    public static function update($where, $info, $groupId)
    {
        $ret = self::getPdo(true)->update(self::TABLE_NAME, $info, $where);
        self::clearCache($groupId);

        return $ret;
    }

    /**
     * @param $where
     * @param $groupId
     * @param $uid
     * @return bool|int
     */
    public static function delete($where, $groupId, $uid)
    {
        $ret = self::getPdo(true)->delete(self::TABLE_NAME, $where);
        self::clearCache($groupId);
        self::clearUserCache($uid);

        return $ret;
    }

    /**
     * 清理缓存
     *
     * @param $groupId
     */
    public static function clearCache($groupId)
    {
        $rdKey = self::getRedisMemberListKey($groupId);
        self::getRedis(true)->delete($rdKey);
    }

    /**
     * 清理缓存
     *
     * @param $uid
     */
    public static function clearUserCache($uid)
    {
        $rdKey = self::getRedisUserGroupListKey($uid);
        self::getRedis(true)->delete($rdKey);
    }

    /**
     * 查询用户最后加入的团
     *
     * @param $userId
     * @return bool|null|string
     */
    public static function getLastJoinGroupPurchaseByUserId($userId)
    {
        //todo 缓存优化
        $groupMember = self::getPdo()->select('*')->from(self::TABLE_NAME)->where([
            self::FIELD_USER_ID => $userId,
            //self::FIELD_PAYMENT_STATE => 1
        ])->order(self::FIELD_ID . ' desc')->limit(1)->getOne();
        if (!empty($groupMember) && ($groupMember[self::FIELD_MEMBER_TYPE] == self::MEMBER_TYPE_LEADER || strtotime($groupMember[self::FIELD_CREATE_TIME]) > time() - loadconf('vip/group_purchase')['GroupPayTime'] * 60)) {
            return QBGroupPurchase::getGroupPurchaseById($groupMember[self::FIELD_GROUP_ID]);
        }

        return false;
    }

    /**
     * @param     $userId
     * @param int $paymentState
     * @return null
     */
    public static function getLastJoinGroupMemberByUserId($userId, $paymentState = 0)
    {
        //todo 缓存优化
        $groupMember = self::getPdo()->select('*')->from(self::TABLE_NAME)->where([
            self::FIELD_USER_ID => $userId,
            self::FIELD_PAYMENT_STATE => $paymentState
        ])->order(self::FIELD_ID . ' desc')->limit(1)->getOne();


        return $groupMember;
    }

    /**
     * 查询支付超时订单
     *
     * @param int $offset
     * @param int $limit
     * @return array|bool
     */
    public static function getPaymentTimeoutMembers($offset = 0, $limit = 100)
    {
        $timeout = loadconf('vip/group_purchase')['GroupPayTime'];//单位分钟
        $timeoutTs = time() - $timeout * 60;
        $timeoutDate = date('Y-m-d H:i:s', $timeoutTs);

        return self::getPdo()->select("*")->from(self::TABLE_NAME)->where([
            'PaymentState' => 0,
            'CreateTime <' => $timeoutDate
        ])->limit($offset, $limit)->getAll();
    }

    /**
     * 删除支付超时成员
     *
     * @param $id
     * @param $groupId
     * @param $uid
     * @return bool|int
     */
    public static function deletePaymentTimeoutMember($id, $groupId, $uid)
    {
        $where = [
            self::FIELD_ID => $id,
            self::FIELD_PAYMENT_STATE => 0
        ];

        return self::delete($where, $groupId, $uid);
    }

    /**
     * 添加团长
     *
     * @param $groupId
     * @param $userId
     * @return bool|string
     */
    public static function addLeader($groupId, $userId)
    {
        $now = date('Y-m-d H:i:s');
        $info = [
            self::FIELD_GROUP_ID => $groupId,
            self::FIELD_USER_ID => $userId,
            self::FIELD_MEMBER_TYPE => self::MEMBER_TYPE_LEADER,
            self::FIELD_PAYMENT_STATE => 1,
            self::FIELD_PAYMENT_TIME => $now
        ];
        $ret = self::add($info);

        return $ret;
    }

    /**
     * 更新已支付
     *
     * @param $groupId
     * @param $userId
     * @return bool
     */
    public static function updateMemberPayment($groupId, $userId)
    {
        $data = self::getPdo()->select('*')->from(self::TABLE_NAME)->where([
            self::FIELD_GROUP_ID => $groupId,
            self::FIELD_USER_ID => $userId
        ])->getOne();

        $now = date('Y-m-d H:i:s');
        if (empty($data)) {
            $info = [
                self::FIELD_GROUP_ID => $groupId,
                self::FIELD_USER_ID => $userId,
                self::FIELD_MEMBER_TYPE => self::MEMBER_TYPE_NORMAL,
                self::FIELD_PAYMENT_STATE => 1,
                self::FIELD_PAYMENT_TIME => $now
            ];
            $ret = self::add($info);
        } else {
            $where = [
                self::FIELD_ID => $data[self::FIELD_ID]
            ];
            $info = [
                self::FIELD_PAYMENT_STATE => 1,
                self::FIELD_PAYMENT_TIME => $now
            ];
            $ret = self::update($where, $info, $groupId);
        }
        if ($ret === false) {
            return false;
        }

        return true;
    }

    /**
     * 更新用户会员状态
     *
     * @param $id
     * @return bool|int
     */
    public static function updateMemberVipState($id, $groupId)
    {
        $now = date('Y-m-d H:i:s');
        $where = [
            self::FIELD_ID => $id,
            self::FIELD_PAYMENT_STATE => 1,
            self::FIELD_VIP_STATE => 0
        ];
        $data = [
            self::FIELD_VIP_STATE => 1,
            self::FIELD_VIP_TIME => $now
        ];

        return self::update($where, $data, $groupId);
    }

    /**
     * 加锁查询团员信息
     *
     * @param $groupId
     * @return array|bool
     */
    public static function getMemberListByGroupId($groupId)
    {
        $rdKey = self::getRedisMemberListKey($groupId);

        $data = self::getRedis()->get($rdKey);
        if ($data) {
            return @unserialize($data);
        }
        $data = self::getPdo()->select('*')->from(self::TABLE_NAME)->where([self::FIELD_GROUP_ID => $groupId])->order(self::FIELD_MEMBER_TYPE . ' desc')->getAll();
        self::getRedis(true)->set($rdKey, @serialize($data), self::REDIS_TIMEOUT);

        return $data;
    }

    /**
     * 加入团
     *
     * @param $groupId
     * @param $userId
     * @param $memberCount
     * @return mixed
     */
    public static function joinGroup($groupId, $userId, $memberCount)
    {
        $members = self::getMemberListByGroupId($groupId);
        foreach ($members as $member) {
            if ($member['UserId'] == $userId) {
                if ($member['PaymentState'] == 1) {
                    return [
                        'code' => 201,
                        'result' => ['ErrorMsg' => '已参团']
                    ];
                } else {
                    return [
                        'code' => 200,
                        'result' => [
                            'ErrorMsg' => 'ok',
                            'RemainingTime' => paymentRemainingTime($member['CreateTime'])
                        ]
                    ];
                }
            }
        }
        if (count($members) >= $memberCount) {
            return [
                'code' => 205,
                'result' => ['ErrorMsg' => '团已满']
            ];
        }

        //add
        $info = [
            self::FIELD_GROUP_ID => $groupId,
            self::FIELD_USER_ID => $userId,
            self::FIELD_MEMBER_TYPE => self::MEMBER_TYPE_NORMAL,
            self::FIELD_PAYMENT_STATE => 0
        ];
        $ret = self::add($info);
        if ($ret === false) {
            return [
                'code' => 203,
                'result' => ['ErrorMsg' => '系统错误']
            ];
        }
        $now = date('Y-m-d H:i:s');

        return [
            'code' => 200,
            'result' => [
                'ErrorMsg' => 'ok',
                'RemainingTime' => paymentRemainingTime($now)
            ]
        ];
    }

    /**
     * 成员数
     *
     * @param $groupId
     * @return int
     */
    public static function getJoinMemberCountByGroupId($groupId)
    {
        $members = self::getMemberListByGroupId($groupId);

        return count($members);
    }

    /**
     * 是否加入
     *
     * @param $groupId
     * @param $userId
     * @return bool
     */
    public static function isJoin($groupId, $userId)
    {
        $members = self::getMemberListByGroupId($groupId);
        $userIds = array_column($members, self::FIELD_USER_ID);

        return in_array($userId, $userIds);
    }

    /**
     * 是否加入其他团
     *
     * @param     $groupId
     * @param     $userId
     * @return bool
     */
    public static function isJoinOther($groupId, $userId)
    {
        $gids = self::getJoinGroup($userId);
        if (isset($gids[$groupId])) {
            unset($gids[$groupId]);
        }
        $ret = 0;
        if (!empty($gids)) {
            $groups = QBGroupPurchase::getGroupPurchaseByIds($gids);
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    if ($group[QBGroupPurchase::FIELD_STATUS] == QBGroupPurchase::STATUS_START) {
                        $ret = $group[QBGroupPurchase::FIELD_ID];
                        break;
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * 获取用户的参团
     * @param $userId
     * @return array
     */
    public static function getJoinGroup($userId)
    {
        $rdKey = self::getRedisUserGroupListKey($userId);
        $data = self::getRedis()->get($rdKey);
        if ($data) {
            return @unserialize($data);
        }
        $data = [];
        if (!empty($userId)) {
            $create_time = date('Y-m-d H:i:s', time() - 86400 * 3);
            $list = self::getPdo()->clear()->select(self::FIELD_GROUP_ID)->from(self::TABLE_NAME)->where([
                self::FIELD_USER_ID => $userId,
                self::FIELD_CREATE_TIME . ' >' => $create_time
            ])->getAll();

            if (!empty($list)) {
                foreach ($list as $v) {
                    $data[$v[self::FIELD_GROUP_ID]] = $v[self::FIELD_GROUP_ID];
                }
            }
            self::getRedis(true)->set($rdKey, @serialize($data), self::REDIS_TIMEOUT);
        }

        return $data;
    }

    /**
     * @param bool $is_write
     * @return \lib\plugins\cache\RedisCache
     */
    public static function getRedis($is_write = false)
    {
        return Bridge::redis($is_write ? Bridge::REDIS_OTHER_W : Bridge::REDIS_OTHER_R, 10);
    }

    /**
     * @param bool $is_write
     * @return \lib\plugins\pdo\PDOQuery|\PDOStatement
     */
    public static function getPdo($is_write = false)
    {
        return Bridge::pdo(($is_write ? Bridge::DB_MINAPP_W : Bridge::DB_MINAPP_R));
    }

    /**
     * @param $groupId
     * @return string
     */
    public static function getRedisMemberListKey($groupId)
    {
        return sprintf(self::REDIS_MEMBER_LIST_KEY, $groupId);
    }

    /**
     * @param $uid
     * @return string
     */
    public static function getRedisUserGroupListKey($uid)
    {
        return sprintf(self::REDIS_MEMBER_USER_GROUP_LIST_KEY, $uid);
    }
}
