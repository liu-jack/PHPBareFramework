<?php

/**
 * 版本管理
 */

namespace Controller\Admin\Mobile;

use Bare\AdminController;
use Model\Admin\Admin\AdminLog;
use Model\Mobile\AppInfo;
use Bare\DB;

class Version extends AdminController
{
    private $appinfo = [
        APP_APPID_ADR => 'Android',
        APP_APPID_IOS => 'IOS',
    ];
    // 表名
    const TABLE = 'AppVersion';

    public function index()
    {
        $page = max(1, intval($_GET[PAGE_VAR]));
        $app_id = intval($_GET['app_id']);
        $app_id = isset($this->appinfo[$app_id]) ? $app_id : APP_APPID_IOS;

        $where = ['AppId' => $app_id];

        $pdo_r = DB::pdo(DB::DB_MOBILE_R);
        $total = $pdo_r->clear()->select('count(Id)')->from(self::TABLE)->where($where)->getValue();

        if ($total > 0) {
            $data = $pdo_r->clear()->select('*')->from(self::TABLE)->where($where)->order('Id DESC')->limit(($page - 1) * PAGE_SIZE,
                PAGE_SIZE)->getAll();
            $this->page($total, PAGE_SIZE, $page);

            $this->value('list', $data);
        }

        $this->value('app_ids', $this->appinfo);
        $this->value('app_id', $app_id);
        $this->view();
    }

    public function add()
    {
        $app_id = intval($_POST['app_id']);
        $version_code = trim($_POST['version_code']);
        $intro = trim($_POST['intro']);
        $down_url = trim($_POST['down_url']);

        if (!isset($this->appinfo[$app_id])) {
            $this->output(['status' => false, 'msg' => '请选择应用！']);
        }
        if (empty($version_code)) {
            $this->output(['status' => false, 'msg' => '应用版本号不能为空！']);
        }
        if (empty($intro)) {
            $this->output(['status' => false, 'msg' => '应用版本升级描述不能为空！']);
        }
        if (empty($down_url)) {
            $this->output(['status' => false, 'msg' => '应用升级下载链接地址不能为空！']);
        }
        if (!filter_var($down_url, FILTER_VALIDATE_URL)) {
            $this->output(['status' => false, 'msg' => '请填写有效的应用升级下载链接地址！']);
        }

        $data = [
            'AppId' => $app_id,
            'VersionCode' => $version_code,
            'Description' => $intro,
            'DownUrl' => $down_url
        ];
        $pdo_w = DB::pdo(DB::DB_MOBILE_W);

        if ($pdo_w->insert(self::TABLE, $data)) {
            AppInfo::removeCache(AppInfo::CACHE_VERSION, $app_id);
            AdminLog::log('添加手机应用版本', $pdo_w->lastInsertId(), 'add', serialize($data));
            $this->output(array('status' => true, 'msg' => '添加成功！'));
        } else {
            $this->output(array('status' => false, 'msg' => '操作失败， 请稍后再试！'));
        }
    }

    public function delete()
    {
        $id = intval($_GET['id']);
        $app_id = intval($_GET['app_id']);
        if (!$id || !isset($this->appinfo[$app_id])) {
            $this->alertErr('删除失败', ['type' => 'error', 'desc' => '参数非法！']);
        }

        $pdo_w = DB::pdo(DB::DB_MOBILE_W);
        $where = ['Id' => $id, 'AppId' => $app_id];
        if ($pdo_w->delete(self::TABLE, $where)) {
            AppInfo::removeCache(AppInfo::CACHE_VERSION, $app_id);
            AdminLog::log('删除手机应用版本', $id, 'del', serialize($where));
            $this->alert('删除成功！');
        } else {
            $this->alertErr('删除失败', ['type' => 'error', 'desc' => '请稍后再试！']);
        }
    }
}
