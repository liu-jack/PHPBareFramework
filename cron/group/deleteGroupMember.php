<?php
/**
 * 删除拼团支付超时成员 定时脚本 每分钟运行一次
 */

require(dirname(dirname(__DIR__)) . "/app.inc.php");

class deleteGroupMember
{
    public function doIndex()
    {
        global $argv;
        $duration = !empty($argv[1]) ? (int)($argv[1]) : 60;
        $sleep = !empty($argv[2]) ? max(1, (int)($argv[2])) : 30;
        $startTime = time();

        $offset = 0;
        $limit = 100;

        do {
            $now = time();
            if ($now >= $startTime + $duration) {
                break;
            }

            $members = QBGroupMember::getPaymentTimeoutMembers();
            echo 'offset:[' . $offset . '],limit:[' . $limit . '],count:[' . count($members) . "]\n";
            foreach ($members as $member) {
                QBGroupMember::deletePaymentTimeoutMember($member[QBGroupMember::FIELD_ID], $member[QBGroupMember::FIELD_GROUP_ID], $member[QBGroupMember::FIELD_USER_ID]);
            }
            $offset += $limit;

            if (empty($members)) {
                sleep($sleep);
                $offset = 0;
            }
        } while (true);

    }
}

$app->run();