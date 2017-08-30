<?php
/**
 * SmsLog.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-8-30 下午3:08
 *
 */

namespace Controller\Admin\Admin;

use Bare\Controller;

class SmsLog extends Controller
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
                $sub_cont = mb_substr($v['Content'], 0, 80);
                $list[$k]['Content'] = '<a title="' . htmlspecialchars($v['Content']) . '">' . $sub_cont . '</a>';
            }
        }

        $list_search = $this->_m->createSearch();
        $list_title = '短信日志';
        $list_list = $this->_m->createList($list);

        $this->value('list_search', $list_search);
        $this->value('list_title', $list_title);
        $this->value('list_list', $list_list);
        $this->view('Public/list');
    }
}