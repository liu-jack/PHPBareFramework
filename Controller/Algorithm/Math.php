<?php
/**
 * test.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-3-3 下午3:28
 *
 */

namespace Controller\Algorithm;

use Bare\Controller;
use Classes\Algorithm\Math as MMath;

class Math extends Controller
{
    public function index()
    {
        var_dump(MMath::x1p1(1, 2));
        var_dump(MMath::x1p2(1, -4, 4));
    }
}