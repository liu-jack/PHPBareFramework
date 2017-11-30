<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/26
 * Time: 16:25
 */

namespace Controller\Cron;

use Bare\DB;
use Bare\Controller;
use Model\Search\BookSearch as SBook;

class BuildBookSearch extends Controller
{
    const TABLE = 'Book';
    const PAGE_SIZE = 1000;

    /**
     * 重建书本搜索 php index.php Cron/BuildBookSearch/index _v1
     */
    public function index()
    {
        need_cli();
        $pdo = DB::pdo(DB::DB_29SHU_R);
        $count = $pdo->select("max(BookId)")->from(self::TABLE)->getValue();
        if ($count > 0) {
            $page = ceil($count / self::PAGE_SIZE);
            echo "buildBookSearch Start!\n";
            for ($i = 1; $i <= $page; $i++) {

                $start = ($i - 1) * self::PAGE_SIZE;
                $end = $start + self::PAGE_SIZE;

                $pdo = DB::pdo(DB::DB_29SHU_R);
                $data = $pdo->select("*")
                    ->from(self::TABLE)
                    ->where(["BookId >" => $start, "BookId <=" => $end])
                    ->getAll();
                $pdo->close();
                DB::pdo(DB::DB_29SHU_R, 'force_close');
                $pdo = null;

                if (count($data) > 0) {
                    SBook::insertSearch($data, (string)$_GET['argv'][1]);
                }

                echo "Process: {$i}/{$page}\r";
            }
            echo "\nFinished!\n";
        }
    }

    public function delete()
    {
        $ret = SBook::delete();
        if ($ret) {
            echo "Delete Success\n";
        } else {
            echo "Delete Fail\n";
        }
    }
}