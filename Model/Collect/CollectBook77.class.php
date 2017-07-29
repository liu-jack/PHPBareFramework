<?php

namespace Model\Collect;

use Classes\Net\Collects;
use Model\Book\{
    Book, Collect, Column, Content
};

class CollectBook77
{
    const BASE_URL = 'http://www.xiaoshuo77.com';

    const ALL_VISIT_URL = 'http://www.xiaoshuo77.com/page_allvisit_%d.html';

    const ALL_VOTE_URL = 'http://www.xiaoshuo77.com/page_allvote_%d.html';

    const MONTH_VISIT_URL = 'http://www.xiaoshuo77.com/page_monthvisit_%d.html';

    const MONTH_VOTE_URL = 'http://www.xiaoshuo77.com/page_monthvote_%d.html';

    const TOP_TIME_URL = 'http://www.xiaoshuo77.com/page_toptime_%d.html';

    const FROM_ID = 77;

    /**
     * 采集整站
     * @param array $page
     * @param string $curl
     */
    public static function book($page = [], $curl = self::ALL_VISIT_URL)
    {
        if (empty($page)) {
            for ($i = 1; $i <= 29; $i++) {
                self::getBookList($i, self::ALL_VISIT_URL);
                self::getBookList($i, self::ALL_VOTE_URL);
                self::getBookList($i, self::TOP_TIME_URL);
                if ($i <= 3) {
                    self::getBookList($i, self::MONTH_VISIT_URL);
                    self::getBookList($i, self::MONTH_VOTE_URL);
                }
            }
        } else {
            foreach ($page as $v) {
                self::getBookList($v, $curl);
            }
        }
    }

    /**
     * 采集整页
     * @param int $p
     * @param string $curl
     * @return int|mixed
     */
    public static function getBookList($p = 1, $curl = self::ALL_VISIT_URL)
    {
        $log_path = 'collect/book/book' . self::FROM_ID;
        $log_path2 = 'collect/book/book_err' . self::FROM_ID;
        $cc = new Collects();
        $n = 0;
        $total = 0;
        while ($n < 3 && empty($total)) {
            $total = $cc->get(sprintf($curl,
                $p))->match(['total' => '@<a href="[^"]*" class="last">(\d+)</a>@'])->getMatch(); // @todo
            $total = $total['total'] ?? 0;
            $n++;
            usleep(500000);
        }
        logs("start collect page {$p}/{$total}", $log_path);
        echo "start collect page {$p}/{$total}" . PHP_EOL;

        if (!empty($total)) {
            // @todo start
            $type = $cc->matchAll('@<div class="con2"><a href="[^"]*">(.*)</a></div>@isU')->getMatch();
            $url_name = $cc->matchAll('@<div class="con3"><a\s*class="tit" href="(.*)" title="(.*)" target="_blank">[^"]*</a>\s*/\s*<a href="[^"]*" title="[^"]*" target="_blank">[^"]*</a></div>@isU')->getMatch();
            $url = $url_name[1];
            $name = $url_name[2];
            $word = $cc->matchAll('@<div class="con4">(\d*)</div>@isU')->getMatch();
            $author = $cc->matchAll('@<div class="con5">(.*)</div>@isU')->getMatch();
            $time = $cc->matchAll('@<div class="con6">(.*)</div>@isU')->getMatch();
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
                                $tdata['Words'] = intval($word[$k]);
                                if (date('m-d H:i') >= $time[$k]) {
                                    $tdata['UpdateTime'] = strtotime(date('Y') . '-' . $time[$k]);
                                } else {
                                    $tdata['UpdateTime'] = strtotime((date('Y') - 1) . '-' . $time[$k]);
                                }
                                Book::updateBook($res, $tdata);
                            } else {
                                $data['TypeName'] = trim($type[$k], '[]');
                                if (empty($data['TypeName'])) {
                                    $data['TypeName'] = '其它小说';
                                }
                                $data['Words'] = intval($word[$k]);
                                if (date('m-d H:i') >= $time[$k]) {
                                    $data['UpdateTime'] = strtotime(date('Y') . '-' . $time[$k]);
                                } else {
                                    $data['UpdateTime'] = strtotime((date('Y') - 1) . '-' . $time[$k]);
                                }
                                $data['CreateTime'] = time();
                                $res = Book::addBook($data);
                            }
                            if (!empty($res)) {
                                $cdata['BookId'] = $res;
                                $cdata['FromId'] = self::FROM_ID;
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
     * 采集内容
     */
    public static function column($collectid = 0, $step = 8)
    {
        $log_path = 'collect/book/column' . self::FROM_ID;

        if ($collectid > 0) {
            $res = Collect::getCollects([], 0, 1);
            $max = max(1, $res['data'][0]['CollectId']);
            while ($collectid <= $max) {
                $res = Collect::getCollectById($collectid);
                if (!empty($res)) {
                    logs("start collect book {$res['BookId']}", $log_path);
                    echo "start collect book {$res['BookId']}" . PHP_EOL;

                    Collect::updateCollect($res['CollectId'], ['CollectTime' => time()]);
                    $book = Book::getBookByIds($res['BookId']);
                    if ($book['IsFinish'] != 2) {
                        switch ($res['FromId']) {
                            case self::FROM_ID:
                                self::getBookColumn($res['BookId'], $res['Url'], $book);
                                break;
                            case 83:
                                CollectBook83::getBookColumn($res['BookId'], $res['Url'], $book);
                                break;
                        }
                    }
                }
                if ($step <= 0) {
                    break;
                }
                $collectid += $step;
            }
        } else {
            $list = Collect::getCollects();
            if (!empty($list['data'])) {
                foreach ($list['data'] as $k => $v) {
                    logs("start collect book {$v['BookId']}", $log_path);
                    echo "start collect book {$v['BookId']}" . PHP_EOL;

                    Collect::updateCollect($v['CollectId'], ['CollectTime' => time()]);
                    $book = Book::getBookByIds($v['BookId']);
                    if ($book['IsFinish'] != 2) {
                        self::getBookColumn($v['BookId'], $v['Url'], $book);
                    }
                }
            }
        }

        logs("collect finished", $log_path);
        echo "collect finished" . PHP_EOL;
    }

    /**
     * @param $bookid
     * @param $url
     * @param $book
     */
    public static function getBookColumn($bookid, $url, $book = [])
    {
        $log_path = 'collect/book/column_err' . self::FROM_ID;
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
            'desc' => '@<div class="introCon">(.*)</div>@',
            'img' => '@<img  src="(.*)" alt="[^"]*" />@',
        ];
        $desc_img = $cc->match($regs)->strip()->getMatch();
        $data['BookDesc'] = preg_replace('@((&?nbsp;?)|(&?amp;?))+@', ' ', trim(strip_tags($desc_img['desc'])));

        if (!is_file(cover($bookid)) && !empty($desc_img['img'])) {
            $cc->getImage($desc_img['img'], cover($bookid)); // 封面
        }
        $column = $cc->matchAll('@<dd><a href="(.*)">(.*)</a></dd>@isU')->getMatch();

        if ($book['IsFinish'] == 0) {
            $url = 'http://www.xiaoshuo77.com/modules/article/pd.php?id=' . $book['BookId'];
            $finish = $cc->get($url)->match(['finish' => '@<title>(.+)</title>@'])->getMatch();
            $finish = trim($finish['finish']);
            if ($finish == '已完成') {
                $data['IsFinish'] = 1;
            }
        }
        if ((empty($book['BookDesc']) && !empty($data['BookDesc'])) || !empty($data['IsFinish'])) {
            Book::updateBook($bookid, $data); // 简介
        }
        // @todo end
        if (!empty($column[1]) && count($column[1]) < 10000) {
            $count = Column::getColumnCount($bookid, self::FROM_ID);
            if ($count < count($column[1])) {
                $offset = $count - 5 >= 0 ? $count - 5 : 0;
                $column[1] = array_slice($column[1], $offset);
                $column[2] = array_slice($column[2], $offset);
                foreach ($column[1] as $k => $v) {
                    if (!empty($column[2][$k]) && $v) {
                        $cdata = [];
                        $cdata['Url'] = self::BASE_URL . $v;
                        $res = Column::getColumnByUrl($bookid, self::FROM_ID, $cdata['Url']);
                        if (!empty($res['ChapterId'])) {
                            $res = $res['ChapterId'];
                        } else {
                            $cdata['ChapterName'] = $column[2][$k];
                            $cdata['BookId'] = $bookid;
                            $cdata['FromId'] = self::FROM_ID;
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
        $log_path = 'collect/book/content_err' . self::FROM_ID;
        $cc = new Collects();
        $n = 0;
        $name = '';
        while ($n < 3 && empty($name)) {
            $name = $cc->get($url)->match(['name' => '@<h1>(.+)</h1>@'])->getMatch();  // @todo
            $name = $name['name'] ?? '';
            $n++;
            usleep(100000);
        }

        $data = [];
        $content = $cc->match(['content' => '@<div id="content">(.*)</div>@isU'])->strip('<p><br>')->getMatch();  // @todo
        if (empty($content['content'])) {
            // http://www.xiaoshuo77.com/view/25/25768/7757638.html
            $contentimg = $cc->match(['img' => '@<div id="content">\s*<div class="divimage"><img src="([^"]+)" border="0" class="imagecontent"></div>\s*</div>@isU'])->getMatch();
            if (!empty($contentimg['img'])) {
                if (!file_exists(contentImg($chapterid, $bookid))) {
                    $cc->getImage($contentimg['img'], contentImg($chapterid, $bookid));
                }
                $content['content'] = contentImg($chapterid, $bookid, 0);
            } else {
                logs("match content failed {$bookid} : {$chapterid}", $log_path);
            }
        }
        $res = Content::getContentByChapterId($bookid, $chapterid);
        if (!empty($res['ContentId'])) {
            if (empty($res['Content']) && !empty($content['content'])) {
                //if (strpos($res['Content'], '.gif') !== false && !empty($content['content'])) {
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