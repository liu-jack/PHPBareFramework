<?php

namespace Controller\Book;

use Bare\Controller;
use Model\Favorite\BookFavorite;
use Model\Account\User as AUser;
use Model\Mongo\UserData;
use Model\Book\{
    Book, Column, Content
};
use Model\Search\BookSearch as SBook;

/**
 * 书库首页
 */
class Index extends Controller
{
    const FROM_SITE = 77;
    const LIST_LIMIT = 1000;
    const CK_TIME = 31536000;
    // 阅读记录 cookie
    const CK_READ_RECORD = 'READ_RECORD:%d:%d';
    // 今日推荐 cookie
    const CK_BOOK_RECOMMEND = 'BOOK_RECOMMEND:%d';
    // 排行榜
    private static $type_search = [
        101 => SBook::TOP_VIEW,
        102 => SBook::TOP_LIKE,
        103 => SBook::TOP_FINISH,
        104 => SBook::TOP_FAVORITE,
    ];

    /**
     * 首页
     */
    public function index()
    {
        $list_ids1 = SBook::getBookTop(SBook::TOP_VIEW);
        $rmlist = Book::getBookByIds($list_ids1['data'], [Book::EXTRA_COVER => true]);
        $list_ids2 = SBook::getBookTop(SBook::TOP_LIKE);
        $tjlist = Book::getBookByIds($list_ids2['data'], [Book::EXTRA_COVER => true]);
        $list_ids3 = SBook::getBookTop(SBook::TOP_FINISH);
        $wblist = Book::getBookByIds($list_ids3['data'], [Book::EXTRA_COVER => true]);
        $list_ids4 = SBook::getBookTop(SBook::TOP_FAVORITE);
        $sclist = Book::getBookByIds($list_ids4['data'], [Book::EXTRA_COVER => true]);
        $typelist = config('book/types');
        $seo = [
            'title' => '29书集',
            'keywords' => implode('小说,', $typelist) . ',29书集',
            'description' => implode('小说,', $typelist) . ',29书集。'
        ];
        $this->value('seo', $seo);
        $this->value('rmlist', $rmlist);
        $this->value('tjlist', $tjlist);
        $this->value('wblist', $wblist);
        $this->value('sclist', $sclist);
        $this->value('typelist', $typelist);
        $this->value('fromsite', self::FROM_SITE);
        $this->view();
    }

    /**
     * 书本列表
     */
    public function type()
    {
        $tid = intval($_GET['tid']);
        $types = config('book/types');
        $typename = $types[$tid];
        $offset = intval($_GET['offset']);
        $offset = max(0, $offset);
        if ($offset > self::LIST_LIMIT) {
            output(201);
        }
        if ($tid > 100) {
            $list_ids = SBook::getBookTop(self::$type_search[$tid], $offset);
        } else {
            $list_ids = SBook::getBookByTypeName($typename, $offset);
        }
        $tlist = Book::getBookByIds($list_ids['data'], [Book::EXTRA_COVER => true]);
        if ($offset > 0) {
            $data = [
                'list' => array_values($tlist)
            ];
            output(200, $data);
        }
        $seo = [
            'title' => $typename . '_29书集',
            'keywords' => $typename . ',' . implode('小说,', $types) . ',29书集',
            'description' => $typename . ',' . implode('小说,', $types) . ',29书集。'
        ];
        $this->value('seo', $seo);
        $this->value('tlist', $tlist);
        $this->value('typename', $typename);
        $this->value('fromsite', self::FROM_SITE);
        $this->view();
    }

    /**
     * 书本目录
     */
    public function column()
    {
        $fid = intval($_GET['fid']);
        $bid = intval($_GET['bid']);
        $book = Book::getBookByIds($bid, [Book::EXTRA_COVER => true]);
        $fromsite = explode(',', $book['FromSite']);
        $sites = config('book/sites');
        $fromsites = [];
        foreach ($fromsite as $v) {
            if (!empty($sites[$v])) {
                $fromsites[$v] = $sites[$v];
            }
        }
        $columns = Column::getColumns($bid, $fid);
        $columns = !empty($columns['data']) ? $columns['data'] : [];
        $types = config('book/types');
        $rtypes = array_flip($types);
        $rbkey = sprintf(self::CK_READ_RECORD, $fid, $bid);
        $rdbk = !empty($_COOKIE[$rbkey]) ? $_COOKIE[$rbkey] : 0;
        $brkey = sprintf(self::CK_BOOK_RECOMMEND, $bid);
        $isrec = !empty($_COOKIE[$brkey]) ? $_COOKIE[$brkey] : 0;
        $isfav = 0;
        if ($uid = $this->isLogin(V_WEB)) {
            if (BookFavorite::isFavorite($uid, $bid)) {
                $isfav = 1;
            }
            $rdbk1 = intval(Column::getReadRecord($uid, $fid, $bid)); // 获取章节记录
            if ($rdbk1 > $rdbk) {
                $rdbk = $rdbk1;
            }
        }
        // 阅读量
        SBook::setViewCount($bid);
        $seo = [
            'title' => $book['BookName'] . '_29书集',
            'keywords' => $book['BookName'] . '全文阅读,' . $book['BookName'] . '最新章节,' . implode('小说,', $types) . ',29书集',
            'description' => $book['BookName'] . '全文阅读,' . $book['BookName'] . '最新章节,' . implode('小说,',
                    $types) . ',29书集。'
        ];
        $this->value('seo', $seo);
        $this->value('bookid', $bid);
        $this->value('fromsite', $fid);
        $this->value('fromsites', $fromsites);
        $this->value('rtypes', $rtypes);
        $this->value('rdbk', $rdbk);
        $this->value('isrec', $isrec);
        $this->value('isfav', $isfav);
        $this->value('book', $book);
        $this->value('column', $columns);
        $this->view();
    }

    /**
     * 章节详细页
     */
    public function content()
    {
        $fid = intval($_GET['fid']);
        $bid = intval($_GET['bid']);
        $cid = intval($_GET['cid']);
        $book = Book::getBookByIds($bid);
        $column = Column::getColumnById($bid, $cid);
        $content = Content::getContentByChapterId($bid, $cid);
        $prev_next = Column::getPrevNext($fid, $bid, $cid);
        $prev = $prev_next['prev'];
        $next = $prev_next['next'];
        $percent = $prev_next['percent'];
        // 记录章节阅读历史
        $ckey = sprintf(self::CK_READ_RECORD, $fid, $bid);
        setcookie($ckey, $cid, time() + self::CK_TIME, '/');
        if ($uid = $this->isLogin(V_WEB)) {
            Column::setReadRecord($uid, $fid, $bid, $cid);
            if (preg_match('#/[\d]+_[\d]+(\.html)?$#isU', $_SERVER['HTTP_REFERER'])) {
                UserData::userReadBook($uid, $bid); // 记录书本阅读历史
            }
        }

        $seo = [
            'title' => $column['ChapterName'] . '_' . $book['BookName'] . '_29书集',
            'keywords' => $column['ChapterName'] . '_' . $book['BookName'] . '_29书集',
            'description' => $column['ChapterName'] . '_' . $book['BookName'] . '_29书集。'
        ];
        $this->value('seo', $seo);
        $this->value('prev', $prev);
        $this->value('next', $next);
        $this->value('percent', $percent);
        $this->value('bookid', $bid);
        $this->value('fromsite', $fid);
        $this->value('book', $book);
        $this->value('column', $column);
        $this->value('content', $content);
        $this->view();
    }

    /**
     * 搜索书本
     */
    public function search()
    {
        $str = trim($_GET['str']);
        $offset = intval($_GET['offset']);
        $offset = max(0, $offset);
        if ($offset > self::LIST_LIMIT) {
            output(201);
        }
        $list_ids = SBook::searchBook($str, $offset);
        $list_info = [];
        if (!empty($list_ids['data'])) {
            $list_info = Book::getBookByIds($list_ids['data'], [Book::EXTRA_COVER => true]);
        }
        if ($offset > 0) {
            $data = [
                'list' => array_values($list_info)
            ];
            output(200, $data);
        }
        $seo = [
            'title' => $str . '_搜索结果-29书集',
            'keywords' => $str . '_搜索结果-29书集',
            'description' => $str . '_搜索结果-29书集。'
        ];
        $this->value('seo', $seo);
        $this->value('str', $str);
        $this->value('list', $list_info);
        $this->value('fromsite', self::FROM_SITE);
        $this->view();
    }

    /**
     * 推荐书本
     */
    public function recommend()
    {
        $bid = intval($_POST['bid']);
        if ($bid > 0) {
            $rkey = sprintf(self::CK_BOOK_RECOMMEND, $bid);
            if (empty($_COOKIE[$rkey])) {
                $ret = SBook::setLikeCount($bid);
                if ($ret) {
                    setcookie($rkey, 1, strtotime(date('Y-m-d 23:59:59')), '/');
                    output(200, '推荐成功！');
                } else {
                    output(202, '推荐失败！');
                }
            } else {
                output(201, '今日已推荐！');
            }
        }
    }

    /**
     * 我的书架
     */
    public function shelf()
    {
        $uid = $this->isLogin(V_WEB);
        if (empty($uid)) {
            redirect(url('account/user/login', '', true));
        }
        $offset = intval($_GET['offset']);
        $offset = max(0, $offset);
        if ($offset > self::LIST_LIMIT) {
            output(201);
        }
        $list_ids = BookFavorite::getItemsByUserId($uid, $offset);
        $total = $list_ids['total'];
        $list = Book::getBookByIds($list_ids['data'], [Book::EXTRA_COVER => true]);
        if ($offset > 0) {
            $data = [
                'list' => array_values($list)
            ];
            output(200, $data);
        }
        $typelist = config('book/types');
        $seo = [
            'title' => '我的书架-29书集',
            'keywords' => implode('小说,', $typelist) . ',29书集',
            'description' => implode('小说,', $typelist) . ',29书集。'
        ];
        $usernick = AUser::getNickByUserId($uid);
        $this->value('seo', $seo);
        $this->value('userid', $uid);
        $this->value('username', !empty($usernick['UserName']) ? $usernick['UserName'] : '');
        $this->value('list', $list);
        $this->value('total', $total);
        $this->value('typelist', $typelist);
        $this->value('fromsite', self::FROM_SITE);
        $this->view();
    }

    /**
     * 我的书架
     */
    public function history()
    {
        $uid = $this->isLogin(V_WEB);
        if (empty($uid)) {
            redirect(url('account/user/login', '', true));
        }
        $offset = intval($_GET['offset']);
        $offset = max(0, $offset);
        if ($offset > self::LIST_LIMIT) {
            output(201);
        }
        $list_info = UserData::getUserData($uid);
        $list_ids = [];
        $total = 0;
        if (!empty($list_info[UserData::FIELD_BOOK_READ_HISTORY])) {
            $list_ids = $list_info[UserData::FIELD_BOOK_READ_HISTORY];
            $list_ids = array_reverse($list_ids);
            $total = count($list_ids);
            $list_ids = array_slice($list_ids, $offset, 10);
        }
        $list = Book::getBookByIds($list_ids, [Book::EXTRA_COVER => true]);
        if ($offset > 0) {
            $data = [
                'list' => array_values($list)
            ];
            output(200, $data);
        }
        $typelist = config('book/types');
        $seo = [
            'title' => '阅读记录-29书集',
            'keywords' => implode('小说,', $typelist) . ',29书集',
            'description' => implode('小说,', $typelist) . ',29书集。'
        ];
        $usernick = AUser::getNickByUserId($uid);
        $this->value('seo', $seo);
        $this->value('userid', $uid);
        $this->value('username', !empty($usernick['UserName']) ? $usernick['UserName'] : '');
        $this->value('list', $list);
        $this->value('total', $total);
        $this->value('typelist', $typelist);
        $this->value('fromsite', self::FROM_SITE);
        $this->view();
    }

    /**
     * 收藏书本
     */
    public function favorite()
    {
        $uid = $this->isLogin(V_WEB);
        if (empty($uid)) {
            output(551, ['url' => url('account/user/login')]);
        }
        $bid = intval($_POST['bid']);
        if (BookFavorite::isFavorite($uid, $bid)) {
            $ret = BookFavorite::remove($uid, $bid);
            if ($ret) {
                output(201, '取消成功！');
            } else {
                output(202, '取消失败！');
            }
        } else {
            $ret = BookFavorite::add($uid, $bid);
            if ($ret) {
                output(200, '收藏成功！');
            } else {
                output(202, '收藏失败！');
            }
        }
    }
}
