<?php
/**
 * Order.class.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-13 下午3:13
 *
 */

namespace Model\Payment;

use Bare\Model;
use Config\DBConfig;

class Order extends Model
{
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
            self::CF_DB_W => '',
            self::CF_DB_R => '',
            self::CF_RD_INDEX => 0,
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

    // 状态: 0：待支付 1：支付成功 2：取消支付 3：已退款
    const STATUS_WAIT = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_CANCELED = 2;
    const STATUS_REFUND = 3;
    //通知状态 0:待通知 1：通知成功 2：通知失败
    const NOTIFY_STATUS_WAIT = 0;
    const GOODS_STATUS_SUCCESS = 1;
    const GOODS_STATUS_FAILURE = 2;

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
     * 通过第三方订单号获取订单详情
     *
     * @param string $sn 支付流水号
     * @return array|bool
     */
    public static function getOrderByNo($sn)
    {
        return self::getList(['OutTradeNo' => $sn], 0, 1)['data'];
    }

    /**
     * 支付成功
     *
     * @param string $sn 订单流水号
     * @param        $info
     * @return int
     */
    public static function paySuccess($sn, $info)
    {
        $pay_info = self::getOrderByNo($sn);
        if (empty($pay_info)) {
            return false;
        }

        return self::update($pay_info['Id'], $info);
    }
}