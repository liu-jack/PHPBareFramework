<?php
/**
 * Version.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-9-21 下午4:19
 *
 */

namespace Model\Mobile;

use Bare\DB;
use Bare\Model;

class Version extends Model
{
    protected static $_conf = [
        // 必选, 数据库代码 (来自DB配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DB::DB_MOBILE_W,
            self::CF_DB_R => DB::DB_MOBILE_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => 'AppVersion',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'Id' => self::VAR_TYPE_KEY,
            'AppId' => self::VAR_TYPE_INT,
            'VersionCode' => self::VAR_TYPE_STRING,
            'Description' => self::VAR_TYPE_STRING,
            'DownUrl' => self::VAR_TYPE_STRING,
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
            self::CF_DB_W => '',
            self::CF_DB_R => '',
            self::CF_RD_INDEX => 0,
            self::CF_RD_TIME => 0,
        ],
    ];

    /**
     * @see \Bare\Model::add() 新增
     * @see \Bare\Model::update() 更新
     * @see \Bare\Model::getInfoByIds() 按主键id查询
     * @see \Bare\Model::getList() 条件查询
     * @see \Bare\Model::delete() 删除
     */
}