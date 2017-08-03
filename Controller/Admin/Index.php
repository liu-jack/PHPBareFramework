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
use Model\Admin\AdminLogin;

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
        redirect('admin/index/login');
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
}