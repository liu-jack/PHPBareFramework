<?php
/**
 * AppAction.class.php
 * Apps 控制器
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-5-11 下午4:14
 *
 */

namespace Bare\C;

class AppsAction
{
    /**
     * 登录用户ID
     *
     * @var int
     */
    protected $login_uid = 0;

    /**
     * 应用程序类
     *
     * @var \Bare\App
     * @access protected
     */
    protected $app;

    /**
     * 构造函数
     *
     * @param \Bare\App &$app 应用程序类
     */
    public function __construct(& $app)
    {
        $this->app = $app;
    }

    /**
     * 默认Action
     */
    public function doIndex()
    {
        //
    }

    /**
     * 统一输出
     *
     * @param mixed   $data  输出数据
     * @param string  $type  输出类型
     * @param boolean $exit  是否结束程序, true结束, false不结束
     * @param mixed   $extra 附加数据
     *
     * @return mixed
     */
    public static function output($data, $type = 'json', $exit = true, $extra = '')
    {
        switch ($type) {
            case 'debug':
                echo '<pre>';
                print_r($data);
                echo '</pre>';
                break;
            case 'xml':
                // echo $this->_toXml();
                break;
            case 'json':
            default:
                if (isset($_GET['callback'])) {
                    if (!headers_sent()) {
                        header("Cache-Control:maxage=1");
                        header("Content-type: text/javascript; charset=UTF-8");
                    }
                    $jsonp = $_GET['callback'];
                    echo $jsonp . '(' . json_encode($data) . ')';
                } else {
                    echo json_encode($data);
                }
        }

        if ($exit) {
            exit();
        }

        return true;
    }

    /**
     * 跨域输出接口
     *
     * @param string $func   回调函数名
     * @param array  $data   数据, 建议使用array
     * @param string $domain 请求发起域名
     */
    public static function crossOutput($func, $data, $domain = 'www.qbaoting.com')
    {
        $data = rawurlencode(json_encode($data));
        $time = time();

        if (!preg_match('/[a-z0-9_]+/i', $func)) {
            $func = 'errorfunc';
        }

        if (!preg_match('/[a-z0-9\.]+/i', $domain)) {
            $domain = 'www.qbaoting.com';
        }

        echo <<<EOT
        <!doctype html>
        <html>
        <head>
        <meta charset=\"utf-8\">
        <title>Crossdomain</title>
        </head>
        <body>
            <iframe src=\"http://{$domain}/proxy.html?callback={$func}&data={$data}&t={$time}\"></iframe>
        </body>
        </html>
EOT
        ;
        exit;
    }

    /**
     * 模板赋值
     *
     * @param      $name
     * @param null $value
     */
    public function value($name, $value = null)
    {
        $this->app->page()->value($name, $value);
    }

    /**
     * 加载模板文件
     *
     * @param string $template
     * @param bool   $fetch
     */
    public function view($template = '', $fetch = false)
    {
        $this->app->page()->output($template, $fetch);
    }

    /**
     * 加载模块
     *
     * @param $name
     */
    public function model($name)
    {
        $name = stripos($name, 'Model\\') === false ? 'Model\\' . $name : $name;
        $this->load($name);
    }

    /**
     * 加载类
     *
     * @param $name
     */
    public function load($name)
    {
        $name = '\\' . trim($name, '\\');
        $_val = trim(substr($name, (int)strrpos($name, '\\')), '\\');
        $this->$_val = new $name;
    }
}