<?php
/**
 * 后台基类控制器
 *
 * @author camfee<camfee@yeah.net>
 * @since  v1.0 2017.09.04
 */

namespace Bare;

use Model\Admin\Admin\AdminLogin;

Class AdminController extends Controller
{
    /**
     * Controller constructor.
     */
    public function __construct()
    {
        parent::__construct();
        if ($GLOBALS['_C'] != 'Index') {
            if (!self::isLogin(2)) {
                $this->alert('请先登录', url('admin/index/login'));
            } elseif (!AdminLogin::isHasAuth()) {
                $this->alertErr('没有权限', url('admin/index/index'), '', 'top');
            }
        }

    }

    /**
     * 后台默认方法调用
     *
     * @param string $method 方法
     * @param array  $args   参数
     * @return void
     */
    public function __call($method, $args)
    {
        $action = 'admin' . ucfirst($GLOBALS['_A']);
        $this->$action();
    }

    /**
     * 后台列表
     */
    public function adminIndex()
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
                if (isset($v['Content'])) {
                    $sub_cont = mb_substr($v['Content'], 0, 80);
                    $list[$k]['Content'] = '<a title="' . htmlspecialchars($v['Content']) . '">' . $sub_cont . '</a>';
                }
                if (isset($v['Log'])) {
                    $sub_cont = mb_substr($v['Log'], 0, 80);
                    $list[$k]['Log'] = '<a title="' . htmlspecialchars($v['Log']) . '">' . $sub_cont . '</a>';
                }
                if (isset($v['Info'])) {
                    $sub_cont = mb_substr($v['Info'], 0, 80);
                    $list[$k]['Info'] = '<a title="' . htmlspecialchars($v['Info']) . '">' . $sub_cont . '</a>';
                }
            }
        }

        $list_search = $this->_m->createSearch();
        $list_title = '列表';
        $list_list = $this->_m->createList($list);

        $this->value('list_search', $list_search);
        $this->value('list_title', $list_title);
        $this->value('list_list', $list_list);
        $this->view('Public/list');
    }

    /**
     * 后台编辑
     */
    public function adminEdit()
    {
        $id = intval($_GET['id']);

        $info = $this->_m->getInfoByIds($id);
        $info_title = '编辑';
        $info_form = $this->_m->createForm($info);

        $this->value('info_title', $info_title);
        $this->value('info_form', $info_form);
        $this->view('Public/info');
    }

    /**
     * 后台新增
     */
    public function adminAdd()
    {
        $info_title = '添加';
        $info_form = $this->_m->createForm();

        $this->value('info_title', $info_title);
        $this->value('info_form', $info_form);
        $this->view('Public/info');
    }
}
