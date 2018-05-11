<?php
/**
 * Tags.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-10-13 上午11:28
 *
 */

namespace Controller\Admin\Data;

use Bare\C\AdminController;
use Bare\DB;
use Classes\Image\PhotoImage;
use Model\Common\Tags as MTags;

class Tags extends AdminController
{
    const TABLE = 'TagName';

    public function index()
    {
        $page = max(1, intval($_GET[PAGE_VAR]));
        $tag_id = (int)$_GET['tagid'];
        $tag_name = trim($_GET['tagname']);
        $limit = PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $where = "1=1";
        if ($tag_id > 1) {
            $where .= " AND TagNameId = {$tag_id}";
        }
        if (!empty($tag_name)) {
            $where .= " AND `TagName` = '{$tag_name}'";
        }
        $sql = "SELECT `TagNameId` FROM " . self::TABLE . " WHERE $where ORDER BY TagNameId DESC LIMIT $offset,$limit";
        $sql_count = "SELECT count(TagNameId) AS num FROM " . self::TABLE . " WHERE $where";
        $count = $this->readDb($sql_count);
        $count = (int)$count[0]['num'];

        if ($count > 0) {
            $this->page($count, $limit, $page);
            $page_info['page'] = ceil($count / $limit);
            $page_info['count'] = $count;
            $list_ids = $this->readDb($sql);
            $list = '';
            if (!empty($list_ids)) {
                $tagid = [];
                foreach ($list_ids as $k => $v) {
                    $tagid[$k] = $v['TagNameId'];
                }
                //$list = MTags::getTagsByIds($tagid, [MTags::EXTRA_OUTDATA => MTags::EXTRA_OUTDATA_ALL]);
            }

            $this->value('tagid', $tag_id);
            $this->value('tagname', $tag_name);
            $this->value('list', $list);
        }
        $this->view();
    }

    public function edit()
    {
        $id = (int)$_GET['id'];

        $info = MTags::getTagsByIds($id,
            [MTags::EXTRA_OUTDATA => MTags::EXTRA_OUTDATA_ALL, MTags::EXTRA_REFRESH => true]);
        $info = current($info);
        $bannerid = 0;
        if (!empty($info['Banner'])) {
            $bannerid = 1;
            foreach ($info['Banner'] as $k => $v) {
                $info['Banner'][$k]['autoImgUrl'] = $v['ImgUrl'];
            }
        }
        $this->value('info', $info);
        $this->value('bannerid', $bannerid);
        $this->view('update');
    }

    public function update()
    {
        set_time_limit(60);
        $id = intval($_POST['id']);
        $file_icon = $_FILES['icon'] ?? []; // icon
        $file_cover = $_FILES['cover'] ?? []; // 封面
        $icon = $cover = [];
        if (!empty($file_icon['tmp_name'])) {
            $icon = PhotoImage::checkImage($file_icon);
            if (strtolower($icon['image_type']) != 'png' || $file_icon['size'] > 2097152) {
                $this->alertErr('保存失败！', '', 'icon图格式不正确,或者超过大小');
            }
        }
        if (!empty($file_cover['tmp_name'])) {
            $cover = PhotoImage::checkImage($file_cover);
            if ((strtolower($cover['image_type']) != 'jpg' && strtolower($cover['image_type']) != 'png') || $file_cover['size'] > 2097152) {
                $this->alertErr('保存失败！', '', '封面图格式不正确,或者超过大小');
            }
        }
        if ($id < 1) {
            $tagname = trim($_POST['tagname']);

            if (empty($tagname)) {
                $this->alertErr('保存失败！', '', '标签名不能为空');
            }
            $result = MTags::addTag([$tagname]);
            $id = key($result);
            if ($id > 0) {
                if ($icon['status']) {
                    MTags::updateTagIcon($id, $icon);
                }
                if ($cover['status']) {
                    MTags::updateTagCover($id, $cover);
                }
                $tagdesc = trim($_POST['TagDesc']);
                MTags::updateTagDesc($id, $tagdesc);
                $this->adminLog('添加标签', 'add', $id, $tagname, self::TABLE);
                $this->alert('添加成功！', url('index'));
            } else {
                $this->alertErr('添加失败！', '', $result['msg']);
            }
        } else {
            $r2 = $r3 = '';
            $banners = [];
            if (!empty($_POST['img'])) {
                foreach ($_POST['img'] as $k => $v) {
                    if (!empty($v) && !empty($_POST['title'][$k])) {
                        $banners[$k]['ImgUrl'] = $v;
                        $banners[$k]['Url'] = $_POST['url'][$k];
                        $banners[$k]['Title'] = $_POST['title'][$k];
                    }
                }
            }
            $r1 = MTags::updateTagBanner($id, $banners);
            if (!empty($icon)) {
                $r2 = MTags::updateTagIcon($id, $icon);
            }
            if (!empty($cover)) {
                $r3 = MTags::updateTagCover($id, $cover);
            }
            $tagdesc = trim($_POST['TagDesc']);
            MTags::updateTagDesc($id, $tagdesc);
            if ($r1 || $r2 || $r3) {
                $this->adminLog('更新标签', 'update', $id, $id, self::TABLE);
                $this->alert('保存成功！', url('index'));
            } else {
                $this->alertErr('未修改任何数据！', url('index'));
            }
        }
    }

    public function add()
    {
        $this->view('update');
    }

    /**
     * 首页标签推荐
     */
    public function recomTag()
    {
        $cate = [
            1 => ['name' => '按钮', 'type' => RecomData::APP_BUTTON_TAG],
            2 => ['name' => '推荐', 'type' => RecomData::APP_INDEX_TAG],
            3 => ['name' => '童话', 'type' => RecomData::APP_FAIRYTALE_TAG],
            4 => ['name' => '寓言', 'type' => RecomData::APP_FABLE_TAG],
            5 => ['name' => '成语', 'type' => RecomData::APP_IDIOM_TAG],
            6 => ['name' => '国学', 'type' => RecomData::APP_SINOLOGY_TAG],
            7 => ['name' => '神话', 'type' => RecomData::APP_MYTH_TAG]
        ];

        if (!empty($_POST)) {
            $id = (int)$_POST['id'];
            $tags = [];
            if (!empty($_POST['tag'])) {
                $tag_arr = explode(' ', trim($_POST['tag']));
                foreach ($tag_arr as $v) {
                    $v = trim($v);
                    $tags[$v] = $v;
                }
            } else {
                $r = RecomData::setData($cate[$id]['type'], []);
                if ($r) {
                    HDshowMsg('清空成功！', ['url' => "data/modules/tag/tag.php?do=RecomTag&id={$id}"]);
                } else {
                    HDshowMsg('清空失败！', ['url' => "data/modules/tag/tag.php?do=RecomTag&id={$id}"]);
                }

            }
            if ($id < 1 || empty($tags)) {
                HDshowMsg('缺少必要数据！', ['url' => "data/modules/tag/tag.php?do=RecomTag&id={$id}"]);
            }
            $tag_ids = MTags::getTagsByName($tags);
            if (empty($tag_ids)) {
                HDshowMsg('所填标签没有找到！', ['url' => "data/modules/tag/tag.php?do=RecomTag&id={$id}"]);
            }
            $tag_all = MTags::getTagsByIds($tag_ids, [Tags::EXTRA_OUTDATA => Tags::EXTRA_OUTDATA_ALL]);
            $data = [];
            if (!empty($tag_all)) {
                foreach ($tag_all as $k => $v) {
                    $data[$k] = $v['TagId'];
                }
            }
            if (!empty($data)) {
                $r = RecomData::setData($cate[$id]['type'], $data);
                if ($r) {
                    HDshowMsg('推荐成功！', ['url' => "data/modules/tag/tag.php?do=RecomTag&id={$id}"]);
                } else {
                    HDshowMsg('推荐失败！', ['url' => "data/modules/tag/tag.php?do=RecomTag&id={$id}"]);
                }
            }
            HDshowMsg('所填标签没有找到！', ['url' => "data/modules/tag/tag.php?do=RecomTag&id={$id}"]);
        }


        $id = $_GET['id'] ? (int)$_GET['id'] : 1;

        $res = RecomData::getData($cate[$id]['type']);
        $tag = '';
        if (!empty($res)) {
            $res = $res[$cate[$id]['type']];
            $tag_arr = MTags::getTagsByIds($res);
            foreach ($tag_arr as $v) {
                $tag .= $v . ' ';
            }
        }
        $tag = trim($tag);
        $this->value('cate', $cate);
        $this->value('tag', $tag);
        $this->value('id', $id);
        $this->view();
    }

    /**
     * 标签banner图上传
     */
    public function uploadBanner()
    {
        set_time_limit(60);
        $file_name = trim($_POST['file_name']);
        $files = $_FILES[$file_name];
        $callback = 'parent._uploadImg';
        if (!empty($files)) {
            $status = PhotoImage::checkImage($files, 0, 0, 2097152);
            if ($status['code'] != 0) {
                callback($callback, ['code' => 201, 'msg' => $status['msg']]);
            }

            $res = MTags::updateTagBannerImg($status);
            if ($res) {
                callback($callback, [
                    'code' => 200,
                    'msg' => '图片上传成功',
                    'data' => [
                        'img_url' => $res,
                        'auto_img_url' => auto_host($res),
                        'file_name' => $file_name,
                    ]
                ]);
            } else {
                callback($callback, ['code' => 202, 'msg' => '上传失败！']);
            }
        } else {
            callback($callback, ['code' => 203, 'msg' => '参数不正确！']);
        }
    }

    private $pdo_r;

    /**
     * 读取数据库
     *
     * @param $sql
     * @return array
     */
    private function readDb($sql)
    {
        $pdo = $this->pdo_r;
        if (empty($pdo)) {
            $pdo = $this->pdo_r = DB::pdo(DB::DB_TAG_R);
        }
        $obj = $pdo->query($sql);

        return $obj->fetchAll();
    }
}