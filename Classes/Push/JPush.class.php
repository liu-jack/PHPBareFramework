<?php

namespace Classes\Push;

/**
 * App推送类
 *
 * @author 周剑锋 <camfee@foxmail.com>
 *
 */
class JPush
{
    // 推送appkey
    private static $appkey = '7644bf4a382cbb9820d2a76f';
    // 推送密钥
    private static $master_secret = '043235c76927cc231a2d9372';

    // 重试次数
    const RETRYTIMES = 3;
    // 推送接口地址
    const APIURL = 'https://api.jpush.cn/v3/push';
    // 设备管理接口地址
    const DEVICEAPIURL = 'https://device.jpush.cn/v3/devices/';
    // 标签管理接口地址
    const TAGSAPIURL = 'https://device.jpush.cn/v3/tags/';

    const TAG_TEST_PREFIX = 'STAGE_TEST'; // 标签前缀

    public static $tags = [
        'STAGE0' => 'STAGE0', // 未登录用户标签
        'STAGE10' => 'STAGE1', // 已登录用户标签
    ];
    // 添加标签时删除对立的标签
    private static $add_del_tags = [
        'STAGE0' => ['STAGE1'],
        'STAGE1' => ['STAGE0']
    ];


    // 推送日志保存路径 不填写则不保存
    public static $log_path = "log/jpush";

    // 推送额外参数
    const EXTRA_BADGE = 'badge'; // ios角标设置 +1：角标数自增, 0：清空角标数, 其他任何正整数
    // 推送应用
    const APP_SHU = 0; // 美特

    public function instance($key, $secret)
    {
        self::$appkey = $key;
        self::$master_secret = $secret;

        return new static();
    }

    /**
     * app推送 推送多个时只能推送同一类型(ID|标签)
     *
     * @param string $cid      推送注册ID|all|标签名，多个 id|标签 用英文逗号','分隔
     * @param int    $type     消息类型
     * @param string $msg      消息
     * @param string $data     数据
     * @param array  $platform 平台类型 ["android", "ios", "winphone"]
     * @param array  $extra    额外参数数组 见self::EXTRA_*
     * @return array|bool
     */
    public static function appPush($cid, $type, $msg, $data, $platform, $extra = [self::EXTRA_BADGE => '+1'])
    {
        if (empty($cid) || empty($type) || empty($msg) || empty($platform)) {
            return false;
        }

        // 推送平台
        $platform = array_map('strtolower', (array)$platform);
        $push['platform'] = $platform;
        // 推送目标
        if (strtolower($cid) === 'all') {
            if (defined("__ENV__") && __ENV__ == 'ONLINE') {
                $push['audience'] = 'all';
            } else {
                $push['audience']['tag_and'] = ['DEV']; // 测试环境
            }
        } elseif (stripos($cid, 'STAGE') !== false) {
            $push['audience']['tag'] = explode(',', $cid); // 标签推送
            if (defined("__ENV__") && __ENV__ == 'ONLINE') {
                $push['audience']['tag_and'] = ['ONLINE']; // 线上环境
            } else {
                $push['audience']['tag_and'] = ['DEV']; // 测试环境
            }
        } else {
            $push['audience']['registration_id'] = explode(',', $cid); // 注册Id推送
        }
        // 通知 & 消息
        $message['Type'] = $type;
        $message['Msg'] = $msg;
        $message['Data'] = $data;
        foreach ($push['platform'] as $v) {
            if ($v == 'ios') {
                $push['notification'][$v]['alert'] = $msg; // 通知
                $push['notification'][$v]['extras'] = $message; // 业务扩展字段
                if (isset($extra[self::EXTRA_BADGE])) {
                    $push['notification'][$v]['badge'] = $extra[self::EXTRA_BADGE]; // iso 角标+1
                }
            }
        }
        $push['message']['msg_content'] = $msg; // 自定义消息 应用内透传消息
        $push['message']['extras'] = $message;

        // 推送设置
        $push['options']['time_to_live'] = 28800;
        $push['options']['apns_production'] = (defined("__ENV__") && __ENV__ == 'ONLINE') ? true : false;//ios推送环境 false：开发环境 true：线上环境

        return self::sendRequest('POST', json_encode($push));
    }

    /**
     * 为设备 添加|删除 标签
     *
     * @param string $cid 注册ID
     * @param string $tag 标签
     * @param string $op  添加|删除 add|remove
     * @return array|bool
     */
    public static function updateDeviceTag($cid, $tag = '', $op = 'add')
    {
        if (empty($cid)) {
            return false;
        }

        $push['tags'] = [];
        // 环境标签
        if (defined("__ENV__") && __ENV__ == 'ONLINE') {
            $push['tags']['add'][] = 'ONLINE';
            $push['tags']['remove'][] = 'DEV';
        } else {
            $push['tags']['add'][] = 'DEV';
            $push['tags']['remove'][] = 'ONLINE';
        }

        // 用户类型标签
        if (!empty($tag)) {
            if (!is_array($tag)) {
                $tag = [$tag];
            }
            $tags = self::$tags;
            if ($op == 'remove') {
                foreach ($tag as $v) {
                    $push['tags']['remove'][] = $v;
                }
            } else {
                $add_del = self::$add_del_tags;
                foreach ($tag as $v) {
                    if (isset($tags[$v]) || strpos($v, self::TAG_TEST_PREFIX) === 0) {
                        $push['tags']['add'][] = $v;
                        if (isset($add_del[$v])) {
                            foreach ($add_del[$v] as $vv) {
                                $push['tags']['remove'][] = $vv;
                            }
                        }
                    }
                }
            }
        }

        return self::sendRequest('POST', json_encode($push), self::DEVICEAPIURL . $cid);
    }

    /**
     * 获取设备标签
     *
     * @param string $cid 注册ID
     * @return array|bool
     */
    public static function getDeviceTag($cid)
    {
        if (empty($cid)) {
            return false;
        }

        return self::sendRequest('GET', null, self::DEVICEAPIURL . $cid);
    }

    /**
     * 删除标签
     *
     * @param string $tag 标签
     * @return array|bool
     */
    public static function removeTag($tag = '')
    {
        if (empty($tag)) {
            return false;
        }

        return self::sendRequest('DELETE', '', self::TAGSAPIURL . $tag);
    }

    /**
     * 发送推送请求
     *
     * @param string $method 推送方法, POST GET PUT DELETE
     * @param null   $body   推送的数据
     * @param string $url    请求地址
     * @param int    $times  重试次数
     * @return array
     */
    private static function sendRequest($method, $body = null, $url = self::APIURL, $times = 1)
    {
        if (!defined('CURL_HTTP_VERSION_2_0')) {
            define('CURL_HTTP_VERSION_2_0', 3);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'JPush-API-PHP-Client');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);  // 连接建立最长耗时
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);  // 请求最长耗时
        // 设置SSL版本 1=CURL_SSLVERSION_TLSv1, 不指定使用默认值,curl会自动获取需要使用的CURL版本
        // curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 如果报证书相关失败,可以考虑取消注释掉该行,强制指定证书版本
        //curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
        // 设置Basic认证
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, self::getAuthStr());
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

        // 设置Post参数
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } else {
            if ($method === 'DELETE' || $method === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
        }
        if (!is_null($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Connection: Keep-Alive'
        ]);

        $output = curl_exec($ch);
        $response = [];
        $errorCode = curl_errno($ch);
        if ($errorCode) {
            self::log("Send " . $method . " " . $url . ", body:" . $body . ", times:" . $times);
            if ($times < self::RETRYTIMES) {
                return self::sendRequest($method, $body, $url, ++$times);
            } else {
                if ($errorCode === 28) {
                    self::log("Error: Response timeout. Your request has probably be received by JPush Server,please check that whether need to be pushed again.");
                } elseif ($errorCode === 56) {
                    // resolve error[56 Problem (2) in the Chunked-Encoded data]
                    self::log("Error: Response timeout, maybe cause by old CURL version. Your request has probably be received by JPush Server, please check that whether need to be pushed again.");
                } else {
                    self::log("Error: Connect timeout. Please retry later. Error:" . $errorCode . " " . curl_error($ch));
                }
            }
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header_text = substr($output, 0, $header_size);
            $body = substr($output, $header_size);
            $headers = [];
            foreach (explode("\r\n", $header_text) as $i => $line) {
                if (!empty($line)) {
                    if ($i === 0) {
                        $headers[0] = $line;
                    } else {
                        if (strpos($line, ": ")) {
                            list ($key, $value) = explode(': ', $line);
                            $headers[$key] = $value;
                        }
                    }
                }
            }
            $response['headers'] = $headers;
            $response['body'] = $body;
            $response['http_code'] = $httpCode;
            if ($httpCode != 200) {
                self::log("Send " . $method . " " . $url . ", body:" . $body . ", times:" . $times);
            }
        }
        curl_close($ch);

        return self::processResp($response);
    }

    /**
     * 推送认证信息
     *
     * @return string
     */
    private static function getAuthStr()
    {
        return self::$appkey . ":" . self::$master_secret;
    }

    /**
     * 推送结果解析
     *
     * @param $response
     * @return array
     */
    private static function processResp($response)
    {
        $result = [];
        if ($response['http_code'] === 200) {
            $data = json_decode($response['body'], true);
            if (!is_null($data)) {
                $result['body'] = $data;
            }
            $result['http_code'] = $response['http_code'];
            $result['headers'] = $response['headers'];
        } else {
            self::log('Error: http_code:' . $response['http_code']);
        }

        return $result;
    }

    /**
     * 推送日志
     *
     * @param $content
     */
    private static function log($content)
    {
        if (!empty(self::$log_path)) {
            logs($content, self::$log_path);
        }
    }
}
