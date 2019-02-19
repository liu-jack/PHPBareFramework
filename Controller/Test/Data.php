<?php

namespace Controller\Test;

use Bare\C\Controller;

/**
 * 前端默认首页控制器
 */
class Data extends Controller
{

    public function index()
    {
        $data = $this->_m->exportExcel([1, 2, 3, 4, 5]);
        //var_dump($data);
        //$this->value('data',$data);
        //$this->view();
    }

    public function test()
    {
        var_dump($_SERVER);
    }
}