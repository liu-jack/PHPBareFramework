<?php
/**
 *
 * 异步同步Mysql数据
 *
 * redis2Mysql.php
 *
 * Date: 2018/1/15
 * Time: 16:31
 */

require dirname(dirname(__DIR__)) . '/app.inc.php';

use Bare\DB;
use Model\RedisDB\RedisCache;
use Model\RedisDB\RedisQueue;

class redis2Mysql
{
    /**
     * 同步redis数据到MySQL * /5 * * * * php redis2Mysql.php 300
     *
     * @return bool
     */
    public function doIndex()
    {
        need_cli();
        global $argv;
        $duration = (int)($argv[1]);
        if ($duration <= 0) {
            fwrite(STDERR, "{$argv[0]} duration\n");

            return false;
        }
        $sleep = 5;
        if (count($argv) > 2) {
            $sleep = (int)$argv[2];
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
                $key = $info[RedisCache::FIELD_REDIS_KEY];
                $tableName = $info[RedisCache::FIELD_DB_TABLE_NAME];
                $primaryKey = $info[RedisCache::FIELD_PRIMARY_KEY];
                $primaryValue = $info[RedisCache::FIELD_PRIMARY_VALUE];
                $fields = $info[RedisCache::FIELD_FIELDS];
                $dbParam = $info[RedisCache::FIELD_DB_PARAM];

                $redisDb = isset($info[RedisCache::FIELD_REDIS_DB]) ? (int)$info[RedisCache::FIELD_REDIS_DB] : 0;
                $redisDbIndex = isset($info[RedisCache::FIELD_REDIS_DB_INDEX]) ? (int)$info[RedisCache::FIELD_REDIS_DB_INDEX] : 0;

                if (empty($fields)) {
                    $result = RedisCache::instance($redisDb, $redisDbIndex)->load($key);
                } else {
                    $result = RedisCache::instance($redisDb, $redisDbIndex)->getMulti($key, $fields);
                }

                if (empty($result)) {
                    fwrite(STDERR, $fields);
                    fwrite(STDERR, "getMultiKey: {$key} failed\n");
                    continue;
                }
                if (isset($result[$primaryKey])) {
                    unset($result[$primaryKey]);
                }
                if (isset($result[RedisCache::FIELD_SYNC_FLAG])) {
                    unset($result[RedisCache::FIELD_SYNC_FLAG]);
                }

                $pdo_w = DB::pdo($dbParam);
                $ret = $pdo_w->update($tableName, $result, [
                    $primaryKey => $primaryValue
                ]);

                if ($ret === false) {
                    $data = $pdo_w->select(implode(",", $fields))->from($tableName)->where([$primaryKey => $primaryValue])->getOne();
                    pre($data);
                    fwrite(STDERR, "fileds:" . json_encode($fields, JSON_UNESCAPED_UNICODE) . "\n");
                    fwrite(STDERR, "result:" . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n");
                    fwrite(STDERR, "Key:" . json_encode($primaryKey, JSON_UNESCAPED_UNICODE) . "\n");
                    fwrite(STDERR, "VAL:" . json_encode($primaryValue, JSON_UNESCAPED_UNICODE) . "\n");
                    fwrite(STDERR, "update " . json_encode($info, JSON_UNESCAPED_UNICODE) . " failed\n===================\n");
                    $info['ErrorMsg'] = 'update mysql fail';
                    logs($info, 'cron/sql/' . __CLASS__);
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

$app->run();
