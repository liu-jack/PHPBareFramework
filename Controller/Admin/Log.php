<?php
/**
 * Log.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date 17-8-9 下午9:14
 *
 */

namespace Controller\Admin;

use Bare\Controller;
use Model\Admin\AdminLog;

class Log extends Controller
{
    public function index()
    {
        $page = max(1, intval($_GET['p']));
        $limit = 10;
        $where = [];

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
        $this->value('list', $list);
        $this->view();
    }
}