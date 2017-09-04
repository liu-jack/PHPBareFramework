<?php
/**
 * Menu.php
 *
 * @author: camfee <camfee@foxmail.com>
 * @date  : 17-8-9 下午4:35
 *
 */

namespace Controller\Admin\Admin;

use Bare\AdminController;
use Model\Admin\Admin\AdminGroup;
use Model\Admin\Admin\AdminLog;
use Model\Admin\Admin\AdminLogin;
use Model\Admin\Admin\AdminMenu;

class Menu extends AdminController
{
    public function index()
    {
        $menu = AdminLogin::getAuthMenu(-2);
        $onemenu = $twomenu[0] = "<option value='0'>主菜单</option>";

        foreach ($menu[0] as $v) {
            $id = $v['AdminMenuId'];
            $onemenu .= "<option value='$id'>-- " . $v['Name'] . "</option>";
            $twomenu[$id] = "<option value='$id'>" . $v['Name'] . "</option>";

            $child = isset($menu[$id]) ? $menu[$id] : [];
            foreach ($child as $value) {
                $cid = $value['AdminMenuId'];
                $twomenu[$id] .= "<option value='$cid'>-- " . $value['Name'] . "</option>";
            }
        }

        $this->value("menu", $menu);
        $this->value("onemenu", $onemenu);
        $this->value("twomenu", json_encode($twomenu));
        $this->view();
    }

    public function update()
    {
        $data['Name'] = strval($_POST['name']);
        $data['Url'] = strval($_POST['url']);
        $data['ParentId'] = intval($_POST['parent']);
        $data['DisplayOrder'] = intval($_POST['order']);
        $id = intval($_POST['id']);

        if ($data['ParentId'] != 0) {
            $parent_menu = AdminMenu::getMenuByIds($data['ParentId']);
            if (empty($parent_menu)) {
                output(201, '上级菜单不存在');
            }
        }

        if ($id == 0) {
            $url_check = AdminMenu::getMenuByUrl($data['Url']);
            if (!empty($url_check)) {
                output(202, '此URL已存在');
            }
            //同名菜单验证
            $menu_check = AdminMenu::getMenusByParentId($data['ParentId'], $data['Name']);
            if (!empty($menu_check)) {
                output(203, '同级菜单下已存在此菜单');
            }
            $ret = AdminMenu::addMenu($data);
            if ($ret) {
                AdminLog::log('添加菜单', 'add', $ret, $data, 'AdminMenu');
                //将此菜单权限加入当前登录管理员所在的权限组
                $group = AdminGroup::getGroupByIds($_SESSION['_admin_info']['AdminUserGroup']);
                $group_auth = $group['AdminAuth'];
                $group_auth[] = $ret;
                AdminGroup::updateGroup($group['GroupId'], ['AdminAuth' => $group_auth]);
            }
        } else {
            $ret = AdminMenu::updateMenu($id, $data);
            if ($ret) {
                AdminLog::log('更新菜单', 'update', $id, $data, 'AdminMenu');
            }

        }
        if ($ret !== false) {
            output(200, '操作成功');
        }
        output(204, '操作失败');
    }

    public function delete()
    {
        $id = intval($_GET['id']);

        $data = AdminMenu::getMenusByParentId($id);
        if (count($data) > 0) {
            $this->alertErr('删除失败', '', '该菜单下还有其他菜单，不可删除！');
        }

        $ret = AdminMenu::delMenu($id);
        if ($ret > 0) {
            AdminLog::log('删除菜单', 'del', $id, $id);
            $this->alertMsg('已删除');
        }
        $this->alertErr('删除失败');
    }
}