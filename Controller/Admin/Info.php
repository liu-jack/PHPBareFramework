<?php
/**
 * Info.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/7/31 18:52
 *
 */

namespace Controller\Admin;

use Bare\Controller;
use Model\Admin\AdminGroup;
use Model\Admin\AdminLogin;
use Classes\Encrypt\Rsa;
use Model\Admin\AdminUser;
use Model\Admin\AdminLog;

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

    public function updatePwd()
    {
        $uid = $this->isLogin(2);
        if ($uid < 1) {
            $this->alertMsg('请先登录', ['url' => url('admin/index/login')]);
        }
        if (!empty($_POST['user_pwd'])) {
            $pwd = strval($_POST['user_pwd']);
            $pwd2 = strval($_POST['user_pwd2']);
            if (empty($pwd) || empty($pwd2)) {
                output(201, '数据填写有误');
            }
            $pwd = Rsa::private_decode($pwd);
            $pwd2 = Rsa::private_decode($pwd2);
            if ($pwd2 != $pwd) {
                output(202, '密码填写不一致');
            }

            $data['Password'] = $pwd;
            $ret = AdminUser::updateUser($uid, $data);

            if ($ret) {
                AdminLog::log('修改密码', 'updatePwd', $uid, '', 'AdminUser');
                output(200, '修改成功');
            }
            output(203, '修改失败');
        }
        $user = AdminUser::getUserByIds($uid);
        $group = AdminGroup::getGroupByIds($user['UserGroup']);
        $user['GroupName'] = $group['GroupName'];

        $this->value('user', $user);
        $this->view();
    }
}