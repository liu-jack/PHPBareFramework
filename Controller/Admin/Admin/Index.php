<?php
/**
 * User: camfee
 * Date: 2017/5/23
 * Time: 19:30
 */

namespace Controller\Admin\Admin;

use Bare\Controller;
use Classes\Encrypt\Rsa;
use Classes\Image\Securimage;
use Model\Admin\Admin\AdminGroup;
use Model\Admin\Admin\AdminLogin;
use Model\Admin\Admin\AdminUser;
use Model\Admin\Admin\AdminLog;

class Index extends Controller
{
    /**
     * 首页
     */
    public function index()
    {
        if (!AdminLogin::isLogin()) {
            $this->alert('请先登录', url('admin/index/login'), '', 'top');
        }

        $menu = AdminLogin::getAuthMenu();
        $this->value('menu', $menu);
        $this->view();
    }

    /**
     * 刷新权限
     */
    public function refresh()
    {
        $uid = $_SESSION['_admin_info']['AdminUserId'];
        unset($_SESSION['_admin_auth_list'], $_SESSION['_admin_info']);
        session_destroy();
        if (empty($uid)) {
            redirect(url('admin/index/login'));
        }
        AdminLogin::isLogin($uid);
        exit();
    }

    /**
     * 修改密码
     */
    public function updatePwd()
    {
        $uid = $this->isLogin(2);
        if ($uid < 1) {
            $this->alert('请先登录', url('admin/index/login'));
        }
        if (!empty($_POST['user_pwd'])) {
            $pwd = strval($_POST['user_pwd']);
            $pwd2 = strval($_POST['user_pwd2']);
            if (empty($pwd) || empty($pwd2)) {
                output(201, '数据填写有误');
            }
            $pwd = Rsa::private_decode($pwd);
            $pwd2 = Rsa::private_decode($pwd2);
            if (empty($pwd)) {
                output(204, '密码数据错误');
            }
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

    /**
     * 登录
     */
    public function login()
    {
        if (!empty($_POST)) {
            $login = trim($_POST['username']);
            $pwd = trim($_POST['password']);
            $code = trim($_POST['code']);
            $pwd = Rsa::private_decode($pwd);
            $img = new Securimage();
            if ($img->check($code) === false) {
                output(201, '验证码错误');
            }
            $userinfo = AdminLogin::doLogin($login, $pwd);
            if (!empty($userinfo['UserId'])) {
                AdminLog::log('登录', 'login', $userinfo['UserId'], '', 'AdminUser');
                output(200, ['url' => url('admin/index/index')]);
            } else {
                $code = !empty($userinfo['code']) ? $userinfo['code'] : 205;
                $msg = !empty($userinfo['msg']) ? $userinfo['msg'] : '登录失败';
                output($code, $msg);
            }
        }
        $this->view();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        AdminLogin::logout();
        redirect(url('admin/index/login'));
    }

    /**
     * 输出验证码
     */
    public function code()
    {
        $options = [
            'image_width' => 96,
            'image_height' => 30,
            'text_scale' => 0.6, //字体比例
        ];
        $img = new Securimage($options);
        $img->show();
    }

    /*
     * 创建超级管理员 php index.php admin/index/addAdminUser
     */
    public function addAdminUser()
    {
        need_cli();
        $groupid = defined('SUPER_ADMIN_GROUP') ? SUPER_ADMIN_GROUP : 29;
        $username = 'camfee';
        $user = AdminUser::getUserByName($username);
        if (empty($user)) {
            $user = [
                'UserName' => $username,
                'Password' => 'camfee29',
                'RealName' => '管理员',
                'UserGroup' => $groupid
            ];

            $group = AdminGroup::getGroupByIds($groupid);
            if (empty($group)) {
                $group = [
                    'GroupId' => $groupid,
                    'GroupName' => '超级管理员',
                ];
                $ret = AdminGroup::addGroup($group);
                if (empty($ret)) {
                    exit("Create UserGroup Failed\n");
                }
            }
            $res = AdminUser::addUser($user);
            if (empty($res)) {
                exit("Create AdminUser Failed\n");
            } else {
                exit("Success\n");
            }
        }
        exit("Already Exists\n");
    }
}