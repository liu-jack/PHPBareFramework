<?php
/**
 *  新浪内容采集 [channelid] [day2]
 *
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
use lib\mobile\Emoji;

require(dirname(dirname(dirname(__DIR__))) . "/common.inc.php");

class CollectWebNews extends Action
{
    private static $isecho = 1;             // 是否输出进度提示信息
    private static $table = 'CollectWeb';   // 数据表
    private static $img_table = 'PicInfo';  // 图片表
    private static $log_path = 'DataImport/' . __CLASS__; // 导入错误日志路径
    private static $pdo = null;             // pdo库实例
    private static $siteid = 101;           // 采集站点id
    private static $channelid = 0;          // 采集频道id
    private static $createtime = '';        // 采集>=$createtime的数据
    private static $savepath = '';          // 多开时图片保存区别路径
    private static $isstatus = 1;           // 采集状态
    private static $page = 1;               // 采集页码
    private static $config = [              // 频道配置
        1 => [
            'name' => '养育有道',
            'url' => 'http://hi.baby.sina.com.cn/baby/yangyuyoudao/list.php?page=%s&dpc=1',
            'tags' => '育儿,学龄期,学龄期家庭教育',
            'channel' => '新浪育儿',
            'list_preg' => [
                [
                    'reg' => '@<span class="title"><a href="([^"]+)"[^>]*>(.+)</a></span>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Title'] // match key => field
                ],
                [
                    'reg' => '@<td colspan="3"><img src="([^"]+)"[^>]*></td>@isU',
                    'field' => [1 => 'Cover']
                ],
                [
                    'reg' => '@<td align="right"><span class="time">(.+)</span></td>@isU',
                    'field' => [1 => 'ArticleTime']
                ],
                [
                    'reg' => '@<span class="content">(.+)</span>@isU',
                    'field' => [1 => 'Description']
                ],
            ],
            'detail_preg' => [
                'Author' => '@<span class="source"><a[^>]*>(.+)</a></span>@isU',
                'ArticleTime' => '@<span class="titer">(.+)</span>@isU',
                'Content' => '@<div class="content" id="artibody" data-sudaclick="blk_content">(.+)<div id="left_hzh_ad">@isU',
            ],
        ],
        2 => [
            'name' => '辣妈style',
            'url' => 'http://hi.baby.sina.com.cn/baby/lamazhenger/list.php?page=%s&dpc=1',
            'tags' => '育儿,学龄期,学龄期家庭教育',
            'channel' => '新浪育儿',
        ],
    ];

    public function doDefault()
    {
        global $argv;
        self::getConfig($argv);

        if (!empty(self::$config[self::$channelid])) {
            $config = self::$config[self::$channelid];
            self::collectLoop($config);
        }

        self::echoMsg("\nFinished!\n");
    }

    /**
     * 采集循环
     *
     * @param $config
     */
    private static function collectLoop($config)
    {
        $list = self::getList($config, self::$page);
        foreach ($list as $v) {
            if (!isset($v['ArticleTime']) || strtotime($v['ArticleTime']) >= self::$createtime) {
                self::getContent($v, $config);
            } else {
                self::$page = 0;
            }
        }
        if (self::$page > 0) {
            self::collectLoop($config);
        }
    }

    /**
     * 列表采集
     *
     * @param $config
     * @param int $page
     * @return array
     */
    private static function getList($config, $page = 1)
    {
        $data = [];
        if ($page > 0) {
            $url = $config['url'];
            $cont1 = self::getCurl(sprintf($url, $page));
            $temp = [];
            if (is_numeric($config['list_preg'])) { //正则复用
                $config['list_preg'] = self::$config[$config['list_preg']]['list_preg'];
            }
            foreach ($config['list_preg'] as $v) {
                preg_match_all($v['reg'], $cont1, $out);
                foreach ($v['field'] as $fk => $fv) {
                    $temp[$fv] = $out[$fk];
                }
            }
            foreach ($temp as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $data[$kk][$k] = trim($vv);
                }
            }
            self::$page += 1;
        }
        return $data;
    }

    /**
     * 内容及图片采集
     *
     * @param $info
     * @param $config
     */
    private static function getContent($info, &$config)
    {
        if (!empty($info['FromUrl'])) {
            $cont2 = self::getCurl($info['FromUrl']);
            if (is_numeric($config['detail_preg'])) { //正则复用
                $config['detail_preg'] = self::$config[$config['detail_preg']]['detail_preg'];
            }
            foreach ($config['detail_preg'] as $k => $v) {
                preg_match($v, $cont2, $out);
                $info[$k] = trim($out[1]);
            }
        }
    }

    /**
     * 根据传人的参数配置导入设置
     * @param array $argv 传入参数
     */
    private static function getConfig($argv)
    {
        if (!empty(self::$config[$argv[1]])) {
            self::$channelid = trim($argv[1]);
            self::$savepath .= str_replace(',', '_', self::$channelid);
        } else {
            exit("getWeb101.php [channelid] [day2]\n");
        }
        if (!empty($argv[2])) {
            if (stripos($argv[2], 'day') !== false) {
                $cday = substr($argv[2], 3);
                if ($cday > 0) {
                    self::$createtime = strtotime(date('Y-m-d')) - ($cday - 1) * 86400;
                }
            }
        }
        if (empty(self::$createtime)) {
            self::$createtime = strtotime(date('Y-m-d'));
        }
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
     * 获取网页内容
     *
     * @param $url string 网址
     * @param array $extra cookie:文件名 referer:来源 isheader:是否获取头文件 header:头文件 nobody:是否获取内容
     * @param int $timeout
     * @return string
     */
    private static function getCurl($url, $extra = [], $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, isset($extra['isheader']) ? $extra['isheader'] : false);
        curl_setopt($ch, CURLOPT_NOBODY, isset($extra['nobody']) ? $extra['nobody'] : false);
        // ip
        $ip = mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 254);
        $header = [
            'CLIENT-IP:' . $ip,
            'X-FORWARDED-FOR:' . $ip,
        ];
        if (empty($extra['header'])) {
            $extra['header'] = [
                'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:53.0) Gecko/20100101 Firefox/53.0'
            ];
        }
        $header = array_merge($header, $extra['header']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 来源
        if (empty($extra['referer'])) {
            $extra['referer'] = 'https://www.google.com';
        }
        curl_setopt($ch, CURLOPT_REFERER, $extra['referer']);
        // cookie
        if (!empty($extra['cookie'])) {
            $cookie_file = BASEPATH_CACHE . 'cookie/' . $extra['cookie'] . '.tmp';
            if (!file_exists($cookie_file)) {
                if (!is_dir(dirname($cookie_file))) {
                    mkdir(dirname($cookie_file), 0777, true);
                }
                $fp = fopen($cookie_file, 'w+');
                fclose($fp);
            }
            //指定保存cookie的文件
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            //指定发送给服务器的cookie文件
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        }

        $output = curl_exec($ch);
        curl_close($ch);
        $encode = mb_detect_encoding($output, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
        if ($encode !== "UTF-8") {
            $output = iconv($encode, "UTF-8//IGNORE", $output);
        }

        return $output;
    }

    /**
     * 获取内容图片宽高信息
     * @param $info
     * @param $content
     * @return mixed
     */
    private static function getContentImg($info, $content)
    {
        self::$isstatus = 1;
        if (!empty($content)) {
            preg_replace_callback(
                '@<img.*[data-]?src="([^"]*)"[^>]*>@isU',
                function ($match) use ($info) {
                    preg_match('@data-type="[^"]*"@isU', $match[0], $out);
                    if (!empty($out[1]) && strtolower($out[1]) == 'gif') {
                        $ext = 'gif';
                    } else {
                        $ext = 'jpg';
                    }
                    $image_info = [];
                    $pdo = self::getPDO();
                    $url_info = $pdo->find(self::$img_table, ['PicUrl' => $match[1]]);
                    if (!empty($url_info)) {
                        $url_info = current($url_info);
                        if ($url_info['Status'] == 2) {
                            $image_url = self::getImage($match[1], 'content', $ext);
                            if (!empty($image_url)) {
                                $image_size = getimagesize($image_url['path']);
                                if (!empty($image_size)) {
                                    $image_info = [
                                        'Width' => $image_size[0],
                                        'Height' => $image_size[1],
                                        'PicUrl' => $match[1],
                                        'SavePath' => $image_url['rpath'],
                                        'Status' => 1,
                                    ];
                                    $pdo->update(self::$img_table, $image_info, ['Id' => $url_info['Id']]);
                                }
                            }
                        }
                        if (!empty($url_info['SavePath'])) {
                            if (empty($url_info['Width']) || empty($url_info['Height'])) {
                                $image_url = self::getImageUrl($url_info['SavePath']);
                                $image_size = getimagesize($image_url);
                                if (!empty($image_size)) {
                                    $image_info = [
                                        'Width' => $image_size[0],
                                        'Height' => $image_size[1],
                                    ];
                                    $pdo->update(self::$img_table, $image_info, ['Id' => $url_info['Id']]);
                                }
                            } else {
                                $image_info = [
                                    'Width' => $url_info['Width'],
                                    'Height' => $url_info['Height'],
                                    'SavePath' => $url_info['SavePath'],
                                ];
                            }
                        }
                    } else {
                        $image_url = self::getImage($match[1], 'content', $ext);

                        if (!empty($image_url)) {
                            $image_size = getimagesize($image_url['path']);
                            if (!empty($image_size)) {
                                $image_info = [
                                    'Width' => $image_size[0],
                                    'Height' => $image_size[1],
                                    'PicUrl' => $match[1],
                                    'SavePath' => $image_url['rpath'],
                                    'ItemId' => $info['Id'],
                                    'CreateTime' => date('Y-m-d H:i:s'),
                                ];
                                $pdo->insert(self::$img_table, $image_info, ['ignore' => true]);
                            } else {
                                goto FAILED;
                            }
                        } else {
                            FAILED:
                            $image_info2 = [
                                'PicUrl' => $match[1],
                                'ItemId' => $info['Id'],
                                'Status' => 2, // 采集失败
                                'CreateTime' => date('Y-m-d H:i:s'),
                            ];
                            $pdo->insert(self::$img_table, $image_info2, ['ignore' => true]);
                            $log = [
                                'id' => $info['Id'],
                                'msg' => 'Content Image Collect Failed',
                                'imgurl' => $match[1],
                                'time' => date('Y-m-d H:i:s'),
                            ];
                            runtime_log(self::$log_path, $log);
                        }
                    }
                    if (empty($image_info)) {
                        self::$isstatus = 2;
                    }
                },
                $content
            );
        }

        return;
    }

    /**
     * 保存封面图
     * @param $info
     * @return array
     */
    private static function getCover($info)
    {
        $covers = '';
        if (!empty($info['Cover'])) {
            $pdo = self::getPDO();
            $url_info = $pdo->find(self::$img_table, ['PicUrl' => $info['Cover']]);
            if (!empty($url_info)) {
                $url_info = current($url_info);
                if ($url_info['Status'] == 2) {
                    $img_url = self::getImage($info['Cover']);
                    if (!empty($img_url)) {
                        $covers = $img_url['rpath'];
                        $image_info = [
                            'SavePath' => $img_url['rpath'],
                            'Status' => 1,
                        ];
                        $pdo->update(self::$img_table, $image_info, ['Id' => $url_info['Id']]);
                    }
                }
                if (!empty($url_info['SavePath'])) {
                    $covers = $url_info['SavePath'];
                }
            } else {
                $img_url = self::getImage($info['Cover']);
                if (!empty($img_url)) {
                    $covers = $img_url['rpath'];
                    $image_info = [
                        'PicUrl' => $info['Cover'],
                        'SavePath' => $img_url['rpath'],
                        'ItemId' => $info['Id'],
                        'CreateTime' => date('Y-m-d H:i:s'),
                    ];
                    $pdo->insert(self::$img_table, $image_info, ['ignore' => true]);
                } else {
                    $image_info2 = [
                        'PicUrl' => $info['Cover'],
                        'ItemId' => $info['Id'],
                        'Status' => 2, // 采集失败
                        'CreateTime' => date('Y-m-d H:i:s'),
                    ];
                    $pdo->insert(self::$img_table, $image_info2, ['ignore' => true]);
                    $log = [
                        'id' => $info['Id'],
                        'msg ' => 'Cover Collect Failed',
                        'cover' => $info['Cover'],
                        'time' => date('Y-m-d H:i:s'),
                    ];
                    runtime_log(self::$log_path, $log);
                }
            }
        }
        return $covers;
    }

    /**
     * 保存视频
     * @param $vurl
     * @param $vext
     * @return array
     */
    private static function getVideo($vurl, $vext)
    {
        $base_dir = '/data/pic/test_qbtoutiao/';
        if (__ENV__ == 'DEV') {
            $base_dir = BASEPATH_PUBLIC . 'temp/';
        }

        $file_dir = 'video/' . self::$siteid . '/' . date("Ym") . '/' . date('d') . '/';
        $file_name = self::$savepath . uniqid() . $vext;
        $img_dir = $base_dir . $file_dir;
        $path = $img_dir . $file_name;
        $rpath = $file_dir . $file_name;

        $curl = curl_init($vurl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $video = curl_exec($curl);
        curl_close($curl);
        if (!empty($video)) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            $fp = fopen($path, 'a');
            fwrite($fp, $video);
            fclose($fp);
            $res = ['path' => $path, 'rpath' => $rpath];
        } else {
            $res = false;
        }

        return $res;
    }

    /**
     * 保存图图片
     *
     * @param $url
     * @param $path
     * @param $ext
     * @return mixed
     */
    private static function getImage($url, $path = 'cover', $ext = 'jpg')
    {
        if (strpos($url, '://') === false || !in_array($path, ['cover', 'content'])) {
            return false;
        }
        $url = str_replace('wx_fmt=webp', 'wx_fmt=jpeg', $url);
        if (!$ext) {
            $ext = 'jpg';
        }
        $base_dir = '/data/pic/test_qbtoutiao/';
        if (__ENV__ == 'DEV') {
            $base_dir = BASEPATH_PUBLIC . 'temp/';
        }
        $file_dir = $path . '/' . self::$siteid . '/' . date("Ym") . '/' . date('d') . '/';
        $file_name = self::$savepath . uniqid() . '.' . $ext;
        $img_dir = $base_dir . $file_dir;
        $path = $img_dir . $file_name;
        $rpath = $file_dir . $file_name;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $img = curl_exec($curl);
        curl_close($curl);

        if (!empty($img)) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            $fp = fopen($path, 'a');
            fwrite($fp, $img);
            fclose($fp);
            return ['path' => $path, 'rpath' => $rpath];
        } else {
            return false;
        }
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
}

$app->run();