<?php
/**
 *队列管理
 */

namespace Controller\Admin\Tool;

use Bare\DB;
use Bare\AdminController;
use Common\RedisConst;

class Queue extends AdminController
{
    public function index()
    {
        $queue = config('tool/queue');
        $redis = DB::redis(RedisConst::QUEUE_DB_R, RedisConst::QUEUE_DB_INDEX);
        //计算每个队列的长度
        foreach ($queue as $k => $v) {
            $size = $redis->lLen($v['queue_name']);
            $queue[$k]['size'] = $size;
        }

        $this->value('list', $queue);
        $this->view();
    }

    /**
     * 显示队列内容
     */
    public function info()
    {
        $queue_name = $_GET['queue_name'];
        $queue_size = $_GET['size'];
        $page = max(1, intval($_GET[PAGE_VAR]));
        $redis = DB::redis(RedisConst::QUEUE_DB_R, RedisConst::QUEUE_DB_INDEX);
        //列表中的元素
        $range = $redis->lRange($queue_name, ($page - 1) * PAGE_SIZE, $page * PAGE_SIZE);
        $this->value('queue_name', $queue_name);
        $this->value('list', $range);
        $this->page($queue_size, PAGE_SIZE, $page);

        $this->view();
    }

    public function delete()
    {
        $queue_name = trim($_POST['queue_name']);
        $redis = DB::redis(RedisConst::QUEUE_DB_W, RedisConst::QUEUE_DB_INDEX);
        $redis->delete($queue_name);
        $this->adminLog('清空队列', 'del', 0, $queue_name, 'queue');
        output(200, ['title' => '操作成功']);
    }
}