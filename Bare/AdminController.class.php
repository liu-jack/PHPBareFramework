<?php
/**
 * 后台基类控制器
 *
 * @author camfee<camfee@yeah.net>
 * @since  v1.0 2017.09.04
 */

namespace Bare;

use Model\Admin\Admin\AdminLogin;
use Model\Admin\Admin\AdminLog;

Class AdminController extends Controller
{
    protected static $_list_extra = []; // 列表扩展设置
    protected static $_search_val = []; // 搜索默认选项
    protected static $_form_val = [];   // 列表默认选项

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        parent::__construct();
        if ($GLOBALS['_C'] != 'Index') {
            if (!self::isLogin(V_ADMIN)) {
                $this->alert('请先登录', url('admin/index/login'));
            } elseif (!AdminLogin::isHasAuth()) {
                if (IS_AJAX) {
                    output(403, '没有访问权限');
                } else {
                    $this->alertErr('没有权限', url('admin/index/index'), '禁止访问', 'top');
                }
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
        if (method_exists($this, $action)) {
            $this->$action();
        } else {
            show404();
        }
    }

    /**
     * 后台列表
     */
    public function adminIndex()
    {
        $page = max(1, intval($_GET[PAGE_VAR]));
        $where = $this->_m->createWhere(static::$_search_val);
        $limit = PAGE_SIZE;
        $offset = ($page - 1) * $limit;
        $list_info = $this->_m->getList($where, $offset, $limit);

        $this->page(intval($list_info['count']), $limit, $page);
        $list = [];
        if (!empty($list_info['data'])) {
            $_sub_fields = ['Content', 'Log', 'Info', 'CronData'];
            foreach ($list_info['data'] as $k => $v) {
                $list[$k] = $v;
                foreach ($_sub_fields as $sv) {
                    if (isset($v[$sv])) {
                        $sub_cont = mb_substr($v[$sv], 0, 50);
                        $list[$k][$sv] = '<a title="' . htmlspecialchars($v[$sv]) . '">' . $sub_cont . '</a>';
                    }
                }
            }
        }

        $list_search = $this->_m->createSearch(static::$_search_val);
        $list_title = '列表';
        $list_list = $this->_m->createList($list, static::$_list_extra);

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
        $info_form = $this->_m->createForm(static::$_form_val);

        $this->value('info_title', $info_title);
        $this->value('info_form', $info_form);
        $this->view('Public/info');
    }

    /**
     * 后台删除
     */
    public function adminDelete()
    {
        $id = intval($_GET['id']);
        if ($id > 0) {
            $ret = $this->_m->delete($id);
            if ($ret !== false) {
                AdminLog::log('删除' . $this->_m::TABLE_REMARK, 'del', $id, $id, $this->_m::TABLE);
                $this->alert('删除成功');
            }
            $this->alertErr('删除失败');
        }
        $this->alertErr('参数错误');
    }
}
