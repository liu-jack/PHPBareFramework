<?php
/**
 * Comment.class.php
 *
 */

namespace Controller\Api\Common;

use Bare\Controller;

/**
 * 公用服务 - 评论 系统
 *
 * @author  周剑锋 <camfee@foxmail.com>
 * @date    2017-01-12 v1.0.0
 *
 */
class Comment extends Controller
{
    /**
     * 允许类型
     *
     * @ignore
     * @var array
     */
    private $allow_type = [
        1 => 'Comment\ArticleComment',
    ];

    /**
     * 评论 某个对象
     *
     * <pre>
     * POST:
     *     itemid:  必选, 对象ID,
     *     type:    必选, 类型, 1:文章(视频）评论
     *     content: 必选, 评论内容,最多2000
     * </pre>
     *
     * @return void|string 返回JSON数据
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {
     *         "CommentId": 17, // 评论ID
     *         "UserId": 20, // 发布评论用户ID
     *         "UserNick": "小五", // 发布评论用户昵称
     *         "UserAvatar": "http://img-head0.qbaobeiapp.com/14/00/20_100.jpg?v=22", // 头像
     *     }
     * }
     *
     * 异常状态
     * 201: 分类不存在
     * 202: 评论内容不能为空
     * 203: 缺少评论对象
     * 204: 评论的对象不存在或已经被删除
     * 205: 评论失败, 请稍后重试
     * 206: 评论内容包含非法关键字
     * </pre>
     */
    public function add()
    {
        $uid = $this->isLogin(true);
        $type = (int)$_POST['type'];
        if ($type == 1) {
            $itemid = decode_id(trim($_POST['itemid']));
        } else {
            $itemid = (int)$_POST['itemid'];
        }
        $content = trim((string)$_POST['content']);

        if (!isset($this->allow_type[$type])) {
            $this->output(201, '分类不存在');
        }

        if ($content == '') {
            $this->output(202, '评论内容不能为空');
        }
        $content = substr($content, 0, 2000);

        if (Filter::fastCheck($content)) {
            $this->output(206, '评论内容包含非法关键字');
        }

        if (empty($itemid)) {
            $this->output(203, '缺少评论对象');
        }

        $exist = false;
        $class = $this->allow_type[$type];
        switch ($type) {
            case 1:
                $class_type = $class::TYPE_ARTICLE;
                $data = Article::getArticleById($itemid);
                $exist = isset($data['ArticleId']) && $itemid == $data['ArticleId'] && $data['Status'] == 1;
                break;
            default:
                break;
        }

        if ($exist != true) {
            $this->output(204, '评论的对象不存在或已经被删除');
        }

        $comment = $class::getEntity($class_type, $itemid);

        $ret = $comment->post([
            'UserId' => $uid,
            'Content' => $content,
            'Platform' => $GLOBALS['g_appid'] == 50 ? 1 : 2,
            'ReplyId' => 0
        ]);

        if ($ret['code'] == $class::RET_CODE_SUCC) {
            $users = CUser::getNickByUserId($uid, 100);
            $data = [
                'CommentId' => (int)$ret['id'],
                'UserId' => (int)$uid,
                'UserNick' => !empty($users['UserNick']) ? $users['UserNick'] : (string)$ret[$uid]['nick'],
                'UserAvatar' => !empty($users['AvatarUrl']) ? $users['AvatarUrl'] : head($uid),
                'Content' => $content,
                'CreateTime' => DataFormat::datetime(date('Y-m-d H:i:s')),
                'LikeCount' => 0,
            ];

            $this->output(200, $data);
        }

        $this->output(205, '评论失败, 请稍后重试');
    }

    /**
     * 评论点赞
     *
     * <pre>
     * POST:
     *     commentid:  必选, 评论ID
     * </pre>
     *
     * @return void|string 返回JSON数据
     *
     * <pre>
     * {
     *     "Status": 200,
     *     "Result": {}
     * }
     *
     * 异常状态
     * 201: 参数错误
     *
     * </pre>
     */
    public function setLike()
    {
        $commentid = (int)$_POST['commentid'];
        if ($commentid < 1) {
            $this->output(201, '参数错误');
        }

        $res = Digg::addDigg(Digg::DIGG_TYPE_COMMENT, $commentid);
        if ($res['status'] == 200) {
            $this->output(200);
        } else {
            $this->output($res['status'], $res['msg']);
        }

    }

    /**
     * 评论列表
     *
     * <pre>
     * GET:
     *     itemid:  必选, 对象ID,
     *     type:    必选, 类型,    1:文章(视频）评论
     *     offset:  可选，偏移量   默认0
     *     limit:   可选，每页个数  默认10 最大50
     * </pre>
     *
     * @return void|string 返回JSON数据
     *
     * <pre>
     *
     * {
     *   "Status": 200,
     *   "Result": {
     *     "HotList": [ //热门评论  offset=0时返回
     *       {
     *         "CommentId": 7,                       // 评论ID
     *         "UserId": 1500,                      // 发布评论用户ID
     *         "UserNick": "camfee",                // 发布评论用户昵称
     *         "UserAvatar": "ht00.jpg",            // 头像
     *         "Content": "testssssss",             // 评论内容
     *         "CreateTime": "2017-02-14 01:21:25", // 评论时间
     *         "LikeCount": 4                        // 点赞数
     *       },...
     *     ],
     *     "Total": 6,
     *     "List": [ //最新评论
     *       {
     *         "CommentId": 7,                       // 评论ID
     *         "UserId": 1500,                      // 发布评论用户ID
     *         "UserNick": "camfee",                // 发布评论用户昵称
     *         "UserAvatar": "ht00.jpg",            // 头像
     *         "Content": "testssssss",             // 评论内容
     *         "CreateTime": "2017-02-14 01:21:25", // 评论时间
     *         "LikeCount": 4
     *       },...
     *     ]
     *   }
     * }
     *
     * 异常状态
     * 201: 分类不存在
     * 202: 评论内容不能为空
     * 203: 缺少评论对象
     * </pre>
     */
    public function getList()
    {
        $type = (int)$_GET['type'];
        if ($type == 1) {
            $itemid = decode_id(trim($_GET['itemid']));
        } else {
            $itemid = (int)$_GET['itemid'];
        }
        $offset = (int)$_GET['offset'];
        $limit = (int)$_GET['limit'];
        $offset = max(0, $offset);
        if ($limit <= 0) {
            $limit = 10;
        }
        $limit = min(50, $limit);

        if (!isset($this->allow_type[$type])) {
            $this->output(201, '分类不存在');
        }
        if (empty($itemid)) {
            $this->output(202, '缺少对象');
        }

        $class = $this->allow_type[$type];
        switch ($type) {
            case 1:
                $class_type = $class::TYPE_ARTICLE;
                break;
            default:
                break;
        }

        $comment = $class::getEntity($class_type, $itemid);
        if ($offset == 0) {
            $data['HotList'] = [];
            $hotids = Digg::getHotComment($itemid);
            if (!empty($hotids)) {
                $hotlist = $comment->getCommentsByIds(array_keys($hotids));
                if (!empty($hotlist)) {
                    $uids = [];
                    foreach ($hotlist as $v) {
                        $uids[$v['UserId']] = $v['UserId'];
                    }
                    $users = CUser::getNickByUserId($uids, 100);
                    foreach ($hotlist as $v) {
                        $data['HotList'][] = [
                            'CommentId' => (int)$v['CommentId'],
                            'UserId' => (int)$v['UserId'],
                            'UserNick' => !empty($users[$v['UserId']]['UserNick']) ? $users[$v['UserId']]['UserNick'] : '',
                            'UserAvatar' => !empty($users[$v['UserId']]['AvatarUrl']) ? $users[$v['UserId']]['AvatarUrl'] : head($v['UserId']),
                            'Content' => $v['Content'],
                            'CreateTime' => DataFormat::datetime($v['CreateTime']),
                            'LikeCount' => (int)$hotids[$v['CommentId']],
                        ];
                    }
                    if (count($data['HotList']) > 5) {
                        $data['HotList'] = array_slice($data['HotList'], 0, 5);
                    }
                }
            }
        }
        $ret = $comment->getList([
            $class::EXTRA_SORT_ORDER => $class::SORT_ORDER_DESC,
            $class::EXTRA_OFFSET => $offset,
            $class::EXTRA_LIMIT => $limit,
        ]);
        $data['Total'] = $ret['total'];
        $data['List'] = [];
        if (!empty($ret['data'])) {
            $idcount = Digg::getCommentByItemId($itemid);
            $uids = [];
            foreach ($ret['data'] as $v) {
                $uids[$v['UserId']] = $v['UserId'];
            }
            $users = CUser::getNickByUserId($uids, 100);

            foreach ($ret['data'] as $v) {
                $data['List'][] = [
                    'CommentId' => (int)$v['CommentId'],
                    'UserId' => (int)$v['UserId'],
                    'UserNick' => !empty($users[$v['UserId']]['UserNick']) ? $users[$v['UserId']]['UserNick'] : '',
                    'UserAvatar' => !empty($users[$v['UserId']]['AvatarUrl']) ? $users[$v['UserId']]['AvatarUrl'] : head($v['UserId']),
                    'Content' => $v['Content'],
                    'CreateTime' => DataFormat::datetime($v['CreateTime']),
                    'LikeCount' => (int)$idcount[$v['CommentId']],
                ];
            }
        }

        $this->output(200, $data);
    }
}