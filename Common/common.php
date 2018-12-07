<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * 共用函数库
 */

/**
 * 获取分表名称
 *
 * @param int    $id    项目id
 * @param string $table 共用表名
 * @return bool|string
 */
function table($id, $table = '')
{
    if (!empty($id)) {
        return $table . sprintf('%02x', $id % 256);
    }

    return $table;
}

/**
 * 获取|保存 文件路径
 *
 * @param string    $path   路径
 * @param int       $itemid 项目id
 * @param string    $ext    图片保存扩展名
 * @param int|array $size   图片裁剪大小
 * @return mixed|string
 */
function build_file_path($path, $itemid = 0, $ext = 'jpg', $size = 0)
{
    if ($itemid) {
        $hash1 = sprintf("%02x", $itemid % 256);
        $hash2 = sprintf("%02x", $itemid / 256 % 256);
        $name = $itemid . '_' . substr(md5(__KEY__ . $itemid), -6);
    } else {
        $time = time();
        $hash1 = date('Ym', $time);
        $hash2 = date('d', $time);
        $name = mt_rand(10, 99) . '_' . substr(md5(__KEY__ . uniqid()), -6);
    }
    if (is_array($size)) {
        $return = [];
        foreach ($size as $v) {
            $v = intval($v);
            $url = UPLOAD_URI . "{$path}/{$hash1}/{$hash2}/{$name}_{$v}.{$ext}";
            $return[$v] = [
                'url' => $url,
                'path' => ROOT_PATH . ltrim($url, '/')
            ];
        }
    } else {
        $size = intval($size);
        $url = UPLOAD_URI . "{$path}/{$hash1}/{$hash2}/{$name}_{$size}.{$ext}";
        $return = [
            'url' => $url,
            'path' => ROOT_PATH . ltrim($url, '/')
        ];
    }

    return $return;
}

/**
 * 获取|保存 文件路径 根路径 图片域名单独配置
 *
 * @param string    $path   路径
 * @param int       $itemid 项目id
 * @param string    $ext    图片保存扩展名
 * @param int|array $size   图片裁剪大小
 * @return mixed|string
 */
function get_file_path($path, $itemid = 0, $ext = 'jpg', $size = 0)
{
    if (IS_ONLINE) {
        $base_dir = '/data/prj_jinguo/';
        $img_host = 'http://img.bare.com/';
    } else {
        $base_dir = UPLOAD_PATH . 'temp/';
        $img_host = 'http://img.bare.com/';
    }
    if ($itemid) {
        $hash1 = sprintf("%02x", $itemid % 256);
        $hash2 = sprintf("%02x", $itemid / 256 % 256);
        $name = $itemid . '_' . substr(md5(__KEY__ . $itemid), -6);
    } else {
        $time = time();
        $hash1 = date('Ym', $time);
        $hash2 = date('d', $time);
        $name = mt_rand(10, 99) . '_' . substr(md5(__KEY__ . uniqid()), -6);
    }
    if (is_array($size)) {
        $return = [];
        foreach ($size as $v) {
            $v = intval($v);
            $url = $img_host . "{$path}/{$hash1}/{$hash2}/{$name}_{$v}.{$ext}";
            $return[$v] = [
                'url' => $url,
                'path' => $base_dir . ltrim($url, '/')
            ];
        }
    } else {
        $size = intval($size);
        $url = $img_host . "{$path}/{$hash1}/{$hash2}/{$name}_{$size}.{$ext}";
        $return = [
            'url' => $url,
            'path' => $base_dir . ltrim($url, '/')
        ];
    }

    return $return;
}

/**
 * 获取文件扩展名
 *
 * @param string $filepath
 * @param bool   $isimg
 * @return string
 */
function get_file_ext($filepath, $isimg = true)
{
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    if ($isimg && !in_array($ext, ['jpg', 'png', 'gif', 'webp'])) {
        $ext = 'jpg';
    }

    return $ext;
}

/**
 * 获取|保存 封面
 *
 * @param int    $id  项目id
 * @param int    $ver 图片版本号
 * @param string $ext 图片保持扩展名
 * @return mixed|string
 */
function cover($id, $ver = -1, $ext = '.jpg')
{
    $base = 'book/cover/%02x/%02x/%d' . $ext;
    $url = UPLOAD_URI . sprintf($base, $id % 256, $id % 255, $id);
    if ($ver >= 0) {
        return IMG_HOST . $url . '?v=' . $ver;
    } else {
        return ROOT_PATH . substr($url, 1);
    }
}

/**
 * 获取|保存 内容图片
 *
 * @param int    $id  项目id
 * @param int    $fid 上级项目id
 * @param string $ext 图片保存扩展名
 * @param int    $ver 图片版本号
 * @return mixed|string
 */
function book_content_img($id, $fid, $ver = -1, $ext = '.gif')
{
    $base = 'book/content/%02x/%02x/%s' . $ext;
    $url = UPLOAD_URI . sprintf($base, $fid % 256, $id % 256, $fid . '_' . $id);
    if ($ver >= 0) {
        return IMG_HOST . $url . '?v=' . $ver;
    } else {
        return ROOT_PATH . $url;
    }
}

/**
 * 根据用户ID 获取头像
 *
 * @param integer $userid 用户ID
 * @param integer $size   头像尺寸 180/100
 * @param integer $ver    头像版本, >1 才体现
 * @return string         返回头像地址
 */
function head($userid, $size = 100, $ver = 0)
{
    static $allow_size = ['180' => 1, '100' => 1];
    $hash1 = sprintf("%02x", $userid % 256);
    $hash2 = sprintf("%02x", $userid / 256 % 256);
    $size = isset($allow_size[$size]) ? $size : '100';
    $ver = $ver > 0 ? '?v=' . $ver : '';

    return IMG_HOST . UPLOAD_URI . "head/{$hash1}/{$hash2}/{$userid}_{$size}.jpg{$ver}";
}

/**
 * 获取一个唯一设备字ID符串
 *
 * @param int $type appid|应用类型ID 0:web 1:wap 2:android 3:iphone
 * @return string
 */
function create_device_id($type = 0)
{
    return $type . sprintf('%02x', crc32(php_uname('n'))) . str_replace('.', '', uniqid('', true));
}

/**
 * 转换设备字ID符串为整数
 *
 * @param $deviceid
 * @return int
 */
function device_id2int($deviceid)
{
    $len = strlen($deviceid);
    $sum = 0;
    for ($i = 0; $i < $len; $i++) {
        $temp = $deviceid{$i};
        if (!is_numeric($temp)) {
            $temp = ord($temp);
        }
        $sum += $temp;
    }

    return intval($sum);
}

/**
 * 获取类中非继承方法和重写方法
 * 只获取在本类中声明的方法，包含重写的父类方法，其他继承自父类但未重写的，不获取
 *
 * @param string $classname 类名
 * @param string $access    public or protected  or private or final 方法的访问权限
 * @return array(methodname=>access)  or array(methodname)
 */
function get_methods($classname, $access = null)
{
    try {
        $class = new \ReflectionClass($classname);
    } catch (\Exception $e) {
        exit($e->getMessage());
    }
    $methods = $class->getMethods();
    $returnArr = [];
    foreach ($methods as $value) {
        if ($value->class == $classname) {
            if ($access != null) {
                try {
                    $methodAccess = new \ReflectionMethod($classname, $value->name);
                } catch (\Exception $e) {
                    exit($e->getMessage());
                }
                switch ($access) {
                    case 'public':
                        if ($methodAccess->isPublic()) {
                            $returnArr[$value->name] = 'public';
                        }
                        break;
                    case 'protected':
                        if ($methodAccess->isProtected()) {
                            $returnArr[$value->name] = 'protected';
                        }
                        break;
                    case 'private':
                        if ($methodAccess->isPrivate()) {
                            $returnArr[$value->name] = 'private';
                        }
                        break;
                    case 'final':
                        if ($methodAccess->isFinal()) {
                            $returnArr[$value->name] = 'final';
                        }
                        break;
                }
            } else {
                array_push($returnArr, $value->name);
            }
        }
    }
    unset($class, $methods, $methodAccess);

    return $returnArr;
}

/**
 * 二维数组排序
 *
 * @param array  $arr
 * @param string $field1 主排序字段
 * @param int    $order1 排序方式 SORT_DESC|SORT_ASC
 * @param string $field2 辅排序字段
 * @param int    $order2 排序方式 SORT_DESC|SORT_ASC
 * @return mixed
 */
function array_sort($arr, $field1, $order1 = SORT_DESC, $field2 = '', $order2 = SORT_DESC)
{
    foreach ($arr as $k => $v) {
        $volume1[$k] = $v[$field1];
        if (!empty($field2)) {
            $volume2[$k] = $v[$field2];
        }
    }
    if (!empty($volume2)) {
        array_multisort($volume1, $order1, $volume2, $order2, $arr);
    } else {
        array_multisort($volume1, $order1, $arr);
    }

    return $arr;
}

/**
 * 清除CDN缓存
 *
 * @param string|array $urls url地址,支持数组
 * @return boolean
 */
function cdn_cache_purge($urls)
{
    $urls = is_array($urls) ? $urls : [$urls];

    return \Bare\M\Queue::addMulti("CDNCachePurge", $urls);
}

/**
 * 手工拼接SQL时, 内容转义 (已经连接PDO时, 推荐 PDO::quote)
 *
 * @param string $str            要转义的字符串
 * @param bool   $utf8_safe      是否使用UTF8编码检查, 默认true(开启)
 * @param bool   $filter_percent 是否转义%, 默认false(关)
 * @return mixed
 */
function mysql_quote($str, $utf8_safe = true, $filter_percent = false)
{
    if (!is_string($str)) {
        return '';
    }
    if ($utf8_safe) {
        $str = iconv("UTF-8//IGNORE", "UTF-8//IGNORE", $str);
    }
    $str = addslashes($str);
    $search = ["\r", "\n"];
    $replace = ['\r', '\n'];
    if ($filter_percent) {
        $search[] = '%';
        $replace[] = '\%';
    }

    return str_replace($search, $replace, $str);
}

/**
 * 加密ID
 *
 * @param int $id 产品ID(必须为正数)
 * @return string 加密后的ID
 */
function encode_id(int $id): string
{
    $salt = sprintf("%02u", $id % 97);

    return base_convert($id . $salt, 10, 36);
}

/**
 * 解密ID
 *
 * @param string $id 加密后的产品ID
 * @return int 解密后的ID
 */
function decode_id(string $id): int
{
    $number = base_convert($id, 36, 10);

    return substr($number, 0, strlen($number) - 2);
}

/**
 * 根据给定时间参数与当前时间的差值转化时间格式
 *
 *    今天内的时间将格式化为:
 *       刚刚 / N秒前/ N分钟前 / 半小时前 / N小时前
 *    昨天/前天内的时间将格式化为:
 *       (昨天|前天)HH:II
 *    7 天内的时间将格式化为:
 *       N天前
 *    1 年内的时间将格式化为: (此处还存在一点问题，应分为今年内的和年前的，但出于效率考量，暂定为此，以后找到了好的方法再行优化)
 *       MM-DD HH:II
 *    1 年前的时间将格式化为:
 *       YYYY-MM-DD HH:II
 *
 * @param string  $datetime     日期时间字符串,支持的格式请参考strtotime
 * @param boolean $is_timestamp 当$datetime为时间戳时设为true
 * @return string
 */
function format_date($datetime, $is_timestamp = false)
{
    static $OFFSET = 28800; // 8 小时时差
    static $ONEDAY = 86400; // 1 天
    static $REMAINS = 57599; // 86400 - 28800 - 1 秒

    $tformat = 'H:i';
    $dtformat = 'm-d H:i';

    // 当前时间
    $now = $_SERVER['REQUEST_TIME'];
    $seconds = $now % $ONEDAY;
    $midnight = $now - $seconds + $REMAINS;
    // 给定日期
    $timestamp = (is_numeric($datetime) || $is_timestamp) ? $datetime : strtotime($datetime);
    // 与今日最后一秒时间差
    $xdiff = $midnight - $timestamp;
    // 天数差
    $days = intval($xdiff / $ONEDAY);
    // 与当前时间的差值
    $diff = $now - $timestamp;
    // 补上时差
    $timestamp += $OFFSET;

    if ($days == 0) {
        if ($diff > 3600) {
            return intval($diff / 3600) . '小时前';
        } elseif ($diff > 1800) {
            return '半小时前';
        } elseif ($diff > 60) {
            return intval($diff / 60) . '分钟前';
        } elseif ($diff > 0) {
            return $diff . '秒前';
        } elseif ($diff == 0) {
            return '刚刚';
        }
    } else {
        if ($days <= 7) {
            if ($days == 1) {
                return '昨天' . gmdate($tformat, $timestamp);
            } else {
                if ($days == 2) {
                    return '前天' . gmdate($tformat, $timestamp);
                }
            }

            return $days . '天前' . gmdate($tformat, $timestamp);
        } else {
            $this_year = (int)date('Y', $now);
            $that_year = (int)date('Y', $timestamp);
            if ($this_year !== $that_year) {
                $dtformat = 'Y-m-d H:i';
            }
        }
    }

    return gmdate($dtformat, $timestamp);
}

/**
 * 字符串转为id
 *
 * @param $str
 * @return float|int
 */
function str2int($str)
{
    $hex_str = substr_replace($str, '', 4, 2);
    $res = base_convert($hex_str, 36, 16);

    return hexdec($res) - pow(10, 11);
}

/**
 * id转为字符串
 *
 * @param        $id
 * @param string $key
 * @return string
 */
function int2str($id, $key = 'www.29fh.com')
{
    $id = pow(10, 11) + intval($id);
    $hex_id = dechex($id);
    $md5_str = substr(md5($key . (string)$hex_id), -2);
    $hex_str = base_convert($hex_id, 16, 36);
    $hex_len = strlen($hex_str);
    $res = substr($hex_str, 0, 4) . $md5_str . substr($hex_str, -($hex_len - 4));

    return strtoupper($res);
}

/**
 * xml 转 array
 *
 * @param $xml
 * @return array|mixed
 */
function xml2array($xml)
{
    if (empty($xml)) {
        return array();
    }
    libxml_disable_entity_loader(true);

    return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
}

/**
 * array 转 xml
 *
 * @param $array
 * @return bool|string
 */
function array2Xml($array)
{
    if (!is_array($array) || count($array) <= 0) {
        return '';
    }

    $xml = "<xml>";
    foreach ($array as $key => $val) {
        if (is_numeric($val)) {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        } else {
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
    }
    $xml .= "</xml>";

    return $xml;
}