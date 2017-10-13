<?php
/**
 * Comment.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-10-13 上午11:29
 *
 */

namespace Controller\Admin\Data;

use Bare\AdminController;

class Comment extends AdminController
{
    public function index()
    {

    }

    const TABLE = 'Comment';
    private static $isGood = [
        1 => '一般',
        2 => '优质'
    ];

    public function doDefault()
    {
        $smarty = $this->app->page();
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

        $page = max(1, intval($_GET['page']));
        $pdo_r = Bridge::pdo(Bridge::DB_COMMENT_R);

        $total = $pdo_r->clear()->select('count(CommentId)')->from(self::TABLE)->where($where)->getValue();

        if ($total > 0) {
            $data = $pdo_r->clear()->select('*')->from(self::TABLE)->where($where)->order('CommentId DESC')
                ->limit(($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE)->getAll();

            $pagination = $this->pagination($total, self::PAGE_SIZE, $page);

            foreach ($data as $k => $v) {
                if ($v['IsGood'] < 100) {
                    $data[$k]['IsGood'] = self::$isGood[1];
                } else {
                    $data[$k]['IsGood'] = self::$isGood[2];
                }
            }

            $smarty->value('data', $data);
            $smarty->value('pagination', $pagination);
        }

        /*
         * 来源平台0. Web 1. Android 2. iPhone 3. Wap
         */
        $platform = ['0' => 'Web', '1' => 'Android', '2' => 'iPhone', '3' => 'Wap'];

        $types = Comment\CommentBase::getCommentTypes();
        $statuss = Comment\CommentBase::getRealStatusMap();
        $subcommentcnt = Comment\CommentBase::getCountFields();
        $status_color = [
            0 => 'style="color:blue"',
            1 => 'style="color:green"',
            2 => 'style="color:gray"'
        ];

        $smarty->value('searchdata', [
            'Type' => isset($type) ? $type : -100,
            'Status' => isset($status) ? intval($status) : 1,
            'UserId' => $userid,
            'ItemId' => $itemid,
            'IsGood' => $is_good,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);

        $smarty->value('status_color', $status_color);
        $smarty->value('platform', $platform);
        $smarty->value('type', $types);
        $smarty->value('status', $statuss);
        $smarty->value("subcommentcnt, $subcommentcnt");
        $smarty->output("comment/manage.tpl");
    }

    public function doDelete()
    {
        if (strstr($_POST['id'], ',')) {
            $id = explode(',', $_POST['id']);
        } else {
            $id[] = $_POST['id'];
        }

        $status = Comment\CommentBase::REAL_STATUS_DELETED;
        $rs = Comment\CommentBase::updateStatusByCommentIds($id, $status);
        if ($rs['code'] == Comment\CommentBase::RET_CODE_SUCC) {
            $this->adminLog('修改状态', 0, 'delete', serialize($id));

            self::output(['title' => '删除成功', 'type' => 'success']);
        }
        self::output(['title' => '删除失败', 'type' => 'error']);
    }

    /**
     * 优质评论
     */
    public function doQuality()
    {
        $cash = 100;//奖励的金币
        $comment_id = intval($_POST['comment_id']);//接收评论ID
        $item_id = intval($_POST['item_id']);//接收评论对象的编号
        $user_id = $_POST['user_id'];
        $re = CommentBase::setGoodComment($item_id, $comment_id, $cash);

        if ($re) {
            AppPush::pushByUserId($user_id, AppPush::PUSH_TYPE_WALLET, '你的评论被评为优质评论，获得100金币', [AppPush::EXTRA_FIELD_TYPE => AppPush::VAL_TYPE_COIN]);
            $this->adminLog('优质评论', $comment_id, 'update', serialize($cash));
            self::output(['type' => 'success']);
        } else {
            self::output(['type' => 'error']);
        }
    }

    /**
     * 取消优质评论
     */
    public function doOrdinary()
    {
        $comment_id = intval($_POST['comment_id']);//接收评论ID
        $item_id = intval($_POST['item_id']);//接收评论对象的编号
        $user_id = $_POST['user_id'];

        $pdo = Bridge::pdo(Bridge::DB_COMMENT_R);
        $money = $pdo->from('Comment')
            ->select("IsGood")
            ->where(['CommentId' => $comment_id])
            ->getOne();

        $re = CommentBase::cancelGoodComment($item_id, $comment_id);

        if ($re) {
            //            AppPush::pushByUserId($user_id, AppPush::PUSH_TYPE_WALLET, '你的评论被取消优质评论，扣除100金币', [AppPush::EXTRA_FIELD_TYPE => AppPush::VAL_TYPE_COIN]);
            $this->adminLog('取消优质评论', $comment_id, 'update', serialize($money['IsGood']));
            self::output(['type' => 'success']);
        } else {
            self::output(['type' => 'error']);
        }
    }
}