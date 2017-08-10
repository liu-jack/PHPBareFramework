<?php
/**
 * Auth.php
 *
 * @author: camfee <camfee@foxmail.com>
 * @date: 17-8-10 下午12:30
 *
 */

namespace Controller\Admin;

use Bare\Controller;
use Model\Admin\AdminGroup;
use Model\Admin\AdminLog;

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
        $ret = AdminGroup::addGroup(['GroupName' => $name]);
        if ($ret) {
            AdminLog::log('添加权限组','add', $ret, $name, 'AdminGroup');
            $this->alertMsg('添加成功');
        }
        $this->alertMsg('添加失败', ['type' => 'error']);
    }
}