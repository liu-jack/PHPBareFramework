<?php
/**
 *  发送消息
 */

namespace Queue\Queues;

use Queue\Queue;

class SendNotice extends Queue
{
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
     * 消息红点
     */
    const REDIS_KEY_DOT = 'D:%d';
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
     * 提醒计数字段
     */
    const REDIS_NUM_FIELD_TODO = 'todo';
    const REDIS_NUM_FIELD_INVITE = 'invite';
    const REDIS_NUM_FIELD_NOTICE = 'notice';

    const GROUP_NUM_LIST = [
        self::REDIS_GROUP_TODO => self::REDIS_NUM_FIELD_TODO,
        self::REDIS_GROUP_INVITE => self::REDIS_NUM_FIELD_INVITE,
        self::REDIS_GROUP_NOTICE => self::REDIS_NUM_FIELD_NOTICE,
    ];

    public function run($data)
    {
        $data = unserialize($data);
        if (empty($data)) {
            logs([
                'status' => 'data_wrong',
                'data' => $data,
                'time' => date("Y-m-d H:i:s")
            ], $this->logPath());
        } else {
            $redis = $this->getRedis('notice', 0);

            $uids = $data['uids'];
            $group = $data['group'];
            $notice = $data['notice'];
            $from_uid = $data['fromuid'];

            $redis->multi(\Redis::PIPELINE);

            $id_map = [];
            $count = 0;
            $uids = is_array($uids) ? $uids : [$uids];
            $group_num = self::GROUP_NUM_LIST;
            foreach ($uids as $v) {
                $key = sprintf(self::REDIS_KEY_NOTICE, $v, $group);
                $redis->lPush($key, serialize($notice));
                $redis->lTrim($key, 0, self::REDIS_LIST_MAX - 1);
                $redis->hIncrBy(sprintf(self::REDIS_KEY_NUM, $v), $group_num[$group], 1);
                if ($from_uid > 0) {
                    $redis->set(sprintf(self::REDIS_KEY_TIME_LIMIT, $from_uid, $v), 1, 3600);
                }

                $count += 3;
                $id_map[$count] = $v;
            }
            $ret = $redis->exec();

            $redis->multi(\Redis::PIPELINE);
            foreach ($ret as $k => $v) {
                $x = $k + 1;
                if ($x % 3 == 0 && $v > self::REDIS_LIST_MAX) {
                    $redis->hMset(sprintf(self::REDIS_KEY_NUM, $id_map[$x]), ['todo' => self::REDIS_LIST_MAX]);
                }
            }
            $redis->exec();
        }
    }
}
