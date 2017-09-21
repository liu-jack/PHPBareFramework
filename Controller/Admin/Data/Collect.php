<?php
/**
 * Collect.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-8-25 下午9:18
 *
 */

namespace Controller\Admin\Data;

use Bare\AdminController;
use Model\Admin\Admin\AdminLog;
use Model\Book\Book as MBook;
use Model\Book\Collect as MCollect;

class Collect extends AdminController
{
    private static $_status = [
        1 => '采集',
        2 => '不采集',
    ];

    public function index()
    {
        $page = max(1, intval($_GET[PAGE_VAR]));
        $book_id = intval($_GET['book_id']);
        $status = intval($_GET['status']);

        $where = [];
        if (!empty($book_id)) {
            $where['BookId'] = $book_id;
        }
        if (!empty($status)) {
            $where['Status'] = $status;
        }

        $limit = PAGE_SIZE;
        $offset = ($page - 1) * $limit;
        $list_info = MCollect::getCollects($where, $offset, $limit);
        $this->page(intval($list_info['count']), $limit, $page);
        $list = $bids = [];
        if (!empty($list_info['data'])) {
            foreach ($list_info['data'] as $k => $v) {
                $bids[$v['BookId']] = $v['BookId'];
            }
            $books = MBook::getBookByIds($bids);
            $sites = config('book/sites');
            foreach ($list_info['data'] as $k => $v) {
                $v['FromSiteName'] = $sites[$v['FromSite']];
                $v['StatusName'] = self::$_status[$v['Status']];
                $v['BookName'] = $books[$v['BookId']]['BookName'];
                $list[$k] = $v;
            }
        }

        $this->value('book_id', !empty($book_id) ? $book_id : '');
        $this->value('status', $status);
        $this->value('status_list', self::$_status);
        $this->value('list', $list);
        $this->view();
    }

    public function add()
    {
        $sites = config('book/sites');
        $this->value('sites', $sites);
        $this->value('status_list', self::$_status);
        $this->view('update');
    }

    public function edit()
    {
        $id = intval($_GET['id']);
        $info = MCollect::getCollectById($id);
        $sites = config('book/sites');
        $book = MBook::getBookByIds($info['BookId']);
        $info = array_merge($book, $info);

        $this->value('sites', $sites);
        $this->value('info', $info);
        $this->value('status_list', self::$_status);
        $this->view('update');
    }

    public function update()
    {
        $id = intval($_POST['id']);
        $sites = config('book/sites');
        if (empty($_POST['Url'])) {
            $this->alertErr('采集地址不能空');
        }
        if (empty($sites[$_POST['FromSite']])) {
            $this->alertErr('来源不存在');
        }
        $book = MBook::getBookByIds($_POST['BookId']);
        if (empty($book)) {
            $this->alertErr('书本不存在');
        }
        $data = [
            'BookId' => trim($_POST['BookId']),
            'Url' => trim($_POST['Url']),
            'FromSite' => intval($_POST['FromSite']),
            'BookDesc' => trim($_POST['BookDesc']),
            'Status' => intval($_POST['Status']),
        ];
        if ($id > 0) {
            $ret = MCollect::updateCollect($id, $data);
            if (!$ret) {
                $this->alertErr('修改失败');
            }
            AdminLog::log('修改采集', 'update', $id, $data, 'BookCollect');
            $this->alert('修改成功', url('index'));
        } else {
            $id = MCollect::addCollect($data);
            if ($id) {
                AdminLog::log('添加采集', 'add', $id, $data, 'BookCollect');
                $this->alert('添加采集成功', url('index'));
            } else {
                $this->alertErr('添加采集失败');
            }
        }
    }
}