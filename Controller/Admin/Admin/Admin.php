<?php
/**
 * Admin.php
 *
 * @author: camfee
 * @date  : 17-8-9 上午8:57
 *
 */

namespace Controller\Admin\Admin;

use Bare\AdminController;
use Model\Admin\Admin\AdminGroup;
use Model\Admin\Admin\AdminLogin;
use Model\Admin\Admin\AdminUser;
use Classes\Encrypt\Rsa;

class Admin extends AdminController
{
    public function index()
    {
        $now = intval($_GET[PAGE_VAR]) > 0 ? intval($_GET[PAGE_VAR]) : 1;
        $per = PAGE_SIZE;
        $offset = ($now - 1) * $per;
        $where = [];

        $user = AdminUser::getUsers($where, $offset, $per);

        $group = AdminGroup::getGroups();
        $groupselect = '';
        $group_hash = [];
        foreach ($group['data'] as $v) {
            $groupselect .= "<option value='{$v['GroupId']}'>{$v['GroupName']}</option>";
            $group_hash[$v['GroupId']] = $v['GroupName'];
        }
        foreach ($user['data'] as $k => $v) {
            $user['data'][$k]['GroupName'] = $group_hash[$v['UserGroup']];
        }

        $this->page($user['count'], $per, $now);
        $this->value("groupselect", $groupselect);
        $this->value("user", $user['data']);
        $this->view();
    }

    public function add()
    {
        $user_name = strval($_POST['user_name']);
        $pwd = strval($_POST['user_pwd']);
        $name = strval($_POST['true_name']);
        $groupid = intval($_POST['groupid']);
        $pwd = Rsa::private_decode($pwd);

        if (empty($pwd) || empty($user_name)) {
            output(201, '用户名或者密码填写有误');
        }
        $exist = AdminUser::getUserByName($user_name);
        if (!empty($exist)) {
            output(202, '此用户已经是管理员');
        }
        if ($groupid == SUPER_ADMIN_GROUP) {
            if ($_SESSION['_admin_info']['AdminUserGroup'] != SUPER_ADMIN_GROUP) {
                $this->alertErr('不能新增超级管理员');
            }
        }
        $data['UserName'] = $user_name;
        $data['Password'] = $pwd;
        $data['UserGroup'] = $groupid;
        $data['RealName'] = $name;
        $ret = AdminUser::addUser($data);

        if ($ret > 0) {
            $this->adminLog('添加管理员', 'add', $ret, $data, 'AdminUser');
            output(200, '添加成功');
        }
        output(203, '添加失败');
    }

    public function edit()
    {
        $uid = intval($_GET['id']);
        $user = AdminUser::getUserByIds($uid);

        if (isset($_POST['user_name'])) {
            if ($user['UserGroup'] == SUPER_ADMIN_GROUP) {
                if ($_SESSION['_admin_info']['AdminUserGroup'] != SUPER_ADMIN_GROUP) {
                    $this->alertErr('不能修改超级管理员');
                }
            }
            $data = [
                'UserGroup' => intval($_POST['auth_group']),
                'UserName' => strval($_POST['user_name']),
                'RealName' => strval($_POST['real_name']),
                'SpecialGroups' => $_POST['menu']
            ];
            if (!empty($_POST['user_pwd'])) {
                $data['Password'] = trim($_POST['user_pwd']);
            }

            $ret = AdminUser::updateUser($uid, $data);
            if ($ret !== false) {
                $this->adminLog('编辑管理员', 'update', $uid, $data, 'AdminUser');
                $this->alert('保存成功', url('index'));
            }
            $this->alertErr('保存失败');
        }

        $menu = AdminLogin::getAllAuthMenu();
        $auth = $user['SpecialGroups'];
        $auth = AdminLogin::getMenuByAuth($auth);
        $group_info = AdminGroup::getGroupByIds($user['UserGroup']);
        $group_auth = $group_info['AdminAuth'];
        $group_auth = AdminLogin::getMenuByAuth($group_auth);

        $group = AdminGroup::getGroups();
        $groupselect = '';
        foreach ($group['data'] as $v) {
            if ($user['UserGroup'] == $v['GroupId']) {
                $groupselect .= "<option value='{$v['GroupId']}' selected='selected'>{$v['GroupName']}</option>";
            } else {
                $groupselect .= "<option value='{$v['GroupId']}'>{$v['GroupName']}</option>";
            }
        }

        $this->value("user", $user);
        $this->value("menu", $menu);
        $this->value("auth", $auth);
        $this->value("group_auth", $group_auth);
        $this->value("groupselect", $groupselect);
        $this->view();
    }

    public function delete()
    {
        $uid = intval($_GET['id']);
        $user = AdminUser::getUserByIds($uid);
        if ($user['UserGroup'] == SUPER_ADMIN_GROUP) {
            if ($_SESSION['_admin_info']['AdminUserGroup'] != SUPER_ADMIN_GROUP) {
                $this->alertErr('不能删除超级管理员');
            }
            $supers = AdminUser::getUsersByGroupId(SUPER_ADMIN_GROUP);
            if ($supers['count'] <= 1) {
                $this->alertErr('不能删除全部的超级管理员');
            }
        }
        $ret = AdminUser::delUser($uid);
        if ($ret) {
            $this->adminLog('删除管理员', 'del', $uid, $uid, 'AdminUser');
            $this->alertMsg('删除成功');
        }
        $this->alertErr('删除失败，请重试');
    }
}