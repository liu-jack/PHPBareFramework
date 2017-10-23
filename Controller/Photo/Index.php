<?php
/**
 * Index.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-10-19 下午3:01
 *
 */

namespace Controller\Photo;

use Bare\Controller;
use Model\Picture\Atlas;
use Model\Picture\Photo;

class Index extends Controller
{
    public function index()
    {
        $list = Atlas::getList();
        $seo = [
            'title' => '相册',
            'key' => '',
            'desc' => '',
        ];
        $this->value('list', $list['data']);
        $this->value('seo', $seo);
        $this->show();
    }

    public function photo()
    {
        $atlasid = $_POST['aid'] ? intval($_POST['aid']) : intval($_GET['aid']);
        $atlas = Atlas::getInfoByIds($atlasid);
        $offset = intval($_POST['offset']);
        $limit = 50;
        $list_info = Photo::getListByAtlasId($atlasid, $offset, $limit);
        $list = [];
        if (!empty($list_info['data'])) {
            $list = Photo::getInfoByIds($list_info['data']);
            $list = array_values($list);
        }
        if (IS_AJAX) {
            output(200, $list);
        }
        $this->value('atlas', $atlas);
        $this->value('list', $list);
        $this->value('limit', $limit);
        $this->value('atlasid', $atlasid);
        $this->show();
    }
}