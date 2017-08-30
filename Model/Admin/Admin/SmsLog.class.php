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
use Bare\ViewModel;

/**
 * 发送短信模型
 * Class SmsLog
 *
 * @package Model\Admin
 */
class SmsLog extends ViewModel
{
    /**
     * 配置文件
     *
     * @var array
     */
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
            'SmsId' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_KEY,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_HIDDEN,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '序号',
            ],
            'Mobile' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TEXT,
                self::FORM_FIELD_NAME => '手机号',
            ],
            'Content' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_TEXTAREA,
                self::FORM_FIELD_NAME => '内容',
            ],
            'Type' => [
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
            'Flag' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TEXT,
                self::FORM_FIELD_NAME => '标识(验证码)',
            ],
            'Ip' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TEXT,
                self::FORM_FIELD_NAME => 'IP地址',
            ],
            'Used' => [
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
            'Status' => [
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
            'CreateTime' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TIME,
                self::FORM_FIELD_NAME => '创建时间',
            ],
            'StartTime' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_HIDDEN,
                self::FIELD_MAP => 'CreateTime',
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TIME,
                self::SEARCH_WHERE_OP => '>=',
                self::FORM_FIELD_NAME => '开始时间',
            ],
            'EndTime' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_HIDDEN,
                self::FIELD_MAP => 'CreateTime',
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
        self::CF_MC_TIME => 86400
    ];


    /**
     * 新增必须字段
     *
     * @var array
     */
    protected static $_add_must_fields = [
        'Mobile' => 1,
        'Content' => 1,
    ];

    /**
     * @param $mobile
     * @param $type
     * @return array
     */
    public static function getSmsLogByMobile($mobile, $type = 0)
    {
        $where = [
            'Mobile' => $mobile,
            'Type' => (int)$type
        ];
        $extra = [
            'fields' => '*',
            'offset' => 0,
            'limit' => 1,
        ];
        $ret = parent::getDataByFields($where, $extra);

        return !empty($ret['data'][0]) ? $ret['data'][0] : [];
    }
}