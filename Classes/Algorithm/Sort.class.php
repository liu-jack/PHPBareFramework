<?php
/**
 * Sort.class.php
 * 排序
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-3-27 下午3:04
 *
 */

namespace Classes\Algorithm;


class Sort
{
    /**
     * 冒泡排序
     *
     * @param $arr
     * @return mixed
     */
    public static function bubble($arr)
    {
        $keys = array_keys($arr);
        $arr = array_values($arr);
        $len = count($arr);
        for ($i = 0; $i < $len; $i++) {
            for ($j = 0; $j < $len - $i; $j++) {
                if ($j + 1 < $len && $arr[$j] > $arr[$j + 1]) {
                    self::swap($arr, $j, $j + 1);
                    self::swap($keys, $j, $j + 1);
                }
            }
        }
        $data = [];
        foreach ($arr as $k => $v) {
            $data[$keys[$k]] = $v;
        }
        unset($keys, $arr);

        return $data;
    }

    /**
     * 选择排序
     *
     * @param $arr
     * @return array
     */
    public static function selection($arr)
    {
        $keys = array_keys($arr);
        $arr = array_values($arr);
        $len = count($arr);
        for ($i = 0; $i < $len; $i++) {
            for ($j = $i + 1; $j < $len; $j++) {
                if ($arr[$i] > $arr[$j]) {
                    self::swap($arr, $i, $j);
                    self::swap($keys, $i, $j);
                }
            }
        }
        $data = [];
        foreach ($arr as $k => $v) {
            $data[$keys[$k]] = $v;
        }
        unset($keys, $arr);

        return $data;
    }

    /**
     * 插入排序
     *
     * @param $arr
     * @return array
     */
    public static function insertion($arr)
    {
        $keys = array_keys($arr);
        $arr = array_values($arr);
        $len = count($arr);
        // 外循环(跳过第一个值，因为第一个直接放到最左侧)
        for ($outer = 1; $outer < $len; $outer++) {
            $temp = $arr[$outer];
            $temp_k = $keys[$outer];
            $inner = $outer;
            // 内循环，比左侧值小就移动让位
            while ($inner > 0 && ($arr[$inner - 1] >= $temp)) {
                $arr[$inner] = $arr[$inner - 1];
                $keys[$inner] = $keys[$inner - 1];
                $inner--;
            }
            $arr[$inner] = $temp;
            $keys[$inner] = $temp_k;
        }
        $data = [];
        foreach ($arr as $k => $v) {
            $data[$keys[$k]] = $v;
        }
        unset($keys, $arr);

        return $data;
    }

    /**
     * 希尔排序
     *
     * @param           $arr
     * @param array|int $gaps
     * @return array
     */
    public static function shell($arr, $gaps)
    {
        $keys = array_keys($arr);
        $arr = array_values($arr);
        $len = count($arr);
        if (is_numeric($gaps)) {
            $gaps = range($gaps, 1, -1);
        }
        $glen = count($gaps);
        // 遍历间隔
        for ($g = 0; $g < $glen; $g++) {
            // 当前间隔
            $currentGap = $gaps[$g];
            // 直接插入法外循环
            for ($outer = $currentGap; $outer < $len; $outer++) {
                $temp = $arr[$outer];
                $temp_k = $keys[$outer];
                $inner = $outer;
                // 直接插入法内循环（注意对比间隔值）
                while ($inner >= $currentGap && ($arr[$inner - $currentGap] >= $temp)) {
                    $arr[$inner] = $arr[$inner - $currentGap];
                    $keys[$inner] = $keys[$inner - $currentGap];
                    $inner = $inner - $currentGap;
                }
                $arr[$inner] = $temp;
                $keys[$inner] = $temp_k;
            }
        }
        $data = [];
        foreach ($arr as $k => $v) {
            $data[$keys[$k]] = $v;
        }
        unset($keys, $arr);

        return $data;
    }

    /**
     * 交换数组中两个值
     *
     * @param array $arr 数组
     * @param int   $i   下标i
     * @param int   $j   下标j
     */
    public static function swap(&$arr, $i, $j)
    {
        $temp = $arr[$i];
        $arr[$i] = $arr[$j];
        $arr[$j] = $temp;
    }
}