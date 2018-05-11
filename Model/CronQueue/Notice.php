<?php
/**
 *  小程序通知 Notice.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   2018/1/2
 */

namespace Model\CronQueue;

use Bare\DB;

class Notice extends AbstractQueue
{
    public function run($data)
    {
        pre($data);
        // TODO 业务逻辑
    }
}

