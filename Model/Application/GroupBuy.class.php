<?php
/**
 * GroupBuy.class.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-11 下午3:25
 *
 */

namespace Model\Application;

use Bare\RedisModel;
use Common\RedisConst;
use Config\DBConfig;
use Model\RedisDB\RedisGroupBuy;

class GroupBuy extends RedisModel
{
    /**
     * @return mixed|\Model\RedisDB\RedisGroupBuy
     */
    protected static function redisCache()
    {
        return RedisGroupBuy::instance();
    }

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
        self::CF_TABLE => 'GroupBuy',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'Id' => self::VAR_TYPE_KEY,
            'ProductId' => self::VAR_TYPE_INT,
            'GroupPrice' => self::VAR_TYPE_FLOAT,
            'GroupCount' => self::VAR_TYPE_INT,
            'JoinCount' => self::VAR_TYPE_INT,
            'UserId' => self::VAR_TYPE_INT,
            'Status' => self::VAR_TYPE_INT,
            'ExpireTime' => self::VAR_TYPE_INT,
            'ActStartTime' => self::VAR_TYPE_STRING,
            'ActEndTime' => self::VAR_TYPE_STRING,
            'StartTime' => self::VAR_TYPE_STRING,
            'EndTime' => self::VAR_TYPE_STRING,
            'SuccessTime' => self::VAR_TYPE_STRING,
            'CreateTime' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        self::CF_MC => '',
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => '',
        // 可选, 超时时间, 默认不过期
        self::CF_MC_TIME => 86400,
        // 可选, redis (来自DB配置), w: 写, r: 读
        self::CF_RD => [
            self::CF_DB_W => RedisConst::GROUP_DB_W,
            self::CF_DB_R => RedisConst::GROUP_DB_R,
            self::CF_RD_INDEX => RedisConst::GROUP_DB_INDEX,
            self::CF_RD_TIME => 3600,
            self::CF_RD_KEY => '', // 可选, redis KEY, "KeyName:%d", %d会用主键ID替代
        ],
        // 可选, 数据表分表前缀 User_%s
        self::CF_PREFIX_TABLE => '',
    ];

    /**
     * @see \Bare\RedisModel::add() 新增
     * @see \Bare\RedisModel::update() 更新
     * @see \Bare\RedisModel::updateCount() 更新计数
     * @see \Bare\RedisModel::getInfoByIds() 按主键id查询
     * @see \Bare\RedisModel::getList() 条件查询
     * @see \Bare\RedisModel::delete() 删除
     */

    protected static $_add_must_fields = [
        'ProductId' => 1,
        'UserId' => 1,
        'GroupCount' => 1,
        'ActStartTime' => 1,
        'ActEndTime' => 1,
    ];

    const GROUP_BUY_EXPIRE = 86400; // 团购有效期 s
    // 状态
    const STATUS_DEFAULT = 0;//默认
    const STATUS_START = 1;//拼团开始
    const STATUS_SUCCESS = 2;//拼团成功
    const STATUS_FAILURE = 3;//拼团失败

    const UPDATE_DEL_CACHE_LIST = true; // 更新是否清除列表缓存
    const REDIS_PRODUCT_GROUP_LIST = 'REDIS_PRODUCT_GROUP_LIST:{ProductId}';
    protected static $_cache_list_keys = [
        self::REDIS_PRODUCT_GROUP_LIST => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_REDIS,
            self::CACHE_LIST_FIELDS => 'ProductId',
        ],
    ];

    /**
     * 获取商品拼团中团购
     *
     * @param $pid
     * @return array|mixed
     */
    public static function getProductGroup($pid)
    {
        $redis_key = str_replace('{ProductId}', $pid, self::REDIS_PRODUCT_GROUP_LIST);
        $redis = self::getRedis(true);
        $ids = unserialize($redis->get($redis_key));
        if ($ids === false) {
            $ids_list = self::getList(['ProductId' => $pid, 'Status' => self::STATUS_START]);
            $ids = isset($ids_list['data']) ? $ids_list['data'] : [];
            $redis->set($redis_key, serialize($ids), self::$_conf[self::CF_RD][self::CF_RD_TIME]);
        }

        return $ids;
    }

    /**
     * 创建团购（创建支付订单时）
     *
     * @param $pid
     * @param $uid
     * @return bool|int|string
     */
    public static function createGroupBuy($pid, $uid)
    {
        $product = Product::getInfoByIds($pid);
        if (empty($product) || $product['Inventory'] < 1) {
            return false;
        }
        $add = [
            'ProductId' => $pid,
            'UserId' => $uid,
            'GroupCount' => $product['GroupNum'],
            'GroupPrice' => $product['GroupPrice'],
            'JoinCount' => 0,
            'Status' => self::STATUS_DEFAULT,
            'ExpireTime' => self::GROUP_BUY_EXPIRE,
            'ActStartTime' => $product['GroupStartTime'],
            'ActEndTime' => $product['GroupEndTime'],
            'CreateTime' => date('Y-m-d H:i:s'),
        ];

        return self::add($add);
    }

    /**
     * 开团成功（支付成功）
     *
     * @param $id
     * @return bool
     */
    public static function startGroupBuy($id)
    {
        $group = self::getInfoByIds($id);
        if (empty($group) && $group['Status'] != self::STATUS_DEFAULT) {
            return false;
        }
        $now = time();
        $end_time = strtotime($group['ActEndTime']);
        $expire = $now + $group['ExpireTime'];
        $act_end_time = min($end_time, $expire);
        $update = [
            'Status' => self::STATUS_START,
            'StartTime' => date('Y-m-d H:i:s', $now),
            'EndTime' => date('Y-m-d H:i:s', $act_end_time),
            'JoinCount' => 1,
        ];

        $ret = self::update($id, $update);
        if ($ret) {
            GroupBuyList::addLeader($id, $group['UserId']);
        }

        return $ret;
    }

    /**
     * 增加参加人数
     *
     * @param     $id
     * @param int $count
     * @return int
     */
    public static function incJoinCount($id, $count = 1)
    {
        return self::updateCount($id, ['JoinCount' => $count]);
    }

    /**
     * 拼团成功
     *
     * @param $id
     * @return bool
     */
    public static function groupBuySuccess($id)
    {
        $group = self::getInfoByIds($id);
        if (empty($group) || $group['Status'] != self::STATUS_START) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $update = [
            'Status' => self::STATUS_SUCCESS,
            'SuccessTime' => $now
        ];
        $ret = self::update($id, $update);
        if ($ret) {
            //TODO 拼团成功逻辑处理
        }

        return $ret;
    }

    /**
     * 拼团失败
     *
     * @param $id
     * @return bool
     */
    public static function groupBuyFailure($id)
    {
        $group = self::getInfoByIds($id);
        if (empty($group) || $group['Status'] != self::STATUS_START) {
            return false;
        }
        $update = [
            'Status' => self::STATUS_FAILURE,
        ];
        $ret = self::update($id, $update);
        if ($ret) {
            //TODO 拼团失败逻辑处理
        }

        return $ret;
    }

    /**
     * 查询超时拼团
     *
     * @param $offset
     * @param $limit
     * @return array|bool
     */
    public static function getTimeoutGroupBuy($offset, $limit)
    {
        $timeout = 600;
        $timeout_ts = time() - $timeout;
        $timeout_date = date('Y-m-d H:i:s');
        $time_min_date = date('Y-m-d H:i:s', $timeout_ts);

        $where = [
            'Status' => self::STATUS_START,
            'EndTime <=' => $timeout_date,
            'EndTime >=' => $time_min_date
        ];

        return self::getList($where, $offset, $limit);
    }

    /**
     * 查询剩余xx时间所有拼团
     *
     * @param int    $time
     * @param int    $range
     * @param string $field
     * @param int    $offset
     * @param int    $limit
     * @return array|bool
     */
    public static function getGroupBuyRemainTime(
        $time = 1800,
        $range = 300,
        $field = 'Id,ProductId,GroupCount,JoinCount',
        $offset = 0,
        $limit = 9999
    ) {
        $timeout_ts = time() + $time;
        $max_date = date('Y-m-d H:i:s', $timeout_ts);
        $min_date = date('Y-m-d H:i:s', $timeout_ts - $range);

        $where = [
            'Status' => self::STATUS_START,
            'EndTime <=' => $max_date,
            'EndTime >=' => $min_date
        ];

        return self::getList($where, $offset, $limit, $field);
    }
}