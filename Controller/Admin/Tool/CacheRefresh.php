<?php
/**
 * 缓存刷新
 */

namespace Controller\Admin\Tool;

use Bare\AdminController;

class CacheRefresh extends AdminController
{
    public function index()
    {
        if (!empty($_POST['url'])) {
            $url = explode("\n", $_POST['url']);
            $urls = [];
            $flag = false;

            foreach ($url as $v) {
                $v = trim($v);
                if (!empty($v)) {
                    if (filter_var($v, FILTER_VALIDATE_URL)) {
                        $urls[] = $v;
                    } else {
                        $flag = true;
                        break;
                    }
                }
            }

            if ($flag) {
                output(201, ['title' => '提交失败', 'text' => 'url地址存在不合法', 'type' => 'error']);
            }

            cdnCachePurge($urls);
            $this->adminLog('缓存刷新', 'refresh', 0, $urls, 'CDNCachePurge');
            output(200, ['title' => '操作成功', 'type' => 'success']);
        }

        $this->view();
    }
}