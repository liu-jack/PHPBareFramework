<?php
/**
 * DataFormat.class.php
 */

namespace Model\Tool;

/**
 * 时间数量等数据规则化
 *
 * @ignore
 * @package Model\Tool
 * @author  周剑锋 <camfee@foxmail.com>
 *
 */
class DataFormat
{

    const HOURTIME = 3600;

    const DAYTIME = 86400;

    const YEARTIME = 31536000;

    /**
     * 时间显示规则
     *
     * @param $datetime
     * @param $type
     * @return false|float|string
     */
    public static function datetime($datetime, $type = 0)
    {
        $stime = strtotime($datetime);
        $dtime = abs(time() - $stime);
        if ($dtime < self::DAYTIME) {
            if ($dtime < 60) {
                return '刚刚';
            } elseif ($dtime < self::HOURTIME) {
                return floor($dtime / 60) . '分钟前';
            } else {
                return floor($dtime / self::HOURTIME) . '小时前';
            }
        } else {
            if ($type == 0) {
                return date('Y-m-d', $stime);
            } else {
                return date('Y-m-d H:i', $stime);
            }
        }
    }

    /**
     * 视频长度格式化
     *
     * @param $second
     * @return string
     */
    public static function videoTime($second)
    {
        $num = intval($second);
        if ($num < 60) {
            return '00:' . sprintf('%02d', $num);
        } else {
            $min = floor($num / 60);
            $sec = $num % 60;

            return sprintf('%02d', $min) . ':' . sprintf('%02d', $sec);
        }
    }

    /**
     * 评论数格式化
     *
     * @param $num
     * @return int|string
     */
    public static function commentNum($num)
    {
        $num = intval($num);
        $ex1 = 10000;
        if ($num <= 0) {
            return '';
        } elseif ($num < $ex1) {
            return $num . '评论';
        } else {
            return round($num / $ex1, 1) . '万评论';
        }
    }

    /**
     * 浏览数格式化
     *
     * @param $num
     * @return int|string
     */
    public static function viewNum($num)
    {
        $num = intval($num);
        $ex1 = 10000;
        if ($num <= 0) {
            return '';
        } elseif ($num < $ex1) {
            return (string)$num;
        } else {
            return round($num / $ex1, 1) . '万';
        }
    }

    /**
     * 订阅数据格式化
     *
     * @param $num
     * @return int|string
     */
    public static function tagNum($num)
    {
        $num = intval($num);
        $ex1 = 10000;
        if ($num <= 0) {
            return '';
        } elseif ($num < $ex1) {
            return $num . '人订阅';
        } else {
            return round($num / $ex1, 1) . '万人订阅';
        }
    }
}