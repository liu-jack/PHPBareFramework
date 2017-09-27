<?php
/**
 * Cron.php 定时任务管理
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-9-27
 *
 */

namespace Controller\Admin\Mobile;

use Bare\AdminController;
use Model\Admin\Admin\AdminCron;

class Cron extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (empty($this->_m)) {
            $this->_m = new AdminCron();
        }
    }
}