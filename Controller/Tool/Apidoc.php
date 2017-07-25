<?php
/**
 * User: camfee
 * Date: 2017/3/30
 * Time: 10:44
 */

namespace Controller\Tool;

use Bare\Controller;

/**
 * API文档
 */
class Apidoc extends Controller
{
    /**
     *  API文档首页
     */
    public function index()
    {
        $api_path = CONTROLLER_PATH . API_PATH;
        $dirs = scandir($api_path);
        foreach ($dirs as $k => $v) {
            if ($v == '.' || $v == '..') {
                unset($dirs[$k]);
            }
        }
        $this->value('dirs', $dirs);
        $this->value('seo_title', '模块列表');
        $this->view();
    }

    /**
     * 类文件列表
     */
    public function lists()
    {
        $dir = $_GET['module'];
        $dir_path = CONTROLLER_PATH . API_PATH . '/' . $dir;
        $files = scandir($dir_path);
        foreach ($files as $k => $v) {
            if ($v == '.' || $v == '..') {
                unset($files[$k]);
            } else {
                $files[$k] = substr($v, 0, strpos($v, '.'));
            }
        }
        $this->value('files', $files);
        $this->value('seo_title', '类列表');
        $this->view();
    }

    /**
     * 接口列表
     */
    public function methods()
    {
        $dir = $_GET['module'];
        $file = $_GET['class'];
        $file_path = CONTROLLER_PATH . API_PATH . '/' . $dir . '/' . $file . '.php';

        $file_data = self::_makeFile($file_path); //通过原来的类文件生成新的类文件
        include_once($file_data['file_name']); //包含文件
        $methods = self::_getMethodData($file_data['class_name']); //通过类名获取方面数据
        $class_document = $methods['class_document'];
        unset($methods['class_document']);
        $class_desc = $class_document['desc'];
        $class_docs = '';
        if (!empty($class_document['author'])) {
            $class_docs .= 'Author: ' . htmlspecialchars($class_document['author']) . ' &nbsp; ';
        }
        if (!empty($class_document['date'])) {
            $class_docs .= 'Date: ' . htmlspecialchars($class_document['date']) . ' &nbsp; ';
        }
        if (!empty($class_document['deprecated'])) {
            $class_docs .= 'Deprecated: ' . htmlspecialchars($class_document['deprecated']) . ' &nbsp; ';
        }

        $this->value('class_desc', $class_desc);
        $this->value('class_docs', $class_docs);
        $this->value('deprecated', $class_document['deprecated']);
        $this->value('module', $dir);
        $this->value('class', $file);
        $this->value('methods', $methods);
        $this->value('seo_title', '接口列表');
        $this->view();
    }

    /**
     * 接口详情
     */
    public function info()
    {
        $dir = $_GET['module'];
        $class = $_GET['class'];
        $method = $_GET['method'];
        $file_path = CONTROLLER_PATH . API_PATH . '/' . $dir . '/' . $class . '.php';
        include_once($file_path);
        //获取返回结果
        $r_method = new \ReflectionMethod('\\Controller\\' . API_PATH . '\\' . $dir . '\\' . $class, $method);
        $doc_comment = $r_method->getDocComment();
        //获取接口参数
        $sp = '/*/';
        $params = $returns = [];
        preg_match('#@author(.*)\s*\*#isU', $doc_comment, $out);
        $author = trim($out[1], " \t\n\r\0\x0B*");
        preg_match('#@date(.*)\s*\*#isU', $doc_comment, $out);
        $date = trim($out[1], " \t\n\r\0\x0B*");
        preg_match('#@deprecated(.*)\s*\*#isU', $doc_comment, $out);
        $deprecated = trim($out[1], " \t\n\r\0\x0B*");
        preg_match('#/\*\*[\s\* ]*\*(.*)[\s\* ]*<pre>#isU', $doc_comment, $out);
        $description = trim($out[1], " \t\n\r\0\x0B*");
        preg_match('#@return(.*)\s*\*#isU', $doc_comment, $out);
        $return = preg_replace('@ @', $sp, trim($out[1], " \t\n\r\0\x0B*"), 1);
        $returns = explode($sp, $return, 2);
        preg_match('#[\s\* ]*<pre>(.*)</pre>[\s\*]*@#isU', $doc_comment, $out);
        $param = trim($out[1], " \t\n\r\0\x0B*");
        $param = explode("\n", $param);
        $http_type = strtoupper(trim($param[0], " \t\n\r\0\x0B:："));
        unset($param[0]);
        $pattern = ['@:@', '@,@'];
        foreach ($param as $v) {
            $v = str_replace(['，', '：'], [', ', ': '], $v);
            $v = preg_replace($pattern, $sp, trim($v, " \t\n\r\0\x0B*"), 1);
            $temp = explode($sp, $v);
            if (count($temp) >= 3) {
                $params[] = [
                    'name' => trim($temp[0]),
                    'require' => trim($temp[1]),
                    'desc' => trim($temp[2])
                ];
            } else {
                $params[] = str_replace($sp, ',', $v);
            }
        }
        preg_match('#@return.*[\s\* ]*(<pre>.*</pre>)[\s\* ]*#isU', $doc_comment, $out);
        $return = str_replace('*', '', trim($out[1], " \t\n\r\0\x0B*"));
        $tips = '';
        if (!empty($author)) {
            $tips .= 'Author: ' . htmlspecialchars($author) . ' &nbsp; ';
        }
        if (!empty($date)) {
            $tips .= 'Date: ' . htmlspecialchars($date) . ' &nbsp; ';
        }
        if (!empty($deprecated)) {
            $tips .= 'Deprecated: ' . htmlspecialchars($deprecated) . ' &nbsp; ';
        }

        $this->value('tips', $tips);
        $this->value('deprecated', $deprecated);
        $this->value('description', $description);
        $this->value('params', $params);
        $this->value('returns', $returns);
        $this->value('return', $return);
        $this->value('module', $dir);
        $this->value('class', $class);
        $this->value('method', $method);
        $this->value('http_type', $http_type);
        $this->value('seo_title', '接口详情');
        $this->view();
    }

    /**
     * 生成文件
     * @param string $path 文件名
     * @return array
     */
    private static function _makeFile($path = '')
    {
        //步骤:分析原来的类文件,将继承去掉并获取类名
        $php_content = file_get_contents($path);
        if (empty($php_content)) {
            die('empty:' . $path);
        }
        if (stristr($php_content, 'extends')) {
            //表示存在 继承 关系
            $start = 'extends';
            $end = '({|\n{)';
            $pattern = "/" . $start . ".*" . $end . "/";
            preg_match_all($pattern, $php_content, $matches);
            $php_content = str_replace($matches[0], '{', $php_content);
        }
        if (stristr($php_content, 'namespace')) {
            //命名空间
            $php_content = preg_replace('@namespace\s*([^;]+);@isU', '', $php_content);
        }
        //获取类名
        $class_name = str_replace('.php', '', basename($path));
        //生成新文件
        $new_file_name = str_replace(CONTROLLER_PATH, CACHE_PATH, $path);
        if (!is_dir(dirname($new_file_name))) {
            mkdir(dirname($new_file_name), 0755, true);
        }
        if (file_exists($new_file_name)) {
            $php_content_old = file_get_contents($new_file_name);
            if (strcmp($php_content, $php_content_old) !== 0) {
                unlink($new_file_name) or die ('删除文件:' . $new_file_name . '失败');
                file_put_contents($new_file_name, $php_content) or die ('写入文件:' . $new_file_name . '失败');
            }
        } else {
            file_put_contents($new_file_name, $php_content) or die ('写入文件:' . $new_file_name . '失败');
        }

        return [
            'file_name' => $new_file_name,
            'class_name' => $class_name
        ];
    }

    /**
     * 获取类中的方法数据
     * @param string $class 类名
     * @return mixed
     */
    private static function _getMethodData($class = '')
    {

        $arr_api = [];
        $r_class = new \ReflectionClass($class);
        $doc_class = $r_class->getDocComment();
        $class_desc = $class_author = $class_date = $class_deprecated = '';
        preg_match('#/\*\*[\s\* ]*(.*)[\s\* ]*(@|\*/)#isU', $doc_class, $out);
        $class_desc = trim($out[1], " \t\n\r\0\x0B*");
        preg_match('#@author(.*)\s*\*#isU', $doc_class, $out);
        $class_author = trim($out[1], " \t\n\r\0\x0B*");
        preg_match('#@date(.*)\s*\*#isU', $doc_class, $out);
        $class_date = trim($out[1], " \t\n\r\0\x0B*");
        preg_match('#@deprecated(.*)\s*\*#isU', $doc_class, $out);
        $class_deprecated = trim($out[1], " \t\n\r\0\x0B*");
        $arr_api['class_document'] = [];
        if (!empty($class_desc) || !empty($class_author) || !empty($class_date) || !empty($class_deprecated)) {
            $arr_api['class_document'] = [
                'desc' => $class_desc,
                'author' => $class_author,
                'date' => $class_date,
                'deprecated' => $class_deprecated,
            ];
        }

        $method = get_class_methods($class);
        if (!empty($method)) {
            foreach ($method as $m_value) {
                $r_method = new \Reflectionmethod($class, $m_value);
                $desc = '';
                $doc_comment = $r_method->getDocComment(); //获取注释
                if ($doc_comment !== false) {
                    preg_match('#/\*\*[\s\* ]*\*(.*)[\s\* ]*<pre>#isU', $doc_comment, $out);
                    $desc = trim($out[1], " \t\n\r\0\x0B*");
                }

                $arr_api[$m_value] = [
                    'method' => $m_value,
                    'desc' => $desc,
                ];
            }
        }
        return $arr_api;
    }

    /**
     * php注释替换js
     */
    public function phpdoc()
    {
        $this->view();
    }
}