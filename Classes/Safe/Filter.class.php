<?php

/**
 * 文本关键字过滤
 */

namespace Classes\Safe;

use Bare\DB;

class Filter
{
    /**
     * Memcache缓存key名称
     */
    private static $mc_key = 'Filter_Keywords';

    private static $mc_time = 86400;

    /**
     * array 关键字数组
     */
    private static $key_words = [];

    /**
     * 获取关键字
     *
     */
    public static function getKeyWords()
    {
        $key_words = self::$key_words;
        if (empty($key_words)) {
            $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
            $key_words = $mc->get(self::$mc_key);
            if (empty($key_words)) {
                $pdo = DB::pdo(DB::DB_ADMIN_R);
                $pdo->prepare('SELECT `Word` FROM Filter');
                $pdo->execute();
                $keys = $pdo->fetchAll();

                if (!is_array($keys)) {
                    $keys = array();
                }
                foreach ($keys as $v) {
                    $key_words[] = $v['Word'];
                }
                $key_words = empty($key_words) ? [] : $key_words;
                $mc->set(self::$mc_key, $key_words, self::$mc_time);
            }
            self::$key_words = $key_words;
        }

        return $key_words;
    }

    /**
     * 快速检查是否含有非法字符
     *
     * @param string $content 需要检查的内容
     * @return boolean 含有非法内容返回true,否则返回false
     */
    public static function fastCheck($content)
    {
        $key_words = self::getKeyWords();
        foreach ($key_words as $v) {
            if (stripos($content, $v) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 模糊检查非法词 (较慢)
     *
     * @param string  $content 需要检查的内容
     * @param integer $depth   检查深度 默认为2
     * @return boolean 含有非法内容返回true,否则返回false
     */
    public static function wholeCheck($content, $depth = 2)
    {
        $key_words = self::getKeyWords();
        $content = preg_replace('/\s/', '', $content);
        foreach ($key_words as $v) {
            $split_v = preg_split('/(?<!^)(?!$)/u', $v);
            foreach ($split_v as & $word) {
                $word = preg_quote($word);
            }

            $reg = '/' . implode(".{0,$depth}", $split_v) . '/ui';
            if (preg_match($reg, $content)) {
                return true;
            }

        }

        return false;
    }

    /**
     * 快速替换非法关键字
     *
     * @param string $content 需要检查的内容
     * @param string $replace 需要替换的内容
     * @return string 返回结果
     */
    public static function fastReplace($content, $replace = "**")
    {
        $key_words = self::getKeyWords();

        return str_ireplace($key_words, $replace, $content);
    }

    /**
     * 模糊搜索替换非法关键字 (较慢)
     *
     * @param string  $content 需要检查的内容
     * @param string  $replace 需要替换的内容
     * @param integer $depth   替换深度 默认为2
     * @return string 返回结果
     */
    public static function wholeReplace($content, $replace = "**", $depth = 2)
    {
        $key_words = self::getKeyWords();
        foreach ($key_words as $v) {
            $split_v = preg_split('/(?<!^)(?!$)/u', $v);
            foreach ($split_v as & $word) {
                $word = preg_quote($word);
            }

            $reg = '/' . implode(".{0,$depth}", $split_v) . '/ui';
            $content = preg_replace($reg, $replace, $content);
        }

        return $content;
    }

    /**
     * 计算字符串相似度
     *
     * @param string $str_one 第1个字符串
     * @param string $str_two 第2个字符串
     * @return array 返回比较结果数组 m表示匹配字符个数 p表示相似度的百分比
     */
    public static function similar($str_one, $str_two)
    {
        $ret = false;
        if ($str_one && $str_two) {
            $res = similar_text($str_one, $str_two, $p);
            $ret = array('m' => $res, 'p' => $p);
        }

        return $ret;
    }

    /**
     * 手工删除过滤内容
     *
     * @param array $filter 过滤内容文本, 同时传多个值使用一维数组 如: array('str1', 'str2')
     * @return boolean 设置成功返回 true, 否则 false
     */
    public static function delFilter($filter)
    {
        $filter = (array)$filter;
        $del_filter = [];

        if (count($filter) > 0) {

            $pdo = DB::pdo(DB::DB_ADMIN_W);
            $query = $pdo->prepare("DELETE FROM Filter WHERE Word=:word");

            foreach ($filter as $v) {
                $v = trim($v);
                $query->bindValue(':word', trim($v));
                $query->execute();
                if ($query->rowCount() > 0) {
                    $del_filter[] = $v;
                }
            }

            if (count($del_filter) > 0) {
                $mc = DB::memcache(DB::MEMCACHE_DEFAULT);
                $key_words = $mc->get(self::$mc_key);
                if (!empty($key_words)) {
                    foreach ($del_filter as $v) {
                        unset($key_words[array_search($v, $key_words)]);
                    }
                    $mc->set(self::$mc_key, $key_words, self::$mc_time);
                    self::$key_words = $key_words;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * 手工设置过滤内容
     *
     * @param array $filter 过滤内容文本, 同时传多个值使用一维数组 如: array('str1', 'str2')
     * @return boolean 设置成功返回 true, 失败返回false
     */
    public static function addFilter($filter)
    {
        $filter = (array)$filter;

        if (count($filter) > 0) {
            $key_words = self::getKeyWords();
            $sql_value = "";
            foreach ($filter as $k => $v) {
                if (in_array($v, $key_words)) {
                    unset($filter[$k]);
                } else {
                    $v = mysql_quote($v);
                    $sql_value .= "(\"{$v}\"),";
                }
            }

            if ($sql_value != '') {
                $sql_value = rtrim($sql_value, ',');

                $pdo = DB::pdo(DB::DB_ADMIN_W);
                $mc = DB::memcache(DB::MEMCACHE_DEFAULT);

                $count = $pdo->exec("INSERT INTO Filter(`Word`) VALUES{$sql_value}");

                if ($count > 0) {
                    self::$key_words = array_merge($key_words, $filter);
                    $mc->set(self::$mc_key, self::$key_words, self::$mc_time);

                    return true;
                }

            }
        }

        return false;
    }

    /**
     * 设置自定义过滤内容
     *
     * @param array $filter 过滤内容文本, 同时传多个值使用一维数组 如: array('str1', 'str2')
     *                      return void
     */
    public static function setFilter($filter)
    {
        $filter = (array)$filter;
        self::$key_words = $filter;
    }

}
