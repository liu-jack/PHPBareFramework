<?php
/**
 * AdminLog.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-8-9 下午9:14
 *
 */

namespace Controller\Admin\Admin;

use Bare\Controller;

class AdminLog extends Controller
{
    public function index()
    {
        $page = max(1, intval($_GET[PAGE_VAR]));
        $where = $this->_m->createWhere();

        $limit = PAGE_SIZE;
        $offset = ($page - 1) * $limit;
        $list_info = $this->_m->getList($where, $offset, $limit);

        $this->page(intval($list_info['count']), $limit, $page);
        $list = [];
        if (!empty($list_info['data'])) {
            foreach ($list_info['data'] as $k => $v) {
                $list[$k] = $v;
                $sub_log = mb_substr($v['Log'], 0, 50);
                $list[$k]['Log'] = '<a title="' . htmlspecialchars($v['Log']) . '">' . $sub_log . '</a>';
            }
        }

        $list_search = $this->_m->createSearch();
        $list_title = '后台操作日志';
        $list_list = $this->_m->createList($list);

        $this->value('list_search', $list_search);
        $this->value('list_title', $list_title);
        $this->value('list_list', $list_list);
        $this->view('Public/list');
    }
}