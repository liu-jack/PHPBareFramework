<?php
/**
 * Book.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-8-12 下午3:21
 *
 */

namespace Controller\Admin\Data;

use Bare\Controller;
use Model\Admin\Admin\AdminLog;
use Model\Book\Book as MBook;
use Model\Book\Collect as MCollect;
use Model\Collect\CollectBook77 as Collect77;

class Book extends Controller
{
    public function index()
    {
        $page = max(1, intval($_GET[PAGE_VAR]));

        $where = [];

        $limit = PAGE_SIZE;
        $offset = ($page - 1) * $limit;
        $list_info = MBook::getBooks($where, '*', $offset, $limit);
        $this->page(intval($list_info['count']), $limit, $page);
        $list = [];
        if (!empty($list_info['data'])) {
            foreach ($list_info['data'] as $k => $v) {
                $list[$k] = $v;
            }
        }

        $this->value('list', $list);
        $this->view();
    }

    public function add()
    {
        if (!empty($_GET['book_url'])) {
            $info = Collect77::getBook($_GET['book_url']);
            output(200, $info);
        }
        $this->view('Data/Book/update');
    }

    public function edit()
    {
        $id = intval($_GET['id']);

        $this->view('Data/Book/update');
    }

    public function update()
    {
        $id = intval($_POST['id']);
        if (empty($_POST['BookName']) || empty($_POST['Author'])) {
            $this->alertErr('书名与作者都不能空');
        }
        $data = [
            'BookName' => trim($_POST['BookName']),
            'Author' => trim($_POST['Author']),
            'TypeName' => trim($_POST['TypeName']),
            'BookDesc' => trim($_POST['BookDesc']),
            'Status' => intval($_POST['Status']),
            'FromSite' => 77,
            'DefaultFromSite' => 77,
            'UpdateTime' => date('Y-m-d H:i:s'),
            'CreateTime' => date('Y-m-d H:i:s')
        ];
        if ($id > 0) {

        } else {
            $id = MBook::addBook($data);
            if ($id) {
                AdminLog::log('添加书本', 'add', $id, $data, 'Book');
                $cdata = [
                    'BookId' => $id,
                    'Url' => trim($_POST['Url']),
                    'FromSite' => $data['FromSite'],
                    'CollectTime' => date('Y-m-d H:i:s')
                ];
                $ret = MCollect::addCollect($cdata);
                if (!$ret) {
                    $this->alertErr('添加采集失败');
                }
                $this->alert('添加书本成功', url('index'));
            } else {
                $this->alertErr('添加书本失败');
            }
        }
    }
}