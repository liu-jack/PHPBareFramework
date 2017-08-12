<?php
/**
 * Auth.php 权限组管理
 *
 * @author: camfee <camfee@foxmail.com>
 * @date  : 17-8-10 下午12:30
 *
 */

namespace Controller\Admin\Admin;

use Bare\Controller;
use Model\Admin\Admin\AdminGroup;
use Model\Admin\Admin\AdminLog;
use Model\Admin\Admin\AdminLogin;
use Model\Admin\Admin\AdminUser;

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
            $this->alertErr('名称不能为空');
        }
        $check_name = AdminGroup::getGroupByName($name);
        if (!empty($check_name)) {
            $this->alertErr('名称已经存在');
        }
        $ret = AdminGroup::addGroup(['GroupName' => $name]);
        if ($ret) {
            AdminLog::log('添加权限组', 'add', $ret, $name, 'AdminGroup');
            $this->alertMsg('添加成功');
        }
        $this->alertErr('添加失败');
    }

    public function edit()
    {
        $id = intval($_GET['id']);

        $group = AdminGroup::getGroupByIds($id);
        if (empty($group)) {
            $this->alertErr('权限组不存在');
        }
        if (isset($_POST['menu'])) {
            $ret = AdminGroup::updateGroup($id, ['AdminAuth' => $_POST['menu']]);
            if ($ret !== false) {
                AdminLog::log('编辑权限组', 'update', $id, $_POST['menu'], 'AdminGroup');
                $this->alert('保存成功', url('index'));
            } else {
                $this->alertErr('保存失败');
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
            $this->alertErr('超级管理员不能删除');
        }
        $data = AdminUser::getUsersByGroupId($id);
        if (!empty($data['count'])) {
            $this->alertErr('删除失败', '', '该管理组中还有管理员，请先删除该组中的所有管理员！');
        }
        $ret = AdminGroup::delGroup($id);
        if ($ret) {
            AdminLog::log('删除权限组', 'del', $id, $id, 'AdminGroup');
            $this->alertMsg('删除成功');
        }
        $this->alertErr('删除失败');
    }
}