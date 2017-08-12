<?php
/**
 * Info.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/7/31 18:52
 *
 */

namespace Controller\Admin\Admin;

use Bare\Controller;
use Model\Admin\Admin\AdminGroup;
use Model\Admin\Admin\AdminLogin;

class Info extends Controller
{
    public function index()
    {
        //客户端IP
        $this->value('remote_addr', ip());
        //服务器IP
        $this->value('server_addr', $_SERVER['SERVER_ADDR']);
        //操作系统版本
        $this->value('php_uname', php_uname());
        //PHP版本
        $this->value('php_version', PHP_VERSION);
        //当前时间
        $this->value('date', date("Y年m月d日 H:i:s"));
        //文档路径
        $this->value('ddir', ROOT_PATH);
        $this->view();
    }

    public function info()
    {
        $user = $_SESSION['_admin_info'];
        $menu = AdminLogin::getAllAuthMenu();
        $auth = AdminLogin::getAuthList();
        $group = AdminGroup::getGroupByIds($user['AdminUserGroup']);
        $user['GroupName'] = $group['GroupName'];

        $this->value('user', $user);
        $this->value('menu', $menu);
        $this->value('auth', $auth);
        $this->view();
    }
}