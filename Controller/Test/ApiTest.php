<?php

namespace Controller\Test;

use Bare\Api;
use Bare\Controller;

/**
 * 测试用控制器
 */
class ApiTest extends Controller
{
    public function index()
    {
        //$url = Api::getUrl('Book/Index/getIndex');
        //$url = Api::getUrl('Book/Index/getList', ['tid' => 1,'offset' => 0]);
        //$url = Api::getUrl('Book/Index/getColumn', ['bid' => 258, 'fid' => 83]);
        //$url = Api::getUrl('Book/Index/getContent', ['bid' => 258, 'cid' => 30]);
        //$url = Api::getUrl('Geo/Geography/getProvinceList');
        $url = Api::getUrl('Book/Index/search', ['str' => '凡人']);
        pre($url);
        die;
        $ret = Api::request($url);
        pre($ret);
    }
}