<?php
/**
 * Atlas.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-10-19 下午4:30
 *
 */

namespace Controller\Admin\Picture;

use Bare\AdminController;
use Model\Picture\Atlas as MAtlas;

class Atlas extends AdminController
{
    protected static $_list_extra = [
        MAtlas::EXTRA_LIST_DEL, // 显示删除按钮
        MAtlas::EXTRA_LIST_EDIT, // 显示编辑按钮
        MAtlas::EXTRA_LIST_ADD, // 显示新增按钮
    ];

    public function __construct()
    {
        parent::__construct();
        if (empty($this->_m)) {
            $this->_m = new MAtlas();
        }
    }

    public function update()
    {

    }

    public function add()
    {
        parent::adminAdd();
    }

    public function edit()
    {
        parent::adminEdit();
    }

    public function delete()
    {
        $id = intval($_GET['id']);
        $info = $this->_m::getInfoByIds($id);
        if (!empty($info)) {
            $this->alertErr('不参数错误');
        }
        parent::adminDelete();
    }
}