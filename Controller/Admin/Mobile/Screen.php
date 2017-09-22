<?php
/**
 *
 * 启动图管理
 *
 */

namespace Controller\Admin\Mobile;

use Bare\AdminController;
use Model\Mobile\AppInfo;
use Classes\Image\PhotoImage;
use Common\PathConst;
use Common\Upload;
use Model\Admin\Admin\AdminLog;
use Bare\DB;
use Model\Mobile\ScreenImage;

class Screen extends AdminController
{
    const TABLE_NAME = 'AppScreenImage';

    private $appinfo = [
        APP_APPID_ADR => 'Android',
        APP_APPID_IOS => 'IOS',
    ];

    public function index()
    {
        $page = max(1, intval($_GET[PAGE_VAR]));

        $appid_ios = intval(APP_APPID_IOS);
        $appid_adr = intval(APP_APPID_ADR);

        $pdo = DB::pdo(DB::DB_MOBILE_R);
        $query = $pdo->prepare("select count(Id) from `" . self::TABLE_NAME . "` where Status=1 and AppId in (:appid_ios,:appid_adr)");
        $query->bindValue(':appid_ios', $appid_ios);
        $query->bindValue(':appid_adr', $appid_adr);
        $query->execute();
        $total = $query->fetchColumn();

        if ($total > 0) {
            $offset = ($page - 1) * PAGE_SIZE;
            $pagesize = PAGE_SIZE;

            $query = $pdo->prepare("select * from `" . self::TABLE_NAME . "` where Status=1  and AppId in (:appid_ios,:appid_adr) order by Id desc limit $offset,$pagesize");
            $query->bindValue(':appid_ios', $appid_ios);
            $query->bindValue(':appid_adr', $appid_adr);
            $query->execute();
            $data = $query->fetchAll();

            if (!empty($data)) {
                $now = time();
                foreach ($data as $key => $value) {
                    if (strtotime($value['StartTime']) > $now) {
                        // 未开始
                        $data[$key]['StartStatus'] = 1;
                    } elseif (strtotime($value['EndTime']) < $now) {
                        // 已结束
                        $data[$key]['StartStatus'] = 3;
                    } else {
                        // 正在进行中
                        $data[$key]['StartStatus'] = 2;
                    }
                }
            }
            $this->value('list', $data);
            $this->page($total, PAGE_SIZE, $page);
        }

        $this->value('app_ids', $this->appinfo);
        $this->view();
    }

    public function add()
    {
        $this->value('app_ids', $this->appinfo);
        $this->view('update');

    }

    public function edit()
    {
        $id = intval($_GET['id']);
        $info = ScreenImage::getInfoByIds($id);
        if (empty($info)) {
            $this->alertErr('参数错误');
        }
        $this->value('info', $info);
        $this->value('app_ids', $this->appinfo);
        $this->view('update');

    }

    public function update()
    {
        $id = intval($_POST['id']);
        $appid = intval($_POST['app_id']);
        $name = trim($_POST['name']);
        $url = trim($_POST['url']);
        $description = trim($_POST['description']);
        $starttime = trim($_POST['start_time']);
        $endtime = trim($_POST['end_time']);

        if (!isset($this->appinfo[$appid])) {
            $this->alertErr('编辑失败！', '', '请选择应用名！');
        } elseif (empty($name) || empty($description) || empty($starttime) || empty($endtime)) {
            $this->alertErr('编辑失败！', '', '字段填写不完整');
        } elseif (strtotime($starttime) > strtotime($endtime)) {
            $this->alertErr('编辑失败！', '', '启动图显示开始时间不能大于结束时间！');
        }
        if (!empty($_FILES['appscreen']['name'])) {
            $rel = PhotoImage::checkImage($_FILES['appscreen'], 375, 512, 0);
            if (!empty($rel['code'])) {
                $this->alertErr('编辑失败！', '', $rel['msg']);
            }
        }
        $data = [
            'AppId' => $appid,
            'Name' => $name,
            'Url' => $url,
            'Description' => $description,
            'StartTime' => $starttime,
            'EndTime' => $endtime,
            'LastTime' => date('Y-m-d H:i:s'),
        ];
        if ($id > 0) {
            // 上传图片
            if (!empty($rel)) {
                $img_url = Upload::saveImg(PathConst::IMG_APP_SCREEN, $rel, PathConst::IMG_APP_SCREEN_SIZE, $id);
                if (empty($img_url['thumb'][0])) {
                    $this->alertErr('编辑失败！', '', '图片上传失败!');
                }
                $data['ImgUrl'] = $img_url['thumb'][0];
            }
            $ret = ScreenImage::update($id, $data);
            if ($ret !== false) {
                // 删除缓存
                AppInfo::removeCache(AppInfo::CACHE_APP_SCREEN, $appid);
                AdminLog::log('更新启动图', 'update', $id, $data, self::TABLE_NAME);
                $this->alert('编辑成功！', url('index'));
            }
        } else {
            if (!isset($_FILES['appscreen']['name']) || $_FILES['appscreen']['error'] != 0) {
                $this->alertErr('编辑失败！', '', '图片上传失败!');
            }
            $id = ScreenImage::add($data);
            if (!empty($id)) {
                // 删除缓存
                AppInfo::removeCache(AppInfo::CACHE_APP_SCREEN, $appid);
                AdminLog::log('添加启动图', 'add', $id, $data, self::TABLE_NAME);
                // 上传图片
                if (!empty($rel)) {
                    $img_url = Upload::saveImg(PathConst::IMG_APP_SCREEN, $rel, PathConst::IMG_APP_SCREEN_SIZE, $id);
                    if (empty($img_url['thumb'][0])) {
                        $this->alertErr('编辑失败！', '', '图片上传失败!');
                    }
                    ScreenImage::update($id, ['ImgUrl' => $img_url['thumb'][0]]);
                }
                $this->alert('编辑成功！', url('index'));
            }
        }
        $this->alertErr('编辑失败！', '', "请稍后重试！");
    }

    public function delete()
    {
        $id = intval($_GET['id']);
        $app_id = intval($_GET['app_id']);
        if (!$id || !isset($this->appinfo[$app_id])) {
            $this->alertErr('删除失败', '', '参数非法！');
        }

        $pdo_w = DB::pdo(DB::DB_MOBILE_W);
        $where = ['Id' => $id, 'AppId' => $app_id];
        $data = ['Status' => 2];

        if ($pdo_w->update(self::TABLE_NAME, $data, $where)) {
            AppInfo::removeCache(AppInfo::CACHE_APP_SCREEN, $app_id);
            AdminLog::log('删除启动图', 'update', $id, $data, self::TABLE_NAME);
            $this->alert('删除成功！');
        } else {
            $this->alertErr('删除失败', '', '请稍后再试！');
        }
    }
}

