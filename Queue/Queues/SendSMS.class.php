<?php
/**
 * 发送手机短信队列
 */

namespace Queue\Queues;

use Queue\Queue;

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
            $this->log([
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

/**
 * Trait SmsCtrl
 *
 * @package Notice
 */
trait SmsCtrl
{
    protected static function _Send($mobile, $content, $site = 1)
    {
        $url = self::_getUrl('send');
        if ($site == 1) {
            $content .= '【亲宝头条】';
        } else {
            $content .= '【她头条】';
        }

        $query = self::_query($url, $mobile, $content);

        return [
            'code' => $query['httpcode'],
            'errno' => $query['errno'],
            'result' => $query['result'],
            'succ' => $query['result']['error'] == 0 ? true : false
        ];
    }

    protected static function _Status()
    {
        $url = self::_getUrl('status');
        $query = self::_query($url);
        if (is_array($query) && isset($query['result']['deposit'])) {
            return $query['result']['deposit'];
        }

        return false;
    }

    private static function _query($url, $mobile = false, $content = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:key-c029e7e26af188cf5c33be61dd4544ad');

        if ($mobile !== false) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['mobile' => $mobile, 'message' => $content]);
        }

        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'result' => json_decode($result, true),
            'errno' => $errno,
            'httpcode' => $httpcode
        ];
    }

    private static function _getUrl($action = 'send')
    {
        $url = 'http://sms-api.luosimao.com/v1/';

        return $url . $action . '.json';
    }
}