<?php
/**
 * 基类控制器
 *
 * @author camfee<camfee@yeah.net>
 * @since  v1.0 2016.09.12
 */

namespace Bare;

use Model\Account\User as AUser;
use Model\Passport\PassportApi;
use Model\Admin\Admin\AdminLogin;

Class Controller
{
    /**
     * Controller constructor.
     */
    public function __construct()
    {
        // 模型加载
        $model_path = MODEL_PATH . $GLOBALS['_MPATH'] . CEXT;
        if (file_exists($model_path)) {
            $model = '\\Model' . $GLOBALS['_NAMESPACE'];
            $this->_m = new $model;
        }
        //接口访问设置
        if (!defined('NO_SESSION') && !isset($_GET['_v'])) {
            session_start();
        }
        // 后端访问设置
        if ($GLOBALS['_M'] == 'Admin') {
            if ($GLOBALS['_C'] != 'Index') {
                if (!self::isLogin(2)) {
                    $this->alert('请先登录', url('admin/index/login'));
                } elseif (!AdminLogin::isHasAuth()) {
                    $this->alertErr('没有权限', url('admin/index/index'), '', 'top');
                }
            }
        }
    }

    /**
     * 数据模型
     */
    protected $_m = null;

    /**
     * 模板数据数组
     */
    private $_var = [];

    /**
     * 自动html模板加载函数
     *
     * @param string $path 模板的路径 默认为
     *                     ROOT_PATH/View/模块名(module)/控制器名(controller)/方法名(action)
     */
    public function view($path = '', $ext = VEXT)
    {
        if ($path) {
            $view_path = VIEW_PATH . $path . $ext;
        } else {
            $view_path = VIEW_PATH . $GLOBALS['_PATH'] . $ext;
        }
        $view_path = parseTemplate($view_path);
        extract($this->_var);
        include_once $view_path;
    }

    /**
     * 赋值到模板
     *
     * @param string $name 保存到前端模板的变量名
     * @param mixed  $data 要保存到前端模板的数据
     */
    public function value($name, $data)
    {
        $this->_var[$name] = $data;
    }

    /**
     * 接口数据输出
     *
     * @param int          $code 返回码 200：成功
     * @param array|string $data 接口输出的数据
     */
    public static function output($code = 200, $data = [])
    {
        $result['Code'] = $code;
        if (is_string($data)) {
            $result['Msg'] = $data;
        } else {
            $result['Data'] = $data;
        }
        if (isset($_GET['callback'])) {
            if (!headers_sent()) {
                header("Cache-Control:maxage=1");
                header("Content-type: text/javascript; charset=UTF-8");
            }
            $jsonp = $_GET['callback'];
            exit($jsonp . '(' . json_encode($data) . ')');
        } else {
            header('Content-type: application/json');
            exit(json_encode($result));
        }
    }

    /**
     * 登录状态验证
     *
     * @param int  $type 0:web/wap 1:api 2:admin
     * @param bool $auto 接口未登录是否退出程序
     * @return int
     */
    public static function isLogin($type = 0, $auto = false)
    {
        switch ($type) {
            case 0:  // 网站登录验证
                if (empty($_SESSION['UserId'])) {
                    if (!empty($_COOKIE['_auth'])) {
                        $uid = cookie_decode($_COOKIE['_auth']);
                        if (!empty($uid)) {
                            $_SESSION['UserId'] = intval($uid);
                        }
                    }
                }

                return !empty($_SESSION['UserId']) ? $_SESSION['UserId'] : 0;
                break;
            case 1:  // 接口登录验证
                $code = 551;
                $msg = '未登录, 请重新登录后再试!';
                $ssid = self::getAuthString();
                if (!empty($ssid)) {
                    ini_set('session.use_cookies', 0);
                    session_id($ssid);
                    session_start();
                    if (empty($_SESSION['uid'])) {
                        $decode = PassportApi::decode($ssid);
                        if (!empty($decode['uid']) && is_numeric($decode['uid'])) {
                            $_SESSION['uid'] = $decode['uid'];
                            $_SESSION['login_count'] = $decode['login_count'];
                            goto succ;
                        }
                    } else {
                        goto succ;
                    }
                }

                fail:
                unset($_SESSION['uid']);
                if ($auto) {
                    self::output($code, $msg);
                } else {
                    return false;
                }

                succ:
                $userinfo = AUser::getUserById($_SESSION['uid']);
                if (empty($userinfo['UserId'])) {
                    goto fail;
                }
                if ($userinfo['Status'] == 0) {
                    $code = 554;
                    $msg = '此用户不存在或已被禁止访问, 请与客服联系!';
                    goto fail;
                }
                if ($userinfo['LoginCount'] - $_SESSION['login_count'] != 0) {
                    $code = 552;
                    $msg = '登录已经失效, 请重新登录';
                    goto fail;
                }

                return $_SESSION['uid'];
                break;
            case 2: // 网站后台登录验证
                return AdminLogin::isLogin();
                break;
            default:
                return 0;
        }
    }

    /**
     * 获取登陆认证字符串
     *
     * @return string
     */
    public static function getAuthString()
    {
        return trim($_SERVER['HTTP_AUTH']);
    }

    /**
     * 全局提示函数
     *
     * @param string $msg     消息
     * @param array  $options 都是可选参数
     *                        url     确定后跳转URL或失败返回的URL,不设置将返回上一页
     *                        desc    详细描述
     *                        to      top或者self，默认 self url target
     *                        target  top或者self，默认 top alert target
     *                        type    消息类型：0：失败；1：成功,默认成功
     *                        button  按钮显示的文字，默认：确定
     * @return void
     * */
    public function alertMsg($msg, $options = [])
    {
        $opt = [
            'url' => '',
            'desc' => '',
            'to' => 'self',
            'target' => 'top',
            'type' => 'success',
            'button' => '确定',
        ];
        $opt = array_merge($opt, $options);

        $this->value('msg', $msg);
        $this->value('url', $opt['url']);
        $this->value('to', $opt['to']);
        $this->value('type', $opt['type']);
        $this->value('desc', $opt['desc']);
        $this->value('target', $opt['target']);
        $this->value('button', $opt['button']);
        $this->view('Public/msg');
        exit();
    }

    /**
     * 出错提示弹窗
     *
     * @param        $msg
     * @param string $url
     * @param string $desc
     * @param string $to
     */
    public function alertErr($msg, $url = '', $desc = '', $to = 'self')
    {
        $opt = [
            'url' => $url,
            'desc' => $desc,
            'to' => $to,
            'target' => 'top',
            'type' => 'error',
            'button' => '确定',
        ];
        $this->alertMsg($msg, $opt);
    }

    /**
     * 成功提示弹窗
     *
     * @param        $msg
     * @param string $url
     * @param string $desc
     * @param string $to
     */
    public function alert($msg, $url = '', $desc = '', $to = 'self')
    {
        $opt = [
            'url' => $url,
            'desc' => $desc,
            'to' => $to,
            'target' => 'top',
            'type' => 'success',
            'button' => '确定',
        ];
        $this->alertMsg($msg, $opt);
    }

    /**
     * 分页函数，返回html
     *
     * @param int  $count  总数量
     * @param int  $per    每页数量
     * @param int  $now    当前页数
     * @param bool $return 是否返回
     * @return mixed
     */
    public function pagination($count, $per, $now, $return = false)
    {
        $page_key = defined('PAGE_VAR') ? PAGE_VAR : 'p'; //分页传值的key
        $_GET[$page_key] = '%d';
        $urls = url($GLOBALS['_URL'], $_GET);
        unset($_GET[$page_key]);
        $burl = url($GLOBALS['_URL'], $_GET);
        $now = max(1, $now);
        $pages = intval(ceil($count / $per));
        $min = 1;
        $max = min(10, $pages);
        if ($pages > 10 && $now > 5) {
            $min = $now - 5;
            $max = $now + 5;
        }
        $max = min($max, $pages);

        $html = '<li><a href="' . sprintf($urls, 1) . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
        $url = sprintf($urls, 1);
        if ($max == $pages && $min > 1) {
            $html .= '<li><a href="' . $url . '">1</a></li><li><a><input type="text" class="form-control" onkeydown="if(event.keyCode==13){var __pagesize=this.value;var url=\'' . $burl . '?' . $page_key . '=\'+__pagesize+\')\';location.href=url}"></a></li>';
        }
        for ($n = $min; $n <= $max; $n++) {
            $url = sprintf($urls, $n);
            if ($n == $now) {
                $html .= '<li class="active"><span>' . $n . '</span></li>';
            } else {
                $html .= '<li><a href="' . $url . '">' . $n . '</a></li>';
            }
        }

        $url = sprintf($urls, $pages);
        if ($max < $pages) {
            $html .= '<li><a><input type="text" class="form-control" onkeydown="if(event.keyCode==13){var __pagesize=this.value;var url=\'' . $burl . '?' . $page_key . '=\'+__pagesize+\')\';location.href=url}"></a></li><li><a href="' . $url . '">' . $pages . '</a></li>';
        }
        $html .= '<li> <a href="' . $url . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
        if ($pages == 1) {
            $html = '';
        }
        if ($return) {
            return $html;
        }
        $pages_total = '';
        if ($count > 0) {
            $pages_total = "<span>一共 {$count} 条, 共 {$pages} 页</span>";
        }
        $this->value('pages_total', $pages_total);
        $this->value('pages', $html);
    }

    /**
     * 记录错误的调用方式
     *
     * @param string $method 方法
     * @param array  $args   参数
     * @return void
     */
    public function __call($method, $args)
    {
        if (isset($_GET['_v'])) {
            logs([
                'API Error',
                "API Class Name:{$args[0]}",
                "API Function Name:{$method}",
                'DATE:' . date("Y-m-d H:i:s"),
                "GET:" . json_encode($_GET),
                "POST:" . json_encode($_POST),
            ], 'Api/CallFailed');
        }
        $this->output(501, '调用方法不存在');
    }
}
