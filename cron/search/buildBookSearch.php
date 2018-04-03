<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/26
 * Time: 16:25
 */

require '../../app.inc.php';

use Bare\DB;
use Model\Search\BookSearch as SBook;

class buildBookSearch
{
    const TABLE = 'Book';
    const PAGE_SIZE = 1000;

    /**
     * 重建书本搜索 php buildBookSearch.php _v1
     */
    public function doIndex()
    {
        need_cli();
        global $argv;
        if (empty($argv[1])) {
            exit('usage: php buildBookSearch.php _v[x]');
        }
        $pdo = DB::pdo(DB::DB_29SHU_R);
        $count = $pdo->select("max(BookId)")->from(self::TABLE)->getValue();
        if ($count > 0) {
            $page = ceil($count / self::PAGE_SIZE);
            echo "buildBookSearch Start!\n";
            for ($i = 1; $i <= $page; $i++) {

                $start = ($i - 1) * self::PAGE_SIZE;
                $end = $start + self::PAGE_SIZE;

                $pdo = DB::pdo(DB::DB_29SHU_R);
                $data = $pdo->select("*")->from(self::TABLE)->where([
                    "BookId >" => $start,
                    "BookId <=" => $end
                ])->getAll();
                $pdo->close();
                DB::pdo(DB::DB_29SHU_R, 'force_close');
                $pdo = null;

                if (count($data) > 0) {
                    SBook::insertSearch($data, trim($argv[1]));
                }

                echo "Process: {$i}/{$page}\r";
            }
            echo "\nFinished!\n";
        }
    }

    public function doDelete()
    {
        $ret = SBook::delete();
        if ($ret) {
            echo "Delete Success\n";
        } else {
            echo "Delete Fail\n";
        }
    }
}

$app->run();