<?php
/**
 *更新设备推送标签
 *
 * @author 周剑锋 <camfee@foxmail.com>
 *
 */

namespace Apps\Queues;

use Classes\Push\JPush;

class AppPushTag extends Queue
{
    public function run($data)
    {
        $data = unserialize($data);

        if (!is_array($data) || empty($data['Tag'])) {
            logs($data, $this->logPath());

            return false;
        }
        if (empty($data['Cid']) && $data['Handle'] == 'removeAppTag') {
            $this->removeAppTag($data['Tag']);
        } else {
            $this->updateTag($data['Cid'], $data['Tag'], $data['Handle']);
        }

        return true;
    }

    /**
     * 为设备 添加|更新 推送标签
     *
     * @param string       $cid 注册ID
     * @param string|array $tag 标签名
     * @param string       $op  操作 add|remove
     * @return void null
     */
    private function updateTag($cid, $tag = '', $op = 'add')
    {
        $ret = JPush::updateDeviceTag($cid, $tag, $op);
        if (!is_array($ret) || $ret['http_code'] != '200') {
            logs([
                'cid' => $cid,
                'tag' => $tag,
                'op' => $op,
                'time' => date("Y-m-d H:i:s"),
                'ret' => $ret,
            ], $this->logPath());
        }
        unset($ret);
    }

    /**
     * 删除应用标签
     *
     * @param string|array $tag 标签名
     * @return void null
     */
    private function removeAppTag($tag = '')
    {
        $ret = JPush::removeTag($tag);
        if (!is_array($ret) || $ret['http_code'] != '200') {
            logs([
                'tag' => $tag,
                'time' => date("Y-m-d H:i:s"),
                'ret' => $ret,
            ], $this->logPath());
        }
        unset($ret);
    }
}
