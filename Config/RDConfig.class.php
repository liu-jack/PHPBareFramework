<?php
/**
 * RDConfig.class.php 推荐数据key配置
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-3-3 下午3:17
 *
 */

namespace Config;

defined('ROOT_PATH') or exit('Access deny');

class RDConfig
{
    // 数据保存获取key mysql memcache
    const APP_TEST = 'app_test';
    const INPUT_SWITCH_SETUP = 'input_switch_setup';
    const INPUT_TEXT_SETUP = 'input_text_setup';
    const AREA_TEXT_SETUP = 'area_text_setup';
    const EDITOR_TEXT_SETUP_1 = 'editor_text_setup_1';
    const EDITOR_TEXT_SETUP_2 = 'editor_text_setup_2';
    // 数据保存获取key redis
    const REDIS_APP_TEST = 'redis_app_test';
    // 支持的访问key数据
    const KEY_CONFIG = [
        self::APP_TEST => 'app test',
        self::INPUT_SWITCH_SETUP => '开关设置配置',
        self::INPUT_TEXT_SETUP => '单行文案配置',
        self::AREA_TEXT_SETUP => '多行文案配置',
        self::EDITOR_TEXT_SETUP_1 => '富文本文案配置',
        self::EDITOR_TEXT_SETUP_2 => '富文本文案配置',
        self::REDIS_APP_TEST => 'redis app test',
    ];
}