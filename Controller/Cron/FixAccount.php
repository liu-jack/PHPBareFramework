<?php
/**
 * FixAccount.php 修复redis帐号缓存
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/7/29 11:07
 *
 */

namespace Controller\Cron;

use Bare\Controller;
use Bare\DB;

class FixAccount extends Controller
{
    //一次处理多少条记录
    const DATA_LIMIT = 100;
    const RD_DB_INDEX = 10;
    const RD_KEY_NICK = 'UNA:%s'; // 昵称 用户名 头像版本缓存key

    /**
     * 更新用户昵称等缓存数据  php index.php Cron/FixAccount/fixUser
     */
    public function fixUser()
    {
        need_cli();
        $pdo = DB::pdo(DB::DB_ACCOUNT_W);
        $count = $pdo->select("MAX(UserId)")->from("User")->getValue();
        echo "Need to deal [" . $count . "]" . PHP_EOL;
        $cnt = ceil($count / self::DATA_LIMIT);
        for ($i = 0; $i < $cnt; $i++) {
            if (empty($pdo)) {
                $pdo = DB::pdo(DB::DB_ACCOUNT_W);
            }
            echo "doing [" . ($i + 1) . "/" . $cnt . "]" . PHP_EOL;
            $pdo->clear();
            $res = $pdo
                ->select("UserId,UserNick,LoginName,Avatar")
                ->from("User")
                ->where([
                    "UserId >=" => $i * self::DATA_LIMIT,
                    "UserId <" => ($i + 1) * self::DATA_LIMIT,
                ])
                ->order("UserId")
                ->getAll();
            if (!empty($res) && count($res) > 0) {
                $redis = DB::redis(DB::REDIS_ACCOUNT_W, self::RD_DB_INDEX);
                $redis->multi(\Redis::PIPELINE);
                foreach ($res as $v) {
                    $temp = [
                        'nick' => $v['UserNick'],
                        'name' => $v['LoginName'],
                        'avatar' => $v['Avatar']
                    ];
                    $redis->hMset(sprintf(self::RD_KEY_NICK, $v['UserId']), $temp);
                }
                $redis->exec();
            }
            $pdo->close();
            DB::pdo(DB::DB_ACCOUNT_W, 'force_close');
            $pdo = null;
        }
        echo "finish!" . PHP_EOL;
    }
}