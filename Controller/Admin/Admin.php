<?php
/**
 * Admin.php
 *
 * @author: camfee
 * @date  : 17-8-9 上午8:57
 *
 */

namespace Controller\Admin;

use Bare\Controller;
use Model\Admin\AdminGroup;
use Model\Admin\AdminLog;
use Model\Admin\AdminLogin;
use Model\Admin\AdminUser;
use Classes\Encrypt\Rsa;

class Admin extends Controller
{
    public function index()
    {
        $now = intval($_GET['p']) > 0 ? intval($_GET['p']) : 1;
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

        $this->pagination($user['count'], $per, $now);
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

        $data['UserName'] = $user_name;
        $data['Password'] = $pwd;
        $data['UserGroup'] = $groupid;
        $data['RealName'] = $name;
        $ret = AdminUser::addUser($data);

        if ($ret > 0) {
            AdminLog::log('添加管理员', 'add', $ret, $data, 'AdminUser');
            output(200, '添加成功');
        }
        output(203, '添加失败');
    }

    public function edit()
    {
        $uid = intval($_GET['id']);

        if (isset($_POST['user_name'])) {
            $data = [
                'UserGroup' => intval($_POST['auth_group']),
                'UserName' => strval($_POST['user_name']),
                'RealName' => strval($_POST['real_name']),
                'SpecialGroups' => $_POST['menu']
            ];

            $ret = AdminUser::updateUser($uid, $data);
            if ($ret !== false) {
                AdminLog::log('编辑管理员', 'update', $uid, $data, 'AdminUser');
                $this->alertMsg('保存成功', ['url' => url('index')]);
            }
            $this->alertMsg('保存失败', ['type' => 'error']);
        }

        $menu = AdminLogin::getAllAuthMenu();
        $user = AdminUser::getUserByIds($uid);
        $auth = $user['SpecialGroups'];
        $auth = array_combine((array)$auth, (array)$auth);
        $group_info = AdminGroup::getGroupByIds($user['UserGroup']);
        $group_auth = $group_info['AdminAuth'];
        $group_auth = array_combine((array)$group_auth, (array)$group_auth);

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
            $supers = AdminUser::getUsersByGroupId(SUPER_ADMIN_GROUP);
            if ($supers['count'] <= 1) {
                $this->alertMsg('不能删除全部的超级管理员');
            }
        }
        $ret = AdminUser::delUser($uid);
        if ($ret) {
            AdminLog::log('删除管理员', 'del', $uid, $uid, 'AdminUser');
            $this->alertMsg('删除成功');
        }
        $this->alertMsg('删除失败，请重试', ['type' => 'error']);
    }
}