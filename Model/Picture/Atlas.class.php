<?php
/**
 * Atlas.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-10-19 下午4:03
 *
 */

namespace Model\Picture;

use Bare\DB;
use Bare\ViewModel;
use Common\PathConst;
use Common\Upload;

class Atlas extends ViewModel
{
    const FD_ATLAS_ID = 'AtlasId';
    const FD_TITLE = 'Title';
    const FD_DESCRIPTION = 'Description';
    const FD_COVER = 'Cover';
    const FD_ATLAS_TIME = 'AtlasTime';
    const FD_CREATE_TIME = 'CreateTime';
    const EX_FD_START_TIME = 'StartTime';
    const EX_FD_END_TIME = 'EndTime';

    const TABLE = 'Atlas';
    const TABLE_REMARK = '相册';
    // 配置文件
    protected static $_conf = [
        // 必选, 数据库连接(来自DBConfig配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DB::DB_PICTURE_W,
            self::CF_DB_R => DB::DB_PICTURE_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => self::TABLE,
        // 必选, 字段信息
        self::CF_FIELDS => [
            self::FD_ATLAS_ID => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_KEY,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_HIDDEN,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '序号',
            ],
            self::FD_TITLE => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::SEARCH_WHERE_OP => 'LIKE',
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TEXT,
                self::FORM_FIELD_NAME => '标题',
            ],
            self::FD_DESCRIPTION => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_TEXTAREA,
                self::FORM_FIELD_NAME => '描述',
            ],
            self::FD_COVER => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_IMG,
                self::FORM_FIELD_NAME => '封面',
            ],
            self::FD_ATLAS_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TIME,
                self::FORM_FIELD_NAME => '相册时间',
            ],
            self::FD_CREATE_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TIME,
                self::FORM_FIELD_NAME => '创建时间',
            ],
            self::EX_FD_START_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_HIDDEN,
                self::FIELD_MAP => self::FD_ATLAS_TIME,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TIME,
                self::SEARCH_WHERE_OP => '>=',
                self::FORM_FIELD_NAME => '开始时间',
            ],
            self::EX_FD_END_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_HIDDEN,
                self::FIELD_MAP => self::FD_ATLAS_TIME,
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
        self::FD_TITLE => 1,
    ];
    // 不可修改字段
    protected static $_un_modify_fields = [
        self::FD_ATLAS_ID => 1,
    ];

    /**
     * @see \Bare\Model::add() 新增
     * @see \Bare\Model::update() 更新
     * @see \Bare\Model::getInfoByIds() 按主键id查询
     * @see \Bare\Model::getList() 条件查询
     * @see \Bare\Model::delete() 删除
     */

    /**
     * 上传封面
     *
     * @param array $cover
     * @param int   $id
     * @return bool|mixed
     */
    public static function uploadCover($cover, $id = 0)
    {
        $ret = Upload::saveImg(PathConst::IMG_ATLAS_COVER, $cover, PathConst::IMG_ATLAS_COVER_SIZE, $id);
        if (is_array($ret) && $ret['status'] == true) {
            return current($ret['thumb']);
        }

        return false;
    }
}