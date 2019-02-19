<?php
/**
 * Comment.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-10-13 上午11:29
 *
 */

namespace Controller\Admin\Data;

use Bare\C\AdminController;
use Bare\DB;
use Model\Comment\CommentBase;
use Model\Mobile\AppPush;

class Comment extends AdminController
{
    const TABLE = 'Comment';
    private static $isGood = [
        1 => '一般',
        2 => '优质'
    ];

    public function index()
    {
        $status = $_GET['status'];
        $type = $_GET['type'];
        $userid = intval($_GET['userid']);
        $itemid = intval($_GET['itemid']);
        $start_time = trim($_GET['start_time']);
        $end_time = trim($_GET['end_time']);
        $is_good = trim($_GET['is_good']);

        $where = [];
        if (isset($status)) {
            $where['Status'] = intval($status);
        } else {
            $where['Status'] = 1;
        }
        if (isset($type) && $type != -100) {
            $where['Type'] = intval($type);
        }
        if (!empty($userid)) {
            $where['UserId'] = $userid;
        }
        if (!empty($itemid)) {
            $where['ItemId'] = $itemid;
        }
        if (!empty($is_good)) {
            if ($is_good < 100) {
                $where['IsGood <'] = 100;
            } else {
                $where['IsGood >='] = 100;
            }
        }
        if (!empty($start_time)) {
            $where['CreateTime >='] = $start_time;
        }
        if (!empty($start_time)) {
            $where['CreateTime <='] = $end_time;
        }

        $page = max(1, intval($_GET[PAGE_VAR]));
        $pdo_r = DB::pdo(DB::DB_COMMENT_R);
        $limit = PAGE_SIZE;

        $total = $pdo_r->clear()->select('count(CommentId)')->from(self::TABLE)->where($where)->getValue();

        if ($total > 0) {
            $data = $pdo_r->clear()->select('*')->from(self::TABLE)->where($where)->order('CommentId DESC')->limit(($page - 1) * $limit,
                $limit)->getAll();

            $this->page($total, $limit, $page);

            foreach ($data as $k => $v) {
                if ($v['IsGood'] < 100) {
                    $data[$k]['IsGood'] = self::$isGood[1];
                } else {
                    $data[$k]['IsGood'] = self::$isGood[2];
                }
            }

            $this->value('data', $data);
        }

        /*
         * 来源平台0. Web 2. Android 3. iPhone 1. Wap
         */
        $platform = [APP_TYPE_WEB => 'Web', APP_TYPE_ADR => 'Android', APP_TYPE_IOS => 'iPhone', APP_TYPE_WAP => 'Wap'];

        $types = CommentBase::getCommentTypes();
        $statuss = CommentBase::getRealStatusMap();
        $subcommentcnt = CommentBase::getCountFields();
        $status_color = [
            0 => 'style="color:blue"',
            1 => 'style="color:green"',
            2 => 'style="color:gray"'
        ];

        $this->value('searchdata', [
            'Type' => isset($type) ? $type : -100,
            'Status' => isset($status) ? intval($status) : 1,
            'UserId' => $userid,
            'ItemId' => $itemid,
            'IsGood' => $is_good,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);

        $this->value('status_color', $status_color);
        $this->value('platform', $platform);
        $this->value('type', $types);
        $this->value('status', $statuss);
        $this->value("subcommentcnt", $subcommentcnt);
        $this->view();
    }

    public function delete()
    {
        if (strstr($_POST['id'], ',')) {
            $id = explode(',', $_POST['id']);
        } else {
            $id[] = $_POST['id'];
        }
        $status = CommentBase::REAL_STATUS_DELETED;
        $rs = CommentBase::updateStatusByCommentIds($id, $status);
        if ($rs['code'] == 200) {
            $this->adminLog('修改状态', 'del', $id, $id, self::TABLE);
            output(200, ['title' => '删除成功', 'type' => 'success']);
        }
        output(201, ['title' => '删除失败', 'type' => 'error']);
    }

    /**
     * 优质评论
     */
    public function quality()
    {
        $cash = 100;//奖励的金币
        $comment_id = intval($_POST['comment_id']);//接收评论ID
        $item_id = intval($_POST['item_id']);//接收评论对象的编号
        $user_id = $_POST['user_id'];
        $re = CommentBase::setGoodComment($item_id, $comment_id, $cash);

        if ($re) {
            AppPush::pushByUserId($user_id, AppPush::PUSH_TYPE_MSG, '你的评论被评为优质评论，获得100金币');
            $this->adminLog('优质评论', 'update', $comment_id, $cash, self::TABLE);
            output(200, ['type' => 'success']);
        } else {
            output(201, ['type' => 'error']);
        }
    }

    /**
     * 取消优质评论
     */
    public function unOrdinary()
    {
        $comment_id = intval($_POST['comment_id']);//接收评论ID
        $item_id = intval($_POST['item_id']);//接收评论对象的编号
        $user_id = $_POST['user_id'];

        $pdo = DB::pdo(DB::DB_COMMENT_R);
        $money = $pdo->from('Comment')->select("IsGood")->where(['CommentId' => $comment_id])->getOne();

        $re = CommentBase::cancelGoodComment($item_id, $comment_id);

        if ($re) {
            AppPush::pushByUserId($user_id, AppPush::PUSH_TYPE_MSG, '你的评论被取消优质评论，扣除100金币');
            $this->adminLog('取消优质评论', 'update', $comment_id, $money['IsGood'], self::TABLE);
            output(200, ['type' => 'success']);
        } else {
            output(201, ['type' => 'error']);
        }
    }
}