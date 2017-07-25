<?php

namespace Classes\Net;

/**
 * 网页采集分析类
 *  $collect = new Collect();
 *   var_dump(
 *       $collect->get("http://www.jb51.net")
 *           ->subText('<!--第一屏 start-->', '<!--第一屏end -->')
 *           //->match(['title'=>'@<div class="title">(.+)</div>@','title2'=>'@<div class="title">(.+)</div>@'])
 *           ->matchAll('@<li><span>(.+)</a></div></li>@')
 *           ->strip()
 *           ->iconvs()
 *           ->getMatch()
 *   );
 */
class Collects
{

    /**
     * 内容
     * @var
     */
    private $content;

    /**
     * 正则匹配结果
     * @var
     */
    private $match;

    /**
     * 清除内容
     */
    public function clear()
    {
        $this->content = '';
        $this->match = [];
    }

    /**
     * 获取网页内容
     *
     * @param $url string 网址
     * @param array $extra cookie:文件名 referer:来源 isheader:是否获取头文件 header:头文件 nobody:是否获取内容
     * @param int $timeout
     * @param int $times 跳转次数
     * @return $this
     */
    public function get($url, $extra = [], $timeout = 30, $times = 5)
    {
        if ($times < 1) {
            return $this;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
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
        if ($extra['header']) {
            $header = array_merge($header, $extra['header']);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 来源
        if (empty($extra['referer'])) {
            $extra['referer'] = 'https://www.google.com/';
        }
        curl_setopt($ch, CURLOPT_REFERER, $extra['referer']);
        // cookie
        if (!empty($extra['cookie'])) {
            $cookie_file = DATA_PATH . 'temp/cookie/' . $extra['cookie'] . '.tmp';
            if (!file_exists($cookie_file)) {
                if (!is_dir(dirname($cookie_file))) {
                    mkdir(dirname($cookie_file), 0755, true);
                }
                $fp = fopen($cookie_file, 'w+');
                fclose($fp);
            }
            //指定保存cookie的文件
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            //指定发送给服务器的cookie文件
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $output = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (in_array($http_code, [301, 302])) {
            $info = curl_getinfo($ch);
            if (!empty($info['redirect_url'])) {
                $output = $this->get($info['redirect_url'], $extra, $timeout, --$times);
            } else {
                $output = '';
            }
        } else {
            $encode = mb_detect_encoding($output, ["ASCII", "UTF-8", "GB2312", "GBK", "BIG5"]);
            if ($encode !== "UTF-8") {
                $output = iconv($encode, "UTF-8//IGNORE", $output);
                //$output = mb_convert_encoding($output, 'UTF-8', $encode);
            }
        }
        curl_close($ch);
        $this->clear();
        $encode = mb_detect_encoding($output, ["ASCII", "UTF-8", "GB2312", "GBK", "BIG5"]);
        if ($encode !== "UTF-8") {
            $output = iconv($encode, "UTF-8//IGNORE", $output);
        }
        $this->content = $output;
        return $this;

    }

    /**
     * 截取文本
     *
     * @param $start string 开始为准
     * @param $end string 结束位置
     * @return $this
     */
    public function subText($start, $end)
    {
        $temp1 = explode($start, $this->content);
        $temp2 = explode($end, $temp1[1]);
        $this->content = $temp2[0];
        return $this;
    }

    /**
     * 获取单个页面的多个不同类型数据
     *
     * @param $regs array 正则匹配数组 [key=>正则式]
     * @return $this
     */
    public function match($regs)
    {
        if (!is_array($regs)) {
            $regs = [$regs];
        }
        foreach ($regs as $k => $v) {
            $out = [];
            preg_match($v, $this->content, $out);
            $this->match[$k] = !empty($out[1]) ? trim($out[1]) : '';
        }
        return $this;
    }

    /**
     * 获取列表
     *
     * @param $reg
     * @return $this
     */
    public function matchAll($reg)
    {
        $out = [];
        preg_match_all($reg, $this->content, $out);
        if (!empty($out[1]) && count($out) == 2) {
            $this->match = $out[1];
        } elseif (!empty($out[1]) && count($out) > 2) {
            unset($out[0]);
            $this->match = $out;
        } else {
            $this->match = '';
        }
        return $this;
    }

    /**
     * 去除html标签
     *
     * @param null $keep
     * @return $this
     */
    public function strip($keep = null)
    {
        if (is_array($this->match)) {
            foreach ($this->match as $k => $v) {
                $this->match[$k] = trim(strip_tags($v, $keep));
            }
        } else {
            $this->match = trim(strip_tags($this->match, $keep));
        }
        return $this;
    }

    /**
     * 字符串编码转换为GBK
     *
     * @return $this
     */
    public function iconvs()
    {
        if (!is_array($this->match)) {
            $this->match = iconv("UTF-8", "GBK//IGNORE", $this->match);
        } else {
            foreach ($this->match as $k => $v) {
                $this->match[$k] = iconv("UTF-8", "GBK//IGNORE", $v);
            }
        }
        return $this;
    }

    /**
     * 获取匹配结果
     *
     * @return mixed
     */
    public function getMatch($getstr = false)
    {
        $match = $this->match;
        return $getstr ? current($match) : $match;
    }

    /**
     * 获取内容
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * 获取图片
     *
     * @param $url
     * @param $path
     * @return mixed
     */
    public function getImage($url, $path = '')
    {
        if (empty($url)) {
            return false;
        }
        if (empty($path)) {
            $ext = $this->getImageType($url);
            if (!in_array($ext, ['.gif', '.png', '.jpg'])) {
                $ext = '.jpg';
            }
            $path = UPLOAD_PATH . 'temp/' . date("Ym") . '/' . date('d') . '/' . uniqid() . $ext;
        }
        if (strpos($url, '://') !== false) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
            $img = curl_exec($ch);
            curl_close($ch);
        } else {
            ob_start();
            readfile($url);
            $img = ob_get_contents();
            ob_end_clean();
        }
        if (!empty($img) && self::getImageSize($url) !== false) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            $fp = fopen($path, 'a');
            fwrite($fp, $img);
            fclose($fp);
            return $path;
        } else {
            return false;
        }
    }

    /**
     * 获取图片扩展名 支持jpg,png,bmp,gif
     *
     * @param string $path 图片路径
     * @return boolean|string  扩展名|false
     */
    public function getImageType($path)
    {
        $type = exif_imagetype($path);
        switch ($type) {
            case IMAGETYPE_JPEG :
                return 'jpg';
                break;
            case IMAGETYPE_GIF :
                return 'gif';
                break;
            case IMAGETYPE_PNG :
                return 'png';
                break;
            case IMAGETYPE_BMP :
                return 'bmp';
                break;
            default :
                return false;
        }
    }

    /**
     * 获取远程图片的宽高
     *
     * @param string $url url 地址
     * @return array|bool 成功结果同 getimagesize
     */
    public function getImageSize($url)
    {
        if (strpos($url, '://') !== false) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RANGE, '0-1024');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            if (empty($data) || strpos($info['content_type'], 'image') !== 0) {
                return false;
            }

            $info = self::getSize($info['content_type'], $data);

            if ($info == false) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_RANGE, '1025-25000');
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $data2 = curl_exec($ch);
                curl_close($ch);

                $info = self::getSize($info['content_type'], $data . $data2);
            }
        } else {
            $info = getimagesize($url);
        }
        return $info;
    }

    /**
     * 获取图片宽高
     * @param $type
     * @param $data
     * @return array|bool
     */
    private static function getSize($type, $data)
    {
        return getimagesize("data://$type;base64," . base64_encode($data));
    }
}
