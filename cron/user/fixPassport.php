<?php
/**
 * FixPassport.php 修复redis通行证缓存
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/7/29 11:07
 *
 */

require '../../app.inc.php';

use Bare\DB;

class fixPassport
{
    //一次处理多少条记录
    const DATA_LIMIT = 100;
    const RD_DB_INDEX = 11;
    const RD_KEY_PN = 'PN:%s'; // 用户名缓存key
    const RD_KEY_PE = 'PE:%s'; // 邮箱缓存key
    const RD_KEY_PM = 'PM:%s'; // 手机缓存key
    const RD_KEY_PU = 'PU:%s'; // 用户名 邮箱 手机缓存key

    /**
     * 更新通行证用户用户名 邮箱 手机等缓存数据 php fixPassport.php FixUser
     */
    public function doFixUser()
    {
        need_cli();
        $pdo = DB::pdo(DB::DB_PASSPORT_W);
        $count = $pdo->select("MAX(UserId)")->from("User")->getValue();
        echo "Need to deal [" . $count . "]" . PHP_EOL;
        $cnt = ceil($count / self::DATA_LIMIT);
        for ($i = 0; $i < $cnt; $i++) {
            if (empty($pdo)) {
                $pdo = DB::pdo(DB::DB_PASSPORT_W);
            }
            echo "doing [" . ($i + 1) . "/" . $cnt . "]" . PHP_EOL;
            $pdo->clear();
            $res = $pdo->select("UserId,UserName,Email,Mobile")->from("User")->where([
                "UserId >=" => $i * self::DATA_LIMIT,
                "UserId <" => ($i + 1) * self::DATA_LIMIT,
            ])->order("UserId")->getAll();
            if (!empty($res) && count($res) > 0) {
                $redis = DB::redis(DB::DB_PASSPORT_W, self::RD_DB_INDEX);
                $redis->multi(\Redis::PIPELINE);
                foreach ($res as $v) {
                    $temp = [
                        'name' => $v['UserName'],
                        'email' => $v['Email'],
                        'mobile' => $v['Mobile']
                    ];
                    $redis->hMset(sprintf(self::RD_KEY_PU, $v['UserId']), $temp);
                }
                $redis->exec();
            }
            $pdo->close();
            DB::pdo(DB::DB_PASSPORT_W, 'force_close');
            $pdo = null;
        }
        echo "finish!" . PHP_EOL;
    }

    /**
     * 更新通行证用户用户名|邮箱|手机等缓存ID查询数据 php fixPassport.php FixUserId
     */
    public function doFixUserId()
    {
        need_cli();
        $pdo = DB::pdo(DB::DB_PASSPORT_W);
        $count = $pdo->select("MAX(UserId)")->from("User")->getValue();
        echo "Need to deal [" . $count . "]" . PHP_EOL;
        $cnt = ceil($count / self::DATA_LIMIT);
        for ($i = 0; $i < $cnt; $i++) {
            if (empty($pdo)) {
                $pdo = DB::pdo(DB::DB_PASSPORT_W);
            }
            echo "doing [" . ($i + 1) . "/" . $cnt . "]" . PHP_EOL;
            $pdo->clear();
            $res = $pdo->select("UserId,UserName,Email,Mobile")->from("User")->where([
                "UserId >=" => $i * self::DATA_LIMIT,
                "UserId <" => ($i + 1) * self::DATA_LIMIT,
            ])->order("UserId")->getAll();
            if (!empty($res) && count($res) > 0) {
                $redis = DB::redis(DB::DB_PASSPORT_W, self::RD_DB_INDEX);
                $redis->multi(\Redis::PIPELINE);
                foreach ($res as $v) {
                    if (!empty($v['UserName'])) {
                        $redis->set(sprintf(self::RD_KEY_PN, base64_encode(strtolower($v['UserName']))), $v['UserId']);
                    }
                    if (!empty($v['Email'])) {
                        $redis->set(sprintf(self::RD_KEY_PE, $v['Email']), $v['UserId']);
                    }
                    if (!empty($v['Mobile'])) {
                        $redis->set(sprintf(self::RD_KEY_PE, $v['Mobile']), $v['UserId']);
                    }
                }
                $redis->exec();
            }
            $pdo->close();
            DB::pdo(DB::DB_PASSPORT_W, 'force_close');
            $pdo = null;
        }
        echo "finish!" . PHP_EOL;
    }
}

global $argv;
if (empty($argv[1])) {
    exit('usage: php fixPassport.php [method]');
}
$app->run(trim($argv[1]));