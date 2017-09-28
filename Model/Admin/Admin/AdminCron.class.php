<?php
/**
 * AdminCron.class.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-9-27 上午11:08
 *
 */

namespace Model\Admin\Admin;

use Bare\DB;
use Bare\ViewModel;

class AdminCron extends ViewModel
{
    const TYPE_PUSH = 1; // 推送
    const STATUS_WAIT = 0; // 待发送
    const STATUS_OK = 1; // 已发送
    const STATUS_FAIL = 2; // 发送失败

    const FD_CRON_ID = 'CronId';
    const FD_TYPE = 'Type';
    const FD_CRON_DATA = 'CronData';
    const FD_STATUS = 'Status';
    const FD_CRON_TIME = 'CronTime';
    const FD_CREATE_TIME = 'CreateTime';
    const EX_FD_START_TIME = 'StartTime';
    const EX_FD_END_TIME = 'EndTime';

    const TABLE = 'AdminCron';
    const TABLE_REMARK = '定时任务';
    // 配置文件
    protected static $_conf = [
        // 必选, 数据库连接(来自DBConfig配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DB::DB_ADMIN_W,
            self::CF_DB_R => DB::DB_ADMIN_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => self::TABLE,
        // 必选, 字段信息
        self::CF_FIELDS => [
            self::FD_CRON_ID => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_KEY,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_HIDDEN,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '序号',
            ],
            self::FD_TYPE => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_SEARCH_TYPE => self::FORM_SELECT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_SELECT,
                self::FORM_SELECT_OPTION => [
                    1 => '定时推送',
                ],
                self::FORM_FIELD_NAME => '类型',
            ],
            self::FD_CRON_DATA => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_ARRAY,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_TEXTAREA,
                self::FORM_FIELD_NAME => '数据',
            ],
            self::FD_STATUS => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_SEARCH_TYPE => self::FORM_SELECT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_RADIO,
                self::FORM_RADIO_OPTION => [
                    0 => '待发送',
                    1 => '已发送',
                    2 => '发送失败'
                ],
                self::FORM_FIELD_NAME => '状态',
            ],
            self::FD_CRON_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TIME,
                self::FORM_FIELD_NAME => '运行时间',
            ],
            self::FD_CREATE_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TIME,
                self::FORM_FIELD_NAME => '创建时间',
            ],
            self::EX_FD_START_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_HIDDEN,
                self::FIELD_MAP => self::FD_CREATE_TIME,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TIME,
                self::SEARCH_WHERE_OP => '>=',
                self::FORM_FIELD_NAME => '开始时间',
            ],
            self::EX_FD_END_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_HIDDEN,
                self::FIELD_MAP => self::FD_CREATE_TIME,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TIME,
                self::SEARCH_WHERE_OP => '<=',
                self::FORM_FIELD_NAME => '结束时间',
            ],
        ],
        // 可选, MC连接参数
        self::CF_MC => '',
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => '',
        // 可选, 超时时间, 默认不过期
        self::CF_MC_TIME => 86400,
    ];
    // 新增必须字段
    protected static $_add_must_fields = [
        self::FD_TYPE => 1,
        self::FD_CRON_DATA => 1,
        self::FD_CRON_TIME => 1,
    ];
    // 不可修改字段
    protected static $_un_modify_fields = [
        self::FD_CRON_ID => 1
    ];

    /**
     * @see \Bare\Model::add() 新增
     * @see \Bare\Model::update() 更新
     * @see \Bare\Model::getInfoByIds() 按主键id查询
     * @see \Bare\Model::getList() 条件查询
     * @see \Bare\Model::delete() 删除
     */
}