<?php

namespace Controller\Jufeng;

use Bare\Controller;
use Classes\Net\Collects;
use Classes\Tool\Emoji;

/**
 * 微信采集
 */
class Collect extends Controller
{
    public function weixin()
    {
        //need_cli();
        $url = 'http://mp.weixin.qq.comhttp://mp.weixin.qq.com/mp/getmasssendmsg?__biz=MjM5NjQ4OTQyMA==&uin=MzM3NzU0MjI4MA%3D%3D&key=5e85f2ba5f0147dfebe1efe1d2e496b83c049a4f0cd8e49e56f7400e911ed62911a1016c3fcea01693f4d59a1e8c551212371fc079b6ea7f70e1425e7adcca05189965454cb3cb81eb5771c3de8c1b91&devicetype=android-21&version=26050434&lang=zh_CN&nettype=WIFI&ascene=3&pass_ticket=8W1Kn%2FAma6bCxCCMMOhD%2FQ37U0JrqwXAg5lqhdrKCIA%2FbhbF7kT2%2FhEi3qwU1kCw&wx_header=1';
        //$url = 'http://mp.weixin.qq.com/mp/getmasssendmsg?__biz=MzA4NDMyNjgyOQ==&uin=MTI2NTg2NjE0Mg%3D%3D&key=5657e61c2ec7753dc174d64622c1483672c8b25f13e52e5030da3d41bfd9ceb99f784f364b095ad901c7e57d212876266a461300b1c6d913b4e7a900b8cee3bd8d0f24d004286376b51c0d012311f1e4&devicetype=android-23&version=26050630&lang=zh_CN&nettype=WIFI&ascene=3&pass_ticket=PKsMu2CUCd7SRvvD2h2VTi9EakF0WokDilpVaKo9Y2xJZLojf86Qd40FbwJfmEwI&wx_header=1';

        $this->getList($url);

    }

    public function content()
    {
        //need_cli();
        $limit = 10;
        $info = $this->m->getWeixins(['status' => 0], 0, $limit, 'content_url');
        $list = $info['data'];
        if (!empty($list)) {
            $cc = new Collects();
            foreach ($list as $v) {
                $reg = [
                    'from' => '@<a .* id="post-user">(.+)</a>@isU',
                    'content' => '@<div class="rich_media_content " id="js_content">(.+)</div>\s*<script@isU',
                ];
                $extra = ['referer' => $v['content_url']];
                $data = $cc->get($v['content_url'], 15, $extra)->match($reg)->getMatch();
                $data['content'] = preg_replace_callback(
                    '@<img.*data-src="([^"]+)"[^>]*>@isU',
                    function ($match) {
                        //$cc->getImage($match[1]);
                        $img = self::getImage($match[1]);
                        return '<img src="' . $match[1] . '" >';
                    },
                    $data['content']
                );
                echo $data['content'];
                die;
                var_dump($data);
                die;
            }
        }
        if ($info['count'] >= $limit) {
            //$this->content();
        }

        exit('finished');
    }

    private function getList($url, $p = 1)
    {
        set_time_limit(0);
        if (empty($url)) {
            exit('url empty');
        }
        echo $p . PHP_EOL;
        $extra = ['cookie' => 'weixin_list'];
        $cc = new Collects();
        if ($p == 1) {
            $extra['isheader'] = true;
            $extra['referer'] = $url;
            $url = $cc->get($url, 30, $extra)->match('@Location: ?(.+)\s@isU')->getMatch($url);
            var_dump($cc->getContent());
            die;
            $extra['referer'] = $url;
            $extra['isheader'] = false;
            $url_arr = parse_url($url);
            parse_str($url_arr['query'], $query);
            $extra['header'] = [
                //'x-wechat-uin: ' . $query['uin'],
                //'x-wechat-key: ' . $query['key'],
                'User-Agent: Mozilla/5.0 (Linux; Android 6.0; PE-TL10 Build/HuaweiPE-TL10; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/53.0.2785.49 Mobile MQQBrowser/6.2 TBS/043024 Safari/537.36 MicroMessenger/6.5.4.1000 NetType/WIFI Language/zh_CN',
            ];
            $extra['referer'] = $url;
            $query['scene'] = 124;
            $query['a8scene'] = $query['ascene'];
            $url = str_replace('#wechat_redirect', '', $url) . '&' . http_build_query($query) . '&#wechat_redirect';

            $content = $cc->get($url, 30, $extra)->match("@msgList = '{(.+)}';@isU")->getMatch();
            $content = htmlspecialchars_decode($content);
            var_dump($content);
            die;
            //echo ($cc->getContent()); die;
            $content = '{' . $content . '}';
        } else {
            $content = $cc->get($url, 10, $extra)->getContent();
            $content = json_decode($content, true);
            $content = $content['general_msg_list'];
        }
        $content = json_decode($content, true);
        $list = $content['list'];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (!empty($v['app_msg_ext_info']['content_url'])) {
                    $data = [
                        'title' => (string)Emoji::removeEmoji($v['app_msg_ext_info']['title']),
                        'digest' => (string)Emoji::removeEmoji($v['app_msg_ext_info']['digest']),
                        'cover' => (string)$v['app_msg_ext_info']['cover'],
                        'content_url' => (string)$v['app_msg_ext_info']['content_url'],
                        'fileid' => (int)$v['app_msg_ext_info']['fileid'],
                        'author' => (string)$v['app_msg_ext_info']['author'],
                        'source_url' => (string)$v['app_msg_ext_info']['source_url'],
                        'content' => (string)$v['app_msg_ext_info']['content'],
                        'datetime' => date('Y-m-d H:i:s', $v['comm_msg_info']['datetime']),
                        'fakeid' => (int)$v['comm_msg_info']['fakeid'],
                        'msgid' => (int)$v['comm_msg_info']['id'],
                    ];
                    $check = $this->m->getWeixins(['msgid' => $data['msgid']], 0, 1);
                    if (empty($check['data'][0])) {
                        $this->m->addWeixin($data);
                    }

                    $frommsgid = $v['comm_msg_info']['id'];
                }
            }

            $url_arr = parse_url($url);
            parse_str($url_arr['query'], $query);
            unset($query['wx_header']);
            $query['f'] = 'json';
            $query['frommsgid'] = $frommsgid;
            $next_url = $url_arr['scheme'] . '://' . $url_arr['host'] . $url_arr['path'] . '?' . http_build_query($query);
            $p++;
            if (!empty($frommsgid) && $p <= 15) {
                sleep(1);
                $this->getList($next_url, $p);
            }
        }

        exit('finished' . PHP_EOL);
    }

    private static function getImage($url)
    {
        $cc = new Collects();
        return $cc->getImage($url);
    }
}


