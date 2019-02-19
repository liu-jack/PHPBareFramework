<?php
/**
 * Atlas.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-10-19 下午4:30
 *
 */

namespace Controller\Admin\Picture;

use Bare\C\AdminController;
use Classes\Image\PhotoImage;
use Model\Picture\Atlas as MAtlas;
use Model\Picture\Photo as MPhoto;

class Atlas extends AdminController
{
    protected static $_list_extra = [
        MAtlas::EXTRA_LIST_EDIT, // 显示编辑按钮
        MAtlas::EXTRA_LIST_DEL, // 显示删除按钮
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
        $id = intval($_POST['AtlasId']);
        $data = $_POST;
        if (empty($data['Title'])) {
            $this->alertErr('相册标题不能为空');
        }
        if ($id > 0) {
            if (!empty($_FILES['Cover'])) {
                $cover = PhotoImage::checkImage($_FILES['Cover']);
                if ($cover['status']) {
                    $cover_url = MAtlas::uploadCover($cover, $id);
                    if (!empty($cover_url)) {
                        $data['Cover'] = $cover_url . '?t=' . time();
                    }
                }
            }
            $ret = MAtlas::update($id, $data);
            if ($ret !== false) {
                $this->adminLog('修改相册', 'update', $id, $data, MAtlas::TABLE);
                $this->alert('修改成功', url('index'));
            } else {
                $this->alertErr('修改失败');
            }
        } else {
            $ret = MAtlas::add($data);
            if ($ret !== false) {
                if (!empty($_FILES['Cover'])) {
                    $cover = PhotoImage::checkImage($_FILES['Cover']);
                    if ($cover['status']) {
                        $cover_url = MAtlas::uploadCover($cover, $ret);
                        if (!empty($cover_url)) {
                            $updata['Cover'] = $cover_url . '?t=' . time();
                            MAtlas::update($ret, $updata);
                        }
                    }
                }
                $this->adminLog('新增相册', 'add', 0, $data, MAtlas::TABLE);
                $this->alert('新增成功', url('index'));
            } else {
                $this->alertErr('新增失败');
            }
        }
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
        $photos = MPhoto::getList([MPhoto::FD_ATLAS_ID => $id], 0, 1);
        if (!empty($photos['count'])) {
            $this->alertErr('该相册下还有相片，不能删除');
        }
        if (empty($info)) {
            $this->alertErr('参数错误');
        }
        parent::adminDelete();
    }
}