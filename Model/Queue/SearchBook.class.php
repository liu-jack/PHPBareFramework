<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/26
 * Time: 15:50
 */

namespace Model\Queue;

use Model\Book\Search;

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
            Search::add($data['data']);
        } elseif ($data['type'] == 'update') {
            Search::update($data['data']);
        }

    }
}