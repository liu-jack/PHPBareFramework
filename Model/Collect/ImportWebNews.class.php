<?php
/**
 *  在线上环境运行 64个网站采集统一导入
 *  导入 importWebArticle.php [siteid] [ID|minID-|-maxID|minID-maxID|day2] [UserId] [Status] [CollectStatus]
 *  默认导入 Status=0 CollectStatus=1
 * @author  周剑锋 <camfee@foxmail.com>
 *
 * $Id$
 */

if (php_sapi_name() != 'cli') {
    exit('cron must run in cli mode!');
}

set_time_limit(0);
ini_set('memory_limit', '2048M');

use lib\core\Action;
use Common\Bridge;
use Center\Article;
use Common\PhotoImage;
use Tools\GetImageSize;
use Safe\HTMLClean;

require(dirname(dirname(dirname(__DIR__))) . "/common.inc.php");

class importWebNews extends Action
{
    const COVER_DOMAIN = "http://i1.qbtoutiao.com/"; // 封面图域名
    const CONTENT_DOMAIN = "http://i0.qbtoutiao.com/"; // 内容图域名

    private static $isecho = 1;            // 是否输出进度提示信息
    private static $page_size = 100;       // 每页条数
    private static $from_table = 'CollectWeb';// 数据来源表
    private static $img_path = '';         // 图片保存路径
    private static $img_table = 'PicInfo'; // 图片表
    private static $siteid = 0;            // 站点Id
    private static $siteid2 = 0;            //正式站点Id
    private static $sitename = '';         // 站点名称
    private static $log_path = 'DataImport/' . __CLASS__; // 导入错误日志路径
    private static $isadd = true;          // 数据是否添加
    private static $pdo = null;            // pdo库实例
    private static $uid = 1;               // 添加文章的用户ID
    private static $status = 0;            // 要导入的文章状态 0：未导入 1：导入成功 2：导入失败 all:所有
    private static $collectstatus = 1;      // 1：图片采集成功 0：图片采集失败 微信 0:未采集 1：采集成功 2：采集失败
    private static $createtime = '';        // CreateTime 导入 CreateTime>= $createtime的数据
    private static $config = [             // 数据站点信息
        101 => [ // 新浪
            'siteid' => 0,               // 站点ID
            'sitename' => '新浪',        // 站点名称
        ],
        105 => [ // yoka
            'siteid' => 105,               // 站点ID
            'sitename' => 'YoKa',        // 站点名称
        ],
        106 => [ // yoka
            'siteid' => 106,               // 站点ID
            'sitename' => 'StyleMode',        // 站点名称
        ],
        107 => [
            'siteid' => 107,               // 站点ID
            'sitename' => '太平洋时尚网',        // 站点名称
        ],
    ];

    public function doDefault()
    {
        global $argv;

        $ids = self::getConfig($argv);
        $where = ['SiteId' => self::$siteid];
        if (!empty(self::$createtime)) {
            $where = ['CreateTime >=' => self::$createtime];
        }
        if (strtolower(self::$status) != 'all') {
            if (strtolower(self::$status) == 'all-1') {
                $where['Status <>'] = 1;
            } else {
                $where['Status'] = self::$status;
            }
        }
        if (strtolower(self::$collectstatus) != 'all') {
            $where['CollectStatus'] = self::$collectstatus;
        }
        if (isset($ids['start'])) {
            $startid = $ids['start'];
        } else {
            $pdo = self::getPDO();
            $startid = $pdo->select("min(Id)")->from(self::$from_table)->where($where)->getValue();
            if ($startid > 0) {
                $startid = $startid - 1;
            } else {
                self::echoMsg("\nFinished!\n", 1);
            }
        }
        if (isset($ids['end'])) {
            $endid = $ids['end'];
        } else {
            $pdo = self::getPDO();
            $endid = $pdo->select("max(Id)")->from(self::$from_table)->where($where)->getValue();
        }

        if ($endid > 0 && $endid >= $startid) {
            $page = $endid == $startid ? 1 : ceil(($endid - $startid) / self::$page_size);
            self::echoMsg("ImportArticle Start!\n");
            for ($i = 1; $i <= $page; $i++) {

                $start = ($i - 1) * self::$page_size + $startid;
                $end = $start + self::$page_size;
                if ($end > $endid) {
                    $end = $endid;
                }

                $data = self::getList($start, $end);
                self::echoMsg("All Process: {$i}/{$page}\n");

                if (count($data) > 0) {
                    self::addArticle($data);
                }

            }

            self::echoMsg("\nFinished!\n");
        }
    }

    /**
     * 数据入库
     * @param $list
     */
    private static function addArticle($list)
    {
        $total = count($list);
        foreach ($list as $k => $v) {
            $data = self::buildData($v);
            if (!empty($data)) {
                if (!empty($data['TagCache'])) { //标签增减修改
                    $data['TagCache'] = self::changeTag($data['TagCache']);
                }
                if (!empty($data['ViewTag'])) { //标签增减修改
                    $data['ViewTag'] = self::changeTag($data['ViewTag']);
                }
                $res = Article::addArticle($data);
                if (empty($res['ArticleId'])) {
                    $pdo = self::getPDO();
                    $pdo->update(self::$from_table, ['Status' => 2], ['Id' => $v['Id']]);
                    $log = [
                        'siteid' => self::$siteid,
                        'id' => $v['Id'],
                        'msg' => 'Import Failed',
                        'time' => date('Y-m-d H:i:s'),
                    ];
                    runtime_log(self::$log_path, $log);
                    self::echoMsg("Import Failed Id: {$v['Id']}\n");
                } else {
                    $pdo = self::getPDO();
                    $pdo->update(self::$from_table, ['Status' => 1], ['Id' => $v['Id']]);
                    Article::updateArticle($res['ArticleId'], ['UpdateTime' => $data['UpdateTime']]);
                    self::echoMsg("Page Process: " . ($k + 1) . "/{$total}\r");
                }
            } else {
                if (self::$isadd === false) {
                    $pdo = self::getPDO();
                    $pdo->update(self::$from_table, ['Status' => 3], ['Id' => $v['Id']]);
                    $log = [
                        'siteid' => self::$siteid,
                        'id' => $v['Id'],
                        'msg' => 'Skip Content Image Error',
                        'time' => date('Y-m-d H:i:s'),
                    ];
                    runtime_log(self::$log_path, $log);
                    self::echoMsg("Skip Import Id: {$v['Id']}\n");
                } else {
                    $log = [
                        'siteid' => self::$siteid,
                        'id' => $v['Id'],
                        'msg' => 'Skip Import',
                        'time' => date('Y-m-d H:i:s'),
                    ];
                    runtime_log(self::$log_path, $log);
                    self::echoMsg("Skip Import Id: {$v['Id']}\n");
                }
            }
        }
        self::echoMsg("\n");
    }

    /**
     * 组装 微信 的数据
     * @param $info
     * @return array
     */
    private static function buildData($info)
    {
        $data = [];
        $info['Content'] = self::checkContentImg($info);
        if ($info['Type'] == 3 && !empty($info['Description']) && $info['FromSourceUrl'] > 0) {
            self::$isadd = true; // 视频
        }
        if (!empty($info) && self::$isadd) {
            // 标签 & 频道
            $tag = explode(',', $info['Tags']);
            $channel = [];
            if (!empty($info['Channel'])) {
                $channel = explode(',', $info['Channel']);
            }
            if (!empty($channel)) {
                $tag = array_merge($channel, $tag);
            }
            $tags = [];
            foreach ($tag as $t) {
                if (!empty($t)) {
                    $tags[$t] = $t;
                }
            }
            $tags = array_values($tags);
            $vtags = $tags;
            if (count($vtags) > 5) {
                $vtags = array_slice($vtags, 0, 5);
            }
            if (!empty(self::$sitename)) {
                $tags[] = self::$sitename;
            }

            // 视频
            if ($info['Type'] == 3 && !empty($info['Description']) && $info['FromSourceUrl'] > 0) {
                $vret = Article::updateVideo(self::$uid, $info['Description']);
            }
            if (!empty($vret)) {
                $typedata = $vret['url'];
                $covertype = 1;
            } else {
                $typedata = '';
                $covertype = 0;
            }

            $data = [
                'Title' => $info['Title'],
                'Covers' => serialize(self::getCover($info)),
                'SiteId' => self::$siteid2,
                'Author' => $info['Author'],
                'FromUrl' => $info['FromUrl'],
                'CreateTime' => date('Y-m-d H:i:s'),
                'UpdateTime' => $info['ArticleTime'],
                'TagCache' => $tags,
                'ViewTag' => $vtags,
                'Content' => $info['Content'],
                'UserId' => self::$uid,
                'Status' => (defined("__ENV__") && __ENV__ == 'ONLINE') ? 2 : 1, // 待审核
                'CateId' => 1, // 其他 大分类
                'Type' => (int)$info['Type'],   // 文章 类型
                'CoverType' => $covertype,
                'TypeData' => $typedata,
                'TypeLen' => is_numeric($info['FromSourceUrl']) ? intval($info['FromSourceUrl']) : 0,
            ];
        }
        return $data;
    }

    /**
     * 根据传人的参数配置导入设置
     * @param array $argv 传入参数
     * @return array
     */
    private static function getConfig($argv)
    {
        $configs = self::$config;
        $siteid = intval($argv[1]);
        if (!isset($configs[$siteid])) {
            exit("importWebArticle.php [siteid] [ID|minID-|-maxID|minID-maxID|day2] [UserId] [Status] [CollectStatus]\n");
        }
        $config = $configs[$siteid];
        self::$siteid = $siteid;
        self::$siteid2 = $config['siteid'];
        self::$sitename = $config['sitename'];
        self::$img_path = (string)$siteid;
        self::$log_path .= $siteid;

        $data = [];
        if (!empty($argv[2])) {
            if (stripos($argv[2], 'day') !== false) {
                $day = intval(substr($argv[2], 3));
                if ($day > 0) {
                    self::$createtime = date('Y-m-d H:i:s', strtotime(date('Y-m-d')) - ($day - 1) * 86400);
                }
            } elseif (is_numeric($argv[2]) && $argv[2] >= 0) {
                $data['start'] = intval($argv[2]);
                $data['end'] = intval($argv[2]);
            } else {
                $ids = explode('-', $argv[2]);
                $data['start'] = intval($ids[0]);
                if (!empty($ids[1])) {
                    $data['end'] = intval($ids[1]);
                }
            }
        }
        if (!empty($argv[3]) && is_numeric($argv[3])) {
            self::$uid = $argv[3];
        }
        if (!empty($argv[4])) {
            self::$status = $argv[4];
        }
        if (!empty($argv[5])) {
            self::$collectstatus = $argv[5];
        }
        return $data;
    }

    /**
     * 输出提示信息
     * @param $msg
     * @param int $exit
     */
    private static function echoMsg($msg, $exit = 0)
    {
        if (self::$isecho) {
            echo $msg;
            if ($exit) {
                exit();
            }
        }
    }

    /**
     * @return bool|\lib\plugins\pdo\PDOQuery|null|PDOStatement
     */
    private static function getPDO()
    {
        if (empty(self::$pdo)) {
            self::$pdo = Bridge::pdo(Bridge::DB_COLLECT_W);
        }
        return self::$pdo;
    }

    /**
     * 获取图片宽高
     * @param $url
     * @return array|bool
     */
    private static function getImgSize($url)
    {
        return GetImageSize::getUrlImageSize($url);
    }

    /**
     * 获取一列数据
     * @param $start
     * @param $end
     * @return array|bool
     */
    private static function getList($start, $end)
    {
        $where = ["SiteId" => self::$siteid, "Id >" => $start, "Id <=" => $end];
        if ($start == $end) {
            $where = ["Id" => $start];
        }
        if (strtolower(self::$status) != 'all') {
            if (strtolower(self::$status) == 'all-1') {
                $where['Status <>'] = 1;
            } else {
                $where['Status'] = self::$status;
            }
        }
        if (strtolower(self::$collectstatus) != 'all') {
            $where['CollectStatus'] = self::$collectstatus;
        }
        if (!empty(self::$createtime)) {
            $where['CreateTime >='] = self::$createtime;
        }

        $pdo = self::getPDO();
        $data = $pdo->find(self::$from_table, $where);
        $pdo = null;

        return $data;
    }

    /**
     * 获取内容图片宽高信息
     * @param $info
     * @return mixed
     */
    private static function checkContentImg($info)
    {
        self::$isadd = true;
        $content = '';
        if (!empty($info['Content'])) {
            if ($info['Type'] == 2) {
                $content = unserialize($info['Content']);
                if (!empty($content)) {
                    foreach ($content as $k => $v) {
                        $image_url = '';
                        $pdo = self::getPDO();
                        $url_info = $pdo->find(self::$img_table, ['PicUrl' => $v['img']]);
                        if (!empty($url_info)) {
                            $url_info = current($url_info);
                            if (!empty($url_info['SavePath'])) {
                                $img_info = PhotoImage::checkImageByUrl(self::getImageUrl($url_info['SavePath']));
                                if ($img_info['code'] == 0 && $img_info['image_type'] != 'bmp') {
                                    $content_img = self::saveContentImg(self::$uid, $img_info);
                                }
                                if (!empty($content_img['url'])) {
                                    $image_url = $content_img['url'];
                                }
                            }
                        }
                        if (empty($image_url)) {
                            self::$isadd = false;
                        }
                        $content[$k]['img'] = $image_url;
                    }
                }
            } else {
                $reg = '@<img.*([_-]?src|data-original)="([^"]*)"[^>]*>@isU';
                $content = preg_replace_callback(
                    $reg,
                    function ($match) use ($info) {
                        $image_info = [];
                        $pdo = self::getPDO();
                        $url_info = $pdo->find(self::$img_table, ['PicUrl' => $match[2]]);
                        if (!empty($url_info)) {
                            $url_info = current($url_info);
                            if ($url_info['Width'] && $url_info['Height']) {
                                $image_info = [
                                    'Width' => $url_info['Width'],
                                    'Height' => $url_info['Height'],
                                ];
                                if (!empty($url_info['SavePath'])) {
                                    $img_info = PhotoImage::checkImageByUrl(self::getImageUrl($url_info['SavePath']));
                                    if ($img_info['code'] == 0 && $img_info['image_type'] != 'bmp') {
                                        $content_img = self::saveContentImg(self::$uid, $img_info);
                                    }
                                    if (!empty($content_img['url'])) {
                                        $image_info['PicUrl'] = $content_img['url'];
                                    }
                                }
                            }
                        }
                        if (empty($image_info['PicUrl'])) {
                            self::$isadd = false;
                        }
                        if ($image_info['Width'] > 640 && stripos($image_info['PicUrl'], '.gif') === false) {
                            $temp_width = $image_info['Width'];
                            $image_info['Width'] = 640;
                            $image_info['Height'] = round(($image_info['Height'] / $temp_width) * 640);
                        }

                        $return = '<img data-width="' . $image_info['Width'] . '" data-height="' . $image_info['Height'] . '" src="' . $image_info['PicUrl'] . '" />';
                        return $return;
                    },
                    $info['Content']
                );

                if (!empty($content)) {
                    if ($info['Type'] != 2) {
                        $content = trim($content);
                        $content = preg_replace('@<a[^>]*>(.+)</a>@isU', '$1', $content);
                        $len1 = strlen($content);
                        $html = new HTMLClean(HTMLClean::CONFIG_IMPORT_ARTICLE);
                        $content = $html->purify($content);
                        $len2 = strlen($content);
                        $nbsp = chr(194) . chr(160);
                        $pattern = [
                            '@(<p>[' . $nbsp . '\s]*</p>\s*){2,}@isU',
                            '@^(<p>[' . $nbsp . '\s]*</p>\s*)*@isU',
                            '@(\s*<p>[' . $nbsp . '\s]*</p>)*$@is',
                        ];
                        $replace = ['<p>&nbsp;</p>', '', ''];
                        $content = preg_replace($pattern, $replace, $content);
                        if (empty($content) || ($len1 > 200 && $len2 < 50)) {
                            self::$isadd = false; // 标签清理发生错误
                            $log = [
                                'id' => $info['Id'],
                                'msg' => 'HTMLClean Error',
                                'time' => date('Y-m-d H:i:s'),
                            ];
                            runtime_log(self::$log_path, $log);
                        }
                    }
                } else {
                    self::$isadd = false; // 匹配图片发生错误
                    $log = [
                        'id' => $info['Id'],
                        'msg' => 'Match Content Images Error',
                        'time' => date('Y-m-d H:i:s'),
                    ];
                    runtime_log(self::$log_path, $log);
                }
            }
        }

        return $content;
    }

    /**
     * 保存封面图
     * @param $info
     * @return array
     */
    private static function getCover($info)
    {
        $covers = [];
        if (!empty($info['Cover'])) {
            $pdo = self::getPDO();
            $cover_arr = explode(',', $info['Cover']);
            foreach ($cover_arr as $v) {
                $url_info = $pdo->find(self::$img_table, ['PicUrl' => $v]);
                if (!empty($url_info)) {
                    $url_info = current($url_info);
                    if (!empty($url_info['SavePath'])) {
                        $img_info = PhotoImage::checkImageByUrl(self::getImageUrl($url_info['SavePath']));
                        if ($img_info['code'] == 0) {
                            $cover = Article::updateCover(self::$uid, $img_info);
                        }
                        if (!empty($cover['url'])) {
                            $covers[] = $cover['url'];
                        }
                    }
                }
            }

        }
        return $covers;
    }

    /**
     * 保存视频
     * @param $info
     * @return array
     */
    private static function getVideo($info)
    {
        $video = false;
        if (!empty($info['Description'])) {
            if (!empty($info['Description'])) {
                $video = Article::updateVideo(self::$uid, $info['Description']);
            }
        }
        return $video;
    }

    /**
     * 保存内容图
     *
     * @param integer $uid 用户D
     * @param mixed $info 封面图信息 通过PhotoImage::checkImage()获取, 仅支持单张图上传
     * @return array|bool     成功则返回数组['code' => '成功代码', 'url' => 600大小的图片地址]
     */
    private static function saveContentImg($uid, $info)
    {
        $cover_dir = '/data/pic/prj_qbtoutiao/a/content/';
        if (__ENV__ == 'DEV') {
            $cover_dir = BASEPATH_PUBLIC . 'temp/a/content/';
        }

        //文件名（时间）
        list($usec, $sec) = explode(" ", microtime());
        $sec = substr($sec, 4, 6);
        $usec = substr($usec, 2, 6);
        $time = $sec . $usec;

        $year = date('Ym', time());
        $day = date('d', time());
        $hash = substr(md5("{$uid}_{$time}"), 0, 2);
        $cfg = [
            'base' => $cover_dir,
            'thumb' => [
                'source' => [
                    'url' => '{year}/{day}/{hash}/{uid}_{time}.jpg'
                ],
                '1000' => [
                    'width' => 960,
                    'position' => 'middle',
                    'quality' => 85,
                    'watermark' => false,
                    'url' => '{year}/{day}/{hash}/{uid}_{time}_1000.jpg'
                ],
                '960' => [
                    'width' => 640,
                    'position' => 'middle',
                    'quality' => 85,
                    'watermark' => false,
                    'url' => '{year}/{day}/{hash}/{uid}_{time}_960.jpg'
                ],
            ],
            'uid' => $uid,
            'year' => $year,
            'day' => $day,
            'hash' => $hash,
            'time' => $time
        ];

        if ($info['image_type'] == 'gif') { // gif图片处理
            $gpath = "{$year}/{$day}/{$hash}/{$uid}_{$time}";
            $gurl = "{$cover_dir}{$gpath}.gif";
            $gurl960 = "{$cover_dir}{$gpath}_1000.gif";
            $gurl640 = "{$cover_dir}{$gpath}_960.gif";
            $dirname = dirname($gurl);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0777, true);
            }
            copy($info['tmp_name'], $gurl);
            $r = copy($info['tmp_name'], $gurl960);
            copy($info['tmp_name'], $gurl640);
            if ($r) {
                $imgurl = self::CONTENT_DOMAIN . "a/content/{$year}/{$day}/{$hash}/{$uid}_{$time}_960.gif";
                return [
                    'code' => 200,
                    'url' => $imgurl,
                ];
            } else {
                return false;
            }
        }

        $image_status = $info;

        $ret = PhotoImage::imageResize($cfg, $image_status);
        $imgurl = self::CONTENT_DOMAIN . "a/content/{$year}/{$day}/{$hash}/{$uid}_{$time}_960.jpg";
        if (is_array($ret) && $ret['status'] == true) {
            return [
                'code' => 200,
                'url' => $imgurl,
            ];
        }

        return false;
    }

    /**
     * 获取图片绝对路径
     *
     * @param $url
     * @return string
     */
    private static function getImageUrl($url)
    {
        if (stripos($url, '://') === false) {
            $base_dir = '/data/pic/test_qbtoutiao/';
            if (__ENV__ == 'DEV') {
                $base_dir = BASEPATH_PUBLIC . 'temp/';
            }
            $url = $base_dir . $url;
        }

        return $url;
    }

    // 标签增减修改
    protected static $_change_tag_arr = [
        '健身' => ['她头条', '美容', '美体'],
        '饮食' => ['美食'],
        '用品' => ['她头条', '生活', '家居'],
        '手工' => ['她头条', '生活', '手工'],
        '二胎' => ['她头条', '育儿', '学龄期', '学龄期家庭教育'],
        '早教' => ['她头条', '育儿', '学龄期', '学龄期家庭教育'],
        '疾病' => ['健康'],
    ];

    // 标签增减修改
    private static function changeTag($tags)
    {
        $tag = [];
        if (!empty($tags)) {
            $tag = $tags;
            foreach ($tags as $v) {
                if (!empty(self::$_change_tag_arr[$v])) {
                    $tag = array_merge($tag, self::$_change_tag_arr[$v]);
                }
            }
            $tag = array_unique($tag);
        }
        return $tag;
    }
}

$app->run();