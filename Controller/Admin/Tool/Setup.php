<?php
/**
 * Setup.php
 * 一些配置
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-5-21 下午4:42
 *
 */

namespace Controller\Admin\Tool;

use Bare\C\AdminController;
use Model\Common\RecomData;

class Setup extends AdminController
{
    // 文案配置
    private $input_text_setup = [
        'Rate' => '比例（%）'
    ];
    // 开关配置
    private $input_switch_setup = [
        'Show' => '显示'
    ];

    public function index()
    {
        $setup = RecomData::getData([
            RecomData::INPUT_TEXT_SETUP,
            RecomData::INPUT_SWITCH_SETUP,
        ]);

        $this->value('input_switch_setup', $this->input_switch_setup);
        $this->value('switch_setup_val', $setup[RecomData::INPUT_SWITCH_SETUP]);
        $this->value('input_text_setup', $this->input_text_setup);
        $this->value('text_setup_val', $setup[RecomData::INPUT_TEXT_SETUP]);
        $this->view();
    }

    // 文案配置修改
    public function textSetup()
    {
        $text_setup = RecomData::getData([RecomData::INPUT_TEXT_SETUP])[RecomData::INPUT_TEXT_SETUP];
        if (!empty($text_setup)) {
            foreach ($text_setup as $k => &$v) {
                if (!isset($this->input_text_setup[$k])) {
                    unset($text_setup[$k]);
                }
            }
        }
        foreach ($_POST as $field => $value) {
            if (isset($this->input_text_setup[$field])) {
                $text_setup[$field] = $value;
            }
        }
        $res = RecomData::setData(RecomData::INPUT_TEXT_SETUP, $text_setup);
        if ($res) {
            $this->alert('操作成功!', url('index'));
        } else {
            $this->alertErr('操作失败!', url('index'));
        }
    }

    // 开关配置修改
    public function switchSetup()
    {
        $switch_setup = RecomData::getData([RecomData::INPUT_SWITCH_SETUP])[RecomData::INPUT_SWITCH_SETUP];
        if (!empty($switch_setup)) {
            foreach ($switch_setup as $k => &$v) {
                if (!isset($this->input_switch_setup[$k])) {
                    unset($switch_setup[$k]);
                }
            }
        }
        $field = trim($_POST['field']);
        $value = intval($_POST['value']);
        if (isset($this->input_switch_setup[$field])) {
            $set = $value ? 0 : 1;
            $switch_setup[$field] = $set;
        }
        $res = RecomData::setData(RecomData::INPUT_SWITCH_SETUP, $switch_setup);
        if ($res) {
            if (!empty($set)) {
                output(200, ['value' => 0]);
            } else {
                output(200, ['value' => 1]);
            }
        } else {
            output(201);
        }
    }
}