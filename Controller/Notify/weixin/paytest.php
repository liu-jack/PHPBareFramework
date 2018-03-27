<?php
/**
 * 支付测试
 *
 * @author: hjh <hjh@jf.com>
 *
 * Date: 2017/7/15
 * Time: 17:20
 */

define("NO_CHECK", true);
require_once dirname(__DIR__) . '/../common.inc.php';

include_once BASEPATH_CONFIG . 'mobileapi/base.cfg.php';

class paytest
{
    public function doDefault()
    {
        echo "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
    }
}

$app->run();