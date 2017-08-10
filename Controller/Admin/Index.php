<?php
/**
 * User: camfee
 * Date: 2017/5/23
 * Time: 19:30
 */

namespace Controller\Admin;

use Bare\Controller;
use Classes\Encrypt\Rsa;
use Classes\Image\Securimage;
use Model\Admin\AdminGroup;
use Model\Admin\AdminLogin;
use Model\Admin\AdminUser;

class Index extends Controller
{
    /**
     * 首页
     */
    public function index()
    {
        if (!AdminLogin::isLogin()) {
            $this->alertMsg('请先登录', ['url' => url('admin/index/login')]);
        }

        $menu = AdminLogin::getAuthMenu();
        $this->value('menu', $menu);
        $this->view();
    }

    /**
     * 刷新权限
     */
    public function resfresh()
    {
        $uid = $_SESSION['AdminUserId'];
        if (empty($uid)) {
            session_destroy();
            redirect(url('admin/index/login'));
        }
        AdminLogin::isLogin($uid);
        exit();
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