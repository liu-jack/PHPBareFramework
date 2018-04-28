<?php
/**
 * 关闭超时团购 定时脚本 每分钟运行一次
 */


require(dirname(dirname(__DIR__)) . "/app.inc.php");

use Model\Application\GroupBuy;

class closeGroupBuy
{
    public function doIndex()
    {
        need_cli();
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

            $group_list = GroupBuy::getTimeoutGroupBuy($offset, $limit);
            $group_ids = $group_list['data'];
            echo 'offset:[' . $offset . '],limit:[' . $limit . '],count:[' . count($group_ids) . "]\n";
            foreach ($group_ids as $id) {
                GroupBuy::groupBuyFailure($id);
            }
            $offset += $limit;

            if (empty($group_ids)) {
                sleep($sleep);
                $offset = 0;
            } else {
                usleep(500000);
            }
        } while (true);
    }
}

$app->run();