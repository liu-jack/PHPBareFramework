<?php
/**
 * Log.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-8-9 下午9:14
 *
 */

namespace Controller\Admin;

use Bare\Controller;
use Model\Admin\AdminLog;

class Log extends Controller
{
    public function index()
    {
        $user_id = intval($_GET['user_id']);
        $user_name = trim($_GET['user_name']);
        $item_id = intval($_GET['item_id']);
        $item_name = trim($_GET['item_name']);
        $menu_url = trim($_GET['menu_url']);
        $log_flag = trim($_GET['log_flag']);
        $start_time = trim($_GET['start_time']);
        $end_time = trim($_GET['end_time']);
        $page = max(1, intval($_GET['p']));
        $limit = PAGE_SIZE;
        $where = [];
        if (!empty($user_id)) {
            $where['UserId'] = $user_id;
        }
        if (!empty($menu_url)) {
            $where['MenuKey'] = $menu_url;
        }
        if (!empty($user_name)) {
            $where['UserName'] = $user_name;
        }
        if (!empty($item_id)) {
            $where['ItemId'] = $item_id;
        }
        if (!empty($item_name)) {
            $where['ItemName'] = $item_name;
        }
        if (!empty($log_flag)) {
            $where['LogFlag'] = $log_flag;
        }
        if (!empty($start_time)) {
            $where['CreateTime >='] = $start_time;
        }
        if (!empty($end_time)) {
            $where['CreateTime <='] = $end_time;
        }

        $offset = ($page - 1) * $limit;
        $list_info = AdminLog::getLogs($where, $offset, $limit);

        $this->pagination(intval($list_info['count']), $limit, $page);
        $list = [];
        if (!empty($list_info['data'])) {
            foreach ($list_info['data'] as $k => $v) {
                $list[$k] = $v;
                $list[$k]['LogSub'] = mb_substr($v['Log'], 0, 50);
                $list[$k]['Log'] = htmlspecialchars($v['Log']);
            }
        }

        $this->value('user_id', $user_id);
        $this->value('menu_url', $menu_url);
        $this->value('user_name', $user_name);
        $this->value('item_id', $item_id);
        $this->value('item_name', $item_name);
        $this->value('log_flag', $log_flag);
        $this->value('start_time', $start_time);
        $this->value('end_time', $end_time);
        $this->value('list', $list);
        $this->view();
    }
}