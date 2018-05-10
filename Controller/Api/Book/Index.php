<?php
/**
 * 书本信息相关接口
 *
 * @author camfee<camfee@foxmail.com>
 */

namespace Controller\Api\Book;

use Bare\C\Controller;
use Model\Book\Book;
use Model\Book\Column;
use Model\Book\Content;
use Model\Favorite\BookFavorite;
use Model\Account\User as AUser;
use Model\Search\BookSearch as SBook;

/**
 * 书本信息相关接口
 *
 * @package Book
 * @author  camfee<camfee@foxmail.com>
 * @date    2017-07-21 14:55
 *
 */
class Index extends Controller
{
    const FROM_ID = 77;
    // 排行榜
    private static $type_search = [
        101 => SBook::TOP_VIEW,
        102 => SBook::TOP_LIKE,
        103 => SBook::TOP_FINISH,
        104 => SBook::TOP_FAVORITE,
    ];

    /**
     * 获取29shu书本首页数据
     *
     * <pre>
     * GET:
     *     无参数
     * </pre>
     *
     * @return void|string 返回JSON数组
     *
     * <pre>
     * {
     *   "Code": 200,
     *   "Data": {
     *     "HotList": { // 热门
     *       {
     *         "BookId": "31",
     *         "BookName": "官途",
     *         "Author": "梦入洪荒",
     *         "Type": "0",
     *         "TypeName": "玄幻魔法",
     *         "Cover": "/Public/upload/cover/1f/1f/31.jpg?v=0",
     *         "BookDesc": "刚刚大学毕业的刘飞是个嚣里的家伙……",
     *         "Words": "24915",
     *         "ViewCount": "4",
     *         "LikeCount": "2",
     *         "FavoriteCount": "0",
     *         "CreateTime": "1483619270",
     *         "UpdateTime": "1467692040",
     *         "Status": "1",
     *         "IsFinish": "0",
     *         "FromSite": "77"
     *       },...
     *     },
     *     "RecommendList": { // 推荐
     *       {
     *         "BookId": "31",
     *         "BookName": "官途",
     *         "Author": "梦入洪荒",
     *         "Type": "0",
     *         "TypeName": "玄幻魔法",
     *         "Cover": "/Public/upload/cover/1f/1f/31.jpg?v=0",
     *         "BookDesc": "刚刚大学毕业的刘",
     *         "Words": "24915",
     *         "ViewCount": "4",
     *         "LikeCount": "2",
     *         "FavoriteCount": "0",
     *         "CreateTime": "1483619270",
     *         "UpdateTime": "1467692040",
     *         "Status": "1",
     *         "IsFinish": "0",
     *         "FromSite": "77"
     *       },...
     *     },
     *     "FinishList": { // 完本
     *       {
     *         "BookId": "1044",
     *         "BookName": "华娱之闪耀巨星",
     *         "Author": "万乘北宸",
     *         "Type": "0",
     *         "TypeName": "都市小说",
     *         "Cover": "/Public/upload/cover/14/18/1044.jpg?v=0",
     *         "BookDesc": "    重回2001，聂唯要做娱乐圈最闪耀的那颗星。",
     *         "Words": "4351",
     *         "ViewCount": "0",
     *         "LikeCount": "0",
     *         "FavoriteCount": "0",
     *         "CreateTime": "1483619371",
     *         "UpdateTime": "1495062660",
     *         "Status": "1",
     *         "IsFinish": "1",
     *         "FromSite": "77"
     *       },...
     *     },
     *     "FavoriteList": { // 收藏
     *       {
     *         "BookId": "41",
     *         "BookName": "校花的贴身高手",
     *         "Author": "鱼人二代",
     *         "Type": "0",
     *         "TypeName": "女生小说",
     *         "Cover": "/Public/upload/cover/29/29/41.jpg?v=0",
     *         "BookDesc": "一个大山里走出来的绝世高手，一块能预知未来的神秘玉佩……",
     *         "Words": "35931",
     *         "ViewCount": "23",
     *         "LikeCount": "7",
     *         "FavoriteCount": "1",
     *         "CreateTime": "1483619271",
     *         "UpdateTime": "1495030260",
     *         "Status": "1",
     *         "IsFinish": "0",
     *         "FromSite": "77"
     *       },...
     *     },
     *     "TypeList": { // 分类
     *       "1": "都市小说",
     *       "2": "网游动漫",
     *       "3": "科幻灵异",
     *       "4": "玄幻魔法",
     *       "5": "武侠修真",
     *       "6": "女生小说",
     *       "7": "历史军事",
     *       "8": "其它小说",
     *       "101": "热门榜",
     *       "102": "推荐榜",
     *       "103": "完本榜",
     *       "104": "收藏榜"
     *     }
     *   }
     * }
     * </pre>
     */
    public function getIndex()
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
        $data = [
            'HotList' => !empty($rmlist) ? array_values($rmlist) : [],
            'RecommendList' => !empty($tjlist) ? array_values($tjlist) : [],
            'FinishList' => !empty($wblist) ? array_values($wblist) : [],
            'FavoriteList' => !empty($sclist) ? array_values($sclist) : [],
            'TypeList' => !empty($typelist) ? array_values($typelist) : [],
        ];
        $this->output(200, $data);
    }

    /**
     * 获取书本列表
     *
     * <pre>
     *  GET:
     *      tid:    必选，书本类别ID
     *      offset: 可选，偏移量, 默认0
     *      limit:  可选，每页条数，默认10
     * </pre>
     *
     * @return void|string 返回json数据
     *
     * <pre>
     * {
     *   "Code": 200,
     *   "Data": {
     *     "Total": 2427,
     *     "List": {
     *       {
     *         "BookId": "265",
     *         "BookName": "重生之资源大亨",
     *         "Author": "月下的孤狼",
     *         "Type": "0",
     *         "TypeName": "都市小说",
     *         "Cover": "/Public/upload/cover/09/0a/265.jpg?v=0",
     *         "BookDesc": "这是一本以重生为噱头的都市架空yy小说",
     *         "Words": "96393",
     *         "ViewCount": "0",
     *         "LikeCount": "0",
     *         "FavoriteCount": "0",
     *         "CreateTime": "1483619288",
     *         "UpdateTime": "1495042740",
     *         "Status": "1",
     *         "IsFinish": "0",
     *         "FromSite": "77"
     *       },...
     *     },
     *     "TypeId": 1,
     *     "TypeName": "都市小说"
     *   }
     * }
     *
     * 异常状态
     * 201:类型ID错误
     *
     * </pre>
     */
    public function getList()
    {
        $tid = intval($_GET['tid']);
        $offset = intval($_GET['offset']);
        $offset = max(0, $offset);
        $limit = !empty($_GET['limit']) ? intval($_GET['limit']) : 10;
        $limit = min(50, max(1, $limit));
        $types = config('book/types');
        if (!isset($types[$tid])) {
            $this->output(201, '类型ID错误');
        }
        $typename = $types[$tid];

        if ($tid > 100) {
            $list_ids = SBook::getBookTop(self::$type_search[$tid], $offset, $limit);
        } else {
            $list_ids = SBook::getBookByTypeName($typename, $offset, $limit);
        }
        $tlist = Book::getBookByIds($list_ids['data'], [Book::EXTRA_COVER => true]);
        $data = [
            'Total' => intval($list_ids['total']),
            'List' => !empty($tlist) ? array_values($tlist) : [],
            'TypeId' => $tid,
            'TypeName' => $typename,
        ];
        $this->output(200, $data);
    }

    /**
     * 获取书本章节列表
     *
     * <pre>
     *  GET:
     *      bid: 必选，书本ID
     *      fid: 可选，来源ID，默认77 （77：小说77 83：83中文）
     * </pre>
     *
     * @return void|string 返回json数据
     *
     * <pre>
     * {
     *   "Code": 200,
     *   "Data": {
     *     "BookId": "258",
     *     "BookName": "最强弃少-鹅是老五",
     *     "Author": "鹅是老五",
     *     "Type": 1,
     *     "TypeName": "都市小说",
     *     "Cover": "/Public/upload/cover/02/03/258.jpg?v=0",
     *     "BookDesc": "重生到地球，叶默趁着下课的时候，",
     *     "Words": "12645",
     *     "ViewCount": "94",
     *     "LikeCount": "6",
     *     "FavoriteCount": "1",
     *     "CreateTime": "1483619288",
     *     "UpdateTime": "1480168860",
     *     "Status": "1",
     *     "IsFinish": "2",
     *     "FromSite": {
     *       "77": "小说77",
     *       "83": "83中文"
     *     },
     *     "CurFromId": 77,
     *     "Column": [
     *       {
     *         "ChapterId": "30",
     *         "ChapterName": "楔子"
     *       },...
     *     ],
     *     "ReadRecord": 0,
     *     "IsRecommend": 0,
     *     "IsFavorite": 0
     *   }
     * }
     *
     * 异常状态
     * 201：书本ID错误
     * 202：书本不存在或已被删除
     * </pre>
     */
    public function getColumn()
    {
        $fid = !empty($_GET['fid']) ? intval($_GET['fid']) : self::FROM_ID;
        $bid = intval($_GET['bid']);
        if ($bid < 1) {
            $this->output(201, '书本ID错误');
        }
        $book = Book::getBookByIds($bid, [Book::EXTRA_COVER => true]);
        if (empty($book)) {
            $this->output(202, '书本不存在或已被删除');
        }
        $fromsite = explode(',', $book['FromSite']);
        $sites = config('book/sites');
        $fromsites = [];
        foreach ($fromsite as $v) {
            if (!empty($sites[$v])) {
                $fromsites[$v] = $sites[$v];
            }
        }
        $book['FromSite'] = $fromsites;
        $book['CurFromId'] = $fid;
        $columns = Column::getColumns($bid, $fid);
        $book['Column'] = !empty($columns['data']) ? $columns['data'] : [];
        $types = config('book/types');
        $rtypes = array_flip($types);
        $book['Type'] = $rtypes[$book['TypeName']];
        $read_record = 0;
        $book_recommend = 0;
        $isfav = 0;
        if ($uid = $this->isLogin()) {
            $read_record = Column::getReadRecord($uid, $fid, $bid);
            $book_recommend = Column::getRecom($uid, $bid);
            if (BookFavorite::isFavorite($uid, $bid)) {
                $isfav = 1;
            }
        }
        $book['ReadRecord'] = $read_record;
        $book['IsRecommend'] = $book_recommend;
        $book['IsFavorite'] = $isfav;
        // 阅读量
        SBook::setViewCount($bid);

        $this->output(200, $book);
    }

    /**
     * 获取书本章节内容
     *
     * <pre>
     *  GET:
     *      bid: 必选，书本ID
     *      cid：必选，章节ID
     *      fid: 可选，来源ID，默认77 （77：小说77 83：83中文）
     * </pre>
     *
     * @return void|string 返回json数据
     *
     * <pre>
     * {
     *   "Code": 200,
     *   "Data": {
     *     "BookId": "258",
     *     "BookName": "最强弃少-鹅是老五",
     *     "Author": "鹅是老五",
     *     "Type": "0",
     *     "TypeName": "都市小说",
     *     "Cover": "0",
     *     "BookDesc": "重生到地球，叶默趁着下课的时候，急匆匆的跑进一个没人的小胡同，",
     *     "Words": "12645",
     *     "ViewCount": "98",
     *     "LikeCount": "6",
     *     "FavoriteCount": "1",
     *     "CreateTime": "1483619288",
     *     "UpdateTime": "1480168860",
     *     "Status": "1",
     *     "IsFinish": "2",
     *     "FromSite": "77,83",
     *     "Column": {
     *       "ChapterId": "30",
     *       "BookId": "258",
     *       "ChapterName": "楔子",
     *       "FromId": "77",
     *       "Url": "http://www.xiaoshuo77.com/view/11/11227/2659721.html"
     *     },
     *     "Content": {
     *       "ContentId": "30",
     *       "ChapterId": "30",
     *       "Content": "燕京，所有的人口加起来数千万，华夏的第一大都市。"
     *     },
     *     "Prev": 0,
     *     "Next": 31,
     *     "Percent": "0.05%",
     *     "CurFromId": 77
     *   }
     * }
     *
     * 异常状态
     * 201：书本ID错误
     * 202：章节ID错误
     * 203：书本不存在或已被删除
     *
     * </pre>
     */
    public function getContent()
    {
        $fid = !empty($_GET['fid']) ? intval($_GET['fid']) : self::FROM_ID;
        $bid = intval($_GET['bid']);
        $cid = intval($_GET['cid']);
        if ($bid < 1) {
            $this->output(201, '书本ID错误');
        }
        if ($cid < 1) {
            $this->output(202, '章节ID错误');
        }
        $book = Book::getBookByIds($bid);
        if (empty($book)) {
            $this->output(203, '书本不存在或已被删除');
        }
        $column = Column::getColumnById($bid, $cid);
        $content = Content::getContentByChapterId($bid, $cid);
        $prev_next = Column::getPrevNext($fid, $bid, $cid);
        if ($uid = $this->isLogin()) {
            Column::setReadRecord($uid, $fid, $bid, $cid);
        }
        $prev = $prev_next['prev'];
        $next = $prev_next['next'];
        $percent = $prev_next['percent'];
        $book['Column'] = $column;
        $book['Content'] = $content;
        $book['Prev'] = $prev;
        $book['Next'] = $next;
        $book['Percent'] = $percent;
        $book['CurFromId'] = $fid;

        $this->output(200, $book);
    }

    /**
     * 搜索书本
     *
     * <pre>
     *  GET:
     *      str:    必选，搜索关键词
     *      offset：可选，偏移量，默认0
     *      limit:  可选，每页条数，默认10
     * </pre>
     *
     * @return void|string 返回json数据
     *
     * <pre>
     * {
     *   "Code": 200,
     *   "Data": {
     *     "Total": 2427,
     *     "List": {
     *       {
     *         "BookId": "30",
     *         "BookName": "凡人修仙传",
     *         "Author": "忘语",
     *         "Type": "0",
     *         "TypeName": "武侠修真",
     *         "Cover": "http://29shu.iok.la/Public/upload/cover/1e/1e/30.jpg?v=0",
     *         "BookDesc": "一个普通的山村穷小子，偶然之下，进入到当地的江湖小门派",
     *         "Words": "10021",
     *         "ViewCount": "2",
     *         "LikeCount": "1",
     *         "FavoriteCount": "0",
     *         "CreateTime": "1483619270",
     *         "UpdateTime": "1477714980",
     *         "Status": "1",
     *         "IsFinish": "1",
     *         "FromSite": "77"
     *       },...
     *     }
     *   }
     * }
     *
     * 异常状态
     * 201：搜索关键词不能为空！
     *
     * </pre>
     */
    public function search()
    {
        $str = trim($_GET['str']);
        if (empty($str)) {
            $this->output(201, '搜索关键词不能为空！');
        }
        $offset = intval($_GET['offset']);
        $offset = max(0, $offset);
        $limit = !empty($_GET['limit']) ? intval($_GET['limit']) : 10;
        $limit = min(50, max(1, $limit));
        $list_ids = SBook::searchBook($str, $offset, $limit);
        $list_info = [];
        if (!empty($list_ids['data'])) {
            $list_info = Book::getBookByIds($list_ids['data'], [Book::EXTRA_COVER => true]);
        }
        $data['Total'] = intval($list_ids['total']);
        $data['List'] = array_values($list_info);
        $this->output(200, $data);
    }

    /**
     * 推荐书本
     *
     * <pre>
     *  POST:
     *      bid:    必选，推荐书本ID
     * </pre>
     *
     * @author     camfee<camfee@foxmail.com>
     * @date       2017-07-21 14:46
     * @return void|string 返回json数据
     *
     * <pre>
     * {
     *   "Code": 200,
     *   "Msg": "推荐成功！"
     * }
     *
     * 异常状态
     * 201：参数bid错误！
     * 202：推荐失败！
     *
     * </pre>
     */
    public function recommend()
    {
        $bid = intval($_POST['bid']);
        if ($bid < 0) {
            $this->output(201, '参数bid错误！');
        }
        $ret = SBook::setLikeCount($bid);
        if ($ret) {
            $this->output(200, '推荐成功！');
        } else {
            $this->output(202, '推荐失败！');
        }
    }
}