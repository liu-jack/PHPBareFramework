<?php
/**
 * 书库采集管理
 */

namespace Controller\Book;

use Bare\DB;
use Bare\Controller;
use Model\Book\{
    Collect77, Column, Book
};

class Collect extends Controller
{
    /**
     * 书本采集 php index.php Book/Collect/index
     */
    public function index()
    {
        need_cli();
        $page = [];
        Collect77::book($page);
    }

    /**
     * 章节内容采集 php index.php Book/Collect/content/id/1
     */
    public function content()
    {
        need_cli();
        $id = intval($_GET['id']);
        Collect77::column($id);
    }

    /**
     * 章节内容采集 php index.php Book/Collect/contentOne/id/6418
     */
    public function contentOne()
    {
        need_cli();
        $id = intval($_GET['id']);
        $step = isset($_GET['step']) ? intval($_GET['step']) : 0;
        Collect77::column($id, $step);

    }

    /**
     * 内容错误日志重新采集 php index.php Book/Collect/collectLog 77_20170108.log
     * @return bool
     */
    public function collectLog()
    {
        need_cli();
        $path = LOG_PATH . 'collect/book/content_err' . $_GET['argv'][1];
        if (!file_exists($path)) {
            return false;
        }
        $fp = fopen($path, 'r');
        while (!feof($fp)) {
            $line = fgets($fp);
            if (preg_match('/(\d+) : (\d+)/', $line, $out)) {
                if (!empty($out[1]) && !empty($out[2])) {
                    $bookid = $out[1];
                    $chapterid = $out[2];
                    $res = Column::getColumnById($bookid, $chapterid);
                    echo "collect content {$bookid}:{$chapterid}" . PHP_EOL;
                    Collect77::getBookContent($chapterid, $res['Url'], $bookid);
                }
            }
        }
        fclose($fp);
    }

    /**
     * book字段修复
     * php index.php book/collect/fixField
     */
    public function fixField()
    {
        need_cli();
        $size = 100;
        $pdo = DB::pdo(DB::DB_29SHU_R);
        $total = $pdo->clear()->select("count(*)")->from('book')->getValue();
        if ($total > $size) {
            $allpage = ceil($total / $size);
        } else {
            $allpage = 1;
        }
        for ($i = 1; $i <= $allpage; $i++) {
            echo "All Process: {$i}/{$allpage} \n";
            $offset = ($i - 1) * $size;
            $list = $pdo->clear()->select("`BookId`,`BookDesc`")->from('book')->limit($offset, $size)->getAll();
            if (!empty($list)) {
                $m = count($list);
                foreach ($list as $k => $v) {
                    echo "Page Process: " . ($k + 1) . "/{$m} \r";
                    if (!empty($v['BookDesc'])) {
                        preg_replace('@((&?nbsp;?)|(&?amp;?))+@', ' ',
                            $data['BookDesc'] = trim(strip_tags($v['BookDesc'])));
                        $data['BookDesc'] = str_replace('()', '', $data['BookDesc']);
                        Book::updateBook($v['BookId'], $data);
                    }
                }
            }
        }
        echo "\nFinished!\n";
    }

    /**
     * 删除无效空图片
     * php index.php book/collect/rmEmptyCover
     */
    public function rmEmptyCover()
    {
        need_cli();
        $size = 100;
        $pdo = DB::pdo(DB::DB_29SHU_R);
        $total = $pdo->clear()->select("count(*)")->from('book')->getValue();
        if ($total > $size) {
            $allpage = ceil($total / $size);
        } else {
            $allpage = 1;
        }
        for ($i = 1; $i <= $allpage; $i++) {
            echo "All Process: {$i}/{$allpage} \n";
            $offset = ($i - 1) * $size;
            $list = $pdo->clear()->select("`BookId`")->from('book')->limit($offset, $size)->getAll();
            if (!empty($list)) {
                $m = count($list);
                foreach ($list as $k => $v) {
                    echo "Page Process: " . ($k + 1) . "/{$m} \r";
                    $cover = cover($v['BookId']);
                    if (is_file($cover)) {
                        $img = getimagesize($cover);
                        if ($img === false) {
                            $r = unlink($cover);
                            if ($r) {
                                echo "\n unlink cover success bookid : {$v['BookId']}!\n";
                            } else {
                                echo "\n unlink cover failed bookid : {$v['BookId']}!\n";
                            }
                        }
                    }
                }
            }
        }
        echo "\nFinished!\n";
    }
}
