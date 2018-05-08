<?php
/**
 * Order.class.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-13 下午3:13
 *
 */

namespace Model\Payment;

use Bare\Api;
use Bare\Model;
use Classes\Payment\PayUtil;
use Common\RedisConst;
use Config\DBConfig;

class Order extends Model
{
    const ERR_LOG_PAY = 'Payment/Order/pay_err'; // 支付错误日志
    const ERR_LOG_REFUND = 'Payment/Order/refund_err'; // 退款错误日志
    /**
     * 基础配置文件
     *
     * @var array
     */
    protected static $_conf = [
        // 必选, 数据库代码 (来自DB配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DBConfig::DB_PAYMENT_W,
            self::CF_DB_R => DBConfig::DB_PAYMENT_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => 'Order',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'Id' => self::VAR_TYPE_KEY,
            'UserId' => self::VAR_TYPE_INT,
            'AppId' => self::VAR_TYPE_INT,
            'OutTradeNo' => self::VAR_TYPE_STRING,
            'Body' => self::VAR_TYPE_STRING,
            'TotalFee' => self::VAR_TYPE_STRING,
            'NotifyUrl' => self::VAR_TYPE_STRING,
            'CreateIp' => self::VAR_TYPE_STRING,
            'ExpireTime' => self::VAR_TYPE_STRING,
            'Status' => self::VAR_TYPE_INT,
            'NotifyStatus' => self::VAR_TYPE_INT,
            'NotifyTimes' => self::VAR_TYPE_INT,
            'OrderNo' => self::VAR_TYPE_STRING,
            'PayTime' => self::VAR_TYPE_STRING,
            'NotifyTime' => self::VAR_TYPE_STRING,
            'RefundTime' => self::VAR_TYPE_STRING,
            'CreateTime' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        self::CF_MC => DBConfig::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => 'PC_Order:%d',
        // 可选, 超时时间, 默认不过期
        self::CF_MC_TIME => 86400,
        // 可选, redis (来自DB配置), w: 写, r: 读
        self::CF_RD => [
            self::CF_DB_W => RedisConst::PAYMENT_DB_W,
            self::CF_DB_R => RedisConst::PAYMENT_DB_R,
            self::CF_RD_INDEX => RedisConst::PAYMENT_INDEX,
            self::CF_RD_TIME => 86400,
            self::CF_RD_KEY => '', // 可选, redis KEY, "KeyName:%d", %d会用主键ID替代
        ],
    ];

    /**
     * @see \Bare\Model::add() 新增
     * @see \Bare\Model::update() 更新
     * @see \Bare\Model::getInfoByIds() 按主键id查询
     * @see \Bare\Model::getList() 条件查询
     * @see \Bare\Model::delete() 删除
     */

    protected static $_add_must_fields = [
        'AppId' => true,
        'OutTradeNo' => true,
        'Body' => true,
        'TotalFee' => true,
        'NotifyUrl' => true,
        'OrderNo' => true,
    ];

    const UPDATE_DEL_CACHE_LIST = true; // 更新是否清除列表缓存
    const REDIS_INFO_ORDER_NO = 'REDIS_INFO_ORDER_NO:{OrderNo}';
    protected static $_cache_list_keys = [
        self::REDIS_INFO_ORDER_NO => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_REDIS,
            self::CACHE_LIST_FIELDS => 'OrderNo',
        ]
    ];

    // 状态: 0：待支付 1：支付成功 2：取消支付 3：支付失败 4：已退款
    const STATUS_WAIT = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_CANCELED = 2;
    const STATUS_FAILURE = 3;
    const STATUS_REFUND = 4;
    //通知状态 0:待通知 1：通知成功 2：通知失败
    const NOTIFY_STATUS_WAIT = 0;
    const NOTIFY_STATUS_SUCCESS = 1;
    const NOTIFY_STATUS_FAILURE = 2;

    /**
     * 生成支付流水号, appid(10) + yyyymmddHHMMSSssss(18) + rand_number
     *
     * @param int $app_id 应用id
     * @return string
     */
    public static function generateOrderNo($app_id)
    {
        $us_str = microtime(true);
        $arr = explode('.', $us_str);
        $sn = sprintf("%s%s%s", $app_id, date('YmdHis'), $arr[1]);
        $l = 32 - strlen($sn);
        if ($l > 0) {
            $num = rand(pow(10, $l - 1), pow(10, $l) - 1);
            $sn .= sprintf('%d', $num);
        }

        return $sn;
    }

    /**
     * 通过平台订单号获取订单详情
     *
     * @param string $sn 支付流水号
     * @return array|bool
     */
    public static function getOrderByNo($sn)
    {
        $redis_key = str_replace('{OrderNo}', $sn, self::REDIS_INFO_ORDER_NO);
        $data = self::getRedis(true)->getS($redis_key);
        if ($data === false) {
            $data = self::getPdo()->clear()->select('*')->from(self::$_conf[self::CF_TABLE])->where(['OrderNo' => $sn])->limit(1)->getOne();
            self::getRedis(true)->setS($redis_key, $data, self::$_conf[self::CF_RD][self::CF_RD_TIME]);
        }

        return $data;
    }

    /**
     * 订单支付
     *
     * @param $order
     * @return bool
     */
    public static function pay($order)
    {
        if ($order['Status'] === self::STATUS_SUCCESS) {
            return true;
        }
        if ($order['Status'] != self::STATUS_WAIT) {
            return false;
        }
        $res = User::updateBalance($order['UserId'], -$order['TotalFee']);
        if ($res === false) {
            $log = [
                'order_id' => $order['Id'],
                'msg' => 'update user balance err'
            ];
            logs($log, self::ERR_LOG_PAY);

            return false;
        }
        $app_info = Application::getInfoByIds($order['AppId']);
        $ret = User::updateBalance($app_info['UserId'], +$order['TotalFee']);
        if ($ret === false) {
            $log = [
                'order_id' => $order['Id'],
                'msg' => 'update merchant balance err'
            ];
            logs($log, self::ERR_LOG_PAY);

            return false;
        }
        $update = [
            'Status' => self::STATUS_SUCCESS,
            'UserId' => $order['UserId'],
            'PayTime' => date('Y-m-d H:i:s')
        ];
        $r = self::update($order['Id'], $update);
        if ($r === false) {
            $log = [
                'order_id' => $order['Id'],
                'msg' => 'update order status err'
            ];
            logs($log, self::ERR_LOG_PAY);
        }

        return true;
    }

    /**
     * 支付通知
     *
     * @param $sn
     * @return bool
     */
    public static function notify($sn)
    {
        $order = self::getOrderByNo($sn);
        if ($order['NotifyStatus'] == self::NOTIFY_STATUS_SUCCESS) {
            return true;
        }
        if ($order['Status'] != self::STATUS_SUCCESS) {
            return false;
        }
        $app_id = $order['AppId'];
        $app_info = Application::getInfoByIds($app_id);
        $mid = $app_info['MerchantId'];
        $post = [
            'app_secret' => $app_info['AppSecret'],
            'OutTradeNo' => $order['OutTradeNo'],
            'TotalFee' => $order['TotalFee'],
            'OrderNo' => $order['OrderNo'],
            'Status' => $order['Status'],
            'PayTime' => $order['PayTime'],
        ];
        $sign_str = PayUtil::signStr($post);
        $post['sign'] = PayUtil::sign($sign_str, $mid);
        $ret = Api::httpPost($order['NotifyUrl'], $post);

        if (strtoupper($ret) === 'SUCCESS') {
            $update = [
                'NotifyStatus' => self::NOTIFY_STATUS_SUCCESS,
                'NotifyTime' => date('Y-m-d H:i:s'),
                'NotifyTimes' => ['NotifyTimes', '+1'],
            ];
        } else {
            $update = [
                'NotifyStatus' => self::NOTIFY_STATUS_FAILURE,
                'NotifyTime' => date('Y-m-d H:i:s'),
                'NotifyTimes' => ['NotifyTimes', '+1'],
            ];
        }

        return self::update($order['Id'], $update);
    }

    /**
     * 订单退款
     *
     * @param $order
     * @return bool
     */
    public static function refund($order)
    {
        if ($order['Status'] != self::STATUS_SUCCESS || empty($order['UserId'])) {
            return false;
        }
        $app_info = Application::getInfoByIds($order['AppId']);
        $ret = User::updateBalance($app_info['UserId'], -$order['TotalFee']);
        if ($ret === false) {
            $log = [
                'order_id' => $order['Id'],
                'msg' => 'update merchant balance err'
            ];
            logs($log, self::ERR_LOG_REFUND);

            return false;
        }
        $res = User::updateBalance($order['UserId'], $order['TotalFee']);
        if ($res === false) {
            $log = [
                'order_id' => $order['Id'],
                'msg' => 'update user balance err'
            ];
            logs($log, self::ERR_LOG_REFUND);

            return false;
        }
        $update = [
            'Status' => self::STATUS_REFUND,
            'RefundTime' => date('Y-m-d H:i:s')
        ];
        $r = self::update($order['Id'], $update);
        if ($r === false) {
            $log = [
                'order_id' => $order['Id'],
                'msg' => 'update order status err'
            ];
            logs($log, self::ERR_LOG_REFUND);
        }

        return true;
    }
}