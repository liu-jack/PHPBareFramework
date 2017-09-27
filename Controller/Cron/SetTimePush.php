<?php

/**
 * 每3分运行后台定时推送
 *
 * @author camfee<camfee@foxmail.com>
 */

use Bare\Controller;
use Model\Mobile\AppPush;
use Model\Admin\Admin\AdminCron;

class SetTimePush extends Controller
{
    /**
     * 定时推送 php index.php Cron/SetTimePush/index
     */
    public function index()
    {
        $select_date = date('Y-m-d H:i:s', time() + 600);
        $where = [
            AdminCron::FD_TYPE => AdminCron::TYPE_PUSH,
            AdminCron::FD_STATUS => AdminCron::STATUS_WAIT,
            AdminCron::FD_CRON_TIME . ' <=' => $select_date
        ];
        $fields = AdminCron::FD_CRON_ID . ',' . AdminCron::FD_CRON_DATA . ',' . AdminCron::FD_CRON_TIME;
        $data = AdminCron::getList($where, 0, 99, $fields);
        if (!empty($data)) {
            foreach ($data as $v) {
                if ($v[AdminCron::FD_CRON_TIME] <= date('Y-m-d H:i:s', time() + 180)) {
                    $cron_data = unserialize($v[AdminCron::FD_CRON_DATA]);
                    if (!empty($cron_data['tag'])) { // 按用户类型推送
                        $ret = AppPush::pushTag($cron_data['tag'], $cron_data['type'], $cron_data['msg'],
                            $cron_data['data'], $cron_data['platform']);
                    } else { // 全体推送
                        $ret = AppPush::pushAll($cron_data['type'], $cron_data['msg'], $cron_data['data'],
                            $cron_data['platform']);
                    }
                    if ($ret) {
                        AdminCron::update($v[AdminCron::FD_CRON_ID], [AdminCron::FD_STATUS => AdminCron::STATUS_OK]);
                    } else {
                        AdminCron::update($v[AdminCron::FD_CRON_ID], [AdminCron::FD_STATUS => AdminCron::STATUS_FAIL]);
                    }
                }
                usleep(100000);
            }
        }
        exit('finished');
    }
}
