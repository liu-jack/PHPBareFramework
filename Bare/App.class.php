<?php

/**
 * 应用程序
 *
 * @author camfee
 *
 * $Id$
 */

namespace Bare;

use Smarty\PageSmarty;

class App
{
    /**
     * 配置信息
     *
     * @var array
     * @access public
     */
    public $cfg;

    /**
     * 应用程序的名字
     *
     * @var string
     * @access public
     */
    public $name;

    /**
     * 模块名称
     *
     * @var string
     * @access public
     */
    public $module;

    /**
     * 动作名称,用于确定调用模块类中的哪个 do . $action 方法, 默认为 Default
     *
     * @var string
     * @access public
     */
    public $action;

    /**
     * application的对象池
     *
     * @var array
     * @access protected
     */
    protected $pool = [];

    /**
     * 构造函数
     *
     * @param string $name 应用程序名称
     */
    public function __construct($name = null)
    {
        global $cfg;
        $this->cfg = &$cfg;
        if ($name === null) {
            $name = basename($_SERVER['SCRIPT_FILENAME'], '.php');
            $p = strpos($name, '.');
            if ($p !== false) {
                $name = substr($name, 0, $p);
            }
        }

        $this->name = $name;
        $this->action = isset($_GET['do']) ? ucfirst($_GET['do']) : 'Index';
        $this->module = $this->action == 'Index' ? '' : '_' . strtolower($this->action);
    }

    /**
     * 运行应用程序
     *
     * @param string $do 动作名称
     *
     * @return App
     */
    public function run($do = null)
    {
        $do = $do ?: $this->action;
        if (class_exists($this->name)) {
            $module = new $this->name($this);
            $action = 'do' . $do;
            if (method_exists($module, $action)) {
                $module->$action();
            } else {
                show404();
            }
        } else {
            exit("应用程序运行出错.文件 {$_SERVER['SCRIPT_FILENAME']} 中找不到类定义:{$this->name}(1002)");
        }

        return $this;
    }

    /**
     * 返回application的page对象
     *
     * @param string $engine Page引擎, 默认按application.cfg.php中的$cfg['page']['engine']设置
     * @return PageSmarty
     */
    public function page($engine = null)
    {
        if (!isset($this->pool['smarty'])) {
            $this->pool['smarty'] = PageSmarty::create($this);
        }

        return $this->pool['smarty'];
    }
}
