<?php
/**
 * Favorite.class.php
 */

namespace Controller\Api\Common;

use Bare\Controller;
use Model\Book\Book;

/**
 * 公用服务 - 赞/收藏/喜欢 系统
 *
 * @package Common
 * @author 周剑锋 <camfee@foxmail.com>
 * @date   2017-01-03 v1.0.0
 *
 */
class Favorite extends Controller
{
    /**
     * 允许类型
     *
     * @ignore
     * @var array
     */
    private $allow_type = [
        1 => 'Model\Favorite\BookFavorite',
        2 => 'Model\Favorite\TagFavorite',
    ];

    /**
     * 收藏(订阅) 某个对象 (已收藏返回成功)
     *
     * <pre>
     * POST:
     *     itemid: 必选, 对象ID，必选
     *     type:   必选，类型,  1:书本 2:标签
     * </pre>
     *
     * @return void|string 返回JSON数据
     *
     * <pre>
     * {
     *   "Status": 200,
     *   "Result": {}  // 收藏返回
     *   "Result": {  // 订阅返回
     *     "TagFavoriteCount": "2人订阅"
     *   }
     * }
     *
     * 异常状态
     * 201: 分类不存在
     * 202: 收藏的对象不存在或已经被删除
     * 203: 收藏失败, 请稍后重试
     * </pre>
     */
    public function add()
    {
        $uid = $this->isLogin(true);
        $type = (int)$_POST['type'];
        $itemid = (int)$_POST['itemid'];

        if (!isset($this->allow_type[$type])) {
            $this->output(201, '分类不存在');
        }

        $exist = false;
        $class = $this->allow_type[$type];

        $class_type = '';
        switch ($type) {
            case 1:
                $class_type = $class::TYPE_BOOK;
                $data = Book::getBookByIds($itemid);
                $exist = isset($data['ArticleId']) && $itemid == $data['ArticleId'] && $data['Status'] == 1;
                break;
            case 2:
                $class_type = $class::TYPE_TAG;
                $data = Tags::getTagsByIds($itemid, [Tags::EXTRA_OUTDATA => Tags::EXTRA_OUTDATA_ALL]);
                $exist = !empty($data) && !empty($data[$itemid]);
                break;
            default:
                break;
        }

        if ($exist != true) {
            $this->output(202, '收藏的对象不存在或已经被删除');
        }

        $fav = new $class($class_type);
        $ret = $fav->add($uid, $itemid);

        if ($ret) {
            $this->output(200);
        }
        $this->output(203, '收藏失败, 请稍后重试');
    }

    /**
     * 取消 收藏（订阅）某个对象
     * (若未收藏时调用接口, 直接返回成功代码)
     *
     * <pre>
     * POST:
     *     itemid: 必选，对象ID
     *     type:   必选，类型,   1:书本 2:标签
     * </pre>
     *
     * @return void|string 返回JSON数据
     *
     * <pre>
     * {
     *    "status": 200,
     *    "Result": {}  // 收藏返回
     *    "Result": {  // 订阅返回
     *      "TagFavoriteCount": "2人订阅"
     *    }
     * }
     *
     * 异常状态
     * 201: 分类不存在
     * 202: 缺少操作对象参数
     * 203: 取消收藏失败, 请稍后重试
     * </pre>
     */
    public function remove()
    {
        $uid = $this->isLogin(true);
        $type = (int)$_POST['type'];
        $itemid = (int)$_POST['itemid'];

        if (!isset($this->allow_type[$type])) {
            $this->output(201, '分类不存在');
        }

        $class = $this->allow_type[$type];

        $class_type = '';
        switch ($type) {
            case 1:
                $class_type = $class::TYPE_ARTICLE;
                break;
            case 2:
                $class_type = $class::TYPE_TAG;
                break;
            default:
                break;
        }

        $fav = new $class($class_type);

        $ret = false;
        if ($itemid > 0) {
            $ret = $fav->remove($uid, $itemid);
        } else {
            $this->output(202, '缺少操作对象参数');
        }

        if ($ret) {
            $this->output(200);
        }
        $this->output(203, '取消收藏失败, 请稍后重试');
    }
}
