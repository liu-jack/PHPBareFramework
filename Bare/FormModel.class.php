<?php
/**
 * 基类视图数据模型
 *
 * @author camfee<camfee@yeah.net>
 * @since  v1.0 2017.08.26
 */

namespace Bare;

class FormModel extends Model
{
    const FORM_INPUT_TEXT = 'text';
    const FORM_INPUT_TIME = 'datetime';
    const FORM_INPUT_PASSWORD = 'password';
    const FORM_INPUT_HIDDEN = 'hidden';
    const FORM_INPUT_RADIO = 'radio';
    const FORM_RADIO_OPTION = 'radio_option';
    const FORM_INPUT_CHECKBOX = 'checkbox';
    const FORM_CHECKBOX_OPTION = 'checkbox_option';
    const FORM_SELECT = 'select';
    const FORM_SELECT_OPTION = 'option';
    const FORM_TEXTAREA = 'textarea';
    const FORM_EDITOR = 'editor';
    const FORM_FIELD_NAME = 'name';
    const FORM_FIELD_TIPS = 'tips';

    protected static $_conf = [
        // 必选, 数据库代码 (来自Bridge配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DB::DB_29SHU_CONTENT_W,
            self::CF_DB_R => DB::DB_29SHU_CONTENT_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => 'Test',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'Id' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_KEY,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_HIDDEN,
            ],
            'UserId' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TEXT,
            ],
            'Status' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_INT,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_RADIO,
                self::FORM_RADIO_OPTION => [
                    '1' => '显示',
                    '2' => '隐藏',
                ],
                self::FORM_FIELD_NAME => '状态',
            ],
            'CreateTime' => [
                self::FIELD_VAR_TYPE => self::VAR_TYPE_STRING,
                self::FIELD_FORM_TYPE => self::FORM_INPUT_TIME,
                self::FORM_FIELD_NAME => '时间',
            ],
        ],
        // 可选, MC连接参数
        self::CF_MC => '',
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => '',
        // 可选, 超时时间, 默认不过期
        self::CF_MC_TIME => 86400
    ];

    public static function createForm($val = [])
    {
        $form = '';
        foreach (static::$_conf[self::CF_FIELDS] as $k => $v) {
            if (isset($v[self::FIELD_FORM_TYPE])) {
                switch ($v[self::FIELD_FORM_TYPE]) {
                    case self::FORM_INPUT_TEXT:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><input type="text" class="form-control" name="' . $k . '" id="' . $k . '" value="' . (isset($val[$k]) ? $val[$k] : '') . '" placeholder="' . $v[self::FORM_FIELD_NAME] . '"></div></div>';
                        break;
                    case self::FORM_INPUT_TIME:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><input type="text" class="form-control" name="' . $k . '" id="' . $k . '" value="' . (isset($val[$k]) ? $val[$k] : '') . '" readonly onFocus="WdatePicker({startDate:\'%y-%M-%d %H:%m:%s\',dateFmt:\'yyyy-MM-dd HH:mm:ss\'})"></div></div>';
                        break;
                    case self::FORM_INPUT_PASSWORD:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><input type="password" class="form-control" name="' . $k . '" id="' . $k . '" value=""></div></div>';
                        break;
                    case self::FORM_INPUT_HIDDEN:
                        $form .= '<input type="hidden" name="' . $k . '" id="' . $k . '" value="' . (isset($val[$k]) ? $val[$k] : '') . '">';
                        break;
                    case self::FORM_INPUT_RADIO:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8">';
                        foreach ($v[self::FORM_RADIO_OPTION] as $kk => $vv) {
                            $form .= '<div class="radio"><label><input type="radio" name="' . $k . '" value="' . $kk . '" ' . (isset($val[$k]) && $val[$k] === $kk ? 'checked' : '') . '> ' . $vv . ' </label></div>';
                        }
                        $form .= '</div></div>';
                        break;
                    case self::FORM_INPUT_CHECKBOX:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8">';
                        foreach ($v[self::FORM_CHECKBOX_OPTION] as $kk => $vv) {
                            $form .= '<label class="checkbox-inline"><input type="checkbox" ' . (isset($val[$k]) && $val[$k] === $kk ? 'checked' : '') . ' name="' . $k . '[]" value="' . $kk . '"> ' . $vv . ' </label>';
                        }
                        $form .= '</div></div>';
                        break;
                    case self::FORM_SELECT:
                        $form .= '<div class="form-group"><label class="col-lg-4 control-label">' . $v[self::FORM_FIELD_NAME] . '</label><div class="col-lg-8"><select name="' . $k . '" id="' . $k . '" class="form-control">';
                        foreach ($v[self::FORM_SELECT_OPTION] as $kk => $vv) {
                            $form .= '<option value="' . $kk . '" ' . (isset($val[$k]) && $val[$k] === $kk ? 'selected' : '') . '>' . $vv . '</option>';
                        }
                        $form .= '</select></div></div>';
                        break;
                }
            }
        }

        return $form;
    }

    /**
     * @param      $data
     * @param bool $ignore
     * @return bool|int|string
     */
    public static function add($data, $ignore = true)
    {
        $ret = false;
        if (!empty($data)) {
            if (empty($data['CreateTime'])) {
                $data['CreateTime'] = date('Y-m-d H:i:s');
            }
            $ret = parent::addData($data, $ignore);
        }

        return $ret;
    }

    /**
     * 更新
     *
     * @param $id
     * @param $data
     * @return bool
     */
    public static function update($id, $data)
    {
        $ret = false;
        if ($id > 0 && !empty($data)) {
            $ret = parent::updateData($id, $data);
        }

        return $ret;
    }

    /**
     * 根据id获取
     *
     * @param int|array $ids
     * @return array
     */
    public static function getInfoByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $ret = parent::getDataById($ids);

        return $ret;
    }

    /**
     * 获取列表
     *
     * @param array  $where
     * @param int    $offset
     * @param int    $limit
     * @param string $fields
     * @param string $order
     * @return array
     */
    public static function getList($where = [], $offset = 0, $limit = 0, $fields = '*', $order = '')
    {
        $extra = [
            'fields' => $fields,
            'offset' => $offset,
            'limit' => $limit,
            'order' => $order,
        ];

        return parent::getDataByFields($where, $extra);
    }

    /**
     * 删除
     *
     * @param $id
     * @return bool
     */
    public static function del($id)
    {
        if ($id > 0) {
            return parent::delData($id);
        }

        return false;
    }
}