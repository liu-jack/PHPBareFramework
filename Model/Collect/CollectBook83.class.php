<?php

namespace Model\Collect;

use Classes\Net\Collects;
use Model\Book\{
    Book, Collect, Column, Content
};

class CollectBook83 extends CollectBookBase
{
    const ALL_VISIT_URL = 'http://m.83zw.com/bookinfo/topallvisit_%d.html';
    const ALL_VOTE_URL = 'http://m.83zw.com/bookinfo/topallvote_%d.html';
    const TOP_GOODNUM_URL = 'http://m.83zw.com/bookinfo/topgoodnum_%d.html';

    /**
     * 采集整站
     *
     * @param array  $page
     * @param string $curl
     */
    public static function book($page = [], $curl = self::ALL_VISIT_URL)
    {
        if (empty($page)) {
            for ($i = 1; $i <= 29; $i++) {
                self::getBookList($i, self::ALL_VISIT_URL);
                self::getBookList($i, self::ALL_VOTE_URL);
                self::getBookList($i, self::TOP_GOODNUM_URL);
            }
        } else {
            foreach ($page as $v) {
                self::getBookList($v, $curl);
            }
        }
    }

    /**
     * 采集整页
     *
     * @param int    $p
     * @param string $curl
     * @return int|mixed
     */
    public static function getBookList($p = 1, $curl = self::ALL_VISIT_URL)
    {
        $log_path = self::$log_book_path . self::FROM_ID_83;
        $log_path2 = self::$log_book_err_path . self::FROM_ID_83;
        $cc = new Collects();
        $n = 0;
        $total = 0;
        while ($n < 3 && empty($total)) {
            $total = $cc->get(sprintf($curl, $p))->match(['total' => '@<a href="[^"]*" class="last">(\d+)</a>@'])->getMatch(); // @todo
            $total = $total['total'] ?? 0;
            $n++;
            usleep(500000);
        }
        logs("start collect page {$p}/{$total}", $log_path);
        echo "start collect page {$p}/{$total}" . PHP_EOL;

        if (!empty($total)) {
            // @todo start
            $all = $cc->matchAll('@<li>(.+)\s*<a href="([^"]+)">(.+)</a>\s*(.+)</li>@isU')->getMatch();
            $type = trim(trim($all[1]), '[]');
            $url = $all[2];
            $name = trim($all[3]);
            $author = trim($all[4]);
            $count = count($name);
            // @todo end
            if ($count > 0 && count($url) == $count && count($author) == $count) {
                foreach ($name as $k => $v) {
                    $data = $tdata = $cdata = [];
                    if (!empty($name[$k]) && !empty($author[$k]) && !empty($url[$k])) {
                        $data['BookName'] = trim($v);
                        $data['Author'] = trim($author[$k]);
                        if (!empty($data['BookName']) && !empty($data['Author'])) {
                            $res = Book::getBooks($data);
                            if (!empty($res['data'][0]['BookId'])) {
                                $res = $res['data'][0]['BookId'];
                                $tdata['Words'] = 0;
                                Book::updateBook($res, $tdata);
                            } else {
                                $data['TypeName'] = trim($type[$k], '[]');
                                if (empty($data['TypeName'])) {
                                    $data['TypeName'] = '其它小说';
                                }
                                $data['Words'] = 0;
                                $data['UpdateTime'] = date('Y-m-d H:i:s');
                                $data['CreateTime'] = date('Y-m-d H:i:s');
                                $res = Book::addBook($data);
                            }
                            if (!empty($res)) {
                                $cdata['BookId'] = $res;
                                $cdata['FromId'] = self::FROM_ID_83;
                                $res = Collect::getCollects($cdata);
                                $cdata['Url'] = trim($url[$k]);
                                if (empty($res['data'][0]['CollectId'])) {
                                    if (!empty($cdata['Url'])) {
                                        Collect::addCollect($cdata);
                                    } else {
                                        logs("url empty page:order {$p}:{$k}", $log_path2);
                                    }
                                }
                            } else {
                                logs("add book error page:order {$p}:{$k}", $log_path2);
                            }
                        } else {
                            logs("single book error page:order {$p}:{$k}", $log_path2);
                        }
                    } else {
                        logs("single book error page:order {$p}:{$k}", $log_path2);
                    }
                }
            } else {
                logs("collect book error on page {$p}", $log_path2);
            }
        }

        return $total;
    }

    /**
     * @param $bookid
     * @param $url
     * @param $book
     */
    public static function getBookColumn($bookid, $url, $book = [])
    {
        $log_path = self::$log_column_path . self::FROM_ID_83;
        $cc = new Collects();
        $n = 0;
        $name = '';
        while ($n < 3 && empty($name)) {
            $name = $cc->get($url)->match(['name' => '@<h1>(.+)</h1>@'])->getMatch();  // @todo
            $name = $name['name'] ?? '';
            $n++;
            usleep(300000);
        }
        if (empty($name)) {
            logs("get book failed {$bookid}", $log_path);
        }
        // @todo start
        $data = [];
        $regs = [
            'desc' => '@<p class="intro"><b>内容简介：</b>(.*)</p>\s*</div>@isU',
        ];
        $desc = $cc->match($regs)->strip()->getMatch();
        $data['BookDesc'] = preg_replace('@((&?nbsp;?)|(&?amp;?))+@', ' ', trim(strip_tags($desc['desc'])));
        if ((empty($book['BookDesc']) && !empty($data['BookDesc']))) {
            Book::updateBook($bookid, $data); // 简介
        }

        $column = $cc->matchAll('@<dd><a href="(.*)">(.*)</a></dd>@isU')->getMatch();

        // @todo end
        if (!empty($column[1]) && count($column[1]) < 10000) {
            $count = Column::getColumnCount($bookid, self::FROM_ID_83);
            if ($count < count($column[1])) {
                $offset = $count - 5 >= 0 ? $count - 5 : 0;
                $column[1] = array_slice($column[1], $offset);
                $column[2] = array_slice($column[2], $offset);
                foreach ($column[1] as $k => $v) {
                    if (!empty($column[2][$k]) && $v) {
                        $cdata = [];
                        $cdata['Url'] = $url . $v;
                        $res = Column::getColumnByUrl($bookid, self::FROM_ID_83, $cdata['Url']);
                        if (!empty($res['ChapterId'])) {
                            $res = $res['ChapterId'];
                        } else {
                            $cdata['ChapterName'] = $column[2][$k];
                            $cdata['BookId'] = $bookid;
                            $cdata['FromId'] = self::FROM_ID_83;
                            $res = Column::addColumn($bookid, $cdata);
                        }
                        if ($res && $res > 0) {
                            self::getBookContent($res, $cdata['Url'], $bookid);
                        } else {
                            logs("add column error {$bookid} : {$k}", $log_path);
                        }
                    } else {
                        logs("single column|url empty {$bookid} : {$k}", $log_path);
                    }
                    unset($column[1][$k], $column[2][$k]);
                }
            }
        } else {
            if (empty($column[1])) {
                logs("match column failed {$bookid}", $log_path);
            } else {
                logs("column > 10000 {$bookid}", $log_path);
            }

        }
    }

    /**
     * @param $chapterid
     * @param $url
     * @param $bookid
     */
    public static function getBookContent($chapterid, $url, $bookid)
    {
        $log_path = self::$log_content_err_path . self::FROM_ID_83;
        $cc = new Collects();
        $n = 0;
        $name = '';
        while ($n < 3 && empty($name)) {
            $name = $cc->get($url)->match(['name' => '@<h1[^>]*>(.+)</h1>@'])->getMatch();  // @todo
            $name = $name['name'] ?? '';
            $n++;
            usleep(100000);
        }

        $data = [];
        $content = $cc->match(['content' => '@<div id="BookText">(.*)</div>@isU'])->strip('<p><br>')->getMatch();  // @todo
        if (empty($content['content'])) {
            logs("match content failed {$bookid} : {$chapterid}", $log_path);
        }
        $res = Content::getContentByChapterId($bookid, $chapterid);
        if (!empty($res['ContentId'])) {
            if (empty($res['Content']) && !empty($content['content'])) {
                $data['Content'] = $content['content'];
                Content::updateContent($bookid, $res['ContentId'], $data);
            }
        } else {
            $data['ChapterId'] = $chapterid;
            $data['Content'] = $content['content'];
            Content::addContent($bookid, $data);
        }

    }

}