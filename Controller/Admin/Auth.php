<?php
/**
 * Auth.php
 *
 * @author: camfee <camfee@foxmail.com>
 * @date  : 17-8-10 下午12:30
 *
 */

namespace Controller\Admin;

use Bare\Controller;
use Model\Admin\AdminGroup;
use Model\Admin\AdminLog;
use Model\Admin\AdminLogin;
use Model\Admin\AdminUser;

class Auth extends Controller
{
    public function index()
    {
        $group = AdminGroup::getGroups();

        $this->value("group", $group['data']);
        $this->view();
    }

    public function add()
    {
        $name = strval($_POST['group_name']);
        if (empty($name)) {
            $this->alertMsg('名称不能为空', ['type' => 'error']);
        }
        $check_name = AdminGroup::getGroupByName($name);
        if (!empty($check_name)) {
            $this->alertMsg('名称已经存在', ['type' => 'error']);
        }
        $ret = AdminGroup::addGroup(['GroupName' => $name]);
        if ($ret) {
            AdminLog::log('添加权限组', 'add', $ret, $name, 'AdminGroup');
            $this->alertMsg('添加成功');
        }
        $this->alertMsg('添加失败', ['type' => 'error']);
    }

    public function edit()
    {
        $id = intval($_GET['id']);

        $group = AdminGroup::getGroupByIds($id);
        if (empty($group)) {
            $this->alertMsg('权限组不存在', ['type' => 'error']);
        }
        if (isset($_POST['menu'])) {
            $ret = AdminGroup::updateGroup($id, ['AdminAuth' => $_POST['menu']]);
            if ($ret !== false) {
                AdminLog::log('编辑权限组', 'update', $id, $_POST['menu'], 'AdminGroup');
                $this->alertMsg('保存成功', ['url' => url('index')]);
            } else {
                $this->alertMsg('保存失败', ['type' => 'error']);
            }
        }

        $auth = $group['AdminAuth'];
        $auth = array_combine((array)$auth, (array)$auth);
        $menu = AdminLogin::getAllAuthMenu();


        $this->value('group', $group);
        $this->value('auth', $auth);
        $this->value('menu', $menu);
        $this->view();
    }

    public function delete()
    {
        $id = intval($_GET['id']);
        if (defined('SUPER_ADMIN_GROUP') && $id == SUPER_ADMIN_GROUP) {
            $this->alertMsg('超级管理员不能删除');
        }
        $data = AdminUser::getUsersByGroupId($id);
        if (!empty($data['count'])) {
            $this->alertMsg('删除失败', ['type' => 'error', 'desc' => '该管理组中还有管理员，请先删除该组中的所有管理员！']);
        }
        $ret = AdminGroup::delGroup($id);
        if ($ret) {
            AdminLog::log('删除权限组', 'del', $id, $id, 'AdminGroup');
            $this->alertMsg('删除成功');
        }
        $this->alertMsg('删除失败', ['type' => 'error']);
    }
}