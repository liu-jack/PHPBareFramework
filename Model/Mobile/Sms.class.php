<?php

/**
 * 短信发送类
 *
 * @author suning <snsnsky@gmail.com>
 *
 * $Id$
 */

namespace Model\Mobile;

use Bare\DB;
use Bare\M\Queue;
use Classes\Sms\LsmSms as SmsCtrl;

class Sms
{
    use SmsCtrl;

    const SMS_TYPE_DEFAULT = 0;       // 通用
    const SMS_TYPE_LOGIN = 1;         // 登录
    const SMS_TYPE_REG = 2;           // 注册
    const SMS_TYPE_FINDPWD = 3;       // 找回密码
    const SMS_TYPE_MODIFY_MOBILE = 4; // 修改手机号码
    const SMS_TYPE_INVITE = 5;        // 邀请
    const SMS_TYPE_BIND = 6;          // 绑定手机

    const VERIFY_SMS_REDIS_TIME = 10;        //设定验证码可重试的次数
    const VERIFY_SMS_REDIS_KEY = "M:%s:%s";  //验证码redis的KEY设定
    const VERIFY_SMS_REDIS_TIMEOUT = 900;    //reids验证超时时间

    /**
     * 发送单条手机短信
     *
     * @param string $mobile    手机号码
     * @param string $content   短信内容
     * @param int    $type      分类 1:登录 2:注册 3:找回密码
     * @param string $flag      识别标志记录, 如验证时,记录验证码
     * @param bool   $add_queue 是否使用队列, 默认是
     * @param bool   $antibots  是否走防刷检测, 默认是
     *
     * @return array    ['status' => true/false, 'code' => xxx]
     *                     200: 成功
     *                     201: 数据库写入失败
     *                     202: 队列写入失败
     *                     203: 短信接口调用失败
     *                     230: 手机号码发送超出限制
     *                     231: IP地址发送超出限制
     */
    public static function send($mobile, $content, $type = 0, $flag = '', $add_queue = true, $antibots = true)
    {
        //是否走防刷检测
        if ($antibots == true) {
            $bots = self::_antiBots($mobile);
            if ($bots !== true) {
                return $bots;
            }
        }

        $sms_id = self::_addSms($mobile, $content, $type, $flag);

        if ($sms_id === false) {
            return ['status' => false, 'code' => 201];
        }

        if ($add_queue) {
            $pack = [
                'mobile' => $mobile,
                'content' => $content,
                'type' => $type,
                'flag' => $flag,
                'id' => $sms_id
            ];

            $res = Queue::add('SendSMS', serialize($pack));
            if ($res) {
                goto succ;
            }

            return ['status' => false, 'code' => 202];
        }

        $status = self::_Send($mobile, $content);
        if ($status['succ'] === false) {
            logs([
                'id' => $sms_id,
                'mobile' => $mobile,
                'content' => $content,
                'type' => $type,
                'flag' => $flag,
                'time' => date("Y-m-d H:i:s"),
                'http_code' => $status['code'],
                'http_result' => $status['result']
            ], 'Sms/Fail');

            return ['status' => false, 'code' => 203];
        }
        self::_updateSms($sms_id, ['Status' => 1]);

        succ:

        return ['status' => true, 'code' => 200];

    }

    private static function _antiBots($mobile)
    {
        $cache_time = 24 * 3600;
        $max_ip_count = 50;
        $max_mobile_count = 10;

        $ip = trim(ip());

        if (defined('__ENV__') && __ENV__ != 'ONLINE') {
            return true;
        }

        $redis = DB::redis(DB::REDIS_OTHER_W, 3);
        $ret = $redis->multi(\Redis::PIPELINE)->incr($mobile)->incr($ip)->expire($mobile, $cache_time)->expire($ip, $cache_time)->exec();

        $mobile_count = $ret[0];
        $ip_count = $ret[1];

        if ($mobile_count > $max_mobile_count) {
            return ['status' => false, 'code' => 230];
        }

        if ($ip_count > $max_ip_count) {
            return ['status' => false, 'code' => 231];
        }

        return true;
    }

    private static function _addSms($mobile, $content, $type, $flag)
    {
        global $app;
        $pdo = DB::pdo(DB::DB_ADMIN_W);
        $res = $pdo->insert('SmsLog', [
            'Mobile' => $mobile,
            'Content' => $content,
            'Type' => $type,
            'Flag' => $flag,
            'Ip' => ip(),
            'Used' => 0,
            'Status' => 0,
            'CreateTime' => date("Y-m-d H:i:s")
        ]);
        if ($res > 0) {
            return $pdo->lastInsertId();
        }

        return false;
    }

    private static function _updateSms($sms_id, $data)
    {
        $pdo = DB::pdo(DB::DB_ADMIN_W);
        $res = $pdo->update('SmsLog', $data, ['SmsId' => $sms_id]);
        if ($res > 0) {
            return true;
        }

        return false;
    }

    /**
     * 验证短信CODE
     *
     * @param string  $mobile 手机号码
     * @param integer $type   类型
     * @param integer $code   要检查的code
     * @param integer $time   有效时间, 单位秒, 默认15分钟
     * @return bool           正确返回true, 失败返回false
     */
    public static function verifySms($mobile, $type, $code, $time = 900)
    {
        // 方便调试
        if (__ENV__ != 'ONLINE' && $code == '888888') {
            return true;
        }

        $sms = self::getLastSms($mobile, $type);

        if (!empty($sms['SmsId'])) {
            //记入redis.只允许10次
            $redis = DB::redis(DB::REDIS_OTHER_W, 3);
            $mckey = sprintf(self::VERIFY_SMS_REDIS_KEY, $mobile, $type);
            $count = $redis->get($mckey);
            $newcount = empty($count) ? 1 : $count + 1;
            if ($newcount > self::VERIFY_SMS_REDIS_TIME) {
                Sms::setSmsUsed($sms['SmsId']);
                $redis->delete($mckey);

                return false;
            }
            $redis->set($mckey, $newcount, self::VERIFY_SMS_REDIS_TIMEOUT);

            $sms_time = strtotime($sms['CreateTime']);
            if ($sms['Flag'] == $code && time() - $sms_time <= $time) {
                Sms::setSmsUsed($sms['SmsId']);
                $redis->delete($mckey);

                return true;
            }
        }

        return false;
    }

    /**
     * 获取最后一条发送短信
     *
     * @param string $mobile 手机号码
     * @param int    $type   短信类型
     * @param bool   $used   过滤出未使用的
     *
     * @return array                见SmsLog表格式
     */
    public static function getLastSms($mobile, $type, $used = true)
    {
        $used_sql = $used ? 'and `Used` = 0 and `Status` = 1' : '';

        $pdo = DB::pdo(DB::DB_ADMIN_R);
        $query = $pdo->prepare("select * from SmsLog where `Mobile`=:mobile and `Type`=:type {$used_sql} ORDER BY SmsId DESC limit 1");
        $query->bindValue(':mobile', $mobile, \PDO::PARAM_STR);
        $query->bindValue(':type', (int)$type, \PDO::PARAM_INT);
        $query->execute();
        $data = $query->fetch();

        return $data;
    }

    public static function setSmsUsed($sms_id)
    {
        return self::_updateSms($sms_id, ['Used' => 1]);
    }

    /**
     * 获取短信余额数量
     *
     * @return integer|bool 成功返回数量, 查询失败返回false
     */
    public static function getBalanceCount()
    {
        return self::_Status();
    }
}