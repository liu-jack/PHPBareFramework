<?php

namespace Controller\Test;

use Bare\C\Controller;
use Classes\Image\QrCode;

/**
 * 前端默认首页控制器
 */
class Image extends Controller
{

    public function index()
    {
        QrCode::instance()->png('http:://www.baidu.com', false, QR_ECLEVEL_M, 5);
        //$this->value('data',$data);
        //$this->view();
    }

    public function test()
    {
        var_dump($_SERVER);
    }
}