<?php
/**
 * Order.class.php
 * 支付订单管理
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-12 下午3:36
 *
 */

namespace Model\Application;

use Bare\M\Model;
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
            self::CF_DB_W => DBConfig::DB_APPLICATION_W,
            self::CF_DB_R => DBConfig::DB_APPLICATION_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => 'Order',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'Id' => self::VAR_TYPE_KEY,
            'AppId' => self::VAR_TYPE_INT,
            'AppType' => self::VAR_TYPE_INT,
            'DeviceId' => self::VAR_TYPE_STRING,
            'UserId' => self::VAR_TYPE_INT,
            'GoodsInfo' => self::VAR_TYPE_STRING,
            'OrderNo' => self::VAR_TYPE_STRING,
            'ProductId' => self::VAR_TYPE_INT,
            'PayType' => self::VAR_TYPE_INT,
            'TradeNo' => self::VAR_TYPE_STRING,
            'TotalFee' => self::VAR_TYPE_INT,
            'ThirdNo' => self::VAR_TYPE_STRING,
            'Content' => self::VAR_TYPE_ARRAY,
            'Status' => self::VAR_TYPE_INT,
            'GoodsStatus' => self::VAR_TYPE_INT,
            'InviteUserId' => self::VAR_TYPE_INT,
            'Channel' => self::VAR_TYPE_STRING,
            'Coupon' => self::VAR_TYPE_INT,
            'GroupId' => self::VAR_TYPE_INT,
            'AddressId' => self::VAR_TYPE_INT,
            'PayTime' => self::VAR_TYPE_STRING,
            'RefundTime' => self::VAR_TYPE_STRING,
            'CreateTime' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        self::CF_MC => DBConfig::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => 'Order:%d',
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
     * @see \Bare\M\Model::add() 新增
     * @see \Bare\M\Model::update() 更新
     * @see \Bare\M\Model::getInfoByIds() 按主键id查询
     * @see \Bare\M\Model::getList() 条件查询
     * @see \Bare\M\Model::delete() 删除
     */

    protected static $_add_must_fields = [
        'AppId' => true,
        'UserId' => true,
        'GoodInfo' => true,
        'OrderNo' => true,
        'ProductId' => true,
        'PayType' => true,
        'TotalFee' => true,
    ];

    const UPDATE_DEL_CACHE_LIST = true; // 更新是否清除列表缓存
    const MC_INFO_ORDER_NO = 'MC_INFO_ORDER_NO:{OrderNo}';
    protected static $_cache_list_keys = [
        self::MC_INFO_ORDER_NO => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_MC,
            self::CACHE_LIST_FIELDS => 'OrderNo',
        ]
    ];

    // 支付类型, 0 appstore, 1 支付宝, 2 微信js 3 微信app 4 微信小程序 5:Pay
    const PAY_TYPE_APP_STORE = 0;
    const PAY_TYPE_ALIPAY = 1;
    const PAY_TYPE_WEIXIN_JS = 2;
    const PAY_TYPE_WEIXIN = 3;
    const PAY_TYPE_WEIXIN_XCX = 4;
    const PAY_TYPE_PAY = 5;
    // 状态: 0 等待支付, 1 支付成功， 2 支付中，3 支付失败，4 取消支付 5:已退款
    const STATUS_WAIT = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_PAYING = 2;
    const STATUS_FAILURE = 3;
    const STATUS_CANCELED = 4;
    const STATUS_REFUND = 5;
    //物流状态 0：等待发货 1：已发货 2：确认收货 3：已退货
    const GOODS_STATUS_WAIT = 0;
    const GOODS_STATUS_SEND = 1;
    const GOODS_STATUS_RECEIVE = 2;
    const GOODS_STATUS_REFUND = 3;

    /**
     * 生成支付流水号, $pay_type(1) + channel(8) + yymmddHHMMSSssssss(18) + rand_number
     *
     * @param int    $pay_type 支付类型
     * @param string $channel  渠道号
     * @return string
     */
    public static function generateOrderNo($pay_type, $channel)
    {
        if (strlen($channel) > 8) {
            $channel = substr($channel, 0, 8);
        }
        $us_str = sprintf('%f', microtime(true));
        $arr = explode('.', $us_str);
        $sn = sprintf("%d%s%s%d", $pay_type, $channel, date('YmdHis'), $arr[1]);
        $l = 32 - strlen($sn);
        if ($l > 0) {
            $num = rand(pow(10, $l - 1), pow(10, $l) - 1);
            $sn .= sprintf('%d', $num);
        }

        return $sn;
    }

    /**
     * 通过sn获取订单详情
     *
     * @param string $sn 支付流水号
     * @return array|bool
     */
    public static function getOrderByNo($sn)
    {
        $mc_key = str_replace('{OrderNo}', $sn, self::MC_INFO_ORDER_NO);
        $data = self::getMC()->get($mc_key);
        if ($data === false) {
            $data = self::getPdo()->clear()->select('*')->from(self::$_conf[self::CF_TABLE])->where(['OrderNo' => $sn])->limit(1)->getOne();
            self::getMC()->set($mc_key, $data, self::$_conf[self::CF_MC_TIME]);
        }

        return $data;
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