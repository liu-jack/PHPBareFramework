<?php
/**
 * @author 周剑锋 <camfee@foxmail.com>
 *
 * Date: 2017/4/26
 * Time: 15:50
 */

namespace Apps\Queues;

use Bare\C\Queue;
use Model\Book\Book;

class UpdateCount extends Queue
{
    // 更新的统计字段
    const VIEW_COUNT = 'ViewCount';
    const LIKE_COUNT = 'LikeCount';
    private $count_type = [
        self::VIEW_COUNT => [
            'count' => 0,
            'cache' => [],
            'last_time' => 0,
            'trigger_time' => 600, //设为10分钟
            'trigger_count' => 100
        ],
        self::LIKE_COUNT => [
            'count' => 0,
            'cache' => [],
            'last_time' => 0,
            'trigger_time' => 600, //设为10分钟
            'trigger_count' => 100
        ],
    ];

    /**
     * 用于处理队列返回的数据
     *
     * @param array $data 队列中存入的数据
     */
    public function run($data)
    {
        if (isset($this->count_type[$data['type']])) {
            $count = intval($data['num']);
            $id = intval($data['id']);
            if ($count) {
                $this->count_type[$data['type']]['count'] += $count;
                if (isset($this->count_type[$data['type']]['cache'][$id])) {
                    $this->count_type[$data['type']]['cache'][$id] += $count;
                } else {
                    $this->count_type[$data['type']]['cache'][$id] = $count;
                }
                // 初始化时间戳
                if ($this->count_type[$data['type']]['last_time'] == 0) {
                    $this->count_type[$data['type']]['last_time'] = time();
                }
                // 检查触发器
                $this->checkTrigger();
            }
        }
    }

    /**
     * 检查触发更新函数
     *
     * @param bool $force 是否强制更新
     * @return bool|void
     */
    public function checkTrigger($force = false)
    {
        $time = time();
        foreach ($this->count_type as $k => $v) {
            if ($time - $v['last_time'] > $v['trigger_time'] || $v['count'] > $v['trigger_count'] || $force) {
                $this->_updateCount($k);
            }
        }
    }

    /**
     * 更新统计数
     *
     * @param $type
     */
    private function _updateCount($type)
    {
        $data = $this->count_type[$type];
        $this->count_type[$type]['last_time'] = time();
        $this->count_type[$type]['count'] = 0;
        $this->count_type[$type]['cache'] = [];
        switch ($type) {
            case self::VIEW_COUNT:
                foreach ($data['cache'] as $k => $v) {
                    $ret = Book::getBookByIds($k);
                    $view_count = !empty($ret['ViewCount']) ? $ret['ViewCount'] : 0;
                    Book::updateBook($k, ['ViewCount' => $view_count + $v]);
                }
                break;
            case self::LIKE_COUNT:
                foreach ($data['cache'] as $k => $v) {
                    $ret = Book::getBookByIds($k);
                    $view_count = !empty($ret['LikeCount']) ? $ret['LikeCount'] : 0;
                    Book::updateBook($k, ['LikeCount' => $view_count + $v]);
                }
                break;
        }
    }
}