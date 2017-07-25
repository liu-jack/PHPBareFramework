<?php

namespace Controller\Test;

use Bare\Controller;

/**
 * 前端默认首页控制器
 */
class Preg extends Controller
{

    public function index()
    {
        $data = $this->m->test();
        var_dump($data);
        //$this->value('data',$data);
        //$this->view();
    }

    public function test()
    {
        var_dump($_SERVER);
    }
}