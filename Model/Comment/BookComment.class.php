<?php

/**
 * 文章评论类
 *
 * @package modules
 * @subpackage Comment
 * @author suning <snsnsky@gmail.com>
 */

namespace Comment;


class BookComment extends CommentBase
{
    const LOG_FAIL_PATH = 'Comment/ArticleComment/Fail';
    const LOG_SUCC_PATH = 'Comment/ArticleComment/Succ';

    /**
     * 获取文章详情
     *
     * @param array $extra 额外参数
     * @return array
     */
    public function getItem($extra = [])
    {
        // 返回 Article::getArticleById($this->_itemid, $extra);
        $item = [];
        return $item;
    }

    /**
     * 更新文章的评论计数
     *
     * @param int $count 数量, [count | "[+|-]count"]
     * @param int $itemid 评论主体ID, >0 时, 表示强制指定, 否则应该使用 $this->_itemid
     * @return bool
     */
    public function updateItemCommentCount($count, $itemid = 0)
    {
        $itemid = $itemid == 0 ? $this->_itemid : $itemid;
        return Article::updateArticle($itemid, ['CommentCount' => ['CommentCount', $count]]);
    }
}
