<?php

/**
 * 后台消息推送
 *
 * @author 周剑锋 <camfee@foxmail.com>
 */

namespace Controller\Admin\Mobile;

use Bare\AdminController;
use Model\Admin\Admin\AdminLog;
use Model\Mobile\AppPush;
use Model\Mobile\Device;
use Bare\DB;

class Push extends AdminController
{
    public function index()
    {
        $system = config('mobileapi/base');
        $this->value('system', $system);

        $types = [
            [
                'TypeId' => AppPush::PUSH_TYPE_MSG,
                'TypeName' => '推送纯消息',
            ],
            [
                'TypeId' => AppPush::PUSH_TYPE_URL,
                'TypeName' => '推送URL',
            ]
        ];
        $this->value('types', $types);

        $this->view();
    }

    /**
     *
     * POST
     * type:    推送消息类型
     * app_id:  推送平台
     * msg:     推送消息
     * data:    推送数据
     *
     * 公共消息推送
     *
     * @return string json
     */
    public function pushAll()
    {
        $type = (int)$_POST['type'];
        $app_id = (array)$_POST['appid'];
        $msg = trim($_POST['msg']);
        $data = trim($_POST['data']);

        if (!AppPush::checkType($type)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '请选择推送消息类型！']);
        }

        $count = count($app_id);
        if ($count < 1) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '平台选择有误！']);
        } else {
            $system = config('mobileapi/base');
            $system_id = array_column($system, 'AppId');
            foreach ($app_id as $v) {
                if (!in_array($v, $system_id)) {
                    $this->output(['status' => false, 'type' => 'error', 'msg' => '平台选择有误！']);
                }
            }
        }

        if (empty($msg)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送消息不能为空！']);
        }
        if ((in_array($type, [2, 3])) && empty($data)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送数据不能为空！']);
        }
        if ((in_array($type, [1])) && !empty($data)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送纯消息时数据只能为空！']);
        }
        $platform = [];
        foreach ($app_id as $v) {
            if ($v == APP_APPID_IOS) {
                $platform[] = 'ios';
            }
            if ($v == APP_APPID_ADR) {
                $platform[] = 'android';
            }
        }

        if ($type == 2) {
            $data = [
                AppPush::VAR_CONT => $data,
            ];
        }

        $result = AppPush::pushAll($type, $msg, $data, $platform);

        if ($result) {
            $this->output(['status' => true, 'type' => 'success', 'msg' => '推送成功！']);
        } else {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送失败，请稍后再试！']);
        }
    }

    /**
     * POST
     * type:    推送消息类型
     * app_id:  推送平台
     * msg:     推送消息
     * data:    推送数据
     *
     * 按用户类型推送
     *
     * @return string json
     */
    public function pushTag()
    {
        $type = (int)$_POST['type'];
        $app_id = (array)$_POST['appid'];
        $stag = (array)$_POST['tag'];
        $msg = trim($_POST['msg']);
        $data = trim($_POST['data']);

        if (!AppPush::checkType($type)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '请选择推送消息类型！']);
        }

        $count = count($app_id);
        if ($count < 1) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '平台选择有误！']);
        } else {
            $system = config('mobileapi/base');
            $system_id = array_column($system, 'AppId');
            foreach ($app_id as $v) {
                if (!in_array($v, $system_id)) {
                    $this->output(['status' => false, 'type' => 'error', 'msg' => '平台选择有误！']);
                }
            }
        }

        if (empty($msg)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送消息不能为空！']);
        }
        if ((in_array($type, [2, 3])) && empty($data)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送数据不能为空！']);
        }
        if ((in_array($type, [1])) && !empty($data)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送纯消息时数据只能为空！']);
        }
        if (empty($stag)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送用户类型不能为空！']);
        }
        $platform = [];
        foreach ($app_id as $v) {
            if ($v == APP_APPID_IOS) {
                $platform[] = 'ios';
            }
            if ($v == APP_APPID_ADR) {
                $platform[] = 'android';
            }
        }
        if ($type == 2) {
            $data = [
                AppPush::VAR_CONT => $data,
            ];
        }
        $stag = implode(',', $stag);
        $result = AppPush::pushTag($stag, $type, $msg, $data, $platform);

        if ($result) {
            $this->output(['status' => true, 'type' => 'success', 'msg' => '推送成功！']);
        } else {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送失败，请稍后再试！']);
        }
    }

    /**
     *
     * POST
     * type:    推送消息类型
     * app_id:  推送平台
     * msg:     推送消息
     * data:    推送数据
     * token:   用户ID
     *
     * 个人消息推送
     *
     * @return string json
     */
    public function pushPerson()
    {
        $type = (int)$_POST['type'];
        $app_id = (int)$_POST['appid'];
        $uid = (int)$_POST['uid'];
        $msg = trim($_POST['msg']);
        $data = trim($_POST['data']);
        $token = trim($_POST['token']);

        //参数验证
        $system = config('mobileapi/base');
        $system_id = array_column($system, 'AppId');
        if (!in_array($app_id, $system_id)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '平台选择有误！']);
        }
        if (empty($token) && $uid > 1) {
            $tokens = Device::getTokenByUserId($uid);
            if ($app_id == APP_APPID_IOS) {
                $token = $tokens['ios'] ?? '';
            } elseif ($app_id == APP_APPID_ADR) {
                $token = $tokens['android'] ?? '';
            }
            if (empty($token)) {
                $this->output(['status' => false, 'type' => 'error', 'msg' => '用户没有关联到手机或者已经退出登录！']);
            }
        }
        $cate = AppPush::checkType($type);
        if (!$cate) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '请选择推送消息类型！']);
        }
        if (empty($token)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '请选填写有效的用户识别标识！']);
        }
        if (empty($msg)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送消息不能为空！']);
        }
        if (in_array($type, [2, 3]) && empty($data)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送数据不能为空！']);
        }

        if ($type == 2) {
            $data = [
                AppPush::VAR_CONT => $data,
            ];
        }

        $result = false;
        if ($app_id == APP_APPID_IOS) {
            $result = AppPush::iOSPushOne($token, $type, $msg, $data);
        } elseif ($app_id == APP_APPID_ADR) {
            $result = AppPush::androidPushOne($token, $type, $msg, $data);
        }

        if ($result) {
            $this->output(['status' => true, 'type' => 'success', 'msg' => '推送成功！']);
        } else {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送失败，请稍后再试！']);
        }
    }

    /**
     * POST
     * tag :    推送用户类型标签
     * type:    推送消息类型
     * app_id:  推送平台
     * msg:     推送消息
     * data:    推送数据
     * settime  推送时间
     * 定时 公共|用户类型 消息推送
     *
     * @return string json
     */
    public function pushSetTime()
    {
        $type = (int)$_POST['type'];
        $app_id = (array)$_POST['appid'];
        $tag = $_POST['tag'] ?? [];
        $msg = trim($_POST['msg']);
        $data = trim($_POST['data']);
        $settime = trim($_POST['settime']);

        if (!in_array($type, [AppPush::PUSH_TYPE_MSG, AppPush::PUSH_TYPE_URL])) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '请选择推送消息类型！']);
        }
        $count = count($app_id);
        if ($count < 1) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '平台选择有误！']);
        } else {
            $system = config('mobileapi/base');
            $system_id = array_column($system, 'AppId');
            foreach ($app_id as $v) {
                if (!in_array($v, $system_id)) {
                    $this->output(['status' => false, 'type' => 'error', 'msg' => '平台选择有误！']);
                }
            }
        }
        if (empty($msg)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送消息不能为空！']);
        }
        if (in_array($type, [2, 3]) && empty($data)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送数据不能为空！']);
        }
        if ((in_array($type, [1])) && !empty($data)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送纯消息时数据只能为空！']);
        }
        if (empty($settime) || $settime <= date('Y-m-d H:i')) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '定时推送时间设置错误！']);
        }
        if (isset($_POST['tag']) && empty($tag)) {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '推送用户类型不能为空！']);
        }
        $platform = [];
        foreach ($app_id as $v) {
            if ($v == APP_APPID_IOS) {
                $platform[] = 'ios';
            }
            if ($v == APP_APPID_ADR) {
                $platform[] = 'android';
            }
        }
        if ($type == 2) {
            $data = [
                AppPush::VAR_CONT => $data,
            ];
        }
        $tag = implode(',', $tag);
        $cron_data = [
            'tag' => $tag,
            'type' => $type,
            'msg' => $msg,
            'data' => $data,
            'platform' => $platform
        ];
        $add_data['Type'] = 1;
        $add_data['Status'] = 0;
        $add_data['CronTime'] = $settime;
        $add_data['CronData'] = serialize($cron_data);
        $add_data['CreateTime'] = date('Y-m-d H:i:s');
        $pdo = DB::pdo(DB::DB_ADMIN_W);
        $result = $pdo->insert('AdminCron', $add_data);

        if ($result) {
            AdminLog::log('设置定时推送', 'add', $pdo->lastInsertId(), $add_data, 'AdminCron');
            $this->output(['status' => true, 'type' => 'success', 'msg' => '设置定时推送成功！']);
        } else {
            $this->output(['status' => false, 'type' => 'error', 'msg' => '设置定时推送失败，请稍后再试！']);
        }
    }
}
