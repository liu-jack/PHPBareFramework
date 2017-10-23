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

class Photo extends ViewModel
{
    const FD_PHOTO_ID = 'PhotoId';
    const FD_ATLAS_ID = 'AtlasId';
    const FD_TITLE = 'Title';
    const FD_DESCRIPTION = 'Description';
    const FD_IMG_URL = 'ImgUrl';
    const FD_PHOTO_TIME = 'PhotoTime';
    const FD_PHOTO_ADDRESS = 'PhotoAddress';
    const FD_CREATE_TIME = 'CreateTime';
    const EX_FD_START_TIME = 'StartTime';
    const EX_FD_END_TIME = 'EndTime';

    const TABLE = 'Photo';
    const TABLE_REMARK = '相片';
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
            self::FD_PHOTO_ID => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_KEY,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_HIDDEN,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_FIELD_NAME => '序号',
            ],
            self::FD_ATLAS_ID => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_SEARCH_TYPE => self::FORM_SELECT,
                self::FIELD_FORM_TYPE => self::FORM_SELECT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FORM_RADIO_OPTION => [],
                self::FORM_FIELD_NAME => '相册',
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
            self::FD_IMG_URL => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_IMG,
                self::FORM_FIELD_NAME => '相片',
            ],
            self::FD_PHOTO_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TIME,
                self::FORM_FIELD_NAME => '相片时间',
            ],
            self::FD_PHOTO_ADDRESS => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TEXT,
                self::FORM_FIELD_NAME => '相片地址',
            ],
            self::FD_CREATE_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TIME,
                self::FORM_FIELD_NAME => '创建时间',
            ],
            self::EX_FD_START_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_HIDDEN,
                self::FIELD_MAP => self::FD_PHOTO_TIME,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TIME,
                self::SEARCH_WHERE_OP => '>=',
                self::FORM_FIELD_NAME => '开始时间',
            ],
            self::EX_FD_END_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_HIDDEN,
                self::FIELD_MAP => self::FD_PHOTO_TIME,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TIME,
                self::SEARCH_WHERE_OP => '<=',
                self::FORM_FIELD_NAME => '结束时间',
            ],
        ],
        // 可选, MC连接参数
        self::CF_MC => DB::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => 'Photo:%d',
        // 可选, 超时时间, 默认不过期
        self::CF_MC_TIME => 86400,
    ];
    // 新增必须字段
    protected static $_add_must_fields = [
        self::FD_ATLAS_ID => 1,
    ];
    // 不可修改字段
    protected static $_un_modify_fields = [
        self::FD_PHOTO_ID => 1,
    ];
    const MC_ATLAS_PHOTO_LIST = 'ATLAS_PHOTO_LIST:{AtlasId}';
    protected static $_cache_list_keys = [
        self::MC_ATLAS_PHOTO_LIST => [self::CACHE_LIST_FIELDS => 'AtlasId']
    ];

    /**
     * @see \Bare\Model::add() 新增
     * @see \Bare\Model::update() 更新
     * @see \Bare\Model::getInfoByIds() 按主键id查询
     * @see \Bare\Model::getList() 条件查询
     * @see \Bare\Model::delete() 删除
     */

    /**
     * 获取搜索或表单选项
     *
     * @return array
     */
    public static function getAtlasIdOption()
    {
        $list_info = Atlas::getList([], 0, 0, 'AtlasId,Title');
        $data = [];
        if (!empty($list_info['data'])) {
            foreach ($list_info['data'] as $k => $v) {
                $data[$v[Atlas::FD_ATLAS_ID]] = $v[Atlas::FD_TITLE];
            }
        }

        return $data;
    }

    /**
     * 获取相册中图片
     *
     * @param int $atlasid
     * @param int $offset
     * @param int $limit
     * @return array|string
     */
    public static function getListByAtlasId(int $atlasid, $offset = 0, $limit = 10)
    {
        $mc_key = str_replace('{AtlasId}', $atlasid, self::MC_ATLAS_PHOTO_LIST);
        $mc = DB::memcache(self::$_conf[self::CF_MC]);
        $data = $mc->get($mc_key);
        if (empty($data['count'])) {
            $data = [
                'count' => 0,
                'data' => []
            ];
            $list_info = self::getList([self::FD_ATLAS_ID => $atlasid], 0, 0, self::FD_PHOTO_ID);
            if (!empty($list_info['data'])) {
                $data['count'] = intval($list_info['count']);
                foreach ($list_info['data'] as $v) {
                    $data['data'][] = $v[self::FD_PHOTO_ID];
                }
                $mc->set($mc_key, $data);
            }
        }
        if ($limit > 0) {
            $data['data'] = array_slice($data['data'], $offset, $limit);
        }

        return $data;
    }

    /**
     * 上传相片
     *
     * @param array $photo
     * @param int   $id
     * @return bool|mixed
     */
    public static function uploadPhoto($photo, $id = 0)
    {
        $ret = Upload::saveImg(PathConst::IMG_ATLAS_PHOTO, $photo, PathConst::IMG_ATLAS_PHOTO_SIZE, $id);
        if (is_array($ret) && $ret['status'] == true) {
            return current($ret['thumb']);
        }

        return false;
    }
}