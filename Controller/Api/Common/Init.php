<?php
/**
 * Init.class.php
 *
 */

namespace Controller\Api\Common;

use Bare\Controller;

/**
 * 客户端启动相关
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017-07-21
 *
 */
class Init extends Controller
{
    /**
     * APP 启动初始化信息
     *
     * <pre>
     * GET:
     *     width:   必选, 屏幕宽度
     *     height:  必选, 屏幕高度
     *     channel: 必选, 频道来源
     *     deviceid: 可选, 为空时,返回服务器分配的deviceid
     * </pre>
     *
     * @return void|string 返回JSON数据
     *
     * <pre>
     * {
     *   "Code": 200,
     *   "Data": {
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
     * </pre>
     *
     */
    public function start()
    {
        $deviceid = trim($_GET['deviceid']);
        $channel = trim($_GET['channel']);
        $width = intval($_GET['width']);
        $height = intval($_GET['height']);

        $appid = $GLOBALS['g_appid'];
        $uid = (int)$this->isLogin(false);

        $result = [
            'StopServer' => [
                "Code" => 0,
                "Msg" => ''
            ],
            'Update' => [
                "LowVerCode" => "v1.0.0",
                "LowMsg" => "",
                "VerCode" => "v1.0.0",
                "VerMsg" => "",
                "DownUrl" => ""
            ],
            'AppScreen' => [
                "ImgUrl" => "",
                "ClickUrl" => "",
                "StartTime" => "",
                "EndTime" => ""
            ],
            'IsLogin' => 0,
            'DeviceId' => "",
            'ServerTime' => time()
        ];

        // 停服信息
        if (false) {
            $result['StopServer'] = [
                'Code' => 1,
                'Msg' => '服务器维护中,暂停服务'
            ];
        }

        // 登录信息
        if ($uid > 0) {
            $result['IsLogin'] = 1;
        }

        $all = AppInfo::getAllInfo($appid, $width, $height);
        $all = $all['all'];

        // 最新版本信息
        $version = $all[AppInfo::CACHE_VERSION . $appid];
        $version = is_array($version) ? $version : unserialize($version);
        if (!empty($version['VerCode'])) {
            $result['Update'] = [
                'LowVerCode' => $appid == MOBILE_APPID_ANDROID ? MOBILE_LOWVER_ANDROID : MOBILE_LOWVER_IPHONE,
                'LowMsg' => '抱歉,当前版本已停用,请升级！',
                'VerCode' => $version['VerCode'],
                'VerMsg' => $version['Feature'],
                'DownUrl' => $version['Url']
            ];
        }

        // 启动图
        $screen = $all[AppInfo::CACHE_APP_SCREEN . $appid];
        $screen = is_array($screen) ? $screen : unserialize($screen);
        if (!empty($screen['Url']) && @strpos($screen['Channel'], $channel) === false) {
            $result['AppScreen'] = [
                "ImgUrl" => $screen['Url'],
                "ClickUrl" => $screen['ClickUrl'],
                "StartTime" => $screen['StartTime'],
                "EndTime" => $screen['EndTime']
            ];
        }

        if (empty($deviceid)) {
            $deviceid = $this->_getDeviceId($appid);
            $result['DeviceId'] = $deviceid;

            // 首次启动时进行默认频道订阅评分
            $channel = loadconf('article/channel');
            $defualt_channels = $channel['defualt_channels'];
            $channels = $channel['channels'];
            $mychannel = $defualt_channels[0];
            $ctag = $ctag2 = $ctagname = [];
            foreach ($channels as $v) {
                if (!empty($v['tags'])) {
                    $ctag[$v['name']] = current($v['tags']);
                } else {
                    $ctagname[$v['name']] = $v['name'];
                }
            }
            if (!empty($ctagname)) {
                $ctag2 = Tags::getTagsByName($ctagname);
            }
            $ctag = $ctag + $ctag2;
            $myctag = [];
            foreach ($mychannel as $v) {
                if (isset($ctag[$channels[$v]['name']])) {
                    $myctag[] = $ctag[$channels[$v]['name']];
                }
            }
            UserScore::subscribeTag($deviceid, $myctag);


            // 初始化设备信息
            Device::initDevice($appid, $deviceid, $channel, '', $uid);
        }

        $this->output(200, $result);
    }

    /**
     * 获取最新版本信息
     *
     * <pre>
     * GET:
     *  无参数
     * </pre>
     *
     * @return void|string 返回Json数据
     *
     * <pre>
     * {
     *   "Code": 200,
     *   "Data": {
     *     "LowVerCode": "v1.0.0", // 强更版本号，低于此版本的强更(不包括此版本)
     *     "LowMsg": "抱歉，当前版本已停用，请升级！", // 强更提示信息
     *     "VerCode": "", // 最新版本号
     *     "VerMsg": "", // 最新版本升级信息
     *     "DownUrl": "" // 最新版本下载地址，iOS为App Store地址
     *   }
     * }
     * </pre>
     *
     */
    public function checkVersion()
    {
        $appid = $GLOBALS['g_appid'];

        $version = AppInfo::getVersion($appid);

        $data = [
            'LowVerCode' => $appid == MOBILE_APPID_ANDROID ? MOBILE_LOWVER_ANDROID : MOBILE_LOWVER_IPHONE,
            'LowMsg' => '抱歉,当前版本已停用,请升级！',
            'VerCode' => $version['VerCode'],
            'VerMsg' => $version['Feature'],
            'DownUrl' => $version['Url']
        ];

        $this->output(200, $data);
    }

    /**
     * 添加/更新设备推送Token
     *
     * <pre>
     * POST:
     *     token: 必选，第三方推送ID
     *     ios_token: IOS必选，iOS下源生推送token, Android不传
     * </pre>
     *
     * @return void|string Json数据
     *
     * <pre>
     * {
     *     "Code": 200,
     *     "Data": {}
     * }
     *
     * 异常情况:
     * 201: 推送ID不可为空
     * 202: iOS下deviceToken不可为空
     * </pre>
     */
    public function setDeviceToken()
    {
        $deviceid = trim($_GET['deviceid']);
        $channel = trim($_GET['channel']);
        $token = trim($_POST['token']);
        $ios_token = trim($_POST['ios_token']);

        $appid = $GLOBALS['g_appid'];
        $uid = (int)$this->isLogin(1);

        if (empty($token)) {
            $this->output(201, '推送ID不可为空');
        }

        if ($appid == MOBILE_APPID_IPHONE && empty($ios_token)) {
            $this->output(202, 'iOS下deviceToken不可为空');
        }

        Device::initDevice($appid, $deviceid, $channel, $token, $uid, $ios_token);

        $this->output(200);
    }
}