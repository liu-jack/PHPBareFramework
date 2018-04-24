<?php
/**
 * 发送手机短信队列
 */

namespace Queue\Queues;

use Queue\Queue;
use Sms\LsmSms as SmsCtrl;

class SendSMS extends Queue
{
    use SmsCtrl;

    public function run($data)
    {
        $data = unserialize($data);
        $site = isset($data['site']) ? $data['site'] : 1;
        if (is_array($data) && $data['mobile'] && $data['content'] && $data['id']) {
            $status = self::_Send($data['mobile'], $data['content'], $site);
            if ($status['succ'] === false) {
                $log_type = 'interface_fail';
                goto log;
            } else {
                $pdo = $this->getPDO('headline_admin');
                $query = $pdo->prepare('UPDATE SmsLog SET Status=1 WHERE SmsId=:id limit 1');
                $query->bindValue(':id', $data['id']);
                $res = $query->execute();
                $count = $query->rowCount();

                $query = null;
                $pdo = null;

                if (!$res || $count != 1) {
                    $log_type = 'db_update_fail';
                    goto log;
                }
            }

            return;

            log:
            logs([
                'status' => $log_type,
                'id' => $data['id'],
                'mobile' => $data['mobile'],
                'content' => $data['content'],
                'type' => $data['type'],
                'flag' => $data['flag'],
                'time' => date("Y-m-d H:i:s"),
                'http_code' => $status['code'],
                'http_result' => $status['result']
            ], $this->logPath());
        }
    }
}