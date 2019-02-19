<?php

namespace Controller\Account;

use Bare\C\Controller;
use Model\Passport\Login;
use Model\Passport\Register;
use Classes\Encrypt\Rsa;
use Classes\Image\Securimage;

class User extends Controller
{
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
            $userinfo = Login::doLogin($login, $pwd);
            if (!empty($userinfo['UserId'])) {
                output(200, ['url' => url('book/index/shelf')]);
            } else {
                $code = !empty($userinfo['code']) ? $userinfo['code'] : 205;
                $msg = !empty($userinfo['msg']) ? $userinfo['msg'] : '登录失败';
                output($code, $msg);
            }
        }

        $this->view();
    }

    /**
     * 注册新用户
     */
    public function reg()
    {
        if (!empty($_POST)) {
            $username = trim($_POST['username']);
            $pwd = trim($_POST['password']);
            $pwd2 = trim($_POST['password2']);
            $code = trim($_POST['code']);
            $img = new Securimage();
            if ($img->check($code) === false) {
                output(201, '验证码错误');
            }
            $pwd = Rsa::private_decode($pwd);
            $pwd2 = Rsa::private_decode($pwd2);
            if ($pwd !== $pwd2) {
                output(202, '两次输入的密码不一致');
            }
            $data = [
                'UserName' => $username,
                'Password' => $pwd,
                'FromPlatform' => Register::REG_PLATFORM_WEB,
                'FromProduct' => Register::REG_FROM_PASSPORT,
                'FromWay' => Register::REG_WAY_USERNAME,

            ];
            $userinfo = Register::addUser($data);
            if (!empty($userinfo['UserId'])) {
                Login::initSession($userinfo);
                Login::initCookie($userinfo['UserId']);
                output(200, ['url' => '/']);
            } else {
                $code = !empty($userinfo['code']) ? $userinfo['code'] : 225;
                $msg = !empty($userinfo['msg']) ? $userinfo['msg'] : '注册失败';
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
        Login::logout();
        redirect('/');
    }

    /**
     * 输出验证码
     */
    public function code()
    {
        $options = [
            'image_width' => 96,
            'image_height' => 42,
            'text_scale' => 0.5, //字体比例
        ];
        $img = new Securimage($options);
        $img->show();
    }
}