<?php
/**
 *  网站内容采集 php index.php collect/collectWeb/index [config] [siteid] [channelid] [day2]
 *
 * @author  周剑锋 <camfee@foxmail.com>
 *
 */

namespace Controller\Collect;

use Bare\DB;
use Bare\Controller;

class CollectWeb extends Controller
{
    private static $isecho = 1;             // 是否输出进度提示信息
    private static $table = 'CollectWeb';   // 数据表
    private static $img_table = 'PicInfo';  // 图片表
    private static $log_path = 'Collect/' . __CLASS__; // 导入错误日志路径
    private static $pdo = null;             // pdo库实例
    private static $siteid = 0;             // 采集站点id
    private static $channelid = 0;          // 采集频道id
    private static $createtime = '';        // 采集>=$createtime的数据
    private static $savepath = '';          // 图片保存区别路径
    private static $collectstatus = 1;      // 采集状态
    private static $page = -1;              // 采集页码
    private static $order = 1;              // 采集到的条数
    private static $config = [];            // 站点频道配置

    public function index()
    {
        need_cli();
        self::getConfig($_GET['argv']);

        if (!empty(self::$config[self::$siteid][self::$channelid])) {
            $config = self::$config[self::$siteid][self::$channelid];
            self::collectLoop($config);
        }

        self::echoMsg("\nFinished!\n");
    }

    /**
     * 采集循环
     *
     * @param $config
     */
    private static function collectLoop(&$config)
    {
        if (self::$page == -1) {
            if (isset($config['page'])) {
                self::$page = $config['page'];
            } else {
                self::$page = 1;
            }
        }
        $page = self::$page;
        $list = self::getList($config, self::$page);

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if ((__ENV__ != 'ONLINE' && ((isset($config['page']) && $page == $config['page']) || $page == 1)) || ((!isset($v['ArticleTime']) || strtotime($v['ArticleTime']) >= self::$createtime) && self::$page > -1)) {
                    self::echoMsg("Collect order/page : " . ($k + 1) . "/{$page}\n");
                    if (self::$order !== -1) {
                        self::$order = $k + 1;
                    }
                    self::getContent($v, $config);
                } else {
                    self::$page = -1;
                }
            }
            if (self::$page > -1) {
                self::collectLoop($config);
            }
        }
    }

    /**
     * 列表采集
     *
     * @param $config
     * @param int $page
     * @return array
     */
    private static function getList(&$config, $page = 1)
    {
        $data = [];
        if ($page > -1) {
            $url = self::getPageUrl($config['url'], $page);
            $cont1 = self::getCurl($url);
            $temp = [];
            if (is_numeric($config['list_preg'])) { //正则复用
                $config['list_preg'] = self::$config[self::$siteid][$config['list_preg']]['list_preg'];
            }
            foreach ($config['list_preg'] as $v) {
                if (!empty($v['reg'])) {
                    $out = [];
                    preg_match_all($v['reg'], $cont1, $out);
                    foreach ($v['field'] as $fk => $fv) {
                        if (!empty($out[$fk]) && (!empty($v[$fv . '_prefix']) || !empty($v[$fv . '_suffix']))) {
                            foreach ($out[$fk] as $fkk => $fvv) {
                                if (!empty($v[$fv . '_prefix'])) {
                                    if (strpos($fvv, 'http') !== 0) {
                                        $fvv = $v[$fv . '_prefix'] . $fvv;
                                    }
                                }
                                if (!empty($v[$fv . '_suffix'])) {
                                    $fvv = $fvv . $v[$fv . '_suffix'];
                                }
                                $out[$fk][$fkk] = $fvv;
                            }
                        }
                        $temp[$fv] = $out[$fk];

                    }
                }
            }
            foreach ($temp as $k => $v) {
                foreach ($v as $kk => $vv) {
                    if (!empty($config['list_preg'][$k])) {
                        $fields = $config['list_preg'][$k];
                        $out = [];
                        if (isset($fields['__reg'])) {
                            preg_match_all($fields['__reg'], $vv, $out);
                            if ($k == 'Cover' && count($out[$fields['field']]) > 3) {
                                $out[$fields['field']] = array_slice($out[$fields['field']], 0, 3);
                            }
                            $vv = !empty($out[$fields['field']]) ? implode(',', $out[$fields['field']]) : '';
                        } elseif (isset($fields['_reg'])) {
                            preg_match($fields['_reg'], $vv, $out);
                            $vv = !empty($out[$fields['field']]) ? trim($out[$fields['field']]) : '';
                        }
                    }
                    if (in_array($k, ['FromUrl', 'Cover'])) {
                        $data[$kk][$k] = trim(stripslashes($vv));
                    } elseif ($k == 'ArticleTime') {
                        $data[$kk][$k] = self::getFormatDate(self::unicode2utf8(trim($vv)));
                    } elseif (in_array($k, ['__id', '__aid'])) {
                        $data[$kk][$k] = (string)$vv;
                    } else {
                        $data[$kk][$k] = self::unicode2utf8(trim($vv));
                    }
                }
            }
        }
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                if (!empty($config['list_preg']['replace'])) {
                    foreach ($config['list_preg']['replace'] as $rk => $rv) {
                        $rep = $rv['rep'];
                        foreach ($rv['keys'] as $rvv) {
                            $rep = str_replace('{' . $rvv . '}', $v[$rvv], $rep);
                        }
                        $data[$k][$rk] = $rep;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 获取翻页url
     * @param $url
     * @param $page
     * @return mixed
     */
    private static function getPageUrl($url, $page = 1)
    {
        if ($page > -1) {
            if (stripos($url, '{page}') !== false) {
                $offset = 1;
                if (!empty($config['offset'])) {
                    $offset = $config['offset'];
                }
                $url = str_replace('{page}', $page, $url);
                self::$page += $offset;
            } else {
                self::$page = -1; // 不翻下一页
            }
        }
        if (stripos($url, '{mtime}') !== false) {
            $url = str_replace('{mtime}', floor(microtime(true) * 1000), $url);
        }
        if (preg_match('@(\{.+\})@isU', $url)) {
            $out = [];
            preg_match_all('@(\{.+\})@isU', $url, $out);
            foreach ($out[1] as $v) {
                $out1 = [];
                preg_match_all('@([^}{|]+)@is', $v, $out1);
                if (!empty($out1[1])) {
                    $opfun = $val = [];
                    foreach ($out1[1] as $kk => $vv) {
                        if ($kk == 0) {
                            $fun = $vv;
                        } elseif (strpos($vv, '=') === 0) {
                            $opfun[] = substr($vv, 1);
                        } else {
                            $val[] = $vv;
                        }
                    }
                    if (!empty($fun)) {
                        switch (count($val)) {
                            case 0:
                                $reps[$v] = $fun();
                                break;
                            case 1:
                                $reps[$v] = $fun($val[0]);
                                break;
                            case 2:
                                $reps[$v] = $fun($val[0], $val[1]);
                                break;
                            case 3:
                                $reps[$v] = $fun($val[0], $val[1], $val[2]);
                                break;
                            case 4:
                                $reps[$v] = $fun($val[0], $val[1], $val[2], $val[3]);
                                break;
                            case 5:
                                $reps[$v] = $fun($val[0], $val[1], $val[2], $val[3], $val[4]);
                                break;
                        }
                        if (!empty($opfun) && isset($reps[$v])) {
                            foreach ($opfun as $ov) {
                                $op = substr($ov, 0, 1);
                                if (in_array($op, ['+', '-', '*', '/', '%'])) {
                                    $ov = substr($ov, 1);
                                    switch ($op) {
                                        case '+':
                                            $reps[$v] = $reps[$v] + $ov;
                                            break;
                                        case '-':
                                            $reps[$v] = $reps[$v] - $ov;
                                            break;
                                        case '*':
                                            $reps[$v] = $reps[$v] * $ov;
                                            break;
                                        case '/':
                                            $reps[$v] = $reps[$v] / $ov;
                                            break;
                                        case '%':
                                            $reps[$v] = $reps[$v] % $ov;
                                            break;
                                    }
                                } else {
                                    $reps[$v] = $ov($reps[$v]);
                                }
                            }
                        }
                    }
                    if (!empty($reps)) {
                        $url = str_replace(array_keys($reps), array_values($reps), $url);
                    }
                }
            }
        }
        return $url;
    }

    /**
     * 内容及图片采集
     *
     * @param $info
     * @param $config
     * @return void
     */
    private static function getContent($info, &$config)
    {
        if (!empty($info['FromUrl']) && self::$order > 0) {

            $url_arr = parse_url($info['FromUrl']);
            $itemid = trim(str_replace(['.shtml', '.html', '.htm'], '', $url_arr['path']), '/');
            $info['ItemId'] = self::$siteid . '|' . $itemid . (!empty($url_arr['query']) ? '|' . $url_arr['query'] : '');
            $info['_host'] = $url_arr['scheme'] . '://' . $url_arr['host'];
            $info['_host_path'] = $url_arr['scheme'] . '://' . $url_arr['host'] . dirname($url_arr['path']) . '/';
            if (substr($info['_host_path'], -2) == '//') {
                $info['_host_path'] = substr($info['_host_path'], 0, -1);
            }


            $pdo = self::getPDO();
            $data = $pdo->find(self::$table, ['ItemId' => $info['ItemId'], 'SiteId' => self::$siteid]);
            if (!empty($data[0]['Id']) && $data[0]['CollectStatus'] == 1) {
                self::echoMsg("Skip Collect Already Exists\n");
                return;
            }

            $cont2 = self::getCurl($info['FromUrl']);

            if (isset($config['detail_preg'])) {
                if (is_numeric($config['detail_preg'])) { //正则复用
                    $config['detail_preg'] = self::$config[self::$siteid][$config['detail_preg']]['detail_preg'];
                }
                foreach ($config['detail_preg'] as $k => $v) {
                    if (strpos($k, '_') !== 0) {
                        if (!is_array($v)) {
                            $v = [$v];
                        }
                        foreach ($v as $kk => $vv) {
                            $out = [];
                            preg_match($vv, $cont2, $out);
                            $info[$k] = trim($out[1]);
                            if (!empty($info[$k])) {
                                break;
                            }
                        }
                    } elseif (strpos($k, '__') === 0) {
                        if (!empty($info[substr($k, 2)])) {
                            $out = [];
                            preg_match_all($v, (string)$info[substr($k, 2)], $out);
                            if (!empty($out[1]) && substr($k, 2) == 'Tags') {
                                foreach ($out[1] as $_k => $_v) {
                                    $out[1][$_k] = trim(strip_tags($_v));
                                }
                            }
                            $info[substr($k, 2)] = array_filter($out[1]);
                        }
                    } elseif (strpos($k, '_') === 0) {
                        if (!empty($info[substr($k, 1)])) {
                            $out = [];
                            preg_match($v, (string)$info[substr($k, 1)], $out);
                            $info[substr($k, 1)] = trim($out[1]);
                        }
                    }
                }
            }

            if (empty($info['Tags'])) {
                $info['Tags'] = [];
            }
            $info['Tags'] = array_unique(array_merge($config['tags'], $info['Tags']));
            $info['Tags'] = implode(',', $info['Tags']);
            $info['Type'] = 1;
            if (!empty($config['type'])) {
                $info['Type'] = intval($config['type']);
            }
            $detail_type = 0; // 详细页类型 0：整页 1：ajax整页 2：翻多页
            if (!empty($config['detail_type'])) {
                if (is_numeric($config['detail_type'])) { //正则复用
                    $config['detail_type'] = self::$config[self::$siteid][$config['detail_type']]['detail_type'];
                }

                foreach ($config['detail_type'] as $k => $v) {
                    if (strpos($k, '__') === 0) {
                        $out = [];
                        preg_match_all($v, $cont2, $out);
                        $info[$k] = $out[1];
                    } elseif (strpos($k, '_') === 0) {
                        $out = [];
                        preg_match($v, $cont2, $out);
                        $info[$k] = self::unicode2utf8(trim($out[1]));
                    }
                }

                if (!empty($config['detail_type']['type_reg'])) {
                    foreach ($config['detail_type']['type_reg'] as $k => $v) {
                        if (preg_match($v, $cont2)) {
                            $info['Type'] = $k;
                            break;
                        }
                    }
                }
                if ($config['detail_type']['reg']) {
                    foreach ($config['detail_type']['reg'] as $k => $v) {
                        if (preg_match($v, $cont2)) {
                            $detail_type = $k;
                            break;
                        }
                    }
                }
            }

            if ($info['Type'] == 2 && !empty($config['atlas_preg']) && $detail_type == 0) { // 图集内容采集
                $info['Content'] = [];
                if (is_numeric($config['atlas_preg'])) { //正则复用
                    $config['atlas_preg'] = self::$config[self::$siteid][$config['atlas_preg']]['atlas_preg'];
                }
                $temp = [];
                if (!empty($config['atlas_preg']['__img_text'])) {
                    $out = [];
                    preg_match_all($config['atlas_preg']['__img_text'], $cont2, $out);
                    if (!empty($out[0])) {
                        foreach ($out[0] as $k => $v) {
                            foreach ($config['atlas_preg'] as $kk => $vv) {
                                if (strpos($kk, '_') !== 0) {
                                    $out1 = [];
                                    preg_match($vv, $v, $out1);
                                    $temp[$k][$kk] = !empty($out1[1]) ? trim(strip_tags($out1[1])) : '';
                                }
                            }
                            if (empty($temp[$k]['img'])) {
                                unset($temp[$k]);
                            } else {
                                if (strpos($temp[$k]['img'], 'http') === false) {
                                    if (strpos($temp[$k]['img'], '//') === 0) {
                                        $temp[$k]['img'] = 'http:' . $temp[$k]['img'];
                                    } elseif (strpos($temp[$k]['img'], '/') === 0) {
                                        $temp[$k]['img'] = $info['_host'] . $temp[$k]['img'];
                                    } else {
                                        $temp[$k]['img'] = $info['_host_path'] . $temp[$k]['img'];
                                    }
                                }
                            }
                        }
                    }
                    $info['Content'] = $temp;
                } else {
                    foreach ($config['atlas_preg'] as $k => $v) {
                        if (!empty($v)) {
                            $out = [];
                            preg_match_all($v, $cont2, $out);
                            $temp[$k] = !empty($out[1]) ? $out[1] : [];
                        }
                    }
                    if (!empty($temp)) {
                        foreach ($temp as $k => $v) {
                            foreach ($v as $kk => $vv) {
                                if ($k == 'img') {
                                    if (strpos($vv, 'http') === false) {
                                        if (strpos($vv, '//') === 0) {
                                            $vv = 'http:' . $vv;
                                        } elseif (strpos($vv, '/') === 0) {
                                            $vv = $info['_host'] . $vv;
                                        } else {
                                            $vv = $info['_host_path'] . $vv;
                                        }
                                    }
                                    $info['Content'][$kk][$k] = trim(stripslashes($vv));
                                } else {
                                    $info['Content'][$kk][$k] = self::unicode2utf8(trim($vv));
                                }
                            }
                        }
                    }
                }
            }


            if ($detail_type == 1) { // 内容ajax跳转一次
                $tmp_text = key($config['detail_type']['url_reg']) == 'Content' ? $cont2 : $info[key($config['detail_type']['url_reg'])];
                $out = [];
                preg_match(current($config['detail_type']['url_reg']), $tmp_text, $out);
                $durl = $config['detail_type']['url'];
                $search = $replace = [];
                foreach ($out as $k => $v) {
                    $search[] = '{' . $k . '}';
                    $replace[] = $v;
                }
                $durl = self::getPageUrl(str_replace($search, $replace, $durl));
                $cont2 = self::getCurl($durl);
                if ($info['Type'] == 1) {
                    foreach ($config['detail_preg'] as $k => $v) {
                        if (strpos($k, '_') !== 0 && empty($info[$k])) {
                            if (!is_array($v)) {
                                $v = [$v];
                            }
                            foreach ($v as $kk => $vv) {
                                $out = [];
                                preg_match($vv, $cont2, $out);
                                $info[$k] = trim($out[1]);
                                if (!empty($info[$k])) {
                                    break;
                                }
                            }
                        } elseif (strpos($k, '__') === 0) {
                            if (!empty($info[substr($k, 2)])) {
                                $out = [];
                                preg_match_all($v, (string)$info[substr($k, 2)], $out);
                                if (!empty($out[1]) && substr($k, 2) == 'Tags') {
                                    foreach ($out[1] as $_k => $_v) {
                                        $out[1][$_k] = trim(strip_tags($_v));
                                    }
                                }
                                $info[substr($k, 2)] = array_filter($out[1]);
                            }
                        } elseif (strpos($k, '_') === 0) {
                            if (!empty($info[substr($k, 1)])) {
                                $out = [];
                                preg_match($v, (string)$info[substr($k, 1)], $out);
                                $info[substr($k, 1)] = trim($out[1]);
                            }
                        }
                    }
                } elseif ($info['Type'] == 2) {
                    $info['Content'] = [];
                    if (is_numeric($config['atlas_preg'])) { //正则复用
                        $config['atlas_preg'] = self::$config[self::$siteid][$config['atlas_preg']]['atlas_preg'];
                    }
                    $temp = [];
                    if (!empty($config['atlas_preg']['__img_text'])) {
                        $out = [];
                        preg_match_all($config['atlas_preg']['__img_text'], $cont2, $out);
                        if (!empty($out[0])) {
                            foreach ($out[0] as $k => $v) {
                                foreach ($config['atlas_preg'] as $kk => $vv) {
                                    if (strpos($kk, '_') !== 0) {
                                        $out1 = [];
                                        preg_match($vv, $v, $out1);
                                        $temp[$k][$kk] = !empty($out1[1]) ? trim(strip_tags($out1[1])) : '';
                                    }
                                }
                                if (empty($temp[$k]['img'])) {
                                    unset($temp[$k]);
                                } else {
                                    if (strpos($temp[$k]['img'], 'http') === false) {
                                        if (strpos($temp[$k]['img'], '//') === 0) {
                                            $temp[$k]['img'] = 'http:' . $temp[$k]['img'];
                                        } elseif (strpos($temp[$k]['img'], '/') === 0) {
                                            $temp[$k]['img'] = $info['_host'] . $temp[$k]['img'];
                                        } else {
                                            $temp[$k]['img'] = $info['_host_path'] . $temp[$k]['img'];
                                        }
                                    }
                                }
                            }
                        }
                        $info['Content'] = $temp;
                    } else {
                        foreach ($config['atlas_preg'] as $k => $v) {
                            if (!empty($v)) {
                                $out = [];
                                preg_match_all($v, $cont2, $out);
                                $temp[$k] = !empty($out[1]) ? $out[1] : [];
                            }
                        }
                        if (!empty($temp)) {
                            foreach ($temp as $k => $v) {
                                foreach ($v as $kk => $vv) {
                                    if ($k == 'img') {
                                        if (strpos($vv, 'http') === false) {
                                            if (strpos($vv, '//') === 0) {
                                                $vv = 'http:' . $vv;
                                            } elseif (strpos($vv, '/') === 0) {
                                                $vv = $info['_host'] . $vv;
                                            } else {
                                                $vv = $info['_host_path'] . $vv;
                                            }
                                        }
                                        $info['Content'][$kk][$k] = trim(stripslashes($vv));
                                    } else {
                                        $info['Content'][$kk][$k] = self::unicode2utf8(trim($vv));
                                    }
                                }
                            }
                        }
                    }
                }
            } elseif ($detail_type == 2) { // 多页内容采集
                if (!empty($config['detail_type']['page_all_reg'])) { // 按总页数循环采集
                    $out = [];
                    preg_match($config['detail_type']['page_all_reg'], $cont2, $out);
                    $page = intval($out[1]);
                    if ($page >= 2) {
                        $reg = $info['Type'] == 1 ? $config['detail_preg']['Content'] : $config['atlas_preg'];
                        for ($p = 2; $p <= $page; $p++) {
                            $url3 = preg_replace($config['detail_type']['page_next_rep'], "_{$p}$2", $info['FromUrl']);
                            $cont3 = self::getCurl($url3);
                            if ($info['Type'] == 1) {
                                if (!is_array($reg)) {
                                    $reg = [$reg];
                                }
                                foreach ($reg as $v) {
                                    $out = [];
                                    preg_match($v, $cont3, $out);
                                    if (!empty($out[1])) {
                                        $info['Content'] .= $out[1];
                                        break;
                                    }
                                }
                            } elseif ($info['Type'] == 2) { // 多页图集
                                $temp = [];
                                if (!empty($config['atlas_preg']['__img_text'])) {
                                    $out = [];
                                    preg_match_all($config['atlas_preg']['__img_text'], $cont3, $out);
                                    if (!empty($out[0])) {
                                        foreach ($out[0] as $k => $v) {
                                            foreach ($config['atlas_preg'] as $kk => $vv) {
                                                if (strpos($kk, '_') !== 0) {
                                                    $out1 = [];
                                                    preg_match($vv, $v, $out1);
                                                    $temp[$k][$kk] = !empty($out1[1]) ? trim(strip_tags($out1[1])) : '';
                                                }
                                            }
                                            if (empty($temp[$k]['img'])) {
                                                unset($temp[$k]);
                                            } else {
                                                if (strpos($temp[$k]['img'], 'http') === false) {
                                                    if (strpos($temp[$k]['img'], '//') === 0) {
                                                        $temp[$k]['img'] = 'http:' . $temp[$k]['img'];
                                                    } elseif (strpos($temp[$k]['img'], '/') === 0) {
                                                        $temp[$k]['img'] = $info['_host'] . $temp[$k]['img'];
                                                    } else {
                                                        $temp[$k]['img'] = $info['_host_path'] . $temp[$k]['img'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    foreach ($config['atlas_preg'] as $k => $v) {
                                        if (!empty($v)) {
                                            $out = [];
                                            preg_match_all($v, $cont3, $out);
                                            $temp[$k] = !empty($out[1]) ? $out[1] : [];
                                        }
                                    }
                                    if (!empty($temp)) {
                                        $temp1 = [];
                                        foreach ($temp as $k => $v) {
                                            foreach ($v as $kk => $vv) {
                                                if ($k == 'img') {
                                                    if (strpos($vv, 'http') === false) {
                                                        if (strpos($vv, '//') === 0) {
                                                            $vv = 'http:' . $vv;
                                                        } elseif (strpos($vv, '/') === 0) {
                                                            $vv = $info['_host'] . $vv;
                                                        } else {
                                                            $vv = $info['_host_path'] . $vv;
                                                        }
                                                    }
                                                    $temp1[$kk][$k] = trim(stripslashes($vv));
                                                } else {
                                                    $temp1[$kk][$k] = self::unicode2utf8(trim($vv));
                                                }
                                            }
                                        }
                                        $temp = $temp1;
                                    }
                                }
                                $info['Content'] = array_merge($info['Content'], $temp);
                            }
                        }
                    }
                } elseif (!empty($config['detail_type']['page_next_reg'])) { // 按下一页循环采集
                    $out = [];
                    preg_match($config['detail_type']['page_next_reg'], $cont2, $out);
                    if (!empty($out[1])) {
                        $url3 = $out[1];
                        if (strpos($url3, 'http') === false) {
                            if (strpos($url3, '//') === 0) {
                                $url3 = 'http:' . $url3;
                            } elseif (strpos($url3, '/') === 0) {
                                $url3 = $info['_host'] . $url3;
                            } else {
                                $url3 = $info['_host_path'] . $url3;
                            }
                        }
                        $reg = $info['Type'] == 1 ? $config['detail_preg']['Content'] : $config['atlas_preg'];
                        while (!empty($url3)) {
                            $cont3 = self::getCurl($url3);
                            if ($info['Type'] == 1) { // 多页文章
                                if (!is_array($reg)) {
                                    $reg = [$reg];
                                }
                                foreach ($reg as $v) {
                                    $out = [];
                                    preg_match($v, $cont3, $out);
                                    if (!empty($out[1])) {
                                        $info['Content'] .= $out[1];
                                        break;
                                    }
                                }
                            } elseif ($info['Type'] == 2) { // 多页图集
                                $temp = [];
                                if (!empty($config['atlas_preg']['__img_text'])) {
                                    $out = [];
                                    preg_match_all($config['atlas_preg']['__img_text'], $cont3, $out);
                                    if (!empty($out[0])) {
                                        foreach ($out[0] as $k => $v) {
                                            foreach ($config['atlas_preg'] as $kk => $vv) {
                                                if (strpos($kk, '_') !== 0) {
                                                    $out1 = [];
                                                    preg_match($vv, $v, $out1);
                                                    $temp[$k][$kk] = !empty($out1[1]) ? trim(strip_tags($out1[1])) : '';
                                                }
                                            }
                                            if (empty($temp[$k]['img'])) {
                                                unset($temp[$k]);
                                            } else {
                                                if (strpos($temp[$k]['img'], 'http') === false) {
                                                    if (strpos($temp[$k]['img'], '//') === 0) {
                                                        $temp[$k]['img'] = 'http:' . $temp[$k]['img'];
                                                    } elseif (strpos($temp[$k]['img'], '/') === 0) {
                                                        $temp[$k]['img'] = $info['_host'] . $temp[$k]['img'];
                                                    } else {
                                                        $temp[$k]['img'] = $info['_host_path'] . $temp[$k]['img'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    foreach ($config['atlas_preg'] as $k => $v) {
                                        if (!empty($v)) {
                                            $out = [];
                                            preg_match_all($v, $cont3, $out);
                                            $temp[$k] = !empty($out[1]) ? $out[1] : [];
                                        }
                                    }
                                    if (!empty($temp)) {
                                        $temp1 = [];
                                        foreach ($temp as $k => $v) {
                                            foreach ($v as $kk => $vv) {
                                                if ($k == 'img') {
                                                    if (strpos($vv, 'http') === false) {
                                                        if (strpos($vv, '//') === 0) {
                                                            $vv = 'http:' . $vv;
                                                        } elseif (strpos($vv, '/') === 0) {
                                                            $vv = $info['_host'] . $vv;
                                                        } else {
                                                            $vv = $info['_host_path'] . $vv;
                                                        }
                                                    }
                                                    $temp1[$kk][$k] = trim(stripslashes($vv));
                                                } else {
                                                    $temp1[$kk][$k] = self::unicode2utf8(trim($vv));
                                                }
                                            }
                                        }
                                        $temp = $temp1;
                                    }
                                }
                                $info['Content'] = array_merge($info['Content'], $temp);
                            }
                            $url3 = '';
                            $out = [];
                            preg_match($config['detail_type']['page_next_reg'], $cont3, $out);
                            if (!empty($out[1])) {
                                $url3 = $out[1];
                                if (strpos($url3, 'http') === false) {
                                    if (strpos($url3, '//') === 0) {
                                        $url3 = 'http:' . $url3;
                                    } elseif (strpos($url3, '/') === 0) {
                                        $url3 = $info['_host'] . $url3;
                                    } else {
                                        $url3 = $info['_host_path'] . $url3;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($config['detail_preg']['___img']) && !empty($info['Content']) && !is_array($info['Content'])) {
                $info['Content'] = preg_replace_callback(
                    $config['detail_preg']['___img'],
                    function ($match) use ($info) {
                        if (strpos($match[1], 'file:') === 0) {
                            return '';
                        }
                        if (strpos($match[1], 'http') === 0) {
                            $imgpath = $match[1];
                        } else {
                            if (strpos($match[1], '/') === 0) {
                                $imgpath = $info['_host'] . $match[1];
                            } else {
                                $imgpath = $info['_host_path'] . $match[1];
                            }
                        }
                        return '<img src="' . $imgpath . '" />';
                    },
                    $info['Content']
                );
            }

            if (!empty($info['ArticleTime'])) {
                $info['ArticleTime'] = self::getFormatDate(self::unicode2utf8($info['ArticleTime']));
            } else {
                $info['ArticleTime'] = date('Y-m-d H:i:s');
            }
            if (!empty($info['Tags'])) {
                $info['Tags'] = str_replace(['，', ';', '；'], ',', $info['Tags']);
            }

            if ((strtotime($info['ArticleTime']) >= self::$createtime || (__ENV__ != 'ONLINE' && self::$order == 1)) && self::$order > 0) {

                if (($info['Type'] != 3 && !empty($info['Content'])) || $info['Type'] == 3) {
                    self::getCover($info);
                    self::getContentImg($info);
                    if ($info['Type'] == 3 && $config['detail_type']['video_url']) { // 视频采集
                        $search = $replace = [];
                        foreach ($config['detail_type']['video_url']['keys'] as $v) {
                            $search[] = '{' . $v . '}';
                            $replace[] = urlencode($info[$v]);
                        }
                        $video_url = str_replace($search, $replace, $config['detail_type']['video_url']['rep']);
                        $video_url = self::getPageUrl($video_url, -2);
                        $vfun = $config['detail_type']['video_fun'];
                        $vret = self::$vfun($video_url);
                        if (!empty($vret)) {
                            $info['FromSourceUrl'] = $vret['vtime'];
                            $info['Description'] = $vret['path'];
                            self::$collectstatus = 1;
                        } else {
                            self::$collectstatus = 2;
                        }
                    }
                    $info['CollectStatus'] = self::$collectstatus;
                    $info['SiteId'] = self::$siteid;
                    $info['CreateTime'] = date('Y-m-d H:i:s');
                    if ($info['Type'] == 2) {
                        $info['Content'] = serialize($info['Content']);
                    }

                    foreach ($info as $k => $v) {
                        if (strpos($k, '_') === 0) {
                            unset($info[$k]);

                        }
                    }

                if (!empty($data[0]['Id'])) {
                    $ret = '';
                    if ($data[0]['CollectStatus'] != 1 && $info['CollectStatus'] == 1) {
                        $ret = $pdo->update(self::$table, $info, ['Id' => $data[0]['Id']]);
                    }
                } else {
                    $ret = $pdo->insert(self::$table, $info, ['ignore' => true]);
                }
                if (empty($ret)) {
                    if ($ret === false) {
                        self::echoMsg("Insert Article Failed\n");
                        $log = [
                            'url' => $info['FromUrl'],
                            'msg' => 'Insert Article Failed',
                            'data' => $info,
                        ];
                        logs($log, self::$log_path);
                    } else {
                        self::echoMsg("Skip Insert Already Exists\n");
                        $log = [
                            'url' => $info['FromUrl'],
                            'itemid' => $info['ItemId'],
                            'msg' => 'Skip Insert Already Exists',
                        ];
                        logs($log, self::$log_path);
		    }
                    }
                }
            } else {
                self::$order = -1; // 不采集下一条
                self::$page = -1; // 不采集下一页
            }
        }
    }

    /**
     * 根据传人的参数配置导入设置
     * @param array $argv 传入参数
     */
    private static function getConfig($argv)
    {

        self::$config = config('collect/' . $argv[1]);
        if (!empty(self::$config[$argv[2]][$argv[3]])) {
            self::$siteid = trim($argv[2]);
            self::$channelid = trim($argv[3]);
            self::$log_path .= self::$siteid . '_' . self::$channelid;
            self::$savepath .= self::$channelid;
        } else {
            exit("php index.php collect/collectWeb/index [config] [siteid] [channelid] [day2]\n");
        }
        if (!empty($argv[4])) {
            if (stripos($argv[4], 'day') !== false) {
                $cday = substr($argv[4], 4);
                if ($cday > 0) {
                    self::$createtime = strtotime(date('Y-m-d')) - ($cday - 1) * 86400;
                }
            }
        }
        if (empty(self::$createtime)) {
            self::$createtime = strtotime(date('Y-m-d'));
        }
    }

    /**
     * 输出提示信息
     * @param $msg
     * @param int $exit
     */
    private static function echoMsg($msg, $exit = 0)
    {
        if (self::$isecho) {
            echo $msg;
            if ($exit) {
                exit();
            }
        }
    }

    /**
     * 中文日期格式化
     *
     * @param $str
     * @return mixed
     */
    private static function getFormatDate($str)
    {
        if (is_numeric($str)) {
            if (strlen($str) > 10) {
                $time = substr($str, 0, 10);
            } elseif (substr($str, 0, 1) == 1) {
                $time = $str;
            } else {
                $time = strtotime($str);
            }
        } else {
            if ($str == '刚刚') {
                $time = time();
            } elseif (strpos($str, '分钟前') !== false) {
                $out = [];
                preg_match('@(\d+)分钟前@', $str, $out);
                $time = time() - intval($out[1]) * 60;
            } elseif (strpos($str, '小时前') !== false) {
                $out = [];
                preg_match('@(\d+)小时前@', $str, $out);
                $time = time() - intval($out[1]) * 3600;
            } elseif ($str == '昨天') {
                $time = time() - 86400;
            } elseif ($str == '前天') {
                $time = time() - 86400 * 2;
            } elseif (strpos($str, '天前') !== false) {
                $out = [];
                preg_match('@(\d+)天前@', $str, $out);
                $time = time() - intval($out[1]) * 86400;
            } else {
                $search = ['年', '月', '日'];
                $replace = ['-', '-', ''];
                $date = str_replace($search, $replace, $str);
                $time = strtotime($date);
            }
        }
        return date('Y-m-d H:i:s', $time);
    }

    /**
     * @return \Bare\PDOQuery|null|PDOStatement
     */
    private static function getPDO()
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_COLLECT_W);
        }
        return self::$pdo;
    }

    /**
     * 获取网页内容
     *
     * @param $url string 网址
     * @param array $extra cookie:文件名 referer:来源 isheader:是否获取头文件 header:头文件 nobody:是否获取内容
     * @param int $timeout 超时时间
     * @param int $times 302跳转次数
     * @return string
     */
    private static function getCurl($url, $extra = [], $timeout = 30, $times = 5)
    {
        if ($times < 1) {
            return 0;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_HEADER, isset($extra['isheader']) ? $extra['isheader'] : false);
        curl_setopt($ch, CURLOPT_NOBODY, isset($extra['nobody']) ? $extra['nobody'] : false);
        // ip
        $ip = mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 254);
        $header = [
            'CLIENT-IP:' . $ip,
            'X-FORWARDED-FOR:' . $ip,
        ];
        if (empty($extra['header'])) {
            $extra['header'] = [
                'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:53.0) Gecko/20100101 Firefox/53.0'
            ];
        }
        $header = array_merge($header, $extra['header']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 来源
        if (empty($extra['referer'])) {
            $extra['referer'] = 'https://www.google.com';
        }
        curl_setopt($ch, CURLOPT_REFERER, $extra['referer']);
        if (empty($extra['cookie'])) {
            //$extra['cookie'] = self::$siteid . '_' . self::$channelid;
        }
        // cookie
        if (!empty($extra['cookie'])) {
            $cookie_file = DATA_PATH . 'cookie/' . $extra['cookie'] . '.tmp';
            if (!file_exists($cookie_file)) {
                if (!is_dir(dirname($cookie_file))) {
                    mkdir(dirname($cookie_file), 0777, true);
                }
                $fp = fopen($cookie_file, 'w+');
                fclose($fp);
            }
            //指定保存cookie的文件
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            //指定发送给服务器的cookie文件
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        }

        $output = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (in_array($http_code, [301, 302])) {
            $info = curl_getinfo($ch);
            if (!empty($info['redirect_url'])) {
                $output = self::getCurl($info['redirect_url'], $extra, $timeout, --$times);
            } else {
                $output = '';
            }
        } else {
            $encode = mb_detect_encoding($output, ["ASCII", "UTF-8", "GB2312", "GBK", "BIG5"]);
            if ($encode !== "UTF-8") {
                $output = iconv($encode, "UTF-8//IGNORE", $output);
                //$output = mb_convert_encoding($output, 'UTF-8', $encode);
            }
        }
        curl_close($ch);

        return $output;
    }

    /**
     * 获取内容图片宽高信息
     * @param $info
     * @return void
     */
    private static function getContentImg(&$info)
    {
        self::$collectstatus = 1;
        if (!empty($info['Content'])) {
            if ($info['Type'] == 2) {
                foreach ($info['Content'] as $k => $v) {
                    self::saveContentImg($v['img'], $info);
                }
            } else {
                $reg = '@<img[^>]*([_-]?src|data-original)="([^"]*)"[^>]*>@isU';
                preg_replace_callback(
                    $reg,
                    function ($match) use ($info) {
                        self::saveContentImg($match[2], $info);
                    },
                    $info['Content']
                );
            }
        }
    }

    /**
     * 保存内容图片
     * @param $picurl
     * @param $info
     */
    private static function saveContentImg($picurl, $info)
    {
        $ext = strtolower(substr($picurl, strripos($picurl, '.')));
        if (empty($ext) || !in_array($ext, ['.gif', '.png'])) {
            $ext = '.jpg';
        }
        $image_info = [];
        $pdo = self::getPDO();
        $url_info = $pdo->find(self::$img_table, ['PicUrl' => $picurl]);
        if (!empty($url_info)) {
            $url_info = current($url_info);
            if ($url_info['Status'] == 2) {
                if (strpos($picurl, 'http') === false) {
                    $purl = strpos($picurl, '/') === 0 ? $info['_host'] . $picurl : $info['_host_path'] . $picurl;
                } else {
                    $purl = $picurl;
                }
                $image_url = self::downloadImage($purl, 'content', $ext);
                if (!empty($image_url)) {
                    $image_size = getimagesize($image_url['path']);
                    if (!empty($image_size)) {
                        $image_info = [
                            'Width' => $image_size[0],
                            'Height' => $image_size[1],
                            'PicUrl' => $picurl,
                            'SavePath' => $image_url['rpath'],
                            'Status' => 1,
                        ];
                        $pdo->update(self::$img_table, $image_info, ['Id' => $url_info['Id']]);
                    }
                }
            }
            if (!empty($url_info['SavePath'])) {
                if (empty($url_info['Width']) || empty($url_info['Height'])) {
                    $image_url = self::getImagePath($url_info['SavePath']);
                    $image_size = getimagesize($image_url);
                    if (!empty($image_size)) {
                        $image_info = [
                            'Width' => $image_size[0],
                            'Height' => $image_size[1],
                        ];
                        $pdo->update(self::$img_table, $image_info, ['Id' => $url_info['Id']]);
                    }
                } else {
                    $image_info = [
                        'Width' => $url_info['Width'],
                        'Height' => $url_info['Height'],
                        'SavePath' => $url_info['SavePath'],
                    ];
                }
            }
        } else {
            if (strpos($picurl, 'http') === false) {
                $purl = strpos($picurl, '/') === 0 ? $info['_host'] . $picurl : $info['_host_path'] . $picurl;
            } else {
                $purl = $picurl;
            }
            $image_url = self::downloadImage($purl, 'content', $ext);
            if (!empty($image_url)) {
                $image_size = getimagesize($image_url['path']);
                if (!empty($image_size)) {
                    $image_info = [
                        'Width' => $image_size[0],
                        'Height' => $image_size[1],
                        'PicUrl' => $picurl,
                        'SavePath' => $image_url['rpath'],
                        'ItemId' => $info['ItemId'],
                        'CreateTime' => date('Y-m-d H:i:s'),
                    ];
                    $pdo->insert(self::$img_table, $image_info, ['ignore' => true]);
                } else {
                    goto FAILED;
                }
            } else {
                FAILED:
                $image_info2 = [
                    'PicUrl' => $picurl,
                    'ItemId' => $info['ItemId'],
                    'Status' => 2, // 采集失败
                    'CreateTime' => date('Y-m-d H:i:s'),
                ];
                $pdo->insert(self::$img_table, $image_info2, ['ignore' => true]);
                $log = [
                    'ItemId' => $info['ItemId'],
                    'msg' => 'Content Image Collect Failed',
                    'imgurl' => $picurl,
                ];
                logs($log, self::$log_path);
            }
        }
        if (empty($image_info)) {
            self::$collectstatus = 2;
        }
    }

    /**
     * 保存封面图
     * @param $info
     * @return array
     */
    private static function getCover(&$info)
    {
        $covers = [];
        if (!empty($info['Cover'])) {
            $pdo = self::getPDO();
            $cover_arr = explode(',', $info['Cover']);
            foreach ($cover_arr as $v) {
                if (!empty($v)) {
                    $url_info = $pdo->find(self::$img_table, ['PicUrl' => $v]);
                    if (!empty($url_info)) {
                        $url_info = current($url_info);
                        if ($url_info['Status'] == 2) {
                            $img_url = self::downloadImage($v);
                            if (!empty($img_url)) {
                                $covers[] = $img_url['rpath'];
                                $image_info = [
                                    'SavePath' => $img_url['rpath'],
                                    'Status' => 1,
                                ];
                                $pdo->update(self::$img_table, $image_info, ['Id' => $url_info['Id']]);
                            }
                        }
                        if (!empty($url_info['SavePath'])) {
                            $covers[] = $url_info['SavePath'];
                        }
                    } else {
                        $img_url = self::downloadImage($v);
                        if (!empty($img_url)) {
                            $covers[] = $img_url['rpath'];
                            $image_info = [
                                'PicUrl' => $v,
                                'SavePath' => $img_url['rpath'],
                                'ItemId' => $info['Id'],
                                'CreateTime' => date('Y-m-d H:i:s'),
                            ];
                            $pdo->insert(self::$img_table, $image_info, ['ignore' => true]);
                        } else {
                            $image_info2 = [
                                'PicUrl' => $v,
                                'ItemId' => $info['Id'],
                                'Status' => 2, // 采集失败
                                'CreateTime' => date('Y-m-d H:i:s'),
                            ];
                            $pdo->insert(self::$img_table, $image_info2, ['ignore' => true]);
                            $log = [
                                'id' => $info['Id'],
                                'msg ' => 'Cover Collect Failed',
                                'cover' => $v,
                            ];
                            logs($log, self::$log_path);
                        }
                    }
                }
            }
        }
        return $covers;
    }

    /**
     * 获取uc头条视频下载地址
     *
     * @param $url
     * @return array|bool
     */
    private static function getUcVideo($url)
    {
        $extra['header'] = [
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36'
        ];
        $cont = self::getCurl($url, $extra);
        $out = '';
        preg_match('@"duration":[^,]+,"url":"([^"]+)"@isU', $cont, $out);
        $vurl = trim($out[1]);
        $out = '';
        preg_match('@"duration":([^,]+),"url":"[^"]+"@isU', $cont, $out);
        $vtime = intval($out[1]);
        $out = '';
        preg_match('@"format":"([^"]+)"@isU', $cont, $out);
        $vext = !empty($out[1]) ? '.' . trim($out[1]) : '.mp4';

        $ret = self::downloadVideo($vurl, $vext);
        if (__ENV__ != 'ONLINE' || empty($ret)) {
            $log = [
                'url' => $url,
                'vurl' => $vurl,
                'ret' => $ret,
            ];
            logs($log, self::$log_path . '_video');
        }
        if (!empty($ret)) {
            $data = $ret;
            $data['vtime'] = $vtime;
            return $data;
        }
        return false;
    }

    /**
     * 获取微信视频下载地址
     *
     * @param $url
     * @param $times
     * @return array|bool
     */
    private static function getWxVideo($url, $times = 1)
    {
        $url = str_replace('https://v.qq.com/iframe/preview.html?', 'https://mp.weixin.qq.com/mp/videoplayer?', $url);
        $extra['header'] = [
            'User-Agent: Mozilla/5.0 (iPad; CPU OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1'
        ];
        $cont = self::getCurl($url, $extra);
        preg_match('@\s+id : "([^"]+)",@', $cont, $out);
        $vid = trim($out[1]);
        preg_match('@\s+vidsn : "([^"]+)",@', $cont, $out);
        $guid = trim($out[1]);
        $random = mt_rand(100000, 999999);
        $time = time();
        $cburl = "http://bkh5vv.video.qq.com/getinfo?callback=tvp_request_getinfo_callback_{$random}&platform=11001&charge=0&otype=json&ehost=http%3A%2F%2Fv.qq.com&sphls=0&sb=1&nocache=0&_rnd={$time}&guid={$guid}&vids={$vid}&defaultfmt=mp4";
        $cont2 = trim(self::getCurl($cburl, $extra));
        $cont2 = str_replace("tvp_request_getinfo_callback_{$random}(", '', $cont2);
        $cont2 = substr($cont2, 0, -1);
        $arr = json_decode($cont2, true);
        $vkey = $arr['vl']['vi'][0]['fvkey'];
        $fn = $arr['vl']['vi'][0]['fn'];
        $br = $arr['vl']['vi'][0]['br'];
        $uri = $arr['vl']['vi'][0]['ul']['ui'][0]['url'];
        $level = $arr['vl']['vi'][0]['level'];
        $vtime = $arr['preview'];
        $vext = substr($fn, strrpos($fn, '.'));
        $vurl = "{$uri}{$fn}?vkey={$vkey}&br={$br}&platform=2&fmt=mp4&level={$level}&sdtfrom=v4010&guid={$guid}";

        $ret = self::downloadVideo($vurl, $vext);
        if (__ENV__ != 'ONLINE' || empty($ret)) {
            $log = [
                'times' => $times,
                'url' => $url,
                'cburl' => $cburl,
                'vurl' => $vurl,
                'ret' => $ret,
            ];
            logs($log, self::$log_path . '_video');
        }
        if (!empty($ret)) {
            $data = $ret;
            $data['vtime'] = $vtime;
            return $data;
        }
        return false;
    }

    /**
     * 保存视频
     * @param $vurl
     * @param $vext
     * @param $times
     * @return array|bool
     */
    private static function downloadVideo($vurl, $vext = '.mp4', $times = 5)
    {
        if ($times < 1) {
            return 0;
        }
        $base_dir = UPLOAD_PATH . 'article/';

        $file_dir = 'video/' . self::$siteid . '/' . date("Ym") . '/' . date('d') . '/';
        $file_name = self::$savepath . uniqid() . $vext;
        $img_dir = $base_dir . $file_dir;
        $path = $img_dir . $file_name;
        $rpath = $file_dir . $file_name;

        $start_time = microtime(true);
        $ch = curl_init($vurl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        $video = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (in_array($http_code, [301, 302])) {
            $info = curl_getinfo($ch);
            if (!empty($info['redirect_url'])) {
                self::downloadVideo($info['redirect_url'], $vext, --$times);
                exit();
            }
        }
        curl_close($ch);
        $end_time = microtime(true);

        if (!empty($video)) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            $fp = fopen($path, 'a');
            fwrite($fp, $video);
            fclose($fp);
            $res = ['path' => $path, 'rpath' => $rpath];
        } else {
            $log = [
                'vurl' => $vurl,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'time_out' => $end_time - $start_time,
            ];
            logs($log, self::$log_path . '_video');
            $res = false;
        }

        return $res;
    }

    /**
     * 保存图片
     *
     * @param $url
     * @param $path
     * @param $ext
     * @return mixed
     */
    private static function downloadImage($url, $path = 'cover', $ext = '.jpg')
    {
        if (strpos($url, '://') === false || !in_array($path, ['cover', 'content'])) {
            return false;
        }
        $url = str_replace('wx_fmt=webp', 'wx_fmt=jpeg', $url);
        if (empty($ext)) {
            $ext = '.jpg';
        }
        $base_dir = UPLOAD_PATH . 'article/';

        $file_dir = $path . '/' . self::$siteid . '/' . date("Ym") . '/' . date('d') . '/';
        $file_name = self::$savepath . uniqid() . $ext;
        $img_dir = $base_dir . $file_dir;
        $path = $img_dir . $file_name;
        $rpath = $file_dir . $file_name;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        $img = curl_exec($ch);
        curl_close($ch);

        if (!empty($img)) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            $fp = fopen($path, 'a');
            fwrite($fp, $img);
            fclose($fp);
            return ['path' => $path, 'rpath' => $rpath];
        } else {
            return false;
        }
    }

    /**
     * 获取图片绝对路径
     *
     * @param $url
     * @return string
     */
    private static function getImagePath($url)
    {
        if (stripos($url, '://') === false) {
            $base_dir = UPLOAD_PATH . 'article/';
            $url = $base_dir . $url;
        }

        return $url;
    }

    /**
     *  json中文Unicode解码
     * @param $str
     * @return mixed|string
     */
    private static function unicode2utf8($str)
    {
        if (!$str) {
            return $str;
        }
        $decode = json_decode($str);
        if ($decode) {
            return $decode;
        }
        $str = '["' . $str . '"]';
        $decode = json_decode($str);
        if (count($decode) == 1) {
            return $decode[0];
        }
        return $str;
    }
}
