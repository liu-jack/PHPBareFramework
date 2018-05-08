<?php
/**
 * 基类视图数据模型
 *
 * @author camfee<camfee@yeah.net>
 * @since  v1.0 2017.08.26
 */

namespace Bare;

defined('ROOT_PATH') or exit('Access deny');

class ViewModel extends Model
{
    // 变量名称
    const FIELD_VAR_TYPE = 'var_type';       // 值类型
    const FIELD_FORM_TYPE = 'form_type';     // 表单类型
    const FIELD_SEARCH_TYPE = 'search_type'; // 搜索表单类型
    const SEARCH_WHERE_OP = 'op';            // 搜索查询操作
    const FIELD_MAP = 'field_map';           // 搜索字段映射
    const FIELD_LIST_TYPE = 'list_type';     // 列表类型
    const LIST_VAL_SHOW = true;              // 在列表显示
    const EXTRA_LIST_EDIT = 'edit';          // 列表显示编辑
    const EXTRA_LIST_DEL = 'delete';         // 列表显示删除
    const EXTRA_LIST_ADD = 'add';            // 列表显示新增
    // form表单
    const FORM_INPUT_TEXT = 'text';
    const FORM_INPUT_TIME = 'datetime';
    const FORM_INPUT_PASSWORD = 'password';
    const FORM_INPUT_HIDDEN = 'hidden';
    const FORM_INPUT_IMG = 'images';
    const FORM_INPUT_FILE = 'file';
    const FORM_INPUT_RADIO = 'radio';
    const FORM_RADIO_OPTION = 'radio_option';
    const FORM_INPUT_CHECKBOX = 'checkbox';
    const FORM_CHECKBOX_OPTION = 'checkbox_option';
    const FORM_SELECT = 'select';
    const FORM_SELECT_OPTION = 'select_option';
    const FORM_TEXTAREA = 'textarea';
    const FORM_EDITOR = 'editor';
    const FORM_FIELD_NAME = 'name';  // 字数描述 列表|表单 显示
    const FORM_FIELD_TIPS = 'tips';
    // 字段
    const FD_ID = 'Id';
    const FD_USER_ID = 'UserId';
    const FD_STATUS = 'Status';
    const FD_UPDATE_TIME = 'UpdateTime';
    const FD_CREATE_TIME = 'CreateTime';
    const EX_FD_START_TIME = 'StartTime';
    const EX_FD_END_TIME = 'EndTime';

    const TABLE = 'Test';
    const TABLE_REMARK = 'Test';
    // 配置
    protected static $_conf = [
        // 必选, 数据库代码 (来自Bridge配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DB::DB_29SHU_CONTENT_W,
            self::CF_DB_R => DB::DB_29SHU_CONTENT_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => self::TABLE,
        // 必选, 字段信息
        self::CF_FIELDS => [
            self::FD_ID => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_KEY,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_HIDDEN,
                self::FORM_FIELD_NAME => 'ID',
            ],
            self::FD_USER_ID => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_TEXT,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TEXT,
                self::FORM_FIELD_NAME => '用户ID',
            ],
            self::FD_STATUS => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_SEARCH_TYPE => self::FORM_INPUT_RADIO,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_RADIO,
                self::FORM_RADIO_OPTION => [
                    1 => '显示',
                    2 => '隐藏',
                ],
                self::FORM_FIELD_NAME => '状态',
            ],
            self::FD_CREATE_TIME => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_LIST_TYPE => self::LIST_VAL_SHOW,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TIME,
                self::FORM_FIELD_NAME => '时间',
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
        // 可选, redis (来自DB配置), w: 写, r: 读 index: 0-15
        self::CF_RD => [
            self::CF_DB_W => '',
            self::CF_DB_R => '',
            self::CF_RD_INDEX => 0,
            self::CF_RD_TIME => 0,
        ],
    ];

    /**
     * CURD
     *
     * @see Model::add() 新增
     * @see Model::update() 更新
     * @see Model::getInfoByIds() 按id查询
     * @see Model::getList() 条件查询
     * @see Model::delete() 删除
     */

    /**
     * @param $extra
     * @return string
     */
    public static function getListAdd($extra)
    {
        return in_array(static::EXTRA_LIST_ADD, $extra) ? static::EXTRA_LIST_ADD : '';
    }

    /**
     * 生成where条件
     *
     * @param array $val
     * @return array
     */
    public static function createWhere($val = [])
    {
        $val = array_merge($val, $_GET);
        $where = [];
        foreach (static::$_conf[self::CF_FIELDS] as $k => $v) {
            if (isset($v[self::FIELD_SEARCH_TYPE]) && isset($val[$k])) {
                $_val = trim($val[$k]);
                if ($_val !== '') {
                    if ($v[self::FIELD_VAR_TYPE] == self::VAR_TYPE_INT) {
                        $_val = intval($_val);
                    }
                    if (!empty($v[self::FIELD_MAP])) {
                        $k = trim($v[self::FIELD_MAP]);
                    }
                    if (empty($v[self::SEARCH_WHERE_OP])) {
                        $where[$k] = $_val;
                    } else {
                        $op = strtoupper(trim($v[self::SEARCH_WHERE_OP]));
                        if ($op == 'LIKE') {
                            $_val = '%' . $_val . '%';
                        }
                        $where[$k . ' ' . $op] = $_val;
                    }
                }
            }
        }

        return $where;
    }

    /**
     * 生成搜索表单
     *
     * @param array $val
     * @return string
     */
    public static function createSearch($val = [])
    {
        $val = array_merge($val, $_GET);
        $form = '<input type="hidden" name="' . ADMIN_VAR . '" value="1"/>';
        $form .= '<table class="table table-bordered table-striped" align="center">';
        $i = 1;
        foreach (static::$_conf[self::CF_FIELDS] as $k => $v) {
            if (isset($v[self::FIELD_SEARCH_TYPE])) {
                $_val = trim($val[$k]);
                if ($_val !== '') {
                    if ($v[self::FIELD_VAR_TYPE] == self::VAR_TYPE_INT) {
                        $_val = intval($_val);
                    }
                }
                if ($i % 5 == 1) {
                    $form .= '<tr>';
                }
                switch ($v[self::FIELD_SEARCH_TYPE]) {
                    case self::FORM_INPUT_TEXT:
                        $form .= '<td class="form-group col-xs-2"><div class="input-group"><div class="input-group-addon">' . $v[self::FORM_FIELD_NAME] . '</div><input type="text" class="form-control" name="' . $k . '" placeholder="' . $v[self::FORM_FIELD_NAME] . '" value="' . (isset($val[$k]) ? $val[$k] : '') . '"></div></td>';
                        break;
                    case self::FORM_SELECT:
                        $form .= '<td class="form-group col-xs-2"><div class="input-group"><div class="input-group-addon">' . $v[self::FORM_FIELD_NAME] . '</div><select name="' . $k . '" class="form-control">';
                        $form .= '<option value="" ' . (empty($_val) && $_val !== 0 ? 'selected' : '') . '>全部</option>';
                        $option = !empty($v[self::FORM_SELECT_OPTION]) ? $v[self::FORM_SELECT_OPTION] : (!empty($v[self::FORM_RADIO_OPTION]) ? $v[self::FORM_RADIO_OPTION] : $v[self::FORM_CHECKBOX_OPTION]);
                        if (empty($option)) {
                            $method = 'get' . $k . 'Option';
                            $option = static::$method();
                        }
                        if (!empty($option)) {
                            foreach ($option as $kk => $vv) {
                                $form .= '<option value="' . $kk . '" ' . ($_val === $kk ? 'selected' : '') . '>' . $vv . '</option>';
                            }
                        }
                        $form .= '</select></div></td>';
                        break;
                    case self::FORM_INPUT_TIME:
                        $time = '%H:%m:%s';
                        if (stripos($k, 'start') !== false) {
                            $time = '00:00:00';
                        } elseif (stripos($k, 'end') !== false) {
                            $time = '23:59:59';
                        }
                        $form .= '<td class="form-inline col-xs-2"><div class="input-group"><div class="input-group-addon">' . $v[self::FORM_FIELD_NAME] . '</div><input class="form-control" name="' . $k . '" type="text" onFocus="WdatePicker({startDate:\'%y-%M-%d ' . $time . '\',dateFmt:\'yyyy-MM-dd HH:mm:ss\'});" value="' . (isset($val[$k]) ? $val[$k] : '') . '" readonly="readonly"></div></td>';
                        break;
                }
                if ($i % 5 == 0) {
                    $form .= '</tr>';
                }
                $i++;
            }
        }
        $sub = '<td class="form-group col-xs-2" colspan="2"><button type="submit" class="btn btn-primary"><i class="icon-search"></i></button> <button type="reset" id="clearSearchForm" class="btn btn-warning"><i class="icon-refresh"></i></button></td>';
        if ($i % 5 != 1) {
            $form .= $sub;
            $form .= '</tr>';
        } else {
            $form .= '<tr>' . $sub . '</tr>';
        }
        $form .= '</table>';

        return $form;
    }

    /**
     * 生成列表
     *
     * @param array $list
     * @param array $extra
     * @return string
     */
    public static function createList($list, $extra = [])
    {
        $primary_key = '';
        $form = '<table class="table table-striped table-bordered table-hover"><thead><tr>';
        foreach (static::$_conf[self::CF_FIELDS] as $k => $v) {
            if ($v[self::FIELD_VAR_TYPE] == self::VAR_TYPE_KEY) {
                $primary_key = $k;
            }
            if (!empty($v[self::FIELD_LIST_TYPE])) {
                $form .= '<th>' . $v[self::FORM_FIELD_NAME] . '</th>';
            }
        }
        if (!empty($extra)) {
            $form .= '<th>操作</th>';
        }
        $form .= '</tr></thead><tbody>';
        foreach ($list as $kk => $vv) {
            $form .= '<tr>';
            foreach (static::$_conf[self::CF_FIELDS] as $k3 => $v3) {
                if (!empty($v3[self::FIELD_LIST_TYPE])) {
                    $option = [];
                    $method = 'get' . $k3 . 'Option';
                    if (isset($v3[self::FORM_RADIO_OPTION])) {
                        $option = !empty($v3[self::FORM_RADIO_OPTION]) ? $v3[self::FORM_RADIO_OPTION] : static::$method();
                    } elseif (isset($v3[self::FORM_CHECKBOX_OPTION])) {
                        $option = !empty($v3[self::FORM_CHECKBOX_OPTION]) ? $v3[self::FORM_CHECKBOX_OPTION] : static::$method();
                    } elseif (isset($v3[self::FORM_SELECT_OPTION])) {
                        $option = !empty($v3[self::FORM_SELECT_OPTION]) ? $v3[self::FORM_SELECT_OPTION] : static::$method();
                    }
                    if ($v3[self::FIELD_FORM_TYPE] == self::FORM_INPUT_IMG) {
                        $vv[$k3] = '<a target="_blank" href="' . $vv[$k3] . '"><img src="' . $vv[$k3] . '" width="150"/></a>';
                    }
                    $form .= '<td>' . (!empty($option[$vv[$k3]]) ? $option[$vv[$k3]] : $vv[$k3]) . '</td>';
                }
            }
            if (!empty($extra)) {
                $form .= '<td>';
                foreach ($extra as $k4 => $v4) {
                    switch ($v4) {
                        case self::EXTRA_LIST_EDIT:
                            $form .= '<a href="' . url(self::EXTRA_LIST_EDIT,
                                    ['id' => $vv[$primary_key]]) . '" class="btn btn-warning"><i class="icon-pencil"></i></a>';
                            break;
                        case self::EXTRA_LIST_DEL:
                            $form .= '<a href="' . url(self::EXTRA_LIST_DEL,
                                    ['id' => $vv[$primary_key]]) . '" class="btn btn-danger delete"><i class="icon-remove"></i></a>';
                            break;
                    }
                }
                $form .= '</td>';
            }
            $form .= '</tr>';
        }
        $form .= '</tbody></table>';

        return $form;
    }

    /**
     * 生成form表单
     *
     * @param array $val
     * @return string
     */
    public static function createForm($val = [])
    {
        $form = '';
        foreach (static::$_conf[self::CF_FIELDS] as $k => $v) {
            if (isset($v[self::FIELD_FORM_TYPE])) {
                $_val = $val[$k] ?? '';
                if ($_val !== '') {
                    if ($v[self::FIELD_VAR_TYPE] == self::VAR_TYPE_INT) {
                        $_val = intval($_val);
                    }
                }
                switch ($v[self::FIELD_FORM_TYPE]) {
                    case self::FORM_INPUT_TEXT:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><input type="text" class="form-control" name="' . $k . '" id="' . $k . '" value="' . $_val . '" placeholder="' . $v[self::FORM_FIELD_NAME] . '"></div></div>';
                        break;
                    case self::FORM_INPUT_TIME:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><input type="text" class="form-control" name="' . $k . '" id="' . $k . '" value="' . $_val . '" readonly onFocus="WdatePicker({startDate:\'%y-%M-%d %H:%m:%s\',dateFmt:\'yyyy-MM-dd HH:mm:ss\'})"></div></div>';
                        break;
                    case self::FORM_INPUT_PASSWORD:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><input type="password" class="form-control" name="' . $k . '" id="' . $k . '" value=""></div></div>';
                        break;
                    case self::FORM_INPUT_HIDDEN:
                        $form .= '<input type="hidden" name="' . $k . '" id="' . $k . '" value="' . $_val . '">';
                        break;
                    case self::FORM_INPUT_FILE:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><input type="file" class="form-control" name="' . $k . '" id="' . $k . '" placeholder="' . $v[self::FORM_FIELD_NAME] . '"></div></div>';
                        break;
                    case self::FORM_INPUT_IMG:
                        $form .= '<div class="form-inline"><div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><input type="file" class="form-control" name="' . $k . '" id="' . $k . '" placeholder="' . $v[self::FORM_FIELD_NAME] . '"></div></div> &nbsp; <div class="form-group"><div class="col-lg-8"><a target="_blank" href="' . $_val . '"><img style="max-width: 465px" src="' . $_val . '"/></a></div></div></div><br>';
                        break;
                    case self::FORM_INPUT_RADIO:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8">';
                        if (empty($v[self::FORM_RADIO_OPTION])) {
                            $method = 'get' . $k . 'Option';
                            $v[self::FORM_RADIO_OPTION] = static::$method();
                        }
                        foreach ($v[self::FORM_RADIO_OPTION] as $kk => $vv) {
                            $form .= '<div class="radio"><label><input type="radio" name="' . $k . '" value="' . $kk . '" ' . ($_val === $kk ? 'checked' : '') . '> ' . $vv . ' </label></div>';
                        }
                        $form .= '</div></div>';
                        break;
                    case self::FORM_INPUT_CHECKBOX:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8">';
                        if (empty($v[self::FORM_CHECKBOX_OPTION])) {
                            $method = 'get' . $k . 'Option';
                            $v[self::FORM_CHECKBOX_OPTION] = static::$method();
                        }
                        foreach ($v[self::FORM_CHECKBOX_OPTION] as $kk => $vv) {
                            $form .= '<label class="checkbox-inline"><input type="checkbox" ' . ($_val === $kk ? 'checked' : '') . ' name="' . $k . '[]" value="' . $kk . '"> ' . $vv . ' </label>';
                        }
                        $form .= '</div></div>';
                        break;
                    case self::FORM_SELECT:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><select name="' . $k . '" id="' . $k . '" class="form-control">';
                        if (empty($v[self::FORM_SELECT_OPTION])) {
                            $method = 'get' . $k . 'Option';
                            $v[self::FORM_SELECT_OPTION] = static::$method();
                        }
                        foreach ($v[self::FORM_SELECT_OPTION] as $kk => $vv) {
                            $form .= '<option value="' . $kk . '" ' . ($_val === $kk ? 'selected' : '') . '>' . $vv . '</option>';
                        }
                        $form .= '</select></div></div>';
                        break;
                    case self::FORM_TEXTAREA:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><textarea class="form-control" name="' . $k . '" id="' . $k . '" rows="5" placeholder="' . $v[self::FORM_FIELD_NAME] . '">' . $_val . '</textarea></div></div>';
                        break;
                    case self::FORM_EDITOR:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><textarea class="cleditor" name="' . $k . '" id="' . $k . '">' . (isset($val[$k]) ? $val[$k] : '') . '</textarea></div></div>';
                        break;
                }
            }
        }

        return $form;
    }
}