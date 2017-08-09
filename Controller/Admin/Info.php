<?php
/**
 * Info.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/7/31 18:52
 *
 */

namespace Controller\Admin;

use Bare\Controller;

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
        $this->view();
    }
}