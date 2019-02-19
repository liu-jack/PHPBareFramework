<?php
/**
 * Tags.class.php
 *
 * @author 周剑锋 <camfee@foxmail.com>
 */

namespace controls\MobileApi\Common;

use Bare\C\Controller;
use MobileApi\DataFormat;
use Common\Tags as CTags;
use Search\Tag as STag;
use Favorite\TagFavorite;

/**
 * 标签类
 *
 * @package Common
 * @author  周剑锋 <camfee@foxmail.com>
 * @date   1.0.0 2017-02-8
 */
class Tag extends Controller
{

    /**
     * 获取我的订阅标签
     *
     * <pre>
     * GET:
     *      offset: 可选，偏移量，  默认为0
     *      limit:  可选，每页数量, 默认10(最高不超过50)
     * </pre>
     *
     * @return string
     *
     * <pre>
     * {
     *   "Status": 200,
     *   "Result": {
     *     "Total": 2,
     *     "List": [
     *       {
     *         "TagId": 11,          //标签ID
     *         "TagName": "1部",     //标签名称
     *         "TagFavoriteCount": 1, //标签订阅数
     *       },
     *     ]
     *   }
     * }
     * </pre>
     */
    public function getMyFavoriteTag()
    {
        $uid = $this->isLogin(true);
        $offset = (int)$_GET['offset'];
        $limit = (int)$_GET['limit'];

        $offset = $offset < 0 ? 0 : $offset;
        $limit = $limit < 1 ? 10 : min($limit, 50);

        $obj = new TagFavorite(TagFavorite::TYPE_TAG);
        $tag_items = $obj->getItemsByUserId($uid, $offset, $limit);
        $tag = CTags::getTagsByIds($tag_items['data'], [CTags::EXTRA_OUTDATA => CTags::EXTRA_OUTDATA_ALL]);
        $data['Total'] = $tag_items['total'];
        $data['List'] = [];
        if (!empty($tag)) {
            foreach ($tag_items['data'] as $k => $v) {
                if (isset($tag[$v])) {
                    $data['List'][] = [
                        'TagId' => intval($tag[$v]['TagId']),
                        'TagName' => $tag[$v]['TagName'],
                        'TagFavoriteCount' => DataFormat::tagNum($tag[$v]['FollowCount']),
                    ];
                }
            }
        }

        $this->output(200, $data);
    }

    /**
     * 标签搜索
     *
     * <pre>
     * GET:
     *      str:    必选，关键词
     *      offset: 可选，偏移量，  默认为0
     *      limit:  可选，每页数量, 默认10(设置时最高不超过50)
     * </pre>
     *
     * @return string
     *
     * <pre>
     * {
     *   "Status": 200,
     *   "Result": {
     *     "Total": 2,
     *     "List": [
     *       {
     *         "TagId": 11,          //标签ID
     *         "TagName": "1部",     //标签名称
     *       },
     *     ]
     *   }
     * }
     *
     * 异常状态
     * 201:搜索关键字不能为空哦
     *
     * </pre>
     */
    public function searchTag()
    {
        $str = trim(urldecode($_GET['str']));
        $offset = (int)$_GET['offset'];
        $limit = (int)$_GET['limit'];
        if (empty($str)) {
            $this->output(201, '搜索关键字不能为空哦！');
        }
        $offset = $offset < 0 ? 0 : $offset;
        $limit = $limit < 1 ? 10 : min($limit, 50);

        $tag_info = STag::searchTag($str, $offset, $limit);
        $data['Total'] = (int)$tag_info['total'];
        $data['List'] = [];
        if (!empty($tag_info['data'])) {
            foreach ($tag_info['data'] as $k => $v) {
                $data['List'][] = [
                    'TagId' => intval($v['id']),
                    'TagName' => $v['name'],
                ];
            }
        }

        $this->output(200, $data);
    }
}
