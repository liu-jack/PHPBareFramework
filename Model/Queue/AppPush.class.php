<?php
/**
 * App推送队列
 */

namespace Model\Queue;

use Bare\C\Queue;
use Classes\Push\JPush;

class AppPush extends Queue
{

    private static $data = [
        'Type' => 1,
        'Msg' => 1,
        'Data' => 1,
        'App' => 1,
        'Cid' => 1
    ];

    public function run($data)
    {
        $data = unserialize($data);

        if (!is_array($data) || count(array_diff_key(self::$data, $data)) > 0) {
            logs($data, $this->logPath());

            return false;
        }
        if ($data['Cid'] == 'ALL') {
            $platform = [];
            if (!empty($data['App'])) {
                $platform = $data['App'];
            }
            $this->appPush($data['Type'], $data['Msg'], $data['Data'], $platform);
        } else {
            $this->singlePush($data['Cid'], $data['Type'], $data['Msg'], $data['Data'], $data['App']);
        }

        return true;
    }

    /**
     * 单个推送
     *
     * @param string|array $cid      推送ID
     * @param int          $type     类型
     * @param string       $msg      消息
     * @param string       $data     数据
     * @param array        $platform 平台类型
     */
    private function singlePush($cid, $type, $msg, $data, $platform)
    {
        $ret = JPush::appPush($cid, $type, $msg, $data, $platform);
        if (!is_array($ret) || $ret['http_code'] != '200') {
            logs([
                'cid' => $cid,
                'type' => $type,
                'msg' => $msg,
                'data' => $data,
                'time' => date("Y-m-d H:i:s"),
                'ret' => $ret,
            ], $this->logPath());
        }
        unset($ret);
    }

    /**
     * 群体推送
     *
     * @param int    $type     类型
     * @param string $msg      消息
     * @param string $data     数据
     * @param array  $platform 平台, 默认全部
     */
    private function appPush($type, $msg, $data, $platform = ['IOS', 'ANDROID'])
    {
        $ret = JPush::appPush('all', $type, $msg, $data, $platform);

        if (!is_array($ret) || $ret['http_code'] != '200') {
            logs([
                'cid' => 'ALL',
                'type' => $type,
                'msg' => $msg,
                'data' => $data,
                'time' => date("Y-m-d H:i:s"),
                'ret' => $ret,
            ], $this->logPath());
        }

        unset($ret);
    }
}
