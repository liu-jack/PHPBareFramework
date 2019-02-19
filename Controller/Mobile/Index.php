<?php

namespace Controller\Mobile;

use Bare\C\Controller;
use Model\Mobile\AppInfo;

/**
 * M站首页控制器
 */
class Index extends Controller
{
    public function index()
    {
        $this->view();
    }

    public function download()
    {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (strpos($agent, 'micromessenger') !== false) {
            if (strpos($agent, 'iphone') !== false || strpos($agent, 'ipad') !== false || strpos($agent,
                    'ios') !== false) {
                $this->show('Mobile/Public/download-iphone');
            } else {
                $this->show('Mobile/Public/download-android');
            }
        }

        $appid = $this->getSystem();
        $version = AppInfo::getVersion($appid);
        $url = $version['Url'];
        header("Location: " . $url);
    }

    // 获取用户系统
    private function getSystem()
    {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $type = trim($_GET['version']);

        $app_id = APP_APPID_ADR;
        if (strpos($agent, 'iphone') !== false || strpos($agent, 'ipad') !== false || strpos($agent,
                'ios') !== false || $type === 'ios') {
            $app_id = APP_APPID_IOS;
        }

        return $app_id;
    }
}