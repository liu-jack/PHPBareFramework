<?php
/**
 * QrCode.class.php
 * 二维码生成
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-7-10 下午2:37
 *
 */

namespace Classes\Image;

require_once LIB_PATH . 'phpqrcode/phpqrcode.php';

class QrCode
{
    private static $_instance = [];

    /**
     * 实例
     *
     * @param string $class
     * @return mixed|\QRcode
     */
    public static function instance($class = 'QRcode')
    {
        $class = '\\' . $class;
        if (empty(self::$_instance[$class])) {
            self::$_instance[$class] = new $class();
        }

        return self::$_instance[$class];
    }

    public static function subStr($str, $len)
    {

        $strlen = strlen($str);
        if ($strlen <= $len) {
            return $str;
        }
        $n = 0;
        $res = '';
        for ($i = 0; $i <= $strlen; $i++) {
            if (ord(substr($str, $i, 1)) > 0xa0) {
                $res .= substr($str, $i, 3);
                $i += 2;
                $n += 3;
            } else {
                $res .= substr($str, $i, 1);
                $n++;
            }
            if ($n >= $len) {
                break;
            }
        }

        return $res;
    }
}