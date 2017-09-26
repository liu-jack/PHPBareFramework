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
    private static $system = [
        APP_APPID_IOS => [
            'AppId' => APP_APPID_IOS,
            'AppName' => 'IOS'
        ],
        APP_APPID_ADR => [
            'AppId' => APP_APPID_ADR,
            'AppName' => 'Android'
        ],
    ];

    public function index()
    {
        $this->value('system', self::$system);
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
     */
    public function pushAll()
    {
        $type = (int)$_POST['type'];
        $app_id = (array)$_POST['appid'];
        $msg = trim($_POST['msg']);
        $data = trim($_POST['data']);

        self::checkParam($type, $app_id, $msg, $data);

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
            output(['status' => true, 'type' => 'success', 'msg' => '推送成功！']);
        } else {
            output(['status' => false, 'type' => 'error', 'msg' => '推送失败，请稍后再试！']);
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
     */
    public function pushTag()
    {
        $type = (int)$_POST['type'];
        $app_id = (array)$_POST['appid'];
        $stag = (array)$_POST['tag'];
        $msg = trim($_POST['msg']);
        $data = trim($_POST['data']);

        self::checkParam($type, $app_id, $msg, $data);

        if (empty($stag)) {
            output(201, '推送用户类型不能为空！');
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
        if ($type == AppPush::PUSH_TYPE_URL) {
            $data = [
                AppPush::VAR_CONT => $data,
            ];
        }
        $stag = implode(',', $stag);
        $result = AppPush::pushTag($stag, $type, $msg, $data, $platform);

        if ($result) {
            output(200, '推送成功！');
        } else {
            output(201, '推送失败，请稍后再试！');
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
     */
    public function pushPerson()
    {
        $type = (int)$_POST['type'];
        $app_id = (int)$_POST['appid'];
        $uid = (int)$_POST['uid'];
        $msg = trim($_POST['msg']);
        $data = trim($_POST['data']);
        $token = trim($_POST['token']);

        self::checkParam($type, $app_id, $msg, $data);

        if (empty($token) && $uid > 1) {
            $tokens = Device::getTokenByUserId($uid);
            if ($app_id == APP_APPID_IOS) {
                $token = $tokens['ios'] ?? '';
            } elseif ($app_id == APP_APPID_ADR) {
                $token = $tokens['android'] ?? '';
            }
            if (empty($token)) {
                output(201, '用户没有关联到手机或者已经退出登录！');
            }
        }
        if (empty($token)) {
            output(201, '请选填写有效的用户识别标识！');
        }

        if ($type == AppPush::PUSH_TYPE_URL) {
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
            output(200, '推送成功！');
        } else {
            output(201, '推送失败，请稍后再试！');
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
     *
     * 定时 公共|用户类型 消息推送
     *
     */
    public function pushSetTime()
    {
        $type = (int)$_POST['type'];
        $app_id = (array)$_POST['appid'];
        $tag = $_POST['tag'] ?? [];
        $msg = trim($_POST['msg']);
        $data = trim($_POST['data']);
        $settime = trim($_POST['settime']);

        self::checkParam($type, $app_id, $msg, $data);

        if (empty($settime) || $settime <= date('Y-m-d H:i')) {
            output(201, '定时推送时间设置错误！');
        }
        if (isset($_POST['tag']) && empty($tag)) {
            output(201, '推送用户类型不能为空！');
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
        if ($type == AppPush::PUSH_TYPE_URL) {
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
            output(200, '设置定时推送成功！');
        } else {
            output(201, '设置定时推送失败，请稍后再试！');
        }
    }

    /**
     * 推送参数验证
     *
     * @param $type
     * @param $app_id
     * @param $msg
     * @param $data
     */
    private static function checkParam($type, $app_id, $msg, $data)
    {
        if (!AppPush::checkType($type)) {
            output(201, '请选择推送消息类型！');
        }
        $count = count($app_id);
        if ($count < 1) {
            output(201, '平台选择有误！');
        } else {
            $system_id = array_column(self::$system, 'AppId');
            $app_id = is_array($app_id) ? $app_id : [$app_id];
            foreach ($app_id as $v) {
                if (!in_array($v, $system_id)) {
                    output(201, '平台选择有误！');
                }
            }
        }
        if (empty($msg)) {
            output(201, '推送消息不能为空！');
        }
        if ((in_array($type, [AppPush::PUSH_TYPE_URL])) && empty($data)) {
            output(201, '推送数据不能为空！');
        }
        if ((in_array($type, [AppPush::PUSH_TYPE_MSG])) && !empty($data)) {
            output(201, '推送纯消息时数据只能为空！');
        }
    }
}
