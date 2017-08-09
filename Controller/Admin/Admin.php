<?php
/**
 * Admin.php
 *
 * @author: camfee
 * @date: 17-8-9 上午8:57
 *
 */

namespace Controller\Admin;

use Bare\Controller;

class Admin extends Controller
{
    public function index()
    {
        $this->view();
    }
}