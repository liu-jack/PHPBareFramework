<?php
/**
 * Math.class.php
 * 数学
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-3-3 下午3:34
 *
 */

namespace Classes\Algorithm;


class Math
{
    /**
     * 一元一次 ax + b = 0
     *
     * @param int $a
     * @param int $b
     * @return bool|float|int
     */
    public static function x1p1($a = 1, $b = 0)
    {
        return $a == 0 ? false : -$b / $a;
    }

    /**
     * 一元二次 a * pow(x,2) + bx +c = 0
     *
     * @param int $a
     * @param int $b
     * @param int $c
     * @return array|bool
     */
    public static function x1p2($a = 1, $b = 2, $c = 1)
    {
        $d = (pow($b, 2) - 4 * $a * $c);
        if ($a == 0 || $d < 0) {
            return false;
        }
        $x1 = -$b - sqrt($d);
        $x2 = -$b + sqrt($d);

        return ['x1' => $x1, 'x2' => $x2];
    }
}