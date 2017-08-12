<?php
/**
 * Book.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-8-12 下午3:21
 *
 */

namespace Controller\Admin\Book;

use Bare\Controller;
use Model\Book\Book as MBook;

class Book extends Controller
{
    public function index()
    {
        $page = max(1, intval($_GET[PAGE_VAR]));

        $where = [];

        $limit = PAGE_SIZE;
        $offset = ($page - 1) * $limit;
        $list_info = MBook::getBooks($where, '*', $offset, $limit);
        $this->pagination(intval($list_info['count']), $limit, $page);
        $list = [];
        if (!empty($list_info['data'])) {
            foreach ($list_info['data'] as $k => $v) {
                $list[$k] = $v;
            }
        }

        $this->value('list', $list);
        $this->view();
    }

}