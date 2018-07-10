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
}