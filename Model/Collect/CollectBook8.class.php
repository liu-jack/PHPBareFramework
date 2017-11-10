<?php

namespace Model\Collect;

use Classes\Net\Collects;
use Model\Book\{
    Book, Collect, Column, Content
};

class CollectBook8 extends CollectBookBase
{
    const MODERN_URL = 'http://www.mzhu8.com/mulu/17/%d.html'; // 现代文学 45
    const FOREIGN_URL = 'http://www.mzhu8.com/mulu/6/%d.html'; // 国外名著 51
    const MING_QIN_URL = 'http://www.mzhu8.com/mulu/16/%d.html'; // 明清小说 36
    const XIAN_QIN_URL = 'http://www.mzhu8.com/mulu/11/%d.html'; // 先秦文学 3

    protected static $type_name = [
        self::MODERN_URL => '文学名著',
        self::FOREIGN_URL => '文学名著',
        self::MING_QIN_URL => '国学经典',
        self::XIAN_QIN_URL => '国学经典',
    ];

    /**
     * 采集整站
     *
     * @param array  $page
     * @param string $curl
     */
    public static function book($page = [], $curl = self::MODERN_URL)
    {
        if (empty($page)) {
            for ($i = 1; $i <= 51; $i++) {
                if ($i <= 3) {
                    self::getBookList($i, self::XIAN_QIN_URL);
                }
                if ($i <= 36) {
                    self::getBookList($i, self::MING_QIN_URL);
                }
                if ($i <= 45) {
                    self::getBookList($i, self::MODERN_URL);
                }
                self::getBookList($i, self::FOREIGN_URL);
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
    public static function getBookList($p = 1, $curl = self::MODERN_URL)
    {
        $log_path = self::$log_book_path . self::FROM_ID_8;
        $log_path2 = self::$log_book_err_path . self::FROM_ID_8;
        $cc = new Collects();
        $n = 0;
        $total = 0;
        while ($n < 3 && empty($total)) {
            $total = $cc->get(sprintf($curl,
                $p))->match(['total' => '@<a[^>]*href="[^"]*"[^>]*class="last"[^>]*>(\d+)</a>@'])->getMatch(); // todo match
            $total = $total['total'] ?? 0;
            $n++;
            usleep(500000);
        }
        logs("start collect page {$p}/{$total}", $log_path);
        echo "start collect page {$p}/{$total}" . PHP_EOL;

        if (!empty($total)) {
            // @todo start
            $all = $cc->matchAll('@<div class="l_pic"><a[^>]*><img[^>]*src="[^"]*"[^>]*></a></div><dl>
<dt><h1[^>]*><a[^>]*href="([^"]+)"[^>]*>([^<]+)</a>.*作者：([^&]+)[&nbsp;]*类别：[^<]*</a>.*更新日期：([^<]*)</h1></dt>@isU')->getMatch();
            $url = $all[1];
            $name = $all[2];
            $author = $all[3];
            $count = count($url);
            // @todo end
            if ($count > 0 && count($name) == $count && count($author) == $count) {
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
                                $data['TypeName'] = self::$type_name[$curl] ?? '';
                                if (empty($data['TypeName'])) {
                                    $data['TypeName'] = '文学名著';
                                }
                                $data['Words'] = 0;
                                $data['FromSite'] = self::FROM_ID_8;
                                $data['DefaultFromSite'] = self::FROM_ID_8;
                                $data['UpdateTime'] = date('Y-m-d H:i:s');
                                $data['CreateTime'] = date('Y-m-d H:i:s');
                                $res = Book::addBook($data);
                            }
                            if (!empty($res)) {
                                $cdata['BookId'] = $res;
                                $cdata['FromSite'] = self::FROM_ID_8;
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
        $log_path = self::$log_column_path . self::FROM_ID_8;
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
            'desc' => '@<script src="http://cpro.baidustatic.com/cpro/ui/c.js" type="text/javascript"></script>(.*)</div>\s*</div>@isU',
            'img' => '@<div id="fmimg"><img[^>]*src="(.*)"[^>]*>\s*</div>@',
        ];
        $desc = $cc->match($regs)->strip()->getMatch();
        $data['BookDesc'] = preg_replace('@((&?nbsp;?)|(&?amp;?))+@', ' ', trim(strip_tags($desc['desc'])));
        if ((empty($book['BookDesc']) && !empty($data['BookDesc']))) {
            Book::updateBook($bookid, $data); // 简介
        }
        if (!is_file(cover($bookid)) && !empty($desc['img'])) {
            $cc->getImage($desc['img'], cover($bookid)); // 封面
        }
        $column = $cc->matchAll('@<dd><a href="(.*)"[^>]*>(.*)</a></dd>@isU')->getMatch();

        // @todo end
        if (!empty($column[1]) && count($column[1]) < 50000) {
            $count = Column::getColumnCount($bookid, self::FROM_ID_8);
            if ($count < count($column[1])) {
                $offset = $count - 5 >= 0 ? $count - 5 : 0;
                $column[1] = array_slice($column[1], $offset);
                $column[2] = array_slice($column[2], $offset);
                foreach ($column[1] as $k => $v) {
                    if (!empty($column[2][$k]) && $v) {
                        $cdata = [];
                        $cdata['Url'] = self::BASE_URL_8 . $v;
                        $res = Column::getColumnByUrl($bookid, self::FROM_ID_8, $cdata['Url']);
                        if (!empty($res['ChapterId'])) {
                            $res = $res['ChapterId'];
                        } else {
                            $cdata['ChapterName'] = $column[2][$k];
                            $cdata['BookId'] = $bookid;
                            $cdata['FromId'] = self::FROM_ID_8;
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
        $log_path = self::$log_content_err_path . self::FROM_ID_8;
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
        $curl = 'http://m.mzhu8.com/modules/article/show.php';
        preg_match('@/book/([\d]+)/([\d]+)(\.html)?$@is', $url, $out);
        $extra['post'] = [
            'aid' => $out[1],
            'cid' => $out[2],
            'r' => mt_rand(1000,9999),
        ];
        $content['content'] = $cc->get($curl,$extra)->getContent();  // @todo

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