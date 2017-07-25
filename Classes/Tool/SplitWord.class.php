<?php
/**
 * php分词提取关键词
 *
 * @author 周剑锋 <camfee@foxmail.com>
 * @since 2017-01-04
 *
 * SplitWord::getSplit($string); 文章分词返回关键字数组
 * 提取关键字算法：
 * （文章词频 / 字典词频 ） * x   x=1或要加大权重的倍数 （详见：self::getFinallyResult）
 * 加大名词动词与新词的权重 名词词频 * x  动词词频 *（x-5） 新词 * （x-7）  x = max(10,文章分词最大词频)
 * SplitWord::makeDict(); 重新编译生成主词典
 */

namespace Classes\Tool;

define('_SP_', chr(0xFF) . chr(0xFE));
define('UCS2', 'ucs-2be');
define('DIC_DATA_PATH', __DIR__ . '/data/');

class SplitWord
{
    //hash算法%值
    const MASK_VALUE = 0xFFFF;
    //句子长度小于这个数值时不拆分，notSplitLen = n(个汉字) * 2 + 1
    const NOT_SPLIT_LEN = 5;
    //主词典词语最大长度 x / 2
    const DIC_WORD_MAX = 14;
    //使用最大切分模式对二元词进行消岐
    const DEFFER_MAX = false;

    //附加词典
    const ADDON_DIC_FILE = DIC_DATA_PATH . 'words_addons.dic';
    protected static $addondic = [];

    //主词典
    const MAIN_TXT_FILE = DIC_DATA_PATH . 'base_dic_full.txt';
    const MAIN_DIC_FILE = DIC_DATA_PATH . 'base_dic_full.dic';
    protected static $maindic = [];
    protected static $maindic_hand = null;
    protected static $maindic_infos = [];

    //系统识别或合并的新词
    protected static $new_words = [];
    protected static $found_word_str = '';

    //被转换为unicode的源字符串
    protected static $source_string = '';

    //分词结果
    protected static $simple_result = [];
    protected static $finally_result = [];
    protected static $finally_index = [];

    // 去除的无意义词库 2个字以上的词语
    protected static $nomeaning = [
        '微信',
        'ID',
        '微博',
        'QQ',
        '小编',
    ];

    /**
     * 执行分词
     * @parem string $source_string 要分词的内容
     * @parem int $limit 返回结果分词的个数
     * @parem bool optimize 是否对结果进行优化
     * @return array
     */
    public static function getSplit($source_string, $limit = 20, $optimize = true)
    {
        if (empty(self::$maindic_hand)) {
            self::loadDict();
        }

        self::$simple_result = [];
        self::$finally_result = [];
        self::$finally_index = [];
        self::$source_string = @iconv('utf-8', UCS2, $source_string);

        self::$source_string .= chr(0) . chr(32);
        $slen = strlen(self::$source_string);
        $sbcArr = [];
        $j = 0;
        //全角与半角字符对照表
        for ($i = 0xFF00; $i < 0xFF5F; $i++) {
            $scb = 0x20 + $j;
            $j++;
            $sbcArr[$i] = $scb;
        }
        //对字符串进行粗分
        $onstr = '';
        $lastc = 1; //1 中/韩/日文, 2 英文/数字/符号('.', '@', '#', '+'), 3 ANSI符号 4 纯数字 5 非ANSI符号或不支持字符
        $s = 0;
        $ansiWordMatch = "[0-9a-z@#%\+\.-]";
        $notNumberMatch = "[a-z@#%\+]";
        for ($i = 0; $i < $slen; $i++) {
            $c = self::$source_string[$i] . self::$source_string[++$i];
            $cn = hexdec(bin2hex($c));
            $cn = isset($sbcArr[$cn]) ? $sbcArr[$cn] : $cn;
            //ANSI字符
            if ($cn < 0x80) {
                if (preg_match('/' . $ansiWordMatch . '/i', chr($cn))) {
                    if ($lastc != 2 && $onstr != '') {
                        self::$simple_result[$s]['w'] = $onstr;
                        self::$simple_result[$s]['t'] = $lastc;
                        self::_deepAnalysis($onstr, $lastc, $s, $optimize);
                        $s++;
                        $onstr = '';
                    }
                    $lastc = 2;
                    $onstr .= chr(0) . chr($cn);
                } else {
                    if ($onstr != '') {
                        self::$simple_result[$s]['w'] = $onstr;
                        if ($lastc == 2) {
                            if (!preg_match('/' . $notNumberMatch . '/i', iconv(UCS2, 'utf-8', $onstr))) {
                                $lastc = 4;
                            }
                        }
                        self::$simple_result[$s]['t'] = $lastc;
                        if ($lastc != 4) {
                            self::_deepAnalysis($onstr, $lastc, $s, $optimize);
                        }
                        $s++;
                    }
                    $onstr = '';
                    $lastc = 3;
                    if ($cn < 31) {
                        continue;
                    } else {
                        self::$simple_result[$s]['w'] = chr(0) . chr($cn);
                        self::$simple_result[$s]['t'] = 3;
                        $s++;
                    }
                }
            } else { //普通字符
                //正常文字
                if (($cn > 0x3FFF && $cn < 0x9FA6) || ($cn > 0xF8FF && $cn < 0xFA2D)
                    || ($cn > 0xABFF && $cn < 0xD7A4) || ($cn > 0x3040 && $cn < 0x312B)
                ) {
                    if ($lastc != 1 && $onstr != '') {
                        self::$simple_result[$s]['w'] = $onstr;
                        if ($lastc == 2) {
                            if (!preg_match('/' . $notNumberMatch . '/i', iconv(UCS2, 'utf-8', $onstr))) {
                                $lastc = 4;
                            }
                        }
                        self::$simple_result[$s]['t'] = $lastc;
                        if ($lastc != 4) {
                            self::_deepAnalysis($onstr, $lastc, $s, $optimize);
                        }
                        $s++;
                        $onstr = '';
                    }
                    $lastc = 1;
                    $onstr .= $c;
                } else { //特殊符号
                    if ($onstr != '') {
                        self::$simple_result[$s]['w'] = $onstr;
                        if ($lastc == 2) {
                            if (!preg_match('/' . $notNumberMatch . '/i', iconv(UCS2, 'utf-8', $onstr))) {
                                $lastc = 4;
                            }
                        }
                        self::$simple_result[$s]['t'] = $lastc;
                        if ($lastc != 4) {
                            self::_deepAnalysis($onstr, $lastc, $s, $optimize);
                        }
                        $s++;
                    }

                    //检测书名
                    if ($cn == 0x300A) {
                        $tmpw = '';
                        $n = 1;
                        $isok = false;
                        $ew = chr(0x30) . chr(0x0B);
                        while (true) {
                            if (!isset(self::$source_string[$i + $n]) && !isset(self::$source_string[$i + $n + 1])) {
                                break;
                            }
                            $w = self::$source_string[$i + $n] . self::$source_string[$i + $n + 1];
                            if ($w == $ew) {
                                self::$simple_result[$s]['w'] = $c;
                                self::$simple_result[$s]['t'] = 5;
                                $s++;

                                self::$simple_result[$s]['w'] = $tmpw;
                                self::$new_words[$tmpw] = 1;
                                if (!isset(self::$new_words[$tmpw])) {
                                    self::$found_word_str .= self::_outStringEncoding($tmpw) . '/nb, ';
                                    self::setWordInfos($tmpw, ['c' => 1, 'm' => 'nb']);
                                }
                                self::$simple_result[$s]['t'] = 13;

                                $s++;

                                //最大切分模式对书名继续分词
                                if (self::DEFFER_MAX) {
                                    self::$simple_result[$s]['w'] = $tmpw;
                                    self::$simple_result[$s]['t'] = 21;
                                    self::_deepAnalysis($tmpw, $lastc, $s, $optimize);
                                    $s++;
                                }

                                self::$simple_result[$s]['w'] = $ew;
                                self::$simple_result[$s]['t'] = 5;
                                $s++;

                                $i = $i + $n + 1;
                                $isok = true;
                                $onstr = '';
                                $lastc = 5;
                                break;
                            } else {
                                $n = $n + 2;
                                $tmpw .= $w;
                                if (strlen($tmpw) > 60) {
                                    break;
                                }
                            }
                        }//while
                        if (!$isok) {
                            self::$simple_result[$s]['w'] = $c;
                            self::$simple_result[$s]['t'] = 5;
                            $s++;
                            $onstr = '';
                            $lastc = 5;
                        }
                        continue;
                    }

                    $onstr = '';
                    $lastc = 5;
                    if ($cn == 0x3000) {
                        continue;
                    } else {
                        self::$simple_result[$s]['w'] = $c;
                        self::$simple_result[$s]['t'] = 5;
                        $s++;
                    }
                }//2byte symbol

            }//end 2byte char

        }//end for

        //处理分词后的结果
        self::_sortFinallyResult();

        $res = self::getFinallyResult();
        if (count($res) > $limit && $limit > 0) {
            $res = array_slice($res, 0, $limit);
        }
        return $res;
    }

    /**
     * 编译词典
     *
     * @parem string $source_file utf-8编码的文本词典数据文件<参见范例data/base_dic_full.txt>
     * base_dic_full.txt内词条含义：文明办网,5,i => 词条名，逆序词频（越小权重越大），词性
     * 注意, 需要PHP开放足够的内存才能完成操作
     * @param string $target_file 编译生成文件
     * @return void
     */
    public static function makeDict($source_file = self::MAIN_TXT_FILE, $target_file = self::MAIN_DIC_FILE)
    {
        $target_file = ($target_file == '' ? self::MAIN_DIC_FILE : $target_file);
        $allk = [];
        $fp = fopen($source_file, 'r');
        while ($line = fgets($fp, 512)) {
            if ($line[0] == '@') {
                continue;
            }
            list($w, $r, $a) = explode(',', $line);
            $a = trim($a);
            $w = iconv('utf-8', UCS2, $w);
            $k = self::_getIndex($w);
            if (isset($allk[$k])) {
                $allk[$k][$w] = array($r, $a);
            } else {
                $allk[$k][$w] = array($r, $a);
            }
        }
        fclose($fp);
        $fp = fopen($target_file, 'w');
        $heade_rarr = [];
        $alldat = '';
        $start_pos = self::MASK_VALUE * 8;
        foreach ($allk as $k => $v) {
            $dat = serialize($v);
            $dlen = strlen($dat);
            $alldat .= $dat;

            $heade_rarr[$k][0] = $start_pos;
            $heade_rarr[$k][1] = $dlen;
            $heade_rarr[$k][2] = count($v);

            $start_pos += $dlen;
        }
        unset($allk);
        for ($i = 0; $i < self::MASK_VALUE; $i++) {
            if (!isset($heade_rarr[$i])) {
                $heade_rarr[$i] = array(0, 0, 0);
            }
            fwrite($fp, pack("Inn", $heade_rarr[$i][0], $heade_rarr[$i][1], $heade_rarr[$i][2]));
        }
        fwrite($fp, $alldat);
        fclose($fp);
    }

    /**
     * 获取最终结果字符串（分词结果）提取关键字算法
     *
     * @return array
     */
    protected static function getFinallyResult()
    {
        $rs = [];
        $tf = self::getFinallyIndex();
        $nomean = array_flip(self::$nomeaning);
        foreach (self::$finally_result as $v) {
            if ($v['t'] == 3 || $v['t'] == 5) { // 去除特殊字符
                continue;
            }
            $w = self::_outStringEncoding($v['w']);
            if ($w != ' ' && isset($tf[$w]) && !isset($nomean[$w])) {
                if (self::filter($w)) {
                    $base = 1;
                    $qz = max($tf);
                    $qz = $qz > 10 ? $qz : 10;
                    $pf = self::getWordProperty($v['w']);
                    if (strpos($pf['p'], 'n') !== false) {
                        $base = $qz; // 名词权重 * x
                    } elseif (strpos($pf['p'], '/ss') !== false) {
                        $base = $qz - 7; // 新词权重 * x-7
                    } elseif (strpos($pf['p'], '/v') !== false) {
                        $base = $qz - 5; // 动词权重 * x-5
                    }
                    $rs[$w] = $tf[$w] * $base / (int)$pf['f'];
                }
            }
        }
        arsort($rs);
        return array_keys($rs);
    }

    /**
     * 过滤不提取的关键词
     * @param $w
     * @return bool
     */
    protected static function filter($w)
    {
        if (mb_strlen($w) <= 1) {
            return false; // 单个字不提取
        } elseif (is_numeric($w) && (strlen($w) < 5 || strlen($w) > 8)) {
            return false; // 小于5位大于8位数字不提取
        } elseif (preg_match('/[\d\.]+%?/', $w)) {
            return false; // 百分数、浮点数 不提取
        } elseif (preg_match('/\d+(年|月|日)/', $w)) {
            return false; // 日期不提取
        }
        return true;
    }

    /**
     * 获取索引hash数组
     * @return array('word'=>count,...)
     */
    protected static function getFinallyIndex()
    {
        $rearr = [];
        foreach (self::$finally_result as $v) {
            if ($v['t'] == 3 || $v['t'] == 5) { // 去除特殊字符
                continue;
            }
            $w = self::_outStringEncoding($v['w']);
            if ($w == ' ') {
                continue;
            }
            if (isset($rearr[$w])) {
                $rearr[$w]++;
            } else {
                $rearr[$w] = 1;
            }
        }
        return $rearr;
    }

    /**
     * 检测某个词是否存在
     */
    protected static function isWord($word)
    {
        $winfos = self::getWordInfos($word);
        return ($winfos !== false);
    }

    /**
     * 获得某个词的词性及词频信息
     *
     * @parem string $word unicode编码的词
     * @parem bool $idf 是否只返回逆序词频（IDF）
     * @return string ['p' =>词性, 'f' => 词频]
     */
    protected static function getWordProperty($word, $idf = false)
    {
        $return = ['p' => '/ss', 'f' => 1];
        if (strlen($word) < 4) {
            return $return;
        }
        $infos = self::getWordInfos($word);
        if (isset($infos[1])) {
            $return['f'] = (int)$infos[0];
            $return['p'] = $infos[1];
        }

        return $idf ? $return['f'] : $return;
    }

    /**
     * 从文件获得词
     * @param $key
     * @param $type (类型 word 或 key_groups)
     * @return int
     */
    protected static function getWordInfos($key, $type = 'word')
    {
        if (empty(self::$maindic_hand)) {
            self::$maindic_hand = fopen(self::MAIN_DIC_FILE, 'r');
        }

        $keynum = self::_getIndex($key);
        if (isset(self::$maindic_infos[$keynum])) {
            $data = self::$maindic_infos[$keynum];
        } else {
            //rewind( self::$maindic_hand );
            $move_pos = $keynum * 8;
            fseek(self::$maindic_hand, $move_pos, SEEK_SET);
            $dat = fread(self::$maindic_hand, 8);
            $arr = unpack('I1s/n1l/n1c', $dat);
            if ($arr['l'] == 0) {
                return false;
            }
            fseek(self::$maindic_hand, $arr['s'], SEEK_SET);
            $data = @unserialize(fread(self::$maindic_hand, $arr['l']));
            self::$maindic_infos[$keynum] = $data;
        }
        if (!is_array($data) || !isset($data[$key])) {
            return false;
        }
        return ($type == 'word' ? $data[$key] : $data);
    }

    /**
     * 指定某词的词性信息（通常是新词）
     * @parem $word unicode编码的词
     * @parem $infos array('c' => 词频, 'm' => 词性);
     * @return void;
     */
    protected static function setWordInfos($word, $infos)
    {
        if (strlen($word) < 4) {
            return;
        }
        if (isset(self::$maindic_infos[$word])) {
            self::$new_words[$word]++;
            self::$maindic_infos[$word]['c']++;
        } else {
            self::$new_words[$word] = 1;
            self::$maindic_infos[$word] = $infos;
        }
    }

    /**
     * 载入词典
     *
     * @return void
     */
    protected static function loadDict()
    {
        //加载主词典（只打开）
        if (empty(self::$maindic_hand)) {
            self::$maindic_hand = fopen(self::MAIN_DIC_FILE, 'r');
        }
        //载入副词典
        $hw = '';
        $ds = file(self::ADDON_DIC_FILE);
        foreach ($ds as $d) {
            $d = trim($d);
            if ($d == '') {
                continue;
            }
            $estr = substr($d, 1, 1);
            if ($estr == ':') {
                $hw = substr($d, 0, 1);
            } else {
                $spstr = _SP_;
                $spstr = iconv(UCS2, 'utf-8', $spstr);
                $ws = explode(',', $d);
                $wall = iconv('utf-8', UCS2, join($spstr, $ws));
                $ws = explode(_SP_, $wall);
                foreach ($ws as $estr) {
                    self::$addondic[$hw][$estr] = strlen($estr);
                }
            }
        }
    }

    /**
     * 深入分词
     * @parem $str
     * @parem $ctype (2 英文类， 3 中/韩/日文类)
     * @parem $spos   当前粗分结果游标
     * @return void
     */
    private static function _deepAnalysis(&$str, $ctype, $spos, $optimize = true)
    {
        //中文句子
        if ($ctype == 1) {
            $slen = strlen($str);
            //小于系统配置分词要求长度的句子
            if ($slen < self::NOT_SPLIT_LEN) {
                $lastType = 0;
                if ($spos > 0) {
                    $lastType = self::$simple_result[$spos - 1]['t'];
                }
                if ($slen < 5) {
                    if ($lastType == 4 && (isset(self::$addondic['u'][$str]) || isset(self::$addondic['u'][substr($str,
                                    0, 2)]))
                    ) {
                        $str2 = '';
                        if (!isset(self::$addondic['u'][$str]) && isset(self::$addondic['s'][substr($str, 2, 2)])) {
                            $str2 = substr($str, 2, 2);
                            $str = substr($str, 0, 2);
                        }
                        $ww = self::$simple_result[$spos - 1]['w'] . $str;
                        self::$simple_result[$spos - 1]['w'] = $ww;
                        self::$simple_result[$spos - 1]['t'] = 4;
                        if (!isset(self::$new_words[self::$simple_result[$spos - 1]['w']])) {
                            self::$found_word_str .= self::_outStringEncoding($ww) . '/mu, ';
                            self::setWordInfos($ww, array('c' => 1, 'm' => 'mu'));
                        }
                        self::$simple_result[$spos]['w'] = '';
                        if ($str2 != '') {
                            self::$finally_result[$spos - 1][] = $ww;
                            self::$finally_result[$spos - 1][] = $str2;
                        }
                    } else {
                        self::$finally_result[$spos][] = $str;
                    }
                } else {
                    self::_deepAnalysisCn($str, $ctype, $spos, $slen, $optimize);
                }
            } else { //正常长度的句子，循环进行分词处理
                self::_deepAnalysisCn($str, $ctype, $spos, $slen, $optimize);
            }
        } else { //英文句子
            self::$finally_result[$spos][] = $str;
        }
    }

    /**
     * 中文的深入分词
     *
     * @parem $str
     * @parem $lastec
     * @parem $spos
     * @parem $slen
     * @parem $optimize
     * @return void
     */
    private static function _deepAnalysisCn(&$str, $lastec, $spos, $slen, $optimize = true)
    {
        $quote1 = chr(0x20) . chr(0x1C);
        $tmparr = [];

        //如果前一个词为 “ ， 并且字符串小于3个字符当成一个词处理。
        if ($spos > 0 && $slen < 11 && self::$simple_result[$spos - 1]['w'] == $quote1) {
            $tmparr[] = $str;
            if (!isset(self::$new_words[$str])) {
                self::$found_word_str .= self::_outStringEncoding($str) . '/nq, ';
                self::setWordInfos($str, array('c' => 1, 'm' => 'nq'));
            }
            if (!self::DEFFER_MAX) {
                self::$finally_result[$spos][] = $str;
                return;
            }
        }
        //进行切分
        for ($i = $slen - 1; $i > 0; $i -= 2) {
            //单个词
            $nc = $str[$i - 1] . $str[$i];
            //是否已经到最后两个字
            if ($i <= 2) {
                $tmparr[] = $nc;
                $i = 0;
                break;
            }
            $isok = false;
            $i = $i + 1;
            for ($k = self::DIC_WORD_MAX; $k > 1; $k = $k - 2) {
                if ($i < $k) {
                    continue;
                }
                $w = substr($str, $i - $k, $k);
                if (strlen($w) <= 2) {
                    $i = $i - 1;
                    break;
                }
                if (self::isWord($w)) {
                    $tmparr[] = $w;
                    $i = $i - $k + 1;
                    $isok = true;
                    break;
                }
            }
            //没适合词
            if (!$isok) {
                $tmparr[] = $nc;
            }
        }
        $wcount = count($tmparr);
        if ($wcount == 0) {
            return;
        }
        self::$finally_result[$spos] = array_reverse($tmparr);
        //优化结果(岐义处理、新词、数词、人名识别等)
        if ($optimize) {
            self::_optimizeResult(self::$finally_result[$spos], $spos);
        }
    }

    /**
     * 对最终分词结果进行优化（把simpleresult结果合并，并尝试新词识别、数词合并等）
     * @parem $optimize 是否优化合并的结果
     * @return void
     */
    //t = 1 中/韩/日文, 2 英文/数字/符号('.', '@', '#', '+'), 3 ANSI符号 4 纯数字 5 非ANSI符号或不支持字符
    private static function _optimizeResult(&$smarr, $spos)
    {
        $newarr = [];
        $prePos = $spos - 1;
        $arlen = count($smarr);
        $i = $j = 0;
        //检测数量词
        if ($prePos > -1 && !isset(self::$finally_result[$prePos])) {
            $lastw = self::$simple_result[$prePos]['w'];
            $lastt = self::$simple_result[$prePos]['t'];
            if (($lastt == 4 || isset(self::$addondic['c'][$lastw])) && isset(self::$addondic['u'][$smarr[0]])) {
                self::$simple_result[$prePos]['w'] = $lastw . $smarr[0];
                self::$simple_result[$prePos]['t'] = 4;
                if (!isset(self::$new_words[self::$simple_result[$prePos]['w']])) {
                    self::$found_word_str .= self::_outStringEncoding(self::$simple_result[$prePos]['w']) . '/mu, ';
                    self::setWordInfos(self::$simple_result[$prePos]['w'], array('c' => 1, 'm' => 'mu'));
                }
                $smarr[0] = '';
                $i++;
            }
        }
        for (; $i < $arlen; $i++) {

            if (!isset($smarr[$i + 1])) {
                $newarr[$j] = $smarr[$i];
                break;
            }
            $cw = $smarr[$i];
            $nw = $smarr[$i + 1];
            $ischeck = false;
            //检测数量词
            if (isset(self::$addondic['c'][$cw]) && isset(self::$addondic['u'][$nw])) {
                //最大切分时保留合并前的词
                if (self::DEFFER_MAX) {
                    $newarr[$j] = chr(0) . chr(0x28);
                    $j++;
                    $newarr[$j] = $cw;
                    $j++;
                    $newarr[$j] = $nw;
                    $j++;
                    $newarr[$j] = chr(0) . chr(0x29);
                    $j++;
                }
                $newarr[$j] = $cw . $nw;
                if (!isset(self::$new_words[$newarr[$j]])) {
                    self::$found_word_str .= self::_outStringEncoding($newarr[$j]) . '/mu, ';
                    self::setWordInfos($newarr[$j], array('c' => 1, 'm' => 'mu'));
                }
                $j++;
                $i++;
                $ischeck = true;
            } else { //检测前导词(通常是姓)
                if (isset(self::$addondic['n'][$smarr[$i]])) {
                    $is_rs = false;
                    //词语是副词或介词或频率很高的词不作为人名
                    if (strlen($nw) == 4) {
                        $winfos = self::getWordInfos($nw);
                        if (isset($winfos['m']) && ($winfos['m'] == 'r' || $winfos['m'] == 'c' || $winfos['c'] > 500)) {
                            $is_rs = true;
                        }
                    }
                    if (!isset(self::$addondic['s'][$nw]) && strlen($nw) < 5 && !$is_rs) {
                        $newarr[$j] = $cw . $nw;
                        //尝试检测第三个词
                        if (strlen($nw) == 2 && isset($smarr[$i + 2]) && strlen($smarr[$i + 2]) == 2 && !isset(self::$addondic['s'][$smarr[$i + 2]])) {
                            $newarr[$j] .= $smarr[$i + 2];
                            $i++;
                        }
                        if (!isset(self::$new_words[$newarr[$j]])) {
                            self::setWordInfos($newarr[$j], array('c' => 1, 'm' => 'nr'));
                            self::$found_word_str .= self::_outStringEncoding($newarr[$j]) . '/nr, ';
                        }
                        //为了防止错误，保留合并前的姓名
                        if (strlen($nw) == 4) {
                            $j++;
                            $newarr[$j] = chr(0) . chr(0x28);
                            $j++;
                            $newarr[$j] = $cw;
                            $j++;
                            $newarr[$j] = $nw;
                            $j++;
                            $newarr[$j] = chr(0) . chr(0x29);
                        }

                        $j++;
                        $i++;
                        $ischeck = true;
                    }
                } else { //检测后缀词(地名等)
                    if (isset(self::$addondic['a'][$nw])) {
                        $is_rs = false;
                        //词语是副词或介词不作为前缀
                        if (strlen($cw) > 2) {
                            $winfos = self::getWordInfos($cw);
                            if (isset($winfos['m']) && ($winfos['m'] == 'a' || $winfos['m'] == 'r' || $winfos['m'] == 'c' || $winfos['c'] > 500)) {
                                $is_rs = true;
                            }
                        }
                        if (!isset(self::$addondic['s'][$cw]) && !$is_rs) {
                            $newarr[$j] = $cw . $nw;
                            if (!isset(self::$new_words[$newarr[$j]])) {
                                self::$found_word_str .= self::_outStringEncoding($newarr[$j]) . '/na, ';
                                self::setWordInfos($newarr[$j], array('c' => 1, 'm' => 'na'));
                            }
                            $i++;
                            $j++;
                            $ischeck = true;
                        }
                    } else { //新词识别（暂无规则）
                        //尝试合并单字
                        if (strlen($cw) == 2 && strlen($nw) == 2
                            && !isset(self::$addondic['s'][$cw]) && !isset(self::$addondic['t'][$cw]) && !isset(self::$addondic['a'][$cw])
                            && !isset(self::$addondic['s'][$nw]) && !isset(self::$addondic['c'][$nw])
                        ) {
                            $newarr[$j] = $cw . $nw;
                            //尝试检测第三个词
                            if (isset($smarr[$i + 2]) && strlen($smarr[$i + 2]) == 2 && (isset(self::$addondic['a'][$smarr[$i + 2]]) || isset(self::$addondic['u'][$smarr[$i + 2]]))) {
                                $newarr[$j] .= $smarr[$i + 2];
                                $i++;
                            }
                            if (!isset(self::$new_words[$newarr[$j]])) {
                                self::$found_word_str .= self::_outStringEncoding($newarr[$j]) . '/ms, ';
                                self::setWordInfos($newarr[$j], array('c' => 1, 'm' => 'ms'));
                            }
                            $i++;
                            $j++;
                            $ischeck = true;
                        }
                    }
                }
            }

            //不符合规则
            if (!$ischeck) {
                $newarr[$j] = $cw;
                //二元消岐处理——最大切分模式
                if (self::DEFFER_MAX && !isset(self::$addondic['s'][$cw]) && strlen($cw) < 5 && strlen($nw) < 7) {
                    $slen = strlen($nw);
                    for ($y = 2; $y <= $slen - 2; $y = $y + 2) {
                        $nhead = substr($nw, $y - 2, 2);
                        $nfont = $cw . substr($nw, 0, $y - 2);
                        if (self::isWord($nfont . $nhead)) {
                            if (strlen($cw) > 2) {
                                $j++;
                            }
                            $newarr[$j] = $nfont . $nhead;
                        }
                    }
                }
                $j++;
            }
        }//end for
        $smarr = $newarr;
    }

    /**
     * 转换最终分词结果到 finallyResult 数组
     * @return void
     */
    private static function _sortFinallyResult()
    {
        $newarr = [];
        $i = 0;
        foreach (self::$simple_result as $k => $v) {
            if (empty($v['w'])) {
                continue;
            }
            if (isset(self::$finally_result[$k]) && count(self::$finally_result[$k]) > 0) {
                foreach (self::$finally_result[$k] as $w) {
                    if (!empty($w)) {
                        $newarr[$i]['w'] = $w;
                        $newarr[$i]['t'] = 20;
                        $i++;
                    }
                }
            } else {
                if ($v['t'] != 21) {
                    $newarr[$i]['w'] = $v['w'];
                    $newarr[$i]['t'] = $v['t'];
                    $i++;
                }
            }
        }
        self::$finally_result = $newarr;
    }

    /**
     * 把uncode字符串转换为输出字符串
     * @parem str
     * return string
     */
    private static function _outStringEncoding(&$str)
    {
        return iconv(UCS2, 'utf-8', $str);
    }

    /**
     * 根据字符串计算key索引
     * @param $key
     * @return int
     */
    private static function _getIndex($key)
    {
        $l = strlen($key);
        $h = 0x238f13af;
        while ($l--) {
            $h += ($h << 5);
            $h ^= ord($key[$l]);
            $h &= 0x7fffffff;
        }
        return ($h % self::MASK_VALUE);
    }
}
