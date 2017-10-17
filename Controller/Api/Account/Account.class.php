<?php
/**
 * Account.class.php
 *
 * @author xiaoshucheng <xiaomail666@163.com>
 *
 * $Id$
 */

namespace MobileApi\Passport;


use MobileApi\Lib\ApiBase;
use Passport\Account as AcountInfo;


/**
 * 用户 - 用户账号相关
 *
 * @package Passport
 * @author  xiaoshucheng <xiaomail666@163.com>
 * @since   1.0.0 2016-03-15
 */
class Account extends ApiBase
{
    /**
     * 账号信息
     *
     * <pre>
     * GET
     * </pre>
     *
     * @return string json
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

        $userInfo = AcountInfo::getUserById($uid);
        $result['Mobile'] = $userInfo['Mobile'];
        $result['Password'] = empty($userInfo['Password'])?0:1;

        $this->output(200, $result);
    }
}