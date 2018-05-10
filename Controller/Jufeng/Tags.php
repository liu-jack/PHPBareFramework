<?php

namespace Controller\Jufeng;

use Bare\DB;
use Bare\C\Controller;

/**
 * 测试用控制器
 */
class Tags extends Controller
{
    public function index()
    {
        /**
         * SELECT COUNT(*) FROM tag WHERE `cate` = '待定'
         * #UPDATE tag SET cate = '其它' WHERE cate = '待定'
         */
        $list = $this->m->getTags(['third' => '']);
        var_dump($list);
        die;
        foreach ($list['data'] as $k => $v) {
            $this->m->updateTag($v['id'], ['second' => $v['third'], 'third' => '']);
        }
    }

    public function tag()
    {
        $data0['频道'] = [
            '频道情感' => '频道情感',
            '频道娱乐' => '频道娱乐',
            '频道两性' => '频道两性',
            '频道健身' => '频道健身',
            '频道时尚' => '频道时尚',
            '频道备孕' => '频道备孕',
            '频道饮食' => '频道饮食',
            '频道怀孕' => '频道怀孕',
            '频道育儿' => '频道育儿',
            '频道搞笑' => '频道搞笑',
            '频道星座' => '频道星座',
            '频道疾病' => '频道疾病',
            '频道用品' => '频道用品',
            '频道手工' => '频道手工',
            '频道二胎' => '频道二胎',
            '频道故事' => '频道故事',
            '频道早教' => '频道早教',
        ];
        $list = $this->m->getTags();
        foreach ($list['data'] as $k => $v) {
            if (!empty($v['third']) && $v['second'] != $v['third']) {
                $data[$v['cate']][$v['second']][$v['third']] = $v['third'];
            }
        }
        foreach ($list['data'] as $k => $v) {
            if (!isset($data[$v['cate']][$v['second']])) {
                $data[$v['cate']][$v['second']] = $v['second'];
            }
        }
        //$other['待定'] = $data['待定'];
        unset($data['待定']);
        unset($data['玩游戏']);
        unset($data['教父母']);
        unset($data['学知识']);
        unset($data['看动画']);
        unset($data['听儿歌']);
        unset($data['讲故事']);
        //$data = $data + $other;
        //$other['频道'] = $data['频道'];
        unset($data['频道']);
        /*         foreach($other['频道'] as $k => $v) {
                    $v = '频道' . $v;
                    unset($other['频道'][$k]);
                    $other['频道'][$v] = $v;
                }
                $data = $other+ $data; */
        $data = $data0 + $data;

        foreach ($data as $k => $v) {
            $cate[] = $k;
        }

        $this->value('cate', $cate);
        $this->value('list', $data);
        $this->view();
    }

    public function tag2()
    {
        $data0['频道'] = [
            '情感' => '情感',
            '娱乐' => '娱乐',
            '两性' => '两性',
            '健身' => '健身',
            '时尚' => '时尚',
            '备孕' => '备孕',
            '饮食' => '饮食',
            '怀孕' => '怀孕',
            '育儿' => '育儿',
            '搞笑' => '搞笑',
            '星座' => '星座',
            '疾病' => '疾病',
            '用品' => '用品',
            '手工' => '手工',
            '二胎' => '二胎',
            '故事' => '故事',
            '早教' => '早教',
        ];
        $list = $this->m->getTags();
        foreach ($list['data'] as $k => $v) {
            if (!empty($v['third']) && $v['second'] != $v['third']) {
                $data[$v['cate']][$v['second']][$v['third']] = $v['third'];
            }
        }
        foreach ($list['data'] as $k => $v) {
            if (!isset($data[$v['cate']][$v['second']])) {
                $data[$v['cate']][$v['second']] = $v['second'];
            }
        }

        $data = $data0 + $data;

        $json = [];
        $i = 1;
        foreach ($data as $k => $v) {
            $json[1][1][] = [$i, $k];
            $j = 0;
            foreach ($v as $kk => $vv) {
                $i2 = $i * 500 + $j;
                $json[2][$i][] = [$i2, $kk];
                if (is_array($vv)) {
                    $n = 0;
                    foreach ($vv as $k3 => $v3) {
                        $json[3][$i2][] = [($i2 * 500 + $n), $k3];
                        $n++;
                    }
                }
                $j++;
            }
            $i++;
        }

        echo json_encode($json);
    }

    public function tagmap()
    {
        $list = $this->m->getTags(['fourth <>' => '']);
        foreach ($list['data'] as $k => $v) {
            if (!empty($v['fourth'])) {
                $data[$v['fourth']] = $v['third'];
            }
        }


        echo json_encode($data);
    }

    public function tagtop()
    {
        $pdo = DB::pdo(DB::DB_TEST_R);
        $list = $pdo->find('tagtop', ['top >=' => 20]);
        foreach ($list as $k => $v) {
            if (!empty($v['name'])) {
                $data[$v['name']] = $v['top'];
            }
        }
        echo json_encode($data);
    }

    public function test()
    {
        $data0['频道'] = [
            '情感' => '情感',
            '娱乐' => '娱乐',
            '两性' => '两性',
            '健身' => '健身',
            '时尚' => '时尚',
            '备孕' => '备孕',
            '饮食' => '饮食',
            '怀孕' => '怀孕',
            '育儿' => '育儿',
            '搞笑' => '搞笑',
            '星座' => '星座',
            '疾病' => '疾病',
            '用品' => '用品',
            '手工' => '手工',
            '二胎' => '二胎',
            '故事' => '故事',
            '早教' => '早教',
        ];
        $list = $this->m->getTags();
        foreach ($list['data'] as $k => $v) {
            if (!empty($v['third']) && $v['second'] != $v['third']) {
                $data[$v['cate']][$v['second']][$v['third']] = $v['third'];
            }
        }
        foreach ($list['data'] as $k => $v) {
            if (!isset($data[$v['cate']][$v['second']])) {
                $data[$v['cate']][$v['second']] = $v['second'];
            }
        }

        unset($data['待定']);
        //$other['频道'] = $data['频道'];
        unset($data['频道']);
        $data = $data0 + $data;

        $json = [];
        $i = 1;
        foreach ($data as $k => $v) {
            $json[1][1][] = [$i, $k];
            $j = 0;
            foreach ($v as $kk => $vv) {
                $i2 = $i * 500 + $j;
                $json[2][$i][] = [$i2, $kk];
                if (is_array($vv)) {
                    $n = 0;
                    foreach ($vv as $k3 => $v3) {
                        $json[3][$i2][] = [($i2 * 500 + $n), $k3];
                        $n++;
                    }
                }
                $j++;
            }
            $i++;
        }

        echo json_encode($json);
    }
}


