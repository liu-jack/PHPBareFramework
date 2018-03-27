<?php
/**
 * 汉字拼音转换类
 *
 */

namespace Classes\Util;

use Bare\DB;

/**
 * 汉字拼音映射文件
 * @var string
 */
define('PINYIN_MAP', __DIR__ . '/pinyin.db');

class Chinese
{
    /**
     * 默认编码
     * @var string
     */
    private static $internal_encode = 'UTF-8';

    /**
     * 获取字符串的拼音
     *   只有汉字才会转化,英文/数字/符号保持不变
     *
     * @param string $str 汉字串
     * @param string $keep 转换结果是否保留非汉字字符, 默认保留
     * @return array
     *    array('拼音' => '首字母缩写', ...)
     */
    public static function getPinyin($str, $keep = true)
    {
        static $map = [];
        if (empty($map)) {
            $map = self::_initWordsMap();
        }
        $pinyins = ['' => ''];
        for ($i = 0, $len = mb_strlen($str); $i < $len; ++$i) {
            $tmp = [];
            $word = mb_substr($str, $i, 1);
            // 汉字且有相应的拼音
            if (isset($map[$word])) {
                $word_py = (array)$map[$word];
                foreach ($pinyins as $pinyin => $abbr) {
                    foreach ($word_py as $py) {
                        $tmp[$pinyin . $py] = $abbr . $py{0};
                    }
                }
            } else {
                // 对于字典中未出现的字(非英文字母, 如标点符号), 是否保持
                $word = ($keep || preg_match('/^[0-9a-zA-Z]$/', $word)) ? $word : '';
                foreach ($pinyins as $pinyin => $abbr) {
                    $tmp[$pinyin . $word] = $abbr . $word;
                }
            }
            $pinyins = $tmp;
        }
        return $pinyins;
    }

    private static function _initWordsMap()
    {
        static $_static = [];
        if (empty($_static)) {
            $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
            $key = __CLASS__ . '_Pinyin';
            $cache = $mc->get($key);
            if (!is_array($cache) || empty($cache)) {
                $cache = unserialize(file_get_contents(PINYIN_MAP));
                $mc->set($key, $cache, 86400);
            }
            if (!empty($cache)) {
                $_static = $cache;
            }
        }
        return $_static;
    }
}
