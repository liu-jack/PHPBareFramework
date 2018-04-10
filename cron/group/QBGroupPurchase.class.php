<?php

/**
 *
 */

namespace MinApp\QBVip;

use Center\BeanOrderLog;
use Center\UserBag;
use Common\Bridge;
use lib\plugins\weixin\Oauth;
use lib\plugins\weixin\PayApi;
use lib\plugins\weixin\ResultData;
use MinApp\QBStory\QBTemplateMessage;
use Notice\Sys;
use RedisDB\RedisDBQBGroupPurchase;
use MinApp\QBStory\QBUserInfo;

/**
 * Class GroupPurchase
 *
 * @package MinApp\QBVip
 */
class QBGroupPurchase
{
    /**
     * table
     */
    const TABLE_NAME = 'QBGroupPurchase';

    /**
     * field name
     */
    const FIELD_ID = 'Id';
    const FIELD_FEESET_ID = 'FeeSetId';
    const FIELD_VIP_TYPE = 'VipType';
    const FIELD_GROUP_PRICE = 'GroupPrice';
    const FIELD_START_TIME = 'StartTime';//活动开始时间
    const FIELD_END_TIME = 'EndTime';//活动结束时间
    const FIELD_EXPIRE_TIME = 'ExpireTime';//主题开始有效时间
    const FIELD_ACTUAL_START_TIME = 'ActualStartTime';//实际开始时间
    const FIELD_ACTUAL_END_TIME = 'ActualEndTime';//实际结束时间
    const FIELD_MEMBER_COUNT = 'MemberCount';
    const FIELD_JOIN_COUNT = 'JoinCount';
    const FIELD_STATUS = 'Status';
    const FIELD_USER_ID = 'UserId';
    const FIELD_SUCCESS_TIME = 'SuccessTime';
    const FIELD_CREATE_TIME = 'CreateTime';

    /**
     * 状态
     */
    const STATUS_DEFAULT = 0;//默认
    const STATUS_START = 1;//拼团开始
    const STATUS_SUCCESS = 2;//拼团成功
    const STATUS_FAILURE = 3;//拼团失败

    /**
     *redis
     */
    const REDIS_LOCK_NAME = 'GroupPurchase:%d';

    /**
     * 创建一个团
     *
     * @param $feeset
     * @param $userId
     * @return bool|string
     */
    public static function createGroupPurchase($feeset, $userId)
    {
        $now = date('Y-m-d H:i:s');
        $data = [
            self::FIELD_FEESET_ID => $feeset[QBFeeSet::FIELD_ID],
            self::FIELD_GROUP_PRICE => $feeset[QBFeeSet::FIELD_GROUP_PRICE],
            self::FIELD_START_TIME => $feeset[QBFeeSet::FIELD_GROUP_START_TIME],
            self::FIELD_END_TIME => $feeset[QBFeeSet::FIELD_GROUP_END_TIME],
            self::FIELD_MEMBER_COUNT => $feeset[QBFeeSet::FIELD_MEMBER_COUNT],
            self::FIELD_JOIN_COUNT => 0,
            self::FIELD_USER_ID => $userId,
            self::FIELD_STATUS => self::STATUS_DEFAULT,
            self::FIELD_CREATE_TIME => $now,
            self::FIELD_VIP_TYPE => $feeset[QBFeeSet::FIELD_VIP_TYPE],
            self::FIELD_EXPIRE_TIME => $feeset[QBFeeSet::FIELD_EXPIRE_TIME],
            self::FIELD_ACTUAL_START_TIME => '',
            self::FIELD_ACTUAL_END_TIME => '',
            self::FIELD_SUCCESS_TIME => ''
        ];
        $pdo = self::getPdo(true);
        $ret = $pdo->insert(self::TABLE_NAME, $data);
        if ($ret === false) {
            return false;
        }
        $groupId = $pdo->lastInsertId();
        $data[self::FIELD_ID] = $groupId;

        $rdKey = RedisDBQBGroupPurchase::getKey($groupId);
        RedisDBQBGroupPurchase::instance()->save($rdKey, $data);

        return $groupId;
    }

    /**
     * 增加参加人数
     *
     * @param     $id
     * @param int $count
     * @return int
     */
    public static function incJoinCount($id, $count = 1)
    {
        $joinCount = RedisDBQBGroupPurchase::instance()->incJoinCount($id, $count);

        $rdKey = RedisDBQBGroupPurchase::getKey($id);
        RedisDBQBGroupPurchase::instance()->async($rdKey, Bridge::DB_MINAPP_W, self::TABLE_NAME, self::FIELD_ID, $id, [
            self::FIELD_JOIN_COUNT
        ]);

        return $joinCount;
    }

    /**
     * 开始团购
     *
     * @param $groupPurchase
     */
    public static function startGroupPurchase($groupPurchase)
    {
        if ($groupPurchase[self::FIELD_STATUS] == self::STATUS_DEFAULT) {
            $id = $groupPurchase[self::FIELD_ID];
            $now = time();
            $endTs = strtotime($groupPurchase[self::FIELD_END_TIME]);
            $expireTs = $now + $groupPurchase[self::FIELD_EXPIRE_TIME] * 3600;
            if (!IS_ONLINE) { // 测试环境 单位改为分钟
                $expireTs = $now + $groupPurchase[self::FIELD_EXPIRE_TIME] * 60;
            }
            $actEndTs = min($endTs, $expireTs);

            $data = [
                self::FIELD_STATUS => self::STATUS_START,
                self::FIELD_ACTUAL_START_TIME => date('Y-m-d H:i:s', $now),
                self::FIELD_ACTUAL_END_TIME => date('Y-m-d H:i:s', $actEndTs),
            ];

            $rdKey = RedisDBQBGroupPurchase::getKey($id);
            RedisDBQBGroupPurchase::instance()->save($rdKey, $data);

            RedisDBQBGroupPurchase::instance()->async($rdKey, Bridge::DB_MINAPP_W, self::TABLE_NAME, self::FIELD_ID,
                $id, [
                    self::FIELD_STATUS,
                    self::FIELD_ACTUAL_START_TIME,
                    self::FIELD_ACTUAL_END_TIME
                ]);
        }
    }

    /**
     * @param $id
     * @return bool|null|string
     */
    public static function getGroupPurchaseById($id)
    {
        $rdKey = RedisDBQBGroupPurchase::getKey($id);
        $groupPurchase = RedisDBQBGroupPurchase::instance()->load($rdKey);
        if (empty($groupPurchase)) {
            $groupPurchase = self::getPdo()->select('*')->from(self::TABLE_NAME)->where([self::FIELD_ID => $id])->getOne();
            if (!empty($groupPurchase)) {
                RedisDBQBGroupPurchase::instance()->save($rdKey, $groupPurchase);
            }
        }

        return $groupPurchase;
    }

    /**
     * 获取多个团购
     * @param array $ids
     * @return array
     */
    public static function getGroupPurchaseByIds($ids)
    {
        $rdKey = [];
        foreach ($ids as $id) {
            $rdKey[$id] = RedisDBQBGroupPurchase::getKey($id);
        }
        $rdata = RedisDBQBGroupPurchase::instance()->loads($rdKey);
        $k = 0;
        $_cache = $group_id = [];
        foreach ($rdKey as $id => $v) {
            if (empty($rdata[$k])) {
                $group_id[$id] = $v;
            } else {
                $_cache[$id] = $rdata[$k];
            }
            $k++;
        }
        if (!empty($group_id)) {
            $groups = self::getPdo()->select('*')->from(self::TABLE_NAME)->where([self::FIELD_ID . ' IN'=> array_keys($group_id)])->getAll();
            if (!empty($groups)) {
                foreach ($groups as $v) {
                    $_cache[$v[self::FIELD_ID]] = $v;
                    RedisDBQBGroupPurchase::instance()->save($group_id[$v[self::FIELD_ID]], $v);
                }

            }
        }
        $data = [];
        foreach ($ids as $id) {
            if (isset($_cache[$id])) {
                $data[$id] = $_cache[$id];
            }
        }

        return $data;
    }

    /**
     * 查询超时拼团
     *
     * @param $offset
     * @param $limit
     * @return array|bool
     */
    public static function getTimeoutGroupPurchases($offset, $limit)
    {
        $timeout = 600;
        $timeoutTs = time() - $timeout;
        $timeoutDate = date('Y-m-d H:i:s');
        $timeMinDate = date('Y-m-d H:i:s', $timeoutTs);

        return self::getPdo()->select("*")->from(self::TABLE_NAME)->where([
            self::FIELD_STATUS => self::STATUS_START,
            self::FIELD_ACTUAL_END_TIME . ' <=' => $timeoutDate,
            self::FIELD_ACTUAL_END_TIME . ' >=' => $timeMinDate
        ])->limit($offset, $limit)->getAll();
    }

    /**
     * 查询剩余xx时间所有拼团
     *
     * @param int    $time
     * @param int    $range
     * @param string $field
     * @param int    $offset
     * @param int    $limit
     * @return array|bool
     */
    public static function getGroupPurchasesRemainTime($time = 1800, $range = 300, $field = 'Id,VipType,MemberCount', $offset = 0, $limit = 9999)
    {
        $timeoutTs = time() + $time;
        $maxDate = date('Y-m-d H:i:s', $timeoutTs);
        $minDate = date('Y-m-d H:i:s', $timeoutTs - $range);

        return self::getPdo()->select($field)->from(self::TABLE_NAME)->where([
            self::FIELD_STATUS => self::STATUS_START,
            self::FIELD_ACTUAL_END_TIME . ' <=' => $maxDate,
            self::FIELD_ACTUAL_END_TIME . ' >=' => $minDate
        ])->limit($offset, $limit)->getAll();
    }

    /**
     * 拼团失败
     */
    public static function updateGroupPurchaseFailure($groupPurchase)
    {
        $where = [
            self::FIELD_ID => $groupPurchase[self::FIELD_ID],
            self::FIELD_STATUS => self::STATUS_START
        ];
        $data = [
            self::FIELD_STATUS => self::STATUS_FAILURE
        ];
        $ret = self::getPdo(true)->update(self::TABLE_NAME, $data, $where);
        if ($ret > 0) {
            $rdKey = RedisDBQBGroupPurchase::getKey($groupPurchase[self::FIELD_ID]);
            RedisDBQBGroupPurchase::instance()->save($rdKey, $data);

            //拼团失败通知
            $members = QBGroupMember::getMemberListByGroupId($groupPurchase[self::FIELD_ID]);
            $userIds = array_column($members, self::FIELD_USER_ID);
            $qbUserInfos = QBUserInfo::getDataListByIds($userIds);
            $weixinConfig = loadconf('minapp/minapp')['QBStory'];
            $productName = QBFeeSet::getVipTitle($groupPurchase[self::FIELD_VIP_TYPE]) . 'VIP会员';
            foreach ($qbUserInfos as $v) {
                if (!empty($v['OpenId'])) {
                    QBTemplateMessage::sendGroupPurchaseFail(Oauth::getAccessTokenWithCache($weixinConfig['AppId'], $weixinConfig['AppSecret']),  $v['OpenId'], $productName);
                }
            }
            // app 通知
            foreach ($userIds as $uid) {
                Sys::addGroupFailNotice($uid, $groupPurchase[self::FIELD_ID]);
            }

            // 退款订单
            $vipOrders = QBVipOrder::getVipOrderByGroupId($groupPurchase[self::FIELD_ID]);
            if (!empty($vipOrders)) {
                foreach ($vipOrders as $vipOrder) {
                    self::refund($vipOrder);
                }
            }
        }
    }

    /**
     * 拼团成功
     *
     * @param $groupPurchase
     */
    public static function updateGroupPurchaseSuccess($groupPurchase)
    {
        $groupId = $groupPurchase[self::FIELD_ID];
        $now = date('Y-m-d H:i:s');
        $where = [
            self::FIELD_ID => $groupId,
            self::FIELD_STATUS => self::STATUS_START
        ];
        $data = [
            self::FIELD_STATUS => self::STATUS_SUCCESS,
            self::FIELD_SUCCESS_TIME => $now
        ];
        $ret = self::getPdo(true)->update(self::TABLE_NAME, $data, $where);
        if ($ret > 0) {

            $vipType = $groupPurchase[self::FIELD_VIP_TYPE];
            $vipDay = 0;
            if ($vipType == 1) {
                $vipDay = 30;
            } elseif ($vipType == 2) {
                $vipDay = 90;
            } elseif ($vipType == 3) {
                $vipDay = 365;
            }

            //
            $rdKey = RedisDBQBGroupPurchase::getKey($groupId);
            RedisDBQBGroupPurchase::instance()->save($rdKey, $data);

            $members = QBGroupMember::getMemberListByGroupId($groupId);
            foreach ($members as $member) {
                $memberId = $member[QBGroupMember::FIELD_ID];
                $userId = $member[QBGroupMember::FIELD_USER_ID];
                $ret = QBGroupMember::updateMemberVipState($memberId, $groupId);
                if ($ret > 0) {
                    QBUserInfo::updateVipExpireTime($userId, $vipType, $vipDay);
                }
            }
            QBGroupMember::clearCache($groupId);
        }
    }

    /**
     * @param bool $is_write
     * @return \lib\plugins\pdo\PDOQuery|\PDOStatement
     */
    public static function getPdo($is_write = false)
    {
        return Bridge::pdo($is_write ? Bridge::DB_MINAPP_W : Bridge::DB_MINAPP_R);
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
     * @param $groupId
     * @return string
     */
    public static function getLockName($groupId)
    {
        return sprintf(self::REDIS_LOCK_NAME, $groupId);
    }

    /**
     * 申请退款
     *
     * @param array $vipOrder 订单详情
     * @return bool
     */
    public static function refund($vipOrder)
    {
        // 小程序退款通知
        $Xcx_Config = loadconf('minapp/minapp')['QBStory'];
        //发起api请求
        $update = [
            QBVipOrder::FIELD_RETURN_TIME => date('Y-m-d H:i:s'),
        ];
        if ($vipOrder['PaymentType'] < 2) {
            $refund_no = uniqid('QBT_');
            $update[QBVipOrder::FIELD_RETURN_NO] = $refund_no;
            $weixinConfig = self::getWeixinConfig($vipOrder['PaymentType']);
            $weixinPay = new PayApi($weixinConfig);
            $params = [
                "out_trade_no" => $vipOrder['OrderNo'],
                "out_refund_no" => $refund_no,
                "total_fee" => $vipOrder['OrderAmt'],
                "refund_fee" => $vipOrder['OrderAmt'],
                "refund_desc" => '拼团失败退款',
            ];
            $res = $weixinPay->refund($params);
            $weixin_result = ResultData::parseFromXml($res);
            if ($weixin_result->getValue('return_code') == 'SUCCESS' && $weixin_result->getValue('result_code') == 'SUCCESS' ) {
                $log = [
                    'OrderNo' => $vipOrder['OrderNo'],
                    'total_fee' => $vipOrder['OrderAmt'],
                    'PaymentType' => $vipOrder['PaymentType'],
                    'Msg' => 'Refund Success',
                ];
                runtime_log('Payment/refund_suc', $log);
                // 修改退款状态
                $update[QBVipOrder::FIELD_RETURN_STATE] = 1;
                QBVipOrder::refundUpdate($vipOrder[QBVipOrder::FIELD_ID], $update, $vipOrder[QBVipOrder::FIELD_ORDER_NO]);
                //退款通知
                $qbUserInfo = QBUserInfo::getDataInfoById($vipOrder[QBVipOrder::FIELD_USER_ID]);
                if (!empty($qbUserInfo['OpenId'])) {
                    $refundReason = QBFeeSet::getVipTitle($vipOrder[QBVipOrder::FIELD_VIP_TYPE]) . 'VIP会员拼团失败';
                    QBTemplateMessage::sendGroupPurchaseRefund(Oauth::getAccessTokenWithCache($Xcx_Config['AppId'], $Xcx_Config['AppSecret']),  $qbUserInfo['OpenId'], '￥' . format_price($vipOrder['OrderAmt']), $refundReason);
                }
                // app 通知
                Sys::addGroupRefundNotice($vipOrder[QBVipOrder::FIELD_USER_ID], $vipOrder[QBVipOrder::FIELD_GROUP_ID],  format_price($vipOrder['OrderAmt']));

                return true;
            } else {
                $log = [
                    'OrderNo' => $vipOrder['OrderNo'],
                    'total_fee' => $vipOrder['OrderAmt'],
                    'ErrorMsg' => 'Refund Error err_code: ' . $weixin_result->getValue('err_code') . 'error:' . $weixin_result->getValue('err_code_des'),
                ];
                runtime_log('Payment/refund_err', $log);
                QBVipOrder::refundUpdate($vipOrder[QBVipOrder::FIELD_ID], $update, $vipOrder[QBVipOrder::FIELD_ORDER_NO]);
                return false;
            }
        } elseif ($vipOrder['PaymentType'] == 2) {
            if (UserBag::addBean($vipOrder['UserId'], $vipOrder['OrderAmt']) !== false) {
                $log = [
                    'OrderNo' => $vipOrder['OrderNo'],
                    'total_fee' => $vipOrder['OrderAmt'],
                    'PaymentType' => $vipOrder['PaymentType'],
                    'Msg' => 'Refund Success',
                ];
                runtime_log('Payment/refund_suc', $log);
                // 修改退款状态
                $update[QBVipOrder::FIELD_RETURN_STATE] = 1;
                QBVipOrder::refundUpdate($vipOrder[QBVipOrder::FIELD_ID], $update, $vipOrder[QBVipOrder::FIELD_ORDER_NO]);
                $user_bag = UserBag::getUserBagByUserId($vipOrder['UserId']);
                BeanOrderLog::recharge($vipOrder['UserId'], 0, $vipOrder['Id'], $user_bag['Bean'], $vipOrder['OrderAmt'], BeanOrderLog::ITEM_TYPE_REFUND);
                //退款通知
                $qbUserInfo = QBUserInfo::getDataInfoById($vipOrder[QBVipOrder::FIELD_USER_ID]);
                if (!empty($qbUserInfo['OpenId'])) {
                    $refundReason = QBFeeSet::getVipTitle($vipOrder[QBVipOrder::FIELD_VIP_TYPE]) . 'VIP会员拼团失败';
                    QBTemplateMessage::sendGroupPurchaseRefund(Oauth::getAccessTokenWithCache($Xcx_Config['AppId'], $Xcx_Config['AppSecret']),  $qbUserInfo['OpenId'], '￥' . format_price($vipOrder['OrderAmt']), $refundReason);
                }
                // app 通知
                Sys::addGroupRefundNotice($vipOrder[QBVipOrder::FIELD_USER_ID], $vipOrder[QBVipOrder::FIELD_GROUP_ID],  format_price($vipOrder['OrderAmt']));

                return true;
            } else {
                $log = [
                    'OrderNo' => $vipOrder['OrderNo'],
                    'total_fee' => $vipOrder['OrderAmt'],
                    'Msg' => 'Refund Error',
                ];
                runtime_log('Payment/refund_err', $log);
            }
        }
        return false;
    }

    /**
     * @param int $paymentType
     * @return mixed
     */
    private static function getWeixinConfig($paymentType = 0)
    {
        switch ($paymentType) {
            case QBVipOrder::PAYMENT_TYPE_JS:
                $conf = loadconf('minapp/minapp')['QBStory'];
                break;
            case QBVipOrder::PAYMENT_TYPE_APP:
                $conf = loadconf('mobileapi/plugins')['weixin'];
                break;
            default:
                $conf = [];
                break;
        }

        return $conf;
    }
}
