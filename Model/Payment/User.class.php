<?php
/**
 * User.class.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-13 下午5:19
 *
 */

namespace Model\Payment;

use Bare\Model;
use Config\DBConfig;

class User extends Model
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
        self::CF_TABLE => 'User',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'UserId' => self::VAR_TYPE_KEY,
            'UserNick' => self::VAR_TYPE_STRING,
            'HeadUrl' => self::VAR_TYPE_STRING,
            'RealName' => self::VAR_TYPE_STRING,
            'Balance' => self::VAR_TYPE_INT,
            'PayPassword' => self::VAR_TYPE_PASSWORD,
            'UpdateTime' => self::VAR_TYPE_STRING,
            'CreateTime' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        self::CF_MC => DBConfig::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => 'PC_User:%d',
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

    /**
     * 用户余额更改
     *
     * @param $uid
     * @param $num
     * @return bool
     */
    public static function updateBalance($uid, $num)
    {
        return self::update($uid, ['Balance' => ['Balance', '+' . $num]]);
    }
}