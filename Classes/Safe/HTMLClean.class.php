<?php

/**
 * HTML安全过滤&清理
 */

namespace Classes\Safe;

require(LIB_PATH . 'HTMLPurifier/HTMLPurifier.standalone.php');

class HTMLClean
{
    // 默认配置
    const CONFIG_DEFAULT = 0;
    // 导入第三方文章
    const CONFIG_IMPORT_ARTICLE = 1;

    // 配置缓存路径
    private static $cache_dir = CACHE_PATH . 'HTMLPurifier';
    // 过滤器配置数据
    private static $config = [];
    // 过滤对象
    private static $purifier = [];

    /**
     * 过滤一段html
     *
     * @param string     $html   需要过滤的内容
     * @param int|object $config HTMLPurifier_Config生成的配置数据，也可以直接为配置常量
     *
     * @return string
     */
    public static function purify($html, $config = self::CONFIG_DEFAULT)
    {
        if (empty(self::$config[$config])) {
            self::$config[$config] = self::createConfig($config);
        }
        if (empty(self::$purifier[$config])) {
            self::$purifier[$config] = new \HTMLPurifier(self::$config[$config]);
        }

        return self::$purifier[$config]->purify($html, self::$config[$config]);
    }

    /**
     * 创建一个新的配置文件
     *
     * @param integer $mode 配置文件模式 使用常量
     * @return \HTMLPurifier_Config
     */
    public static function createConfig($mode = self::CONFIG_DEFAULT)
    {
        $config = \HTMLPurifier_Config::createDefault();
        // 设置缓存路径
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
        $config->set('Cache.SerializerPath', self::$cache_dir);

        switch ($mode) {
            case self::CONFIG_IMPORT_ARTICLE:
                $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
                $config->set('Attr.AllowedClasses', null);
                $config->set('Attr.DefaultImageAlt', '');
                $config->set('CSS.AllowedProperties', null);
                $config->set('HTML.Allowed',
                    'table[border|width],tbody,tr,td,th,img[src|alt|data-height|data-width],span,p,ul,ol,li,strong,em,sup,sub,b,div,section,blockquote,pre,small,article');
                $config->set('HTML.DefinitionID', 'html5-definitions');
                $config->set('HTML.DefinitionRev', 1);
                $config->set('AutoFormat.RemoveEmpty', true);
                if ($def = $config->maybeGetRawHTMLDefinition()) {
                    $def->addElement('section', 'Block', 'Flow', 'Common');
                    $def->addElement('article', 'Block', 'Flow', 'Common');
                    $def->addAttribute('img', 'data-height', 'Length');
                    $def->addAttribute('img', 'data-width', 'Length');
                }

                break;
            case self::CONFIG_DEFAULT:
            default:
                $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
                $config->set('Attr.AllowedClasses', []);
                $config->set('Attr.AllowedFrameTargets', ['_blank' => true, '_self' => true]);
                break;
        }

        return $config;
    }
}