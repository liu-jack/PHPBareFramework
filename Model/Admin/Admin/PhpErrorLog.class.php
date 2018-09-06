<?php
/**
 * PhpErrorLog.class.php php错误日志记录
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-9-4 下午4:11
 *
 */

namespace Admin;

use Bare\DB;
use Bare\M\Model;

class PhpErrorLog extends Model
{
    /**
     * 基础配置文件
     *
     * @var array
     */
    protected static $_conf = [
        // 必选, 数据库代码 (来自Bridge配置), w: 写, r: 读
        'db' => [
            'w' => DB::DB_ADMIN_W,
            'r' => DB::DB_ADMIN_R
        ],
        // 必选, 数据表名
        'table' => 'PhpErrorLog',
        // 必选, 字段信息
        'fields' => [
            'Id' => self::VAR_TYPE_KEY,
            'Type' => self::VAR_TYPE_INT,
            'Url' => self::VAR_TYPE_STRING,
            'Info' => self::VAR_TYPE_STRING,
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
            self::CF_RD_TIME => 86400,
            self::CF_RD_KEY => '', // 可选, redis KEY, "KeyName:%d", %d会用主键ID替代
        ],
    ];

    /**
     * @see Model::add() 新增
     * @see Model::update() 更新
     * @see Model::getInfoByIds() 按id查询
     * @see Model::getList() 条件查询
     * @see Model::delete() 删除
     */

    const TYPE_LIST = [
        E_NOTICE => '通知(E_NOTICE)',
        E_USER_NOTICE => '通知(E_USER_NOTICE)',
        E_DEPRECATED => '通知(E_DEPRECATED)',
        E_STRICT => '修改建议(E_STRICT)',
        E_ALL => '其他错误(E_ALL)',
        E_WARNING => '警告(E_WARNING)',
        E_USER_WARNING => '警告(E_USER_WARNING)',
        E_CORE_WARNING => '警告(E_CORE_WARNING)',
        E_COMPILE_WARNING => '警告(E_COMPILE_WARNING)',
        E_USER_DEPRECATED => '警告(E_USER_DEPRECATED)',
        E_PARSE => '语法解析错误(E_PARSE)',
        E_ERROR => '致命错误(E_ERROR)',
        E_USER_ERROR => '致命错误(E_USER_ERROR)',
        E_RECOVERABLE_ERROR => '致命错误(E_RECOVERABLE_ERROR)',
        E_CORE_ERROR => '致命错误(E_CORE_ERROR)',
        E_COMPILE_ERROR => '致命错误(E_COMPILE_ERROR)',
    ];
}