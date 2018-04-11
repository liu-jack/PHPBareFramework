<?php
/**
 * GroupBuyList.class.php
 * 团购成员
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-11 下午5:34
 *
 */

namespace Model\Application;

use Bare\Model;
use Config\DBConfig;

class GroupBuyList extends Model
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
        self::CF_TABLE => 'GroupBuyList',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'Id' => self::VAR_TYPE_KEY,
            'GroupId' => self::VAR_TYPE_INT,
            'UserId' => self::VAR_TYPE_INT,
            'Type' => self::VAR_TYPE_INT,
            'PayState' => self::VAR_TYPE_INT,
            'PayTime' => self::VAR_TYPE_STRING,
            'CreateTime' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        self::CF_MC => DBConfig::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => '',
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

    const GROUP_MEMBER_PAY_TIME = 900; // 成员支付时间

    const TYPE_LEADER = 1;//团长
    const TYPE_NORMAL = 2;//成员

    /**
     * 查询用户最后加入的团
     *
     * @param $userId
     * @return bool|null|string
     */
    public static function getLastGroupByUid($uid)
    {
        $where = [
            'UserId' => $uid,
        ];
        $member = self::getList($where, 0, 1, '*');
        if (!empty($member) && ($member['Type'] == self::TYPE_LEADER || strtotime($member['CreateTime']) > time() - self::GROUP_MEMBER_PAY_TIME)) {
            return GroupBuy::getInfoByIds($member['GroupId']);
        }

        return false;
    }
}