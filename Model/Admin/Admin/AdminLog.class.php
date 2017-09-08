<?php
/**
 * AdminLog.class.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/5/24 12:33
 *
 */

namespace Model\Admin\Admin;

use Bare\ViewModel;
use Bare\DB;

/**
 * 后台操作日志模型
 * Class AdminLog
 *
 * @package Model\Admin
 */
class AdminLog extends ViewModel
{
    const FD_LOG_ID = 'LogId';
    const FD_USER_ID = 'UserId';
    const FD_USER_NAME = 'UserName';
    const FD_ITEM_ID = 'ItemId';
    const FD_ITEM_NAME = 'ItemName';
    const FD_MENU_KEY = 'MenuKey';
    const FD_MENU_NAME = 'MenuName';
    const FD_LOG_FLAG = 'LogFlag';
    const FD_LOG = 'Log';
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
        self::CF_TABLE => 'AdminLog',
        // 必选, 字段信息
        self::CF_FIELDS => [
            self::FD_LOG_ID => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_KEY,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '序号',
            ],
            self::FD_USER_ID => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '管理员ID',
            ],
            self::FD_USER_NAME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '用户名',
            ],
            self::FD_ITEM_ID => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '项目ID',
            ],
            self::FD_ITEM_NAME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '项目名称',
            ],
            self::FD_MENU_KEY => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '菜单URL',
            ],
            self::FD_MENU_NAME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '操作菜单',
            ],
            self::FD_LOG_FLAG => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '操作细分',
            ],
            self::FD_LOG => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '操作内容',
            ],
            self::FD_CREATE_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '操作时间',
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
        self::CF_MC_TIME => 86400
    ];


    // 新增必须字段
    protected static $_add_must_fields = [
        self::FD_USER_ID => 1,
        self::FD_ITEM_ID => 1,
        self::FD_LOG => 1,
    ];

    /**
     * 记录后台日志
     *
     * @param string $title    操作名称
     * @param string $option   操作细分
     * @param int    $itemid   操作项目id
     * @param array  $data     操作数据
     * @param string $itemname 项目名称（数据表名）
     * @return bool|int|string
     */
    public static function log($title, $option, $itemid = 0, $data = [], $itemname = '')
    {
        if (isset($data['Password'])) {
            unset($data['Password']);
        }
        $adddata = [
            self::FD_USER_ID => $_SESSION['_admin_info']['AdminUserId'],
            self::FD_USER_NAME => $_SESSION['_admin_info']['AdminRealName'],
            self::FD_ITEM_ID => $itemid,
            self::FD_ITEM_NAME => $itemname,
            self::FD_MENU_KEY => $GLOBALS['_URL'],
            self::FD_MENU_NAME => $title,
            self::FD_LOG_FLAG => $option,
            self::FD_LOG => is_array($data) ? serialize($data) : $data,
        ];

        return self::add($adddata);
    }
}