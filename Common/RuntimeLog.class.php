<?php
/**
 * RuntimeLog.class.php
 * 优化调试类
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-7-5 下午7:56
 *
 */

namespace Common;

class RuntimeLog
{
    const RUNTIME_DEBUG = true;
    // 计时段名称
    private static $name = 0;
    // 计时段数据
    private static $data = [];
    // 计时段运行时长数据
    private static $distance = [];
    // 实例
    private static $_instance = null;

    /**
     * @return RuntimeLog|null
     */
    public static function instance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new static();
        }

        return self::$_instance;
    }

    /**
     * 开始计时点
     *
     * @param string $name
     */
    public static function start($name = '')
    {
        self::instance()->log_start($name);
    }

    /**
     * 结束计时点
     *
     * @param string $name
     */
    public static function end($name = '')
    {
        self::instance()->log_end($name);
    }

    /**
     * 开始计时点
     *
     * @param string $name
     * @return bool|mixed
     */
    public function log_start($name = '')
    {
        if (!self::RUNTIME_DEBUG) {
            return false;
        }
        if (empty($name)) {
            $name = self::$name + 1;
        }
        self::$name = $name;

        return self::$data[self::$name]['start'] = microtime(true);
    }

    /**
     * 结束计时点
     *
     * @param string $name
     * @return bool|mixed
     */
    public function log_end($name = '')
    {
        if (!self::RUNTIME_DEBUG) {
            return false;
        }
        if (!empty($name)) {
            self::$name = $name;
        }

        return self::$data[self::$name]['end'] = microtime(true);
    }

    /**
     * 运行时间信息显示
     *
     * @return array|bool
     */
    public static function show_debug()
    {
        if (!self::RUNTIME_DEBUG) {
            return false;
        }
        foreach (self::$data as $k => $v) {
            self::$distance[$k] = sprintf('%.4f', $v['end'] - $v['start']);
        }
        logs(self::$distance, 'RuntimeLog/debug');

        return self::$distance;
    }

    /**
     * 析构函数输出运行信息
     */
    public function __destruct()
    {
        $this->show_debug();
    }
}