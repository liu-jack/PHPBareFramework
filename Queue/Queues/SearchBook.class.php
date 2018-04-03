<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/26
 * Time: 15:50
 */

namespace Queue\Queues;

use Model\Search\BookSearch as SBook;
use Queue\Queue;

class SearchBook extends Queue
{
    /**
     * 用于处理队列返回的数据
     *
     * @param array $data 队列中存入的数据
     */
    public function run($data)
    {
        if ($data['type'] == 'add') {
            SBook::add($data['data']);
        } elseif ($data['type'] == 'update') {
            SBook::update($data['data']);
        }

    }
}