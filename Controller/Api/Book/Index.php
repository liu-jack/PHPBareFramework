<?php

namespace Controller\Api\Book;

use Bare\Controller;
use Model\Book\{
    Book, Column, Content
};
use Model\Favorite\BookFavorite;
use Model\Account\User as AUser;
use Model\Search\BookSearch as SBook;

/**
 * 书本信息相关接口
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017-07-21 14:55
 *
 */
class Index extends Controller
{
    const FROM_ID = 77;
    // 排行榜
    private static $type_seach = [
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
     *         "BookDesc": "刚刚大学毕业的刘飞是个嚣张到骨子里的家伙，费尽周折考上了公务员，却在入职当天，将顶头上司暴打一顿…… 他这样的性格适合当官吗？不适合是吧！ 但",
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
     *         "BookDesc": "刚刚大学毕业的刘飞是个嚣张到骨子里的家伙，费尽周折考上了公务员，却在入职当天，将顶头上司暴打一顿…… 他这样的性格适合当官吗？不适合是吧！ 但",
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
     *         "BookDesc": "       重回2001，聂唯要做娱乐圈最闪耀的那颗星。 &gt;/p&lt;",
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
     *         "BookDesc": "一个大山里走出来的绝世高手，一块能预知未来的神秘玉佩…… 林逸是一名普通的高三学生，不过，他还有身负另外一个重任，那就是泡校花！而且还是奉校花老爸之命！ 虽然林逸很不想跟这",
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
     *         "BookDesc": "这是一本以重生为噱头的都市架空yy小说。好吧，理解成平行位面的相似世界也可以，希望读者们能够因此而找到似是而非的过去的人生，也只是似是而非而已！故事纯属虚构，如有现实雷同，",
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
            $list_ids = SBook::getBookTop(self::$type_seach[$tid], $offset, $limit);
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
     *     "BookDesc": "重生到地球，叶默趁着下课的时候，急匆匆的跑进一个没人的小胡同，第一件事就是扒下自己的裤子，其实他只是想查看一下自己的小鸡鸡而已......",
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
        if ($uid = $this->isLogin(1)) {
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
     *     "BookDesc": "重生到地球，叶默趁着下课的时候，急匆匆的跑进一个没人的小胡同，第一件事就是扒下自己的裤子，其实他只是想查看一下自己的小鸡鸡而已......",
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
     *       "Content": "&nbsp;&nbsp;&nbsp;&nbsp;燕京，所有的人口加起来数千万，华夏的第一大都市。如果站在燕京的某一栋大厦顶端向下望去，全是看不到边的火柴盒，一栋栋或高或矮的高楼大厦密密麻麻的挤在一起。<br />\r\n<br />\r\n&nbsp;&nbsp;&nbsp;&nbsp;此时，燕京的宁氏药材总部大厦的顶端正站着两名女子，一个身穿白色的绒裙，脸上表情平静，如果仔细看去，就会发现这居然是一名绝美的少女。此时她的衣裙被微风带起，有一些波动，配合她那一副绝美的容颜，简直犹如九天仙子一般，让人只敢远观，哪怕走近了一点，都感觉到是在亵渎她。<br />\r\n<br />\r\n&nbsp;&nbsp;&nbsp;&nbsp;“轻雪，你真要嫁给那个，那个天……什么的叶默吗？”说话的是站在她身边的一名身穿红色衣裙的女子，虽然也是一个长相娇媚的女子，不过和那名白衣裙的女子比起来还是逊色不少。<br />\r\n<br />\r\n&nbsp;&nbsp;&nbsp;&nbsp;白色衣裙的少女眼睛盯着远方不计其数的高楼大厦，还有那些只有蚂蚁大小的汽车人流，默默无语。似乎一切都和她无关一般。<br />\r\n<br />\r\n&nbsp;&nbsp;&nbsp;&nbsp;红衣少女叹了口气说道：“轻雪，我知道你根本不想嫁给那个废人叶默，可以说这个世界又有谁可以配的上你呢。虽然我知道，你在那个聚会上说要嫁给叶默的话，也不过是个气话而已，或者说是拿那个废人做个挡箭牌而已。<br />\r\n<br />\r\n&nbsp;&nbsp;&nbsp;&nbsp;不过轻雪，像我们这种身份的女子，婚姻大事早就不是我们自己可以做主的了。挡了一次，还有许多次，如果每次都拿那个废人来挡住，以京城那几个公子的手段，说不定会让那个废人永远消失，这样你就再也没有借口了。”<br />\r\n<br />\r\n&nbsp;&nbsp;&nbsp;&nbsp;白群女子皱了皱眉，说道：“我没有让别人去杀他，况且他的死活和我有何关系，从我和他定亲时，我就不知道他长的是圆的还是扁的。如果他真的被杀了，也只能怪他自己吧。慕枚，你不是说他现在连生活都很困难吗？<br />\r\n<br />\r\n&nbsp;&nbsp;&nbsp;&nbsp;你拿个一百万给他吧，就当着我拿他当挡箭牌的报酬好了，以后不要再提起这个人了，我是我，他是他，我们没有任何关系。”<br />\r\n<br />\r\n&nbsp;&nbsp;&nbsp;&nbsp;“轻雪，你拿一百万，你是让他死的快点，你知道他是个什么人吗？一个纨绔却没有资格去纨绔的世家弃子，有了一百万，还不立即嚣张的满大街都知道啊，我看还是拿个两万吧。”叫慕枚的女子连忙说道。<br />\r\n<br />\r\n&nbsp;&nbsp;&nbsp;&nbsp;“好吧，你去办吧，我不想再烦这些事情。”白衣裙的女子说完这句话后，再也懒得说一个字，只是愣愣的盯着远处的天空，不知道她在想些什么。<br />\r\n<br />\r\n&nbsp;&nbsp;&nbsp;&nbsp;（老五的都市新书《最强弃少》，已经发书了，求推荐票和收藏啊！！！拜托各位新老朋友了。）"
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
        if ($uid = $this->isLogin(1)) {
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
     *         "BookDesc": "一个普通的山村穷小子，偶然之下，进入到当地的江湖小门派，成了一名记名弟子。他以这样的身份，如何在门派中立足？又如何以平庸的资质，进入到修仙者的行列？和其他巨枭魔头，仙宗仙",
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
     * @author camfee<camfee@foxmail.com>
     * @date 2017-07-21 14:46
     * @deprecated since v1.3.0
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