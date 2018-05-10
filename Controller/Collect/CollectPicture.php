<?php

namespace Controller\Collect;

use Bare\C\Controller;
use Classes\Net\Collects;
use Model\Collect\{
    Atlas, Picture
};

/**
 * 图片采集控制器
 */
class CollectPicture extends Controller
{
    const BASE_URL = 'http://1024.stv919.club/pw/';
    
    /**
     * 图片采集入口 php index.php Collect/CollectPicture/index
     *
     */
    public function index()
    {
        need_cli();
        $url = 'http://1024.stv919.club/pw/thread.php?fid=14&page=%d';
        for ($i = 1; $i <= 100; $i++) {
            self::getList($url, $i);
        }
    }

    /**
     *  采集图片列表
     */
    public function getList($url, $p = 1)
    {
        $log_path = 'collect/picture/atlas';
        $cc = new Collects();
        $atlas = $cc->get(sprintf($url,$p))->matchAll('@<tr align="center" class="tr3 t_one"><td[^>]*><a[^>]*>.::</a></td><td[^>]*>\s*<h3><a href="(htm_data/[\d]*/[\d]*/[\d]*\.html)"[^>]*>(.*)</a></h3>(\s*|\s*<img[^>]*>\s*)</td>\s*<td class="tal y-style"><a[^>]*>.*</a>\s*</td>\s*<td class="tal f10 y-style">.*</td> <td class="tal y-style"><a[^>]*>(.*)</a></td>\s*</tr>@isU')->getMatch();
        if (count($atlas[1]) > 0 && count($atlas[1]) == count($atlas[2]) && count($atlas[1]) == count($atlas[4])) {
            foreach ($atlas[1] as $k => $v) {
                echo "\n start collect order/page {$k}/{$p} \n";
                $data = [];
                $data['CollectUrl'] = self::BASE_URL . $v;
                $data['Title'] = trim(preg_replace('@\[.+\]@isU', '', $atlas[2][$k]));
                $data['CreateTime'] = date('Y-m-d H:i:s', strtotime(trim($atlas[4][$k])));

                $aid = Atlas::addAtlas($data);
                if ($aid > 0) {
                    self::getPicture($data['CollectUrl'], $aid, $data['CreateTime']);
                } else {
                    logs("add atlas order/page {$k}/{$p} error", $log_path);
                }
            }
        }
    }

        /**
     *  采集图片列表
     */
    public function getPicture($url, $atlasid = 0, $date = '')
    {
        $log_path = 'collect/picture/picture';
        $time = !empty($date) ? strtotime($date) : time();
        $cc = new Collects();
        $picture = $cc->get($url)->matchAll('@<img[^>]*src="([^"]+)"[^>]*border="[^"]*"[^>]*onclick="[^"]*"[^>]*onload="[^"]*"[^>]*>@isU')->getMatch();
        $total = count($picture);
        if (count($total > 0)) {
            foreach ($picture as $k => $v) {
                echo "collect picture order/total {$k}/{$total} \r";
                $data = [];
                $ext = substr($v, strripos($v, '.'));
                if (!in_array($ext, ['.gif', '.png', '.jpg'])) {
                    $ext = '.jpg';
                }
                $basename = 'atlas/' . date("Ym", $time) . '/' . date('d', $time) . '/' . uniqid() . $ext;
                $path = UPLOAD_PATH . $basename;
                $res = $cc->getImage($v, $path);
                if (!empty($res)) {
                    $data['AtlasId'] = $atlasid;
                    $data['PicUrl'] = $v;
                    $data['Url'] = UPLOAD_URI . $basename;
                    $pid = Picture::addPicture($data);
                }
                if (empty($pid)) {
                    logs("add picture order/total {$k}/{$total} error", $log_path);
                }
            }
        }
    }
}




