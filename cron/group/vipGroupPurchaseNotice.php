<?php
/**
 * 团购提示通知
 * 定时脚本 每5分钟运行一次
 * php vipGroupPurchaseNotice.php 3Hours 5分钟运行一次 拼团剩余三小时通知
 * php vipGroupPurchaseNotice.php 1Hours 5分钟运行一次 拼团剩余一小时通知
 * php vipGroupPurchaseNotice.php 30Minutes 5分钟运行一次 拼团剩余半小时通知
 *
 * @author camfee <camfee@foxmail.com>
 *
 */

require(dirname(__DIR__) . "/cronCommon.php");

use lib\core\Action;
use MinApp\QBStory\QBUserInfo;
use MinApp\QBStory\QBTemplateMessage;
use MinApp\QBVip\QBGroupPurchase;
use MinApp\QBVip\QBGroupMember;
use Notice\Sys;

class vipGroupPurchaseNotice extends Action
{
    /**
     * 剩余3小时通知
     */
    public function do3Hours()
    {
        $time = 3 * 3600;
        self::sendRemainTimeNotice($time);
    }

    /**
     * 剩余1小时通知
     */
    public function do1Hours()
    {
        $time = 3600;
        self::sendRemainTimeNotice($time);
    }

    /**
     * 剩余30分钟通知
     */
    public function do30Minutes()
    {
        $time = 1800;
        self::sendRemainTimeNotice($time);
    }

    private static function sendRemainTimeNotice($time)
    {
        $list = QBGroupPurchase::getGroupPurchasesRemainTime($time);
        if (!empty($list)) {
            foreach ($list as $k => $groupPurchase) {
                $groupId = $groupPurchase[QBGroupPurchase::FIELD_ID];
                $members = QBGroupMember::getMemberListByGroupId($groupId);
                $userIds = array_column($members, QBGroupMember::FIELD_USER_ID);
                $qbUserInfos = QBUserInfo::getDataListByIds($userIds);
                $weixinConfig = loadconf('minapp/minapp')['QBStory'];
                $remainCount = min(1, $groupPurchase[QBGroupPurchase::FIELD_MEMBER_COUNT] - count($members));
                $remainTime = format_remain_time($time);
                foreach ($qbUserInfos as $v) {
                    if (!empty($v['OpenId'])) {
                        QBTemplateMessage::sendGroupPurchaseInvite(\lib\plugins\weixin\Oauth::getAccessTokenWithCache($weixinConfig['AppId'], $weixinConfig['AppSecret']), $v['OpenId'], $groupId, $remainCount, $remainTime, self::getVipTitle($groupPurchase[QBGroupPurchase::FIELD_VIP_TYPE]), true);
                    }
                }
                // app 通知
                foreach ($userIds as $uid) {
                    Sys::addGroupInviteNotice($uid, $groupId, $remainCount);
                }
            }
        }
    }

    private static function getVipTitle($type)
    {
        $title = '';
        if ($type == 1) {
            $title = '月卡VIP会员';
        } elseif ($type == 2) {
            $title = '季卡VIP会员';
        } elseif ($type == 3) {
            $title = '年卡VIP会员';
        }

        return $title;
    }
}

global $argv;
if (count($argv) == 1) {
    $do = 'Default';
} else {
    $do = $argv[1];
}
$app->run($do);