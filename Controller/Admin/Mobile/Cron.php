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
    protected static $_list_extra = [
        AdminCron::EXTRA_LIST_DEL,
    ];

    protected static $_search_val = [
        AdminCron::FD_TYPE => AdminCron::TYPE_PUSH,
        AdminCron::FD_STATUS => AdminCron::STATUS_WAIT
    ];

    public function __construct()
    {
        parent::__construct();
        if (empty($this->_m)) {
            $this->_m = new AdminCron();
        }
    }

    public function delete()
    {
        $id = intval($_GET['id']);
        $info = $this->_m::getInfoByIds($id);
        if (!empty($info) && $info[$this->_m::FD_STATUS] != $this->_m::STATUS_WAIT) {
            $this->alertErr('不能删除已操作数据');
        }
        parent::adminDelete();
    }
}