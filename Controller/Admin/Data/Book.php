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
use Model\Book\Column;
use Model\Book\Content;
use Model\Book\Collect as MCollect;
use Model\Collect\CollectBook77 as Collect77;

class Book extends Controller
{
    public function index()
    {
        $page = max(1, intval($_GET[PAGE_VAR]));
        $book_id = intval($_GET['book_id']);
        $book_name = trim($_GET['book_name']);
        $author = trim($_GET['author']);
        $isfinish = intval($_GET['isfinish']);
        $status = intval($_GET['status']);

        $where = [];
        if (!empty($book_id)) {
            $where['BookId'] = $book_id;
        }
        if (!empty($book_name)) {
            $where['BookName LIKE'] = "%{$book_name}%";
        }
        if (!empty($author)) {
            $where['Author LIKE'] = "%$author%";
        }
        if ($book_id !== '') {
            $where['IsFinish'] = $isfinish;
        }
        if (!empty($status)) {
            $where['Status'] = $status;
        }

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

        $this->value('book_id', !empty($book_id) ? $book_id : '');
        $this->value('book_name', $book_name);
        $this->value('author', $author);
        $this->value('isfinish', $isfinish);
        $this->value('status', $status);
        $this->value('list', $list);
        $this->view();
    }

    public function add()
    {
        if (!empty($_GET['book_url'])) {
            $info = Collect77::getBook($_GET['book_url']);
            output(200, $info);
        }
        $sites = config('book/sites');

        $this->value('sites', $sites);
        $this->view('Data/Book/update');
    }

    public function edit()
    {
        $id = intval($_GET['id']);
        $book = MBook::getBookByIds($id);
        $book['FromSite'] = explode(',', $book['FromSite']);
        $sites = config('book/sites');

        $this->value('sites', $sites);
        $this->value('info', $book);
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
            'Words' => intval($_POST['Words']),
            'IsFinish' => intval($_POST['IsFinish']),
            'FromSite' => !empty($_POST['FromSite']) ? implode(',', $_POST['FromSite']) : '',
            'DefaultFromSite' => intval($_POST['DefaultFromSite']),
            'UpdateTime' => date('Y-m-d H:i:s'),
        ];
        if ($id > 0) {
            $ret = MBook::updateBook($id, $data);
            if (!$ret) {
                $this->alertErr('修改失败');
            }
            AdminLog::log('修改书本', 'update', $id, $data, 'Book');
            $this->alert('修改成功', url('index'));
        } else {
            $data['CreateTime'] = date('Y-m-d H:i:s');
            $id = MBook::addBook($data);
            if ($id) {
                AdminLog::log('添加书本', 'add', $id, $data, 'Book');
                $cdata = [
                    'BookId' => $id,
                    'FromSite' => $data['FromSite']
                ];
                if (!empty($_POST['Url'])) {
                    $cdata['Url'] = trim($_POST['Url']);
                }
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

    /**
     * 章节管理
     */
    public function column()
    {
        $page = max(1, intval($_GET[PAGE_VAR]));
        $bid = intval($_GET['bid']);
        $fid = !empty($_GET['fid']) ? intval($_GET['fid']) : 77;
        $limit = PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $list_info = Column::getColumns($bid, $fid, $offset, $limit);
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

    /**
     * 章节内容管理
     */
    public function content()
    {
        $bid = !empty($_POST['bid']) ? intval($_POST['bid']) : intval($_GET['bid']);
        $cid = !empty($_POST['cid']) ? intval($_POST['cid']) : intval($_GET['cid']);

        if (!empty($_POST['bid']) && !empty($_POST['cid'])) {
            $ccid = intval($_POST['ccid']);
            $data['ChapterName'] = trim($_POST['ChapterName']);
            $cdata['Content'] = trim($_POST['Content']);
            if (!empty($data['ChapterName'])) {
                $ret = Column::updateColumn($bid, $cid, $data);
                if (!$ret) {
                    $this->alertErr('修改章节名称失败');
                }
            }
            if (!empty($cdata['Content']) && $ccid > 0) {
                $ret = Content::updateContent($bid, $ccid, $cdata);
                if (!$ret) {
                    $this->alertErr('修改内容失败');
                }
            }
            AdminLog::log('修改书本章节内容', 'update', $cid, $data, 'BookColumn');
            $this->alert('修改成功');
        }

        $column = Column::getColumnById($bid, $cid);
        $content = Content::getContentByChapterId($bid, $cid);
        $info = array_merge($column, $content);

        $this->value('info', $info);
        $this->view();
    }
}