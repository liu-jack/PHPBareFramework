<?php
/**
 * error_msg.php
 * 错误代码 信息
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-5-7 上午10:27
 *
 */

return [
    //接口签名相关
    500 => '缺少必选参数：%s',
    501 => '调用方法不存在',
    502 => '请求失效,请检查本机时间',
    503 => '未知错误, 代码：%d',
    504 => 'AppId不存在',
    505 => 'HASH值错误',
    506 => '请求URL格式不正确',
    507 => '仅公测版本可用',
    508 => '服务器维护中，暂停服务',
    // api 接口相关
    1001 => '参数错误',
    1002 => '系统错误',
    1003 => '操作成功',
    1004 => '操作失败',
    1005 => '数据不存在或已被删除',
    1006 => '缺少必要参数',
    1007 => '数据已被使用',
    1008 => '操作过于频繁',
    1009 => '网络错误',
];