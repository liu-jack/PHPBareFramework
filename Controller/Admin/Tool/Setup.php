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
    // 文案配置 注意field key需要唯一
    private $input_text_setup = [
        '比例配置（%）' => [
            'Rate1' => '比例1（%）',
            'Rate2' => '比例2（%）',
            'Rate3' => '比例3（%）',
        ],
        '文案配置' => [
            'Text1' => '文案1',
            'Text2' => '文案2',
            'Text3' => '文案3',
        ],
    ];
    // 多行文案配置 注意field key需要唯一
    private $area_text_setup = [
        '多行说明配置' => [
            'Area1' => '说明1',
            'Area2' => '说明2',
            'Area3' => '说明3',
        ],
        '多行文案配置' => [
            'Text1' => '文案1',
            'Text2' => '文案2',
            'Text3' => '文案3',
        ],
    ];
    // 开关配置
    private $input_switch_setup = [
        '支付开关' => [
            'Pay' => '支付开关',
            'Pay2' => '支付开关2',
        ],
        '分享开关' => [
            'Share' => '分享开关',
            'Share2' => '分享开关2',
        ],
    ];

    public function index()
    {
        $setup = RecomData::getData([
            RecomData::INPUT_TEXT_SETUP,
            RecomData::AREA_TEXT_SETUP,
            RecomData::INPUT_SWITCH_SETUP,
        ]);
        $this->value('input_switch_setup', $this->input_switch_setup);
        $this->value('switch_setup_val', $setup[RecomData::INPUT_SWITCH_SETUP]);
        $this->value('input_text_setup', $this->input_text_setup);
        $this->value('text_setup_val', $setup[RecomData::INPUT_TEXT_SETUP]);
        $this->value('area_text_setup', $this->area_text_setup);
        $this->value('area_setup_val', $setup[RecomData::AREA_TEXT_SETUP]);
        $this->view();
    }

    // 文案配置修改
    public function textSetup()
    {
        foreach ($this->input_text_setup as $k => &$v) {
            foreach ($v as $kk => &$vv) {
                $input_text_setup[$kk] = $vv;
            }
        }
        $text_setup = RecomData::getData([RecomData::INPUT_TEXT_SETUP])[RecomData::INPUT_TEXT_SETUP];
        if (!empty($text_setup)) {
            foreach ($text_setup as $k => &$v) {
                if (!isset($input_text_setup[$k])) {
                    unset($text_setup[$k]);
                }
            }
        }
        foreach ($_POST as $field => $value) {
            if (isset($input_text_setup[$field])) {
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

    // 文案配置修改
    public function areaSetup()
    {
        foreach ($this->area_text_setup as $k => &$v) {
            foreach ($v as $kk => &$vv) {
                $area_text_setup[$kk] = $vv;
            }
        }
        $area_setup = RecomData::getData([RecomData::AREA_TEXT_SETUP])[RecomData::AREA_TEXT_SETUP];
        if (!empty($area_setup)) {
            foreach ($area_setup as $k => &$v) {
                if (!isset($area_text_setup[$k])) {
                    unset($area_setup[$k]);
                }
            }
        }
        foreach ($_POST as $field => $value) {
            if (isset($area_text_setup[$field])) {
                $area_setup[$field] = $value;
            }
        }

        $res = RecomData::setData(RecomData::AREA_TEXT_SETUP, $area_setup);
        if ($res) {
            $this->alert('操作成功!', url('index'));
        } else {
            $this->alertErr('操作失败!', url('index'));
        }
    }

    // 开关配置修改
    public function switchSetup()
    {
        foreach ($this->input_switch_setup as $k => &$v) {
            foreach ($v as $kk => &$vv) {
                $input_switch_setup[$kk] = $vv;
            }
        }
        $switch_setup = RecomData::getData([RecomData::INPUT_SWITCH_SETUP])[RecomData::INPUT_SWITCH_SETUP];
        if (!empty($switch_setup)) {
            foreach ($switch_setup as $k => &$v) {
                if (!isset($input_switch_setup[$k])) {
                    unset($switch_setup[$k]);
                }
            }
        }
        $field = trim($_POST['field']);
        $value = intval($_POST['value']);
        if (isset($input_switch_setup[$field])) {
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