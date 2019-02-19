<?php
/**
 * Math.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-3-3 下午3:28
 *
 */

namespace Controller\Algorithm;

use Bare\C\Controller;
use Classes\Algorithm\Math as MMath;
use Classes\Algorithm\Sort;

class Math extends Controller
{
    public function index()
    {
//        pre(MMath::x1p1(1, 2));
//        pre(MMath::x1p2(1, -4, 4));
//        pre(PHP_INT_MAX);
        $str = 'abcdefghijklmnopqrstuvwxyz';
        $arr = [];
        for ($i = 0; $i < 20; $i++) {
            $arr[$i . $str{mt_rand(1,26)}] = mt_rand(1, 9999);
        }
//        pre($arr, Sort::bubble($arr));
//        pre($arr, Sort::selection($arr));
//        pre($arr, Sort::insertion($arr));
        pre($arr, Sort::shell($arr, 3));
    }
}