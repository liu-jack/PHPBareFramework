<?php

namespace Controller\Home;

use Bare\Controller;

/**
 * 音乐
 */
class Music extends Controller
{

    public function index()
    {
        $uid = $this->isLogin();
        if (__ENV__ == 'ONLINE' && empty($uid)) {
            redirect(url('account/user/login', '', true));
        }
        $this->view();
    }

    /**
     * 音乐列表生成
     * php index.php home/music/make
     */
    public function make()
    {
        need_cli();
        $dir = DATA_PATH . 'music/';
        $files = scandir($dir);
        $data = [];
        foreach ($files as $v) {
            if ($v != '.' && $v != '..') {
                if (is_file($dir . $v) && stripos($v, '.mp3') !== false) {
                    $arr = explode(' - ', str_replace('.mp3', '', $v));
                    $lrc = str_replace('.mp3', '.lrc', $v);
                    if (file_exists($dir . $lrc)) {
                        $str = file_get_contents($dir . $lrc);
                        $str = $this->strToUtf8($str);
                        file_put_contents($dir . $lrc, $str);
                    }
                    $data[] = [
                        'title' => $arr[0],
                        'singer' => $arr[1],
                        'cover' => '/Public/images/zjf.png',
                        'src' => '/Data/music/' . $v,
                        'lyric' => file_exists($dir . $lrc) ? '/Data/music/' . $lrc : '',
                    ];
                }
            }
        }
        $json_string = "var musicList = " . json_encode($data);
        file_put_contents(
            str_replace('//', '/', ROOT_PATH . str_replace(HTTP_HOST, '', STATICS_PATH) . 'music/js/musicList.js'),
            $json_string);
        echo 'ok';
    }

    /**
     * 歌词转为utf-8
     */
    private function strToUtf8($data)
    {
        if (!empty($data)) {
            $filetype = mb_detect_encoding($data, ["ASCII", 'UTF-8', "GB2312", 'GBK', 'LATIN1', 'BIG5']);
            if ($filetype != 'UTF-8') {
                $data = mb_convert_encoding($data, 'UTF-8', $filetype);
            }
        }
        return $data;
    }

}