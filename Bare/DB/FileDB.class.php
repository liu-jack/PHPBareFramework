<?php

namespace Bare\DB;

use Bare\Util\FileSystem;

class FileDB
{
    /**
     * @var string $cachePath 缓存文件目录
     * @access public
     */
    public $cachePath = CACHE_PATH;

    /**
     * 构造函数
     *
     * @param string $path 缓存文件目录
     */
    public function __construct($path = './')
    {
        ($path[strlen($path) - 1] != '/') && $path .= '/';
        (!empty($path)) && $this->cachePath .= $path;
        (!is_dir($this->cachePath)) && FileSystem::makeDir($this->cachePath);
    }

    /**
     * 在cache中设置键为$key的项的值，如果该项不存在，则新建一个项
     *
     * @param string $key    键值
     * @param mixed  $var    值
     * @param int    $expire 到期秒数, 0 无限期, 到期时间不得超过1年，超过按无限期算
     * @return boolean 如果成功则返回 true，失败则返回 false。
     * @access public
     */
    public function set($key, $var, $expire = 0)
    {
        $file = $this->makeFilename($key, 'set');
        $fp = fopen($file, 'w');
        if (gettype($expire) == 'string') {
            $expire = strtotime($expire);
        } elseif ($expire > 0 && $expire <= 31536000) {
            $expire = time() + $expire;
        } else {
            $expire = '0000000000';
        }
        $value = $expire . serialize($var);
        flock($fp, LOCK_EX);
        $result = fwrite($fp, $value);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $result;
    }

    /**
     * 在cache中获取键为$key的项的值
     *
     * @param string $key 键值
     * @return mixed 如果该项不存在，则返回 NULL
     * @access public
     */
    public function get($key)
    {
        $result = null;
        $file = $this->makeFilename($key, 'get');
        if (file_exists($file)) {
            $value = file_get_contents($file);
            $timeout = substr($value, 0, 10);
            if ($timeout == '0000000000' || time() <= $timeout) {
                $var = substr($value, 10);
                $var_unser = unserialize($var);
                if ($var_unser) {
                    $result = $var_unser;
                }
            }
        }

        return $result;
    }

    /**
     * 清空cache中所有项
     *
     * @return bool
     * @access public
     */
    public function flush()
    {
        $fileList = FileSystem::ls($this->cachePath, array(), 'ASC', true);
        FileSystem::rm($fileList);

        return true;
    }

    /**
     * 删除在cache中键为$key的项的值
     *
     * @param string $key 键值
     * @return bool
     * @access public
     */
    public function delete($key)
    {
        $file = $this->makeFilename($key, 'del');
        if (file_exists($file)) {
            FileSystem::rm($file);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取缓存文件路径及文件名
     *
     * @param string $key  键名
     * @param string $type 类型(get/set/del)
     * @return string
     */
    public function makeFilename($key, $type)
    {
        $pos = strrpos($key, '/');
        $path = $this->cachePath;
        if ($pos !== false) {
            $path .= substr($key, 0, $pos);
            $key = substr($key, $pos + 1);
        }
        $cache_file = $path . '/' . urlencode($key) . '.cache.php';
        if (!file_exists($cache_file) && $type == 'set') {
            (!is_dir($path)) && FileSystem::makeDir($path);
        }

        return $cache_file;
    }
}
