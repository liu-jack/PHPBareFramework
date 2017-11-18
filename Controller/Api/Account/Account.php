<?php
/**
 * Account.class.php
 *
 */

namespace Controller\Api\Account;

use Bare\Controller;
use Model\Account\User as AUser;


/**
 * 用户 - 用户账号相关
 *
 * @package Account
 * @author  camfee <camfee@foxmail.com>
 * @since   1.0.0 2017-10-19
 */
class Account extends Controller
{
    /**
     * 账号信息
     *
     * <pre>
     *  GET
     * </pre>
     *
     * @return string|void json
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *         "Mobile": "18373128832",     //手机号码
     *         "Password": 0             //int 类型  是否设置密码，设置则为1否则为 0
     *     }
     * }
     * </pre>
     */
    public function accountInfo(){
        $uid = $this->isLogin(true);

        $userInfo = AUser::getUserById($uid);
        $result['Mobile'] = $userInfo['Mobile'];
        $result['Password'] = empty($userInfo['Password'])?0:1;

        $this->output(200, $result);
    }
}