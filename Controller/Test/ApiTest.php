<?php

namespace Controller\Test;

use Bare\Api;
use Bare\C\Controller;

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
        //        $url = Api::getUrl('Geo/Geography/getProvinceList', ['a' => 1, 'b' => 2, 'c' => 3]);
        //        Api::$verid = 'v1.2.0';
        //        $url = Api::getUrl('Book/Index/getIndex', ['str' => '凡人']);
        //        Api::$verid = 'v1.1.0';
        //        $url1 = Api::getUrl('Book/Index/getIndex', ['str' => '凡人']);
        //        $url = Api::getUrl('Common/Init/start');
        //        $url = Api::getUrl('Common/Init/checkVersion');
        //        $url = Api::getUrl('Geo/Geography/getProvinceList');
        //        $url = Api::getUrl('Geo/Geography/getAllRegions');
        //        $url = Api::getUrl('Account/User/loginByCode');
        //        $post = [
        //            'mobile' => '18574611486',
        //            'code' => '888888',
        //        ];
        $url = Api::getUrl('Account/Account/accountInfo');
        $post['header'] = [
            'auth:399cf51fa0bf7b266d235e797a618e6fe0f181d8d14e184e'
        ];
        $ret = Api::request($url, $post);
        //$ret = Api::request($url);
        pre($url, $ret);
    }
}