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
        pre($_SERVER);
        pre($_SERVER);
        pre($_SERVER);
        pre($_SERVER);die;
        $this->view();
    }

    public function info()
    {
        var_dump($_SERVER);die;
        $this->view();
    }
}