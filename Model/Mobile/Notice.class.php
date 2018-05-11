<?php

/**
 * 系统通知
 *
 * @package    modules
 * @subpackage Notice
 * @author     苏宁 <snsnsky@gmail.com>
 *
 * $Id$
 */

namespace Notice;

use Common\Bridge;
use Queue\Queue;
use Mobile\AppPush;
use Center\User as CUser;
use Center\UserBonus;

class Notice
{
    const TID_MSG_COMMENT = 1; // 评论消息

    const TID_NOTICE_INVITE = 1; // 邀请通知
    const TID_NOTICE_FIRST_INVITE = 11; // 首次邀请通知
    const TID_NOTICE_CHECK_OK = 8; // 提现审核通过
    const TID_NOTICE_CHECK_FAIL = 9; // 提现审核不通过
    const TID_NOTICE_OTHER = 10; // 其它系统通知
    const TID_NOTICE_DREW_OK = 12; // 提现转账成功
    const TID_NOTICE_DREW_FAIL = 13; // 提现转账成功
    const TID_NOTICE_ACTIVE = 14; // 活动奖励
    const TID_NOTICE_CHANGE = 15; // 金币自动转换

    const TID_REPORT_REPORT = 11; // 公告
    /**
     * DB 位置
     */
    const REDIS_DB = 0;
    const PUSH_TIMES_REDIS_DB = 1; // 进贡推送次数限制
    // 进贡推送次数限制
    const REDIS_KEY_PUSH_TIMES = 'PT:%d';
    /**
     * 时间限制KEY
     */
    const REDIS_KEY_TIME_LIMIT = 'T:%d:%d';
    /**
     * 消息数量KEY
     */
    const REDIS_KEY_NUM = 'N:%d';
    /**
     * 消息内容KEY
     */
    const REDIS_KEY_NOTICE = 'L:%d:%d';
    /**
     * 消息最大数量
     */
    const REDIS_LIST_MAX = 100;

    /**
     * 提醒分组
     */
    const REDIS_GROUP_TODO = 1;
    /**
     * 邀请和奖励
     */
    const REDIS_GROUP_INVITE = 2;
    /**
     * 通知分组
     */
    const REDIS_GROUP_NOTICE = 3;
    /**
     * 分组列表
     */
    const GROUP_LIST = [
        self::REDIS_GROUP_TODO => self::REDIS_NUM_FIELD_TODO,
        self::REDIS_GROUP_INVITE => self::REDIS_GROUP_INVITE,
        self::REDIS_GROUP_NOTICE => self::REDIS_GROUP_NOTICE
    ];

    const STRUCT_TODO = [
        'tid' => 'tid', // 消息类型ID 详见 config/mobileapi/task.cfg.php
        'title' => 'title', //消息标题
        'cont' => 'cont', // 消息内容
        'count' => 'count', // 评论点赞等
        'itemid' => 'itemid', //评论id
        'url' => 'url', // 评论url
        't' => 't' // 时间戳
    ];

    const STRUCT_INVITE = [
        'tid' => 'tid', // 消息类型ID
        'title' => 'title', //消息标题
        'cont' => 'cont', // 消息内容
        'coinnum' => 'coinnum', // 金币数量 活动人数
        'cashnum' => 'cashnum', // 金币数量 红包ID
        'itemid' => 'itemid', // 对象ID[可以是好友ID，可以是评论ID]
        'url' => 'url', // 消息url
        't' => 't' // 时间戳
    ];

    const STRUCT_NOTICE = [
        'tid' => 'tid', // 消息类型ID
        'title' => 'title', //消息标题
        'cont' => 'cont', // 消息内容
        'url' => 'url', // 消息url
        't' => 't' // 时间戳
    ];
    /**
     * 提醒计数字段
     */
    const REDIS_NUM_FIELD_TODO = 'todo';
    const REDIS_NUM_FIELD_INVITE = 'invite';
    const REDIS_NUM_FIELD_NOTICE = 'notice';

    /**
     * 计数字段列表
     */
    const FIELD_NUM_LIST = [
        self::REDIS_NUM_FIELD_TODO => self::REDIS_NUM_FIELD_TODO,
        self::REDIS_NUM_FIELD_INVITE => self::REDIS_NUM_FIELD_INVITE,
        self::REDIS_NUM_FIELD_NOTICE => self::REDIS_NUM_FIELD_NOTICE,
    ];

    const GROUP_NUM_LIST = [
        self::REDIS_GROUP_TODO => self::REDIS_NUM_FIELD_TODO,
        self::REDIS_GROUP_INVITE => self::REDIS_NUM_FIELD_INVITE,
        self::REDIS_GROUP_NOTICE => self::REDIS_NUM_FIELD_NOTICE,
    ];

    /**
     * 添加一条/多条同一类型的消息
     *
     * @param int|array $uids 用户ID, 可以多个
     * @param int $group 分组ID, self::REDIS_GROUP_*
     * @param array $struct 数据结构, 参见self::STRUCT_*
     * @return bool
     */
    public static function addNotice($uids, $group, $struct)
    {
        $uids = is_array($uids) ? $uids : [$uids];
        switch ($group) {
            case self::REDIS_GROUP_TODO:
                $notice = array_intersect_key($struct, self::STRUCT_TODO);

                if (count($notice) != count(self::STRUCT_TODO)) {
                    return false;
                }

                $task = loadconf('mobileapi/task');
                if (empty($notice['title']) || $notice['title'] == 'title') {
                    $notice['title'] = $task[$group][$notice['tid']]['Title'];
                }
                $field = self::REDIS_NUM_FIELD_TODO;

                break;
            case self::REDIS_GROUP_INVITE:
                $notice = array_intersect_key($struct, self::STRUCT_INVITE);

                if (count($notice) != count(self::STRUCT_INVITE)) {
                    return false;
                }

                $task = loadconf('mobileapi/task');
                if ($notice['title'] == '') {
                    $notice['title'] = $task[$group][$notice['tid']]['Title'];
                }
                if ($notice['cont'] == '') {
                    $notice['cont'] = $task[$group][$notice['tid']]['TaskName'];
                }
                $field = self::REDIS_NUM_FIELD_INVITE;

                break;
            case self::REDIS_GROUP_NOTICE:
                $notice = array_intersect_key($struct, self::STRUCT_NOTICE);

                if (count($notice) != count(self::STRUCT_NOTICE)) {
                    return false;
                }
                $task = loadconf('mobileapi/task');
                if (empty($notice['title']) || $notice['title'] == 'title') {
                    $notice['title'] = $task[$group][$notice['tid']]['Title'];
                }
                $field = self::REDIS_NUM_FIELD_NOTICE;

                break;
            default:
                return false;
        }

        //合并
        if ($group == self::REDIS_GROUP_TODO) {
            foreach ($uids as $v) {
                self::_addPush($v, $group, $notice, $notice['itemid']);
            }
        } else {
            //无需合并。直接写入Redis
            $redis = Bridge::redis(Bridge::REDIS_NOTICE_W, self::REDIS_DB);
            $redis->multi(\Redis::PIPELINE);

            $id_map = [];
            $count = 0;
            foreach ($uids as $v) {
                $key = sprintf(self::REDIS_KEY_NOTICE, $v, $group);
                $redis->lPush($key, serialize($notice));
                $redis->lTrim($key, 0, self::REDIS_LIST_MAX - 1);
                $redis->hIncrBy(sprintf(self::REDIS_KEY_NUM, $v), $field, 1);

                $count += 3;
                $id_map[$count] = $v;
            }
            $ret = $redis->exec();
            $redis->multi(\Redis::PIPELINE);
            foreach ($ret as $k => $v) {
                $x = $k + 1;
                if ($x % 3 == 0 && $v > self::REDIS_LIST_MAX) {
                    $redis->hMset(sprintf(self::REDIS_KEY_NUM, $id_map[$x]), [$field => self::REDIS_LIST_MAX]);
                }
            }
            $redis->exec();
        }

        // 消息推送
        self::sendPush($group, $struct, $uids);

        return true;
    }

    private static function _addPush($uid, $group, $notice, $itemid)
    {
        $redis = Bridge::redis(Bridge::REDIS_NOTICE_W, self::REDIS_DB);
        $mckey = sprintf(self::REDIS_KEY_NOTICE, $uid, $group, $itemid);
        $data = $redis->lRange($mckey, 0, self::REDIS_LIST_MAX - 1);
        $read = self::getNum($uid);
        $readnum = $read[self::REDIS_NUM_FIELD_TODO];
        $num = 0;
        //处理合并消息
        $tmp_del_num = 0;
        $dellist = [];
        if (count($data) > 0 && is_array($data)) {
            $i = 0;
            foreach ($data as $val) {
                //只处理未读的消息
                $tmp = unserialize($val);
                if ($tmp['itemid'] == $notice['itemid'] && $i < $readnum) {
                    preg_match('/\{num\}([\d]+)\{\/num\}/isU', $tmp['title'], $tmpnum);
                    if ($tmpnum) {
                        $num = $tmpnum[1];
                    } else {
                        $num = 1;
                    }
                    $dellist[] = $val;
                }

            }

        }
        $task = loadconf('mobileapi/task');
        $num++;
        $notice['title'] = str_replace('{n}', '{num}' . $num . '{/num}',
            $task[$group][$notice['tid']]['Title']);

        $list = serialize($notice);
        $delnum = count($dellist);
        $readnum = $readnum - $delnum;

        //

        $redis->multi(\Redis::PIPELINE);
        if (count($dellist) > 0) {
            foreach ((array)$dellist as $v) {
                $redis->lRem($mckey, $v, 0);
            }
        }
        if ($list) {
            $redis->lPush($mckey, $list);
            $readnum++;
            $redis->lTrim($mckey, 0, self::REDIS_LIST_MAX - 1);
        }

        $redis->hSet(sprintf(self::REDIS_KEY_NUM, $uid), self::REDIS_NUM_FIELD_TODO, $readnum);
        $ret = $redis->exec();

        return $ret;
    }


    /**
     * 获取通知列表
     *
     * @param int $uid 用户ID
     * @param int $group 分组ID, self::REDIS_GROUP_*
     * @param int $offset 偏移量
     * @param int $limit 总数
     * @return array|bool 成功返回数组, 参见self::STRUCT_*
     */
    public static function getNotice($uid, $group, $offset = 0, $limit = 99)
    {
        $groups = self::GROUP_LIST;
        if (!isset($groups[$group])) {
            return false;
        }

        $key = sprintf(self::REDIS_KEY_NOTICE, $uid, $group);

        $redis = Bridge::redis(Bridge::REDIS_NOTICE_R, self::REDIS_DB);
        $list = $redis->lRange($key, $offset, $limit);
        foreach ($list as & $v) {
            $v = unserialize($v);
        }

        return $list;
    }

    /**
     * 清空通知列表
     *
     * @param int $uid 用户ID
     * @param int $group 分组ID, self::REDIS_GROUP_*
     * @return bool
     */
    public static function clearNotice($uid, $group)
    {
        $groups = self::GROUP_LIST;
        if (!isset($groups[$group])) {
            return false;
        }

        $key = sprintf(self::REDIS_KEY_NOTICE, $uid, $group);
        $redis = Bridge::redis(Bridge::REDIS_NOTICE_W, self::REDIS_DB);
        $redis->del($key);

        $group_num_list = self::GROUP_NUM_LIST;
        self::setNum($uid, $group_num_list[$group], 0);

        return true;
    }

    /**
     * 设置未读消息数量
     *
     * @param int $uid 用户ID
     * @param int $type 类型, 见self::FIELD_NUM_LIST;
     * @param int|string $num 数量, 可以是 [count|'-count'|'+count']
     * @return bool|int  如果是设置, 返回bool, 如果是递增, 返回递增后的数
     */
    public static function setNum($uid, $type, $num)
    {
        $uid = (int)$uid;
        $type_list = self::FIELD_NUM_LIST;
        $key = sprintf(self::REDIS_KEY_NUM, $uid);

        if (!is_numeric($num)) {
            return false;
        }

        if (!isset($type_list[$type])) {
            return false;
        }

        $flag = substr($num, 0, 1);
        $field = $type;

        $redis = Bridge::redis(Bridge::REDIS_NOTICE_W, self::REDIS_DB);
        if ($flag == '+' || $flag == '-') {
            return $redis->hIncrBy($key, $field, $num);
        }

        return $redis->hMset($key, [$field => (int)$num]);
    }

    /**
     * 检查(提醒)是否存在时间限制
     *
     * @param int $uid 用户ID
     * @param int $from_uid 来源用户ID, 当为REDIS_GROUP_TODO时有效
     * @param int $babyid 宝宝ID
     * @return bool true: 是, false: 否
     */
    public static function checkTimeLimit($uid, $from_uid, $babyid = 0)
    {
        $redis = Bridge::redis(Bridge::REDIS_NOTICE_R, self::REDIS_DB);
        $time = $redis->get(sprintf(self::REDIS_KEY_TIME_LIMIT, $from_uid, $uid, $babyid));
        if (!empty($time)) {
            return true;
        }

        return false;
    }

    /**
     * 获得全部消息数量
     *
     * @param int $uid 用户ID
     * @param int $group 分组ID, self::REDIS_GROUP_*
     * @return int 消息数量
     */
    public static function getAllNum($uid, $group)
    {
        $redis = Bridge::redis(Bridge::REDIS_NOTICE_R, self::REDIS_DB);
        $key = sprintf(self::REDIS_KEY_NOTICE, $uid, $group);

        return $redis->lLen($key);

    }

    /**
     * 获得未读消息数量
     *
     * @param int $uid 用户ID
     * @return array 见 self::FIELD_NUM_LIST
     */
    public static function getNum($uid)
    {
        $redis = Bridge::redis(Bridge::REDIS_NOTICE_R, self::REDIS_DB);

        return $redis->hGetAll(sprintf(self::REDIS_KEY_NUM, $uid));
    }


    /**
     * 向所有用户发送通知
     *
     * @param $group
     * @param $struct
     * @param int $from_uid
     * @return bool
     */
    public static function sendAll($group, $struct, $from_uid = 0)
    {
        set_time_limit(0);
        //每次发送人数为100
        $num = 100;

        $notice = array_intersect_key($struct, self::STRUCT_NOTICE);
        if (count($notice) != count(self::STRUCT_NOTICE)) {
            return false;
        }

        $pdo = Bridge::pdo(Bridge::DB_USER_W);
        $count = $pdo->select("MAX(UserId)")->from("User")->getValue();
        $cnt = ceil($count / $num);
        for ($i = 0; $i < $cnt; $i++) {
            $pdo->clear();
            $res = $pdo
                ->select("UserId")
                ->from("User")
                ->where([
                    "UserId >=" => $i * $num,
                    "UserId <" => ($i + 1) * $num,
                ])
                ->order("UserId")
                ->getAll();
            //发送队列
            $userids = array_column($res, 'UserId');

            if (count($userids) > 0 && is_array($userids)) {
                $queue_obj = self::_getQueue();
                $data = [
                    'uids' => $userids,
                    'group' => $group,
                    'notice' => $notice,
                    'fromuid' => $from_uid,
                ];
                $queue_obj->add('SendNotice', $data);
            }
        }

        //发送消息推送
        sleep(10); //延迟推送 10s
        self::sendPush($group, $struct);

        return true;
    }

    /**
     * 获得队列实例
     *
     * @return null|Queue
     */
    private static function _getQueue()
    {
        static $queue_obj = null;

        if ($queue_obj === null) {
            $queue_obj = new Queue();
        }

        return $queue_obj;
    }

    /**
     * 推送一条/多条同一类型的消息
     *
     * @param int $group 分组ID, self::REDIS_GROUP_*
     * @param array $struct 数据结构, 参见self::STRUCT_*
     * @param int|array $uids 用户ID, 可以多个
     */
    public static function sendPush($group, $struct, $uids = [])
    {
        $uids = is_array($uids) ? $uids : [$uids];
        $task = loadconf('mobileapi/task')[$group];
        if ($group == self::REDIS_GROUP_TODO) {
            if (!empty($uids)) {
                $data = [AppPush::EXTRA_FIELD_TYPE => AppPush::VAL_TYPE_MSG];
                //$msg = '你的评论获得了' . intval($struct['count']) . '次点赞';
                $msg = '你的评论获得了1次点赞';
                foreach ($uids as $uid) {
                    AppPush::pushByUserId($uid, AppPush::PUSH_TYPE_NOTICE, $msg, $data);
                }
            }
        } elseif ($group == self::REDIS_GROUP_INVITE) {
            if (!empty($uids)) {
                $data = [];
                $msg = $str = '';
                $type = AppPush::PUSH_TYPE_MSG;
                $cont = $task[$struct['tid']]['TaskName'];
                switch ($struct['tid']) {
                    case self::TID_NOTICE_INVITE:
                        if (!empty($struct['itemid'])) {
                            $users = CUser::getNickByUserId($struct['itemid']);
                            if (!empty($users)) {
                                $str = $users['UserNick'];
                            }
                        }
                        $data = [AppPush::EXTRA_FIELD_STR => auto_host('http://m.tatoutiao.com/app/task/invite.php?tab=1')];
                        $msg = '你成功邀请了一个闺蜜' . $str;
                        $type = AppPush::PUSH_TYPE_URL;
                        break;
                    case self::TID_NOTICE_FIRST_INVITE:
                        if (!empty($struct['itemid'])) {
                            $users = CUser::getNickByUserId($struct['itemid']);
                            if (!empty($users)) {
                                $str = $users['UserNick'];
                            }
                        }
                        $data = [AppPush::EXTRA_FIELD_TYPE => AppPush::VAL_TYPE_NOTICE];
                        $msg = '你通过邀请闺蜜' . $str . '，获得1个红包。';
                        $type = AppPush::PUSH_TYPE_NOTICE;
                        break;
                    case self::TID_NOTICE_CHECK_OK:
                        $msg = '你申请的' . abs($struct['cashnum']) . '元微信提现审核通过。';
                        $type = AppPush::PUSH_TYPE_CONVERT_HISTORY;
                        break;
                    case self::TID_NOTICE_CHECK_FAIL:
                        $msg = '你申请的' . abs($struct['cashnum']) . '元微信提现审核失败';
                        if (!empty($struct['cont']) && $struct['cont'] != $cont) {
                            $msg .= '：' . $struct['cont'];
                        }
                        $type = AppPush::PUSH_TYPE_CONVERT_HISTORY;
                        break;
                    case self::TID_NOTICE_DREW_OK:
                        $msg = '你申请的' . abs($struct['cashnum']) . '元微信提现转账成功，请注意查收。';
                        $type = AppPush::PUSH_TYPE_CONVERT_HISTORY;
                        break;
                    case self::TID_NOTICE_DREW_FAIL:
                        $msg = '你申请的' . abs($struct['cashnum']) . '元微信提现转账失败';
                        if (!empty($struct['cont']) && $struct['cont'] != $cont) {
                            $msg .= '：' . $struct['cont'];
                        }
                        $type = AppPush::PUSH_TYPE_CONVERT_HISTORY;
                        break;
                    case self::TID_NOTICE_ACTIVE:
                        $data = [AppPush::EXTRA_FIELD_TYPE => AppPush::VAL_TYPE_NOTICE];
                        $bonuses = UserBonus::getBonusByIds($struct['cashnum']);
                        $bonuscash = !empty($bonuses[$struct['cashnum']]) ? $bonuses[$struct['cashnum']]['Number'] : '';
                        $msg = '你在活动期间获得了' . abs($struct['coinnum']) . '个闺蜜，获得' . abs($bonuscash) . '元红包奖励。';
                        $type = AppPush::PUSH_TYPE_NOTICE;
                        break;
                    case self::TID_NOTICE_CHANGE:
                        $data = [AppPush::EXTRA_FIELD_TYPE => AppPush::VAL_TYPE_CASH];
                        $msg = '你昨日自动兑换了' . abs($struct['coinnum']) . '金币获得' . abs($struct['cashnum']) . '元现金。';
                        $type = AppPush::PUSH_TYPE_WALLET;
                        break;
                    case self::TID_NOTICE_OTHER:
                        $msg = '';
//                        $msg = '其它系统通知';
//                        if (!empty($struct['cont'])) {
//                            $msg = $struct['cont'];
//                        }
//                        if (empty($struct['url'])) {
//                            $data = [AppPush::EXTRA_FIELD_TYPE => AppPush::VAL_TYPE_NOTICE];
//                            $type = AppPush::PUSH_TYPE_NOTICE;
//                        } else {
//                            $data = [AppPush::EXTRA_FIELD_STR => $struct['url']];
//                            $type = AppPush::PUSH_TYPE_URL;
//                        }

                        break;
                }
                foreach ($uids as $uid) {
                    if (!empty($msg)) {
                        AppPush::pushByUserId($uid, $type, $msg, $data);
                    }
                }
            }
        } elseif ($group == self::REDIS_GROUP_NOTICE) {
//            if (empty($struct['url'])) {
//                $data = [AppPush::EXTRA_FIELD_TYPE => AppPush::VAL_TYPE_REPORT];
//                $type = AppPush::PUSH_TYPE_NOTICE;
//            } else {
//                $data = [AppPush::EXTRA_FIELD_STR => $struct['url']];
//                $type = AppPush::PUSH_TYPE_URL;
//            }
//            $msg = $struct['cont'];
//
//            if (!empty($uids)) {
//                foreach ($uids as $uid) {
//                    if (!empty($msg)) {
//                        AppPush::pushByUserId($uid, $type, $msg, $data);
//                    }
//                }
//            } else {
//                AppPush::pushAll($type, $msg, $data);
//            }
        }
    }

    /**
     * 获取推送次数
     * @param $uid
     * @return bool|string
     */
    public static function getPushTimes($uid)
    {
        $redis = Bridge::redis(Bridge::REDIS_NOTICE_R, self::PUSH_TIMES_REDIS_DB);
        $key = sprintf(self::REDIS_KEY_PUSH_TIMES, $uid);
        return $redis->get($key);
    }

    /**
     *
     */
    public static function setPushTimes($uid)
    {
        $redis = Bridge::redis(Bridge::REDIS_NOTICE_W, self::PUSH_TIMES_REDIS_DB);
        $key = sprintf(self::REDIS_KEY_PUSH_TIMES, $uid);
        $timeout = strtotime(date('Y-m-d', strtotime('+ 1 day'))) - time();
        return $redis->set($key, 1, $timeout);
    }
}
