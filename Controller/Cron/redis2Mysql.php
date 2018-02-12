<?php
/**
 *
 * 异步同步Mysql数据
 *
 * asyncMysql.php
 * Created by IntelliJ IDEA.
 *
 * Date: 2018/1/15
 * Time: 16:31
 */

namespace Controller\Cron;

use Bare\Controller;
use Bare\DB;
use Model\RedisDB\RedisDB;
use Model\RedisDB\RedisQueue;

class redis2Mysql extends Controller
{
    public function index()
    {
        need_cli();
        $duration = (int)($_GET['argv'][1]);
        if ($duration <= 0) {
            fwrite(STDERR, "{$_GET['argv'][0]} duration\n");

            return false;
        }
        $sleep = 1;
        if (count($_GET['argv']) > 2) {
            $sleep = (int)$_GET['argv'][2];
        }

        $n = 0;
        $startTime = time();
        while (true) {
            $now = time();
            if ($now > $startTime + $duration) {
                break;
            }

            $info = RedisQueue::instance(RedisQueue::TYPE_ASYNC_TABLES)->pop();
            if (empty($info)) {
                sleep($sleep);
                continue;
            }

            try {
                $key = $info[RedisDB::FIELD_REDIS_KEY];
                $tableName = $info[RedisDB::FIELD_DB_TABLE_NAME];
                $primaryKey = $info[RedisDB::FIELD_PRIMARY_KEY];
                $primaryValue = $info[RedisDB::FIELD_PRIMARY_VALUE];
                $fields = $info[RedisDB::FIELD_FIELDS];
                $dbParam = $info[RedisDB::FIELD_DB_PARAM];

                $redisDb = isset($info[RedisDB::FIELD_REDIS_DB]) ? (int)$info[RedisDB::FIELD_REDIS_DB] : 0;
                $redisDbIndex = isset($info[RedisDB::FIELD_REDIS_DB_INDEX]) ? (int)$info[RedisDB::FIELD_REDIS_DB_INDEX] : 0;

                if (empty($fields)) {
                    $result = RedisDB::instance($redisDb, $redisDbIndex)->load($key);
                } else {
                    $result = RedisDB::instance($redisDb, $redisDbIndex)->getMulti($key, $fields);
                }

                if (empty($result)) {
                    fwrite(STDERR, $fields);
                    fwrite(STDERR, "getMultiKey: {$key} failed\n");
                    continue;
                }
                if (isset($result[$primaryKey])) {
                    unset($result[$primaryKey]);
                }
                if (isset($result[RedisDB::FIELD_SYNC_FLAG])) {
                    unset($result[RedisDB::FIELD_SYNC_FLAG]);
                }

                $pdo_w = DB::pdo($dbParam);
                $ret = $pdo_w->update($tableName, $result, [
                    $primaryKey => $primaryValue
                ]);

                if ($ret === false) {
                    var_dump($pdo_w->select(implode(",", $fields))->from($tableName)->where([$primaryKey => $primaryValue])->getOne());
                    fwrite(STDERR, "fileds:" . json_encode($fields, JSON_UNESCAPED_UNICODE) . "\n");
                    fwrite(STDERR, "result:" . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n");
                    fwrite(STDERR, "Key:" . json_encode($primaryKey, JSON_UNESCAPED_UNICODE) . "\n");
                    fwrite(STDERR, "VAL:" . json_encode($primaryValue, JSON_UNESCAPED_UNICODE) . "\n");
                    fwrite(STDERR, "update " . json_encode($info, JSON_UNESCAPED_UNICODE) . " failed\n===================\n");
                }
            } catch (\Exception $exception) {
                var_dump($exception);
            }
            $n++;

            if ($n % 50 === 0) {
                usleep(500 * 1000);
                fwrite(STDOUT, "processing count: {$n}\n");
            }
        }
        fwrite(STDOUT, "process count: {$n}\n");

        return true;
    }
}
