<?php

namespace Controller\Api\Test;

use Bare\Controller;

/**
 * 测试用类
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017-07-21 16:17
 * @deprecated since v1.1.0
 *
 */
class Index extends Controller
{
    /**
     *
     *
     * 注释测试
     *
     *
     * <pre>
     * post:
     *     width:   必选, 屏幕宽度 ,sdgsdg
     *     height:  必选, 屏幕高度, sdgsag
     *     channel: 必选, 频道来源, sagsa
     *     deviceid: 可选, 为空时,返回服务器分配的deviceid
     * </pre>
     *
     * @return void|string 返回JSON数组 频道来源
     *
     * <pre>
     * {
     *   "Status": 200,
     *   "Result": {
     *     "StopServer": { // 停服
     *       "Code": 0, // 0：不停服 1：停服
     *       "Msg": "服务器维护中，清稍后访问"  // 停服说明
     *     },
     *     "Update": { // 版本更新信息
     *       "LowVerCode": "v1.0.0", // 强更版本号，低于此版本的强更(不包括此版本)
     *       "LowMsg": "此版本已经停用，请更新到最新版本", // 强更提示信息
     *       "VerCode": "v1.0.0", // 最新版本号
     *       "VerMsg": "升级了", // 最新版本升级信息
     *       "DownUrl": "http://xxx.com/xx.apk" // 最新版本下载地址，iOS为App Store地址
     *     },
     *     "AppScreen": { // 闪屏广告
     *       "ImgUrl": "", // 图片地址
     *       "ClickUrl": "", // 图片链接，这里需要支持http url 和 URL Scheme
     *       "StartTime": "2017-01-01 00:00:00", // 广告开始时间
     *       "EndTime": "2017-01-02 23:12:26" // 广告结束时间
     *     },
     *     "IsLogin": 0, // 登录状态 0：未登录 1：登录
     *     "DeviceId": "106f25ccc35864c43458aa5589942556", // 设备id， 已分配时为空
     *     "ServerTime": 1486199520 // 时间戳，精确到秒
     *   }
     * }
     * 异常状态：
     * 201：xxxx
     * 202：xxx2
     * </pre>
     *
     */
    public function getIndex()
    {
        $this->output(200, $this->_m->index());
    }

    /**
     * APP启动初始化信息APP启动初始化信息APP启动初始化信息APP启动初始化信息APP启动初始化信息APP启动初始化信息APP启动初始化信息APP启动初始化信息
     * <pre>
     * GET:
     *     width:   必选, 屏幕宽度
     *     height:  必选, 屏幕高度
     *     channel: 必选, 频道来源
     *     deviceid: 可选, 为空时,返回服务器分配的deviceid
     * </pre>
     *
     * @return void|string 数据格式 返回JSON数组
     *
     * <pre>
     * {
     *   "Status": 200,
     *   "Result": {
     *     "StopServer": { // 停服
     *       "Code": 0, // 0：不停服 1：停服
     *       "Msg": "服务器维护中，清稍后访问"  // 停服说明
     *     },
     *     "Update": { // 版本更新信息
     *       "LowVerCode": "v1.0.0", // 强更版本号，低于此版本的强更(不包括此版本)
     *       "LowMsg": "此版本已经停用，请更新到最新版本", // 强更提示信息
     *       "VerCode": "v1.0.0", // 最新版本号
     *       "VerMsg": "升级了", // 最新版本升级信息
     *       "DownUrl": "http://xxx.com/xx.apk" // 最新版本下载地址，iOS为App Store地址
     *     },
     *     "AppScreen": { // 闪屏广告
     *       "ImgUrl": "", // 图片地址
     *       "ClickUrl": "", // 图片链接，这里需要支持http url 和 URL Scheme
     *       "StartTime": "2017-01-01 00:00:00", // 广告开始时间
     *       "EndTime": "2017-01-02 23:12:26" // 广告结束时间
     *     },
     *     "IsLogin": 0, // 登录状态 0：未登录 1：登录
     *     "DeviceId": "106f25ccc35864c43458aa5589942556", // 设备id， 已分配时为空
     *     "ServerTime": 1486199520 // 时间戳，精确到秒
     *   }
     * }
     *
     *
     * 异常状态
     * 201:参数错误
     *
     * </pre>
     *
     */
    public function test()
    {
        $this->output();
    }
}