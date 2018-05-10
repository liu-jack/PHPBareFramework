<?php

namespace Controller\Admin\Tool;

use Bare\C\AdminController;
use Bare\M\MongoModel;

class Mongodb extends AdminController
{
    public function index()
    {
        $mongo = config('tool/mongodb');
        if (!empty($mongo)) {
            $this->value('list', $mongo);
        }
        $this->view();
    }

    public function info()
    {
        $db = trim($_GET['db']);
        $info = MongoModel::getCollections($db);

        $this->value('list', $info);
        $this->value('db', $db);
        $this->view();
    }


    public function delete()
    {
        $db = $_GET['db'];
        $name = $_GET['collection'];
        $res = MongoModel::removeCollection($name, $db);

        if ($res !== false) {
            $this->adminLog('删除集合', 'del', 0, [$db, $name], 'mongodb');
            output(200);
        } else {
            output(201);
        }

    }
}