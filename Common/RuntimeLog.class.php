<?php
/**
 * RuntimeLog.class.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-7-5 下午7:56
 *
 */

namespace Common;

class RuntimeLog
{
    private static $name = '';

    private static $data = [];

    private static $distance = [];

    private static $_instance = null;

    public static function instance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new static();
        }

        return self::$_instance;
    }

    public static function start($name = '')
    {
        self::instance()->log_start($name);
    }

    public static function end($name = '')
    {
        self::instance()->log_end($name);
    }

    public function log_start($name = '')
    {
        if (empty($name)) {
            $name = uniqid();
        }
        self::$name = $name;
        self::$data[self::$name]['start'] = microtime(true);
    }

    public function log_end($name = '')
    {
        if (!empty($name)) {
            self::$name = $name;
        }
        self::$data[self::$name]['end'] = microtime(true);
    }

    public static function show_debug()
    {
        foreach (self::$data as $k => $v) {
            self::$distance[$k] = $v['end'] - $v['start'];
        }
        pre(self::$data);
        pre(self::$distance);
    }

    public function __destruct()
    {
        self::instance()->show_debug();
    }
}