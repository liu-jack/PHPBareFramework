<?php
/**
 * 关闭超时团购 定时脚本 每分钟运行一次
 */

if (php_sapi_name() != 'cli') {
    exit('cron must run in cli mode!');
}

set_time_limit(0);
ini_set('memory_limit', '2048M');

use lib\core\Action;
use MinApp\QBVip\QBGroupPurchase;

require(dirname(dirname(dirname(__DIR__))) . "/common.inc.php");

class closeTimeoutGroupPurchase extends Action
{
    const TABLE_NAME = 'QBGroupPurchase';

    public function doDefault()
    {
        global $argv;
        $duration = !empty($argv[1]) ? (int)($argv[1]) : 60;
        $sleep = !empty($argv[2]) ? max(1, (int)($argv[2])) : 30;
        $startTime = time();

        $offset = 0;
        $limit = 100;

        do {
            $now = time();
            if ($now > $startTime + $duration) {
                break;
            }

            $groupPurchases = QBGroupPurchase::getTimeoutGroupPurchases($offset, $limit);
            echo 'offset:[' . $offset . '],limit:[' . $limit . '],count:[' . count($groupPurchases) . "]\n";
            foreach ($groupPurchases as $groupPurchase) {
                QBGroupPurchase::updateGroupPurchaseFailure($groupPurchase);
            }
            $offset += $limit;

            if (empty($groupPurchases)) {
                sleep($sleep);
                $offset = 0;
            }
        } while (true);
    }
}

$app->run();