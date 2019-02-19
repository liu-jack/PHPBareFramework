<?php
/**
 * SmsLog.class.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/5/24 14:18
 *
 */

namespace Model\Admin\Admin;

use Bare\DB;
use Bare\M\ViewModel;

/**
 * 发送短信模型
 * Class SmsLog
 *
 * @package Model\Admin
 */
class SmsLog extends ViewModel
{
    const FD_SMS_ID = 'SmsId';
    const FD_MOBILE = 'Mobile';
    const FD_CONTENT = 'Content';
    const FD_TYPE = 'Type';
    const FD_FLAG = 'Flag';
    const FD_IP = 'Ip';
    const FD_USED = 'Used';
    const FD_STATUS = 'Status';
    const FD_CREATE_TIME = 'CreateTime';
    const EX_FD_START_TIME = 'StartTime';
    const EX_FD_END_TIME = 'EndTime';
    // 配置文件
    protected static $_conf = [
        // 必选, 数据库连接(来自DBConfig配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DB::DB_ADMIN_W,
            self::CF_DB_R => DB::DB_ADMIN_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => 'SmsLog',
        // 必选, 字段信息
        self::CF_FIELDS => [
            self::FD_SMS_ID => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_KEY,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_HIDDEN,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '序号',
            ],
            self::FD_MOBILE => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TEXT,
                self::FORM_FIELD_NAME => '手机号',
            ],
            self::FD_CONTENT => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_TEXTAREA,
                self::FORM_FIELD_NAME => '内容',
            ],
            self::FD_TYPE => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_SEARCH_TYPE => self::FORM_SELECT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_SELECT,
                self::FORM_SELECT_OPTION => [
                    0 => '通用',
                    1 => '登录',
                    2 => '注册',
                    3 => '找回密码',
                ],
                self::FORM_FIELD_NAME => '类别',
            ],
            self::FD_FLAG => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TEXT,
                self::FORM_FIELD_NAME => '标识(验证码)',
            ],
            self::FD_IP => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TEXT,
                self::FORM_FIELD_NAME => 'IP地址',
            ],
            self::FD_USED => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_SEARCH_TYPE => self::FORM_SELECT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_RADIO,
                self::FORM_RADIO_OPTION => [
                    0 => '未使用',
                    1 => '已使用'
                ],
                self::FORM_FIELD_NAME => '是否使用',
            ],
            self::FD_STATUS => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_SEARCH_TYPE => self::FORM_SELECT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_RADIO,
                self::FORM_RADIO_OPTION => [
                    0 => '未发送',
                    1 => '已发送'
                ],
                self::FORM_FIELD_NAME => '状态',
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
        self::FD_MOBILE => 1,
        self::FD_CONTENT => 1,
    ];

    /**
     * @see \Bare\M\Model::add() 新增
     * @see \Bare\M\Model::update() 更新
     * @see \Bare\M\Model::getInfoByIds() 按主键id查询
     * @see \Bare\M\Model::getList() 条件查询
     * @see \Bare\M\Model::delete() 删除
     */

    /**
     * @param $mobile
     * @param $type
     * @return array
     */
    public static function getSmsLogByMobile($mobile, $type = 0)
    {
        $where = [
            self::FD_MOBILE => $mobile,
            self::FD_TYPE => (int)$type
        ];
        $extra = [
            self::EXTRA_FIELDS => '*',
            self::EXTRA_OFFSET => 0,
            self::EXTRA_LIMIT => 1,
        ];
        $ret = parent::getDataByFields($where, $extra);

        return !empty($ret['data'][0]) ? $ret['data'][0] : [];
    }
}