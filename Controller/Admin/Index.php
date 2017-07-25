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

class Index extends Controller
{
    /**
     * 首页
     */
    public function index()
    {
        $this->view();
    }

    /**
     * 登录
     */
    public function login()
    {

    }

    /**
     * 退出登录
     */
    public function logout()
    {

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