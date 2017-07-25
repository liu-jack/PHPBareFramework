<?php
/**
 * 基类控制器
 * @author camfee<camfee@yeah.net>
 * @since v1.0 2016.09.12
 */

namespace Bare;

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
            $this->m = new $model;
        }
        //接口访问设置
        if (!defined('NO_SESSION') && !isset($_GET['_v'])) {
            session_start();
        }
        // 后端访问设置
        if ($GLOBALS['_M'] == 'Admin') {
            if ($GLOBALS['_C'] != 'Index') {
                if (!$this->isLogin(2)) {
                    redirect(url('admin/index/index'));
                }
            }
        }
    }

    /**
     * 数据模型
     */
    protected $m = '';

    /**
     * 模板数据数组
     */
    private $_var = [];

    /**
     * 自动html模板加载函数
     * @param string $path 模板的路径 默认为
     *      ROOT_PATH/View/模块名(module)/控制器名(controller)/方法名(action)
     */
    public function view($path = '', $ext = '.html')
    {
        extract($this->_var);
        if ($path) {
            include(VIEW_PATH . $path . $ext);
        } else {
            include(VIEW_PATH . $GLOBALS['_PATH'] . $ext);
        }
    }

    /**
     * 接口数据输出
     * @param int $code 返回码 200：成功
     * @param array|string $data 接口输出的数据
     */
    public function output($code = 200, $data = [])
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
     * 赋值到模板
     * @param string $name 保存到前端模板的变量名
     * @param mixed $data 要保存到前端模板的数据
     */
    public function value($name, $data)
    {
        $this->_var[$name] = $data;
    }

    /**
     * 登录状态验证
     * @param int $type 0:web/wap 1:api 2:admin
     * @param bool $auto 接口未登录是否退出程序
     * @return int
     */
    public function isLogin($type = 0, $auto = false)
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
                        $decode = \Model\Passport\PassportApi::decode($ssid);
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
                    $this->output($code, $msg);
                } else {
                    return false;
                }

                succ:
                $userinfo = \Model\Account\User::getUserById($_SESSION['uid']);
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
                if (empty($_SESSION['AdminUserId'])) {
                    if (!empty($_COOKIE['_admin_auth'])) {
                        $uid = cookie_decode($_COOKIE['_admin_auth']);
                        if (!empty($uid)) {
                            $_SESSION['AdminUserId'] = intval($uid);
                        }
                    }
                }
                return !empty($_SESSION['AdminUserId']) ? $_SESSION['AdminUserId'] : 0;
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
     * 记录错误的调用方式
     *
     * @param string $method 方法
     * @param array $args 参数
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
