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
            'Content' => self::VAR_TYPE_STRING,
            'Status' => self::VAR_TYPE_INT,
            'GoodsStatus' => self::VAR_TYPE_INT,
            'CreateTime' => self::VAR_TYPE_STRING,
            'UpdateTime' => self::VAR_TYPE_STRING,
            'InviteUserId' => self::VAR_TYPE_INT,
            'Channel' => self::VAR_TYPE_STRING,
            'Coupon' => self::VAR_TYPE_INT,
            'GroupId' => self::VAR_TYPE_INT,
            'AddressId' => self::VAR_TYPE_INT,
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
     * @see \Bare\Model::add() 新增
     * @see \Bare\Model::update() 更新
     * @see \Bare\Model::getInfoByIds() 按主键id查询
     * @see \Bare\Model::getList() 条件查询
     * @see \Bare\Model::delete() 删除
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

    // 支付类型, 0 appstore, 1 支付宝, 2 微信js 3 微信app 4 微信小程序
    const PAY_TYPE_APP_STORE = 0;
    const PAY_TYPE_ALIPAY = 1;
    const PAY_TYPE_WEIXIN_JS = 2;
    const PAY_TYPE_WEIXIN = 3;
    const PAY_TYPE_WEIXIN_XCX = 4;
    // 状态: 0 等待支付, 1 支付中，2 支付成功，3 支付失败，4 取消支付 5:已退款
    const STATUS_WAIT = 0;
    const STATUS_PAYING = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAILURE = 3;
    const STATUS_CANCELED = 4;
    const STATUS_REFUND = 5;
    //物流状态 0：等待发货 1：已发货 2：确认收货 3：已退货
    const GOODS_STATUS_WAIT = 0;
    const GOODS_STATUS_SEND = 1;
    const GOODS_STATUS_RECEIVE = 2;
    const GOODS_STATUS_REFUND = 3;

    /**
     * 生成支付流水号, appid(2) + $pay_type(1) + channel(6) + yymmddHHMMSSssssss(18) + rand_number
     *
     * @param int    $app_id   应用id
     * @param int    $pay_type 支付类型
     * @param string $channel  渠道号
     * @return string
     */
    public static function generateOrderNo($app_id, $pay_type, $channel)
    {
        if (strlen($channel) > 6) {
            $channel = substr($channel, 0, 6);
        }
        $us_str = sprintf('%f', microtime(true));
        $arr = explode('.', $us_str);
        $sn = sprintf("%d%d%s%s%d", $app_id, $pay_type, $channel, date('ymdHis'), $arr[1]);
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
        return self::getList(['OrderNo' => $sn], 0, 1)['data'];
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