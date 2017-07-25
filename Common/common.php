<?php
/**
 * 共用函数库
 */

/**
 * 获取分表名称
 * @param int $id 项目id
 * @param string $table 共用表名
 * @return bool|string
 */
function table(int $id, $table = '')
{
    if ($id > 0) {
        return $table . sprintf('%02x', $id % 256);
    }
    return $table;
}

/**
 * 获取|保存 封面
 * @param int $id 项目id
 * @param int $ver 图片版本号
 * @param string $ext 图片保持扩展名
 * @return mixed|string
 */
function cover(int $id, $ver = -1, $ext = '.jpg')
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
 * @param int $id 项目id
 * @param int $fid 上级项目id
 * @param string $ext 图片保存扩展名
 * @param int $ver 图片版本号
 * @return mixed|string
 */
function contentImg(int $id, $fid, $ver = -1, $ext = '.gif')
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
 * @param integer $size 头像尺寸 180/100
 * @param integer $ver 头像版本, >1 才体现
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
 * @param int $type 应用类型ID 0:web 1:wap 2:android 3:iphone
 * @return string
 */
function getDeviceId($type = 0)
{
    return $type . sprintf('%02x', crc32(php_uname('n'))) . str_replace('.', '', uniqid('', true));
}

/**
 * 转换设备字ID符串为整数
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