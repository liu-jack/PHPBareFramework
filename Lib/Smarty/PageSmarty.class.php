<?php
/**
 * 应用Smarty的Page类
 *
 * @author  camefe
 *
 */

namespace Smarty;

include __DIR__ . '/Smarty.class.php';

class PageSmarty
{
    /**
     * 页面参数,如title等
     *
     * @var array
     */
    public $params = [];

    /**
     * 应用程序类
     *
     * @var \Bare\App
     * @access protected
     */
    public $app;

    /**
     * Smarty 对象
     *
     * @var object Smarty
     * @access private
     */
    private $smarty;

    /**
     * 构造函数
     *
     * @param \Bare\App $app
     */
    public function __construct($app)
    {
        $this->app = &$app;
        $this->smarty = new \Smarty;
        // 设置模板目录
        if (isset($app->cfg['smarty']['template_dir'])) {
            $this->smarty->setTemplateDir($app->cfg['smarty']['template_dir']);
        }
        // 设置编译文件目录
        if (isset($app->cfg['smarty']['compile_dir'])) {
            $this->smarty->setCompileDir($app->cfg['smarty']['compile_dir']);
        }
        // 设置插件目录
        $this->smarty->setPluginsDir(SMARTY_DIR . 'plugins/');
        // 赋值引用
        $this->smarty->assignByRef('cfg', $app->cfg);
        // 注册插件
        try {
            $this->smarty->registerPlugin('modifier', 'head', 'head');
            $this->smarty->registerPlugin('modifier', 'html', '_htmlspecialchars');
            $this->smarty->registerPlugin('modifier', 'format_date', 'format_date');
        } catch (\Exception $e) {
            exit($e->getMessage());
        }

        // 设置默认的图片、样式、js、flash的模版路径
        $this->value('url_public', $app->cfg['url']['public']);
        $this->value('url_statics', $app->cfg['url']['statics']);
        $this->value('url_images', $app->cfg['url']['images']);
        $this->value('url_css', $app->cfg['url']['css']);
        $this->value('url_js', $app->cfg['url']['js']);
        $this->value('url_swf', $app->cfg['url']['swf']);
        // 设置站点名称
        $this->value('site_title', $app->cfg['site']['title']);
    }

    /**
     * 创建一个页面类
     *
     * @param \Bare\App $app 应用程序类
     * @return PageSmarty
     */
    public static function create(&$app)
    {
        return new static($app);
    }

    /**
     * 给页面变量赋值
     *
     * @param string $name  变量名,如果参数类型为数组,则为变量赋值,此时$value参数无效
     * @param mixed  $value 变量值,如果该参数未指定,则返回变量值,否则设置变量值
     * @return PageSmarty 如果参数为NULL则返回Page对象本身,否则返回变量值
     */
    public function value($name, $value = null)
    {
        // 取值
        if ($value === null && !is_array($name)) {
            return $this->smarty->getTemplateVars($name);
        } else { //赋值
            //如果是数组则批量变量赋值
            if (is_array($name)) {
                foreach ($name as $k => $v) {
                    $this->smarty->assign($k, $v);
                }
            } else {
                $this->smarty->assign($name, $value);
            }

            return $this;
        }
    }

    /**
     * 页面内容输出
     *
     * @param string  $template 指定输出的模板
     * @param boolean $fetch    是否取回
     * @return mixed  是否提取输出结果
     */
    public function output($template = '', $fetch = false)
    {
        if ($template) {
            $this->params['template'] = strpos($template, '.') === false ? $template . '.html' : $template;
        } else {
            if (!isset($this->params['template'])) {
                $path_len = strlen($this->app->cfg['path']['root']);
                $offsetPath = substr($this->app->cfg['path']['current'], $path_len);
                ($offsetPath{0} == '/') && $offsetPath = substr($offsetPath, 1);
                $this->params['template'] = $offsetPath . $this->app->name . '.html';
            }
        }
        try {
            if ($fetch) {
                return $this->smarty->fetch($this->params['template']);
            }
            $this->smarty->display($this->params['template']);
        } catch (\Exception $e) {
            exit($e->getMessage());
        }

        return null;
    }

    /**
     * 返回smarty对象，供使用smarty其他功能
     */
    public function getSmarty()
    {
        return $this->smarty;
    }

    /**
     * 分页函数，输出全部html代码
     *
     * @param int    $page_total   总页数
     * @param int    $page_current 当前页
     * @param string $page_alias   分页参数别名 如：index.php?${page_alias}=${d}
     * @param string $url          地址，如：index.php?page=%d 默认为当前页，page=%d
     * @param int    $page_num     当前页左右边显示页数
     * @return string               html
     */
    public function getPageStr($page_total, $page_current, $page_alias = 'p', $url = '', $page_num = 4)
    {
        $page_current = intval($page_current);
        $page_current = $page_current < 1 ? 1 : $page_current;
        if ($page_total < 2) {
            return '';
        }

        $url = trim($url, '&?');
        $no_params = empty($url);

        // 默认url地址处理
        $params = $no_params ? '?' : "?{$url}&";

        // 第一页的链接
        $first_page_url = $no_params ? "{$params}{$page_alias}=1" : rtrim($params, '&');

        $html = '';
        // 当前选中页大于第一页，出现上一页
        if ($page_current > 1) {
            $page_pre = $page_current - 1;
            $pre_html = ($page_num < 3) ? '前页' : '上一页';
            $html .= '<span class="pre"><a href="' . ($page_pre > 1 ? "{$params}{$page_alias}={$page_pre}" : $first_page_url) . "\"><span class=\"pageNext\"></span>{$pre_html}</a></span>";
        }

        // 第一页
        $html .= ($page_current == 1) ? '<span class="cur">1</span>' : "<a href=\"{$first_page_url}\">1</a>";
        // 当前选中页前的页数大于$page_num(默认为4)时，第一页后出现 ...
        if ($page_current - 2 >= $page_num) {
            $html .= '<span style="margin-right:4px;">...</span>';
        }

        // 循环列出页数除第一页和最后一页的其他当前选中页的前$page_num(默认为4)页和后$page_num(默认为4)页
        for ($i = $page_current - $page_num; $i <= $page_current + $page_num; $i++) {
            // 不显示第一页、最后页、不存在页
            if ($i > 1 && $i < $page_total) {
                $html .= ($i == $page_current) ? "<span class=\"cur\">{$i}</span>" : "<a href=\"{$params}{$page_alias}={$i}\">{$i}</a>";
            }
        }

        // 当前选中页后页数大于$page_num(默认为4)时，最后一页前出现 ...
        if ($page_current < $page_total - $page_num) {
            $html .= '<span style="margin-right:4px;">...</span>';
        }

        // 最后一页
        $html .= ($page_current == $page_total) ? "<span class=\"cur\">{$page_total}</span>" : "<a href=\"{$params}{$page_alias}={$page_total}\">{$page_total}</a>";

        // 当前页小于总页数，出现下一页
        if ($page_current < $page_total) {
            $page_next = $page_current + 1;
            $next_html = ($page_num < 3) ? '后页' : '下一页';
            $html .= "<span class=\"next\"><a href=\"{$params}{$page_alias}={$page_next}\">{$next_html}<span class=\"pagePre\"></span></a></span>";
        }

        return $html;
    }

    /**
     * 分页函数，输出全部html代码
     *
     * @param integer $page_max         总页数
     * @param integer $page_dango       当前页
     * @param string  $page_dango_class a标签样式
     * @param string  $page_attr_id     a标签id值
     * @param string  $page_pre_class   下一页样式
     * @param integer $page_num         中间页最大显示数
     * @param boolean $limit_flag       有最大页数限制
     *
     * @return string
     */
    public function getPageAjax(
        $page_max,
        $page_dango,
        $page_dango_class,
        $page_attr_id,
        $page_pre_class = 'next',
        $page_num = 4,
        $limit_flag = true
    ) {
        if ($page_max < 2) {
            return "";
        }
        if ($page_dango < 1) {
            $page_dango = 1;
        }
        if ($page_max > 100 && $limit_flag) {
            $page_max = 100;
        }

        if ($page_dango > $page_max) {
            $page_dango = $page_max;
        }

        $elide = "<span style='margin-right:4px;'>...</span>";
        $html = "";
        //当前选中页大于第一页，显示上一页
        if ($page_dango > 1) {
            $html .= "<span class='" . $page_pre_class . "'><a href='javaScript:;' page='" . ($page_dango - 1) . "' id='" . $page_attr_id . "' class='" . $page_pre_class . "'>上一页</a></span>";
        }
        //第一页
        if ($page_dango == 1) {
            $html .= "<a href='javaScript:;' page='1' id='" . $page_attr_id . "' class='" . $page_dango_class . "'>1</a>";
        } else {
            $html .= "<a href='javaScript:;' page='1' id='" . $page_attr_id . "'>1</a>";
        }
        //当前选中页前的页数大于$page_num(默认为4)，第一页后出现...
        if ($page_dango - $page_num > 2) {
            $html .= $elide;
        }
        //循环显示除第一页、最终页的当前选中页的前$page_num(默认为4)页和后$page_num(默认为4)页
        for ($i = $page_dango - $page_num; $i <= $page_dango + $page_num; $i++) {
            if ($i > 1 && $i < $page_max) {
                if ($i == $page_dango) {
                    $html .= "<a href='javaScript:;' page='" . $i . "' id='" . $page_attr_id . "' class='" . $page_dango_class . "'>" . $i . "</a>";
                } else {
                    $html .= "<a href='javaScript:;' page='" . $i . "' id='" . $page_attr_id . "'>" . $i . "</a>";
                }
            }
        }
        //当前选中页后页数大于$page_num(默认为4)页，最终页前出现...
        if ($page_dango + 1 < $page_max - $page_num) {
            $html .= $elide;
        }
        //最终页
        if ($page_dango == $page_max) {
            $html .= "<a href='javaScript:;' page='" . $page_max . "' id='" . $page_attr_id . "' class='" . $page_dango_class . "'>" . $page_max . "</a>";
        } else {
            $html .= "<a href='javaScript:;' page='" . $page_max . "' id='" . $page_attr_id . "'>" . $page_max . "</a>";
        }
        //当前选中页小于最终页，显示下一页
        if ($page_dango < $page_max) {
            $html .= "<span class='" . $page_pre_class . "'><a href='javaScript:;' page='" . ($page_dango + 1) . "' id='" . $page_attr_id . "' class='" . $page_pre_class . "'>下一页</a></span>";
        }

        //返回分页
        return $html;
    }
}
