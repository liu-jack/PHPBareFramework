<?php

/**
 * 关键字过滤
 */

namespace Controller\Admin\User;

use Bare\DB;
use Bare\AdminController;
use Classes\Safe\Filter as CFilter;

class Filter extends AdminController
{
    const TABLE = 'Filter';

    public function index()
    {
        $page = max(1, intval($_GET[PAGE_VAR]));
        $offset = ($page - 1) * PAGE_SIZE;
        $limit = PAGE_SIZE;

        $pdo = DB::pdo(DB::DB_ADMIN_R);
        $query = $pdo->prepare('select count(*) from ' . self::TABLE);
        $query->execute();
        $total = $query->fetchColumn();

        $query = $pdo->prepare("select * from " . self::TABLE . " order by Id DESC limit $offset,$limit");
        $query->execute();
        $filter = $query->fetchAll();
        $this->page($total, PAGE_SIZE, $page);
        $this->value('filter', $filter);
        $this->view();
    }

    //多选删除
    public function deletes()
    {
        $dbw = DB::pdo(DB::DB_ADMIN_W);
        $delid = explode(',', $_POST['id']);
        $arrid = '';
        foreach ($delid as $v) {
            $arrid .= intval($v) . ',';
            if (!$v) {
                continue;
            }
        }
        $arrid = rtrim($arrid, ',');
        if ($arrid) {
            $query = $dbw->query("delete from " . self::TABLE . " where Id in($arrid)");
            $this->adminLog('删除敏感词', 'del', 0, $delid, self::TABLE);
            if ($query->rowCount() > 0) {
                output(200, ['title' => '删除成功', 'type' => 'success']);
            }
        }
        output(201, ['title' => '删除失败', 'type' => 'error']);
    }

    // 单项删除
    public function delete()
    {
        $dbw = DB::pdo(DB::DB_ADMIN_W);

        $id = intval($_GET['id']);
        if (!$id) {
            $this->alertErr('删除失败', '', '参数非法');
        } else {
            $dbw->query("delete from " . self::TABLE . " where Id = $id ");
            $this->adminLog('删除敏感词', 'del', $id, $id, self::TABLE);
            $this->alert('删除成功', url('index'));
        }
    }

    public function add()
    {
        $keywords = trim($_POST['content']);
        $keyword = explode("\n", $keywords);
        $data = [];

        foreach ($keyword as $k => $v) {
            $v = trim($v);
            if (!empty($v)) {
                $data[$v] = $v;
            }
        }
        if (CFilter::addFilter($data)) {
            $this->adminLog('添加敏感词', 'add', 0, $keywords, self::TABLE);
            output(200, ['title' => '添加成功', 'type' => 'success']);
        }
        output(201, ['title' => '添加失败', 'type' => 'error']);
    }

    public function edit()
    {
        $new = strval($_POST['name']);
        $id = intval($_POST['id']);

        //检查关键字是否存在
        $dbr = DB::pdo(DB::DB_ADMIN_R);
        $isThere = $dbr->prepare("SELECT Id FROM " . self::TABLE . " WHERE Word = :new");
        $isThere->execute([':new' => $new]);
        $data = $isThere->fetch();

        if ($data) {
            output(201, ['title' => '敏感词已存在', 'type' => 'error']);
        }

        //修改关键字
        $dbw = DB::pdo(DB::DB_ADMIN_W);
        $re = $dbw->update('Filter', ['Word' => $new], ['Id' => $id]);
        $this->adminLog('敏感词修改', 'update', $id, $new, self::TABLE);
        if ($re !== false) {
            output(200, ['title' => '修改成功', 'type' => 'success']);
        }
        output(201, ['title' => '修改失败', 'type' => 'error']);
    }
}