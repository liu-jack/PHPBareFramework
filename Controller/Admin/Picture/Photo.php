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
use Model\Picture\Photo as MPhoto;

class Photo extends AdminController
{
    protected static $_list_extra = [
        MPhoto::EXTRA_LIST_EDIT, // 显示编辑按钮
        MPhoto::EXTRA_LIST_DEL, // 显示删除按钮
        MPhoto::EXTRA_LIST_ADD, // 显示新增按钮
    ];

    public function __construct()
    {
        parent::__construct();
        if (empty($this->_m)) {
            $this->_m = new MPhoto();
        }
    }

    public function update()
    {
        $id = intval($_POST['PhotoId']);
        $data = $_POST;
        if (empty($data['AtlasId'])) {
            $this->alertErr('相册ID不能为空');
        }
        if ($id > 0) {
            if (!empty($_FILES['ImgUrl'])) {
                $photo = PhotoImage::checkImage($_FILES['ImgUrl']);
                if ($photo['status']) {
                    $photo_url = MPhoto::uploadPhoto($photo, $id);
                    if (!empty($photo_url)) {
                        $data['ImgUrl'] = $photo_url . '?t=' . time();
                    }
                }
            }
            $ret = MPhoto::update($id, $data);
            if ($ret !== false) {
                $this->adminLog('修改相片', 'update', $id, $data, MPhoto::TABLE);
                $this->alert('修改成功', url('index'));
            } else {
                $this->alertErr('修改失败');
            }
        } else {
            $ret = MPhoto::add($data);
            if ($ret !== false) {
                if (!empty($_FILES['ImgUrl'])) {
                    $photo = PhotoImage::checkImage($_FILES['ImgUrl']);
                    if ($photo['status']) {
                        $photo_url = MPhoto::uploadPhoto($photo, $ret);
                        if (!empty($photo_url)) {
                            $updata['ImgUrl'] = $photo_url . '?t=' . time();
                            MPhoto::update($ret, $updata);
                        }
                    }
                }
                $this->adminLog('新增相片', 'add', 0, $data, MPhoto::TABLE);
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
        if (empty($info)) {
            $this->alertErr('参数错误');
        }
        parent::adminDelete();
    }
}