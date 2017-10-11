<?php
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
function getSavePath($path, $itemid = 0, $ext = 'jpg', $size = 0)
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
                'url' => HTTP_HOST . $url,
                'path' => ROOT_PATH . ltrim($url, '/')
            ];
        }
    } else {
        $size = intval($size);
        $url = UPLOAD_URI . "{$path}/{$hash1}/{$hash2}/{$name}_{$size}.{$ext}";
        $return = [
            'url' => HTTP_HOST . $url,
            'path' => ROOT_PATH . ltrim($url, '/')
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
function getFileExt($filepath, $isimg = true)
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
        return HTTP_HOST . $url . '?v=' . $ver;
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
function contentImg($id, $fid, $ver = -1, $ext = '.gif')
{
    $base = 'book/content/%02x/%02x/%s' . $ext;
    $url = UPLOAD_PATH . sprintf($base, $fid % 256, $id % 256, $fid . '_' . $id);
    if ($ver >= 0) {
        return $url . '?v=' . $ver;
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

    return "http://{$_SERVER['HTTP_HOST']}/Public/upload/head/{$hash1}/{$hash2}/{$userid}_{$size}.jpg{$ver}";
}

/**
 * 获取一个唯一设备字ID符串
 *
 * @param int $type appid|应用类型ID 0:web 1:wap 2:android 3:iphone
 * @return string
 */
function getDeviceId($type = 0)
{
    return $type . sprintf('%02x', crc32(php_uname('n'))) . str_replace('.', '', uniqid('', true));
}

/**
 * 转换设备字ID符串为整数
 *
 * @param $deviceid
 * @return int
 */
function intDeviceId($deviceid)
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
function getMethods($classname, $access = null)
{
    $class = new \ReflectionClass($classname);
    $methods = $class->getMethods();
    $returnArr = [];
    foreach ($methods as $value) {
        if ($value->class == $classname) {
            if ($access != null) {
                $methodAccess = new \ReflectionMethod($classname, $value->name);
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
function arraySort($arr, $field1, $order1 = SORT_DESC, $field2 = '', $order2 = SORT_DESC)
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

    return \Bare\Queue::addMulti("CDNCachePurge", $urls);
}