<?php
/**
 * 标签类
 *
 * @package    modules
 * @subpackage Common
 * @author     suning <snsnsky@gmail.com>
 *
 * $Id$
 */

namespace Common;

use Center\Article;

class Tags extends CommonBase
{
    const LOG_FAIL_PATH = 'Common/Tags/Fail';

    /**
     * MC缓存前缀: 标签名
     *
     * @var string
     */
    const MC_PFX = 'Tags:';

    /**
     * 匹配标签名的正则
     *
     * @var string
     */
    const REGEXP_TAG_NAME = '/^[\x{3001}\x{4e00}-\x{9fa5}0-9a-z\-\+]{1,20}$/iu';

    const ENTIRE_TABLE_NAME = 'Tag';
    const SHARD_TABLE_NAME = 'Tag_%02x';

    // {{{ 产品类别
    /**
     * 产品类别 - 所有 (一般不用)
     *
     * @var integer
     */
    const TYPE_TAG_ALL = 0;

    /**
     * 产品类别 - 用户
     *
     * @var integer
     */
    const TYPE_TAG_ARTICLE = 1;

    /**
     * 支持的对象类型
     *
     * @var array
     */
    private static $_object_type_map = [
        self::TYPE_TAG_ALL => true,
        self::TYPE_TAG_ARTICLE => true
    ];

    /**
     * 额外参数: 标签类别
     *
     * @var string
     */
    const EXTRA_OBJECT_TYPE = 'tag_type';

    /**
     * 额外参数: 对象
     *
     * @var string
     */
    const EXTRA_FILTER_ITEM = 'filter_item';

    /**
     * 额外参数: 取标签时输出数组的参数
     *
     * @var integer
     */
    const EXTRA_OUTDATA = 'out_data';
    const EXTRA_OUTDATA_TAGNAME = 1;
    const EXTRA_OUTDATA_ALL = 2;

    protected static $_extra_meta = [
        self::EXTRA_LOGIN_UID => [
            'filter' => FILTER_VALIDATE_INT,
            'default' => 0,
        ],
        self::EXTRA_OBJECT_TYPE => [
            'filter' => FILTER_VALIDATE_INT,
            'default' => 1,
            'options' => [
                'min_range' => 1,
                'max_range' => self::TYPE_TAG_ARTICLE,
            ],
        ],
        self::EXTRA_FILTER_ITEM => [
            'options' => [
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_FORCE_ARRAY,
            ],
        ],
        self::EXTRA_FETCH => [
            'filter' => FILTER_VALIDATE_INT,
            'options' => [
                'min_range' => self::FETCH_ID,
            ],
            'default' => self::FETCH_DATA_FIELD,
        ],
        self::EXTRA_FROM_W => [
            'filter' => FILTER_VALIDATE_BOOLEAN,
            'default' => false,
        ],
        self::EXTRA_REFRESH => [
            'filter' => FILTER_VALIDATE_BOOLEAN,
            'default' => false,
        ],
        self::EXTRA_OFFSET => [
            'filter' => FILTER_VALIDATE_INT,
            'default' => 0,
        ],
        self::EXTRA_LIMIT => [
            'filter' => FILTER_VALIDATE_INT,
            'default' => self::LIST_SIZE,
        ],
        self::EXTRA_OUTDATA => [
            'filter' => FILTER_VALIDATE_INT,
            'default' => self::EXTRA_OUTDATA_TAGNAME,
        ],
    ];


    private function __construct()
    {
    }

    /**
     * 为单个故事项目关联标签
     *
     * @param integer $type     产品类型,参见 self::TYPE_TAG_* 系列常量, 不能为 self::TYPE_TAG_ALL
     * @param integer $itemId   项目ID
     * @param integer $uid      用户ID
     * @param array   $tags     标签名数组
     * @param integer $tag_type 标签类型
     *                          0 - $tags中标签的key不是标签ID, 即 $tags = ['标签1', '标签2', ...]
     *                          1 - $tags中标签的key为标签ID, 即$tags = ['标签1ID' => '标签1', '标签2ID' => '标签2', ...]
     * @param integer $iscache  是否更新故事搜索
     * @return mixed
     *                          false - 参数错误或添加失败
     *                          array - 添加成功
     *                          ['标签ID' => '标签名', ...']
     */
    public static function bind($type, $itemId, $uid, $tags, $tag_type = 0, $iscache = 1)
    {
        if (!is_numeric($type) || ($type = (int)$type) < 0 || !isset(self::$_object_type_map[$type])
            || !is_numeric($itemId) || ($itemId = (int)$itemId) <= 0
            || !is_numeric($uid) || ($uid = (int)$uid) < 0 || empty($tags)
        ) {
            return false;
        }

        $bind_tags = $tags;
        if ($tag_type == 0) {
            $bind_tags = self::addTag($tags);
            if (empty($bind_tags)) {
                return false;
            }
        }

        $data = [];
        $now = date('Y-m-d H:i:s');

        foreach ($bind_tags as $tagid => $tag) {
            $data[$tagid] = "{$uid},{$tagid},{$itemId},{$type},'{$now}'";
        }
        $new_data = implode('),(', $data);

        $ins_sql = '';
        $exception = '';

        do {
            $commit_result = false;

            $db = Bridge::pdo(Bridge::DB_TAG_W, [
                'errorMode' => \PDO::ERRMODE_EXCEPTION,
            ]);

            try {
                $db->beginTransaction();

                $ins_sql = "INSERT IGNORE INTO Tag (`UserId`,`TagNameId`,`ItemId`,`Type`,`CreateTime`) VALUES ({$new_data})";
                $ins_result = $db->exec($ins_sql);
                if ($ins_result === false) {
                    break;
                }

                // 获取新插入的关联数据
                $tagid_str = implode(',', array_keys($data));
                $get_inserted = "SELECT `TagId` rid,`TagNameId` tid FROM Tag WHERE ItemId = {$itemId} AND TagNameId IN ({$tagid_str}) AND Type = {$type} AND CreateTime = '{$now}'";
                $stmt = $db->query($get_inserted);
                if (!$stmt) {
                    break;
                }

                $result = $stmt->fetchAll();

                if (is_array($result) && count($result) > 0) {
                    $table_suffix = sprintf('%02x', $itemId % 256);

                    // 同步至分表
                    foreach ($result as $row) {
                        $tid = (int)$row['tid'];

                        if (isset($data[$tid])) {
                            $ins_sql = "INSERT IGNORE INTO Tag_{$table_suffix} (`TagId`,`UserId`,`TagNameId`,`ItemId`,`Type`,`CreateTime`) VALUES ({$row['rid']},{$data[$tid]})";
                            $ins_result = $db->exec($ins_sql);
                            if ($ins_result === false) {
                                break;
                            }
                        }
                    }
                }

                if ($ins_result === false) {
                    $db->rollBack();
                } else {
                    $commit_result = $db->commit();
                }
            } catch (\Exception $e) {
                $exception = $e->getMessage();
            }
        } while (false);

        if ($commit_result) {
            $mc = Bridge::memcache();
            // 清除MC缓存: 对象的标签列表
            $mc->delete("Tags_M:{$type}_{$itemId}");
            //更新文章的TagCache
            if ($type == self::TYPE_TAG_ARTICLE) {
                //todo
                //Story::updateStory($itemId, ['TagCache' => $bind_tags], $iscache);
            }

            return $bind_tags;
        }

        runtime_log(self::LOG_FAIL_PATH, __METHOD__ . ": [{$ins_sql}], exception: {$exception} @ " . date('Y-m-d H:i:s'));

        return false;
    }

    /**
     * 解除指定项目与指定标签的关系
     *
     * @param integer $type     产品类型,参见 self::TYPE_TAG_* 系列常量, 不能为 self::TYPE_TAG_ALL
     * @param integer $itemId   项目ID
     * @param integer $uid      用户ID
     * @param array   $tags     标签ID数组,若要删除所有与指定项目ID相关的标签,不要传此参数
     * @param integer $tag_type 0: $tags为标签ID数组, 1: $tags为标签名数组
     * @return boolean
     */
    public static function unbind($type, $itemId, $uid, $tags = [], $tag_type = 0)
    {
        if (!is_numeric($type) || !is_numeric($itemId) || ($itemId = (int)$itemId) <= 0 || !is_numeric($uid) || ($uid = (int)$uid) < 0) {
            return false;
        }

        $cdn = '';
        $tags = (array)$tags;
        // 只删除指定的标签关联
        if (count($tags) > 0) {
            $ids = [];

            // 按标签ID移除
            if ($tag_type == 0) {
                foreach ($tags as $id) {
                    if (is_numeric($id) && ($id = (int)$id) > 0) {
                        $ids[$id] = $id;
                    }
                }
            } // 按标签名移除
            else {
                $arr = [];
                foreach ($tags as $name) {
                    $name = trim($name);
                    if (!empty($name)) {
                        $arr[$name] = $name;
                    }
                }
                if (!empty($arr)) {
                    $ids = self::getTagsByName($arr, $type);
                }
            }

            if (empty($ids)) {
                return false;
            }

            $data = implode(',', $ids);
            $cdn = "AND TagNameId IN ({$data})";
        }

        $raw_sql = "DELETE FROM `%s` WHERE ItemId = {$itemId} AND Type = {$type} {$cdn}";

        $del_result = $commit_result = false;

        $tables = [self::ENTIRE_TABLE_NAME, self::shardTable($itemId)];

        $db = Bridge::pdo(Bridge::DB_TAG_W, [
            'errorMode' => \PDO::ERRMODE_EXCEPTION,
        ]);

        $del_sql = '';
        $exception = '';

        try {
            $db->beginTransaction();

            foreach ($tables as $table) {
                $del_sql = sprintf($raw_sql, $table);
                $del_result = $db->exec($del_sql);

                if ($del_result === false) {
                    break;
                }
            }

            if ($del_result === false) {
                $db->rollBack();
            } else {
                $commit_result = $db->commit();
            }
        } catch (\Exception $e) {
            $exception = $e->getMessage();
        }

        if ($commit_result) {
            $mc = Bridge::memcache();
            // 清除MC缓存: 对象的标签列表
            $mc->delete("Tags_M:{$type}_{$itemId}");
            //更新文章的TagCache
            if ($type == self::TYPE_TAG_ARTICLE) {
                //todo
//                Story::updateStory($itemId, ['TagCache' => true]);
            }

            return true;
        }

        runtime_log(self::LOG_FAIL_PATH, __METHOD__ . ": [{$del_sql}], exception: {$exception} @ " . date('Y-m-d H:i:s'));

        return false;
    }

    /**
     * 在项目的原有标签关系基础上重新关联或解除关联
     *   若新的$tags中没有传入已有的标签, 该关联将被解除
     *   若新的$tags中拥有项目已有的标签, 将保持原有关联
     *   若新的$tags中拥有项目没有的标签, 将添加关联
     *
     * @param integer $type   产品类型,参见 self::TYPE_TAG_* 系列常量, 不能为 self::TYPE_TAG_ALL
     * @param integer $itemId 项目ID
     * @param integer $uid    用户ID
     * @param array   $tags   标签名数组,若要删除所有与指定项目ID相关的标签,不要传此参数
     * @return mixed
     */
    public static function rebind($type, $itemId, $uid, $tags = [])
    {
        if (!is_numeric($type) || !is_numeric($itemId) || ($itemId = (int)$itemId) <= 0
            || !is_numeric($uid) || ($uid = (int)$uid) < 0 || !is_array($tags)
        ) {
            return false;
        }

        $rets = $old_tags = self::getTagsByItemId($itemId, [
            self::EXTRA_OBJECT_TYPE => $type,
            self::EXTRA_REFRESH => true,
            self::EXTRA_FROM_W => true,
        ]);
        $old_name_id_map = array_flip($old_tags);

        if (count($tags) > 0) {
            $tags = array_combine($tags, $tags);
        }

        $new_tags = array_diff_key($tags, $old_name_id_map);
        $del_tags = array_diff_key($old_name_id_map, $tags);

        $binded = empty($new_tags) ? true : self::bind($type, $itemId, $uid, $new_tags);

        // 绑定新标签成功 或 没有新标签需要绑定
        if ($binded) {
            $unbinded = empty($del_tags) ? false : self::unbind($type, $itemId, $uid, $del_tags);

            if (is_array($binded)) {
                $rets += $binded;
            }

            if ($unbinded) {
                $rets = array_diff_key($rets, array_flip($del_tags));
            }
        }

        return $rets;
    }

    /**
     * 添加标签
     *
     * @param array $tags 标签名数组 ['标签名1', '标签名2', ...]
     * @return array
     *                    ['标签ID' => '标签名', ...]
     */
    public static function addTag($tags)
    {
        $rets = $names = [];

        // 只保留符合标准的Tag
        foreach ((array)$tags as $tag) {
            $tag = trim(strval($tag));
            if (preg_match(self::REGEXP_TAG_NAME, $tag)) {
                $names[$tag] = $tag;
            }
        }
        if (empty($names)) {
            return $rets;
        }

        $data = self::getTagsByName($names, [
            self::EXTRA_REFRESH => true,
            self::EXTRA_FROM_W => true,
        ]);
        $new_tags = array_diff_key($names, $data);

        if (count($new_tags) > 0) {
            $row = [];
            foreach ($new_tags as $tag) {
                //产生随机数
                $followcount = mt_rand(50, 100);
                $row[] = ['Tagname' => $tag, 'FollowCount' => $followcount];
            }
            $db = Bridge::pdo(Bridge::DB_TAG_W);
            $result = $db->insert(TagName, $row, ['ignore' => 'IGNORE']);
            if ($result !== false) {
                $new_tags = self::getTagsByName($new_tags, [
                    self::EXTRA_REFRESH => true,
                    self::EXTRA_FROM_W => true,
                ]);

                $data += $new_tags;
            }
        }

        if (count($data) > 0) {
            foreach ($names as $name) {
                if (isset($data[$name])) {
                    $rets[$data[$name]] = $name;
                }
            }
        }

        return $rets;
    }

    public static function updateTagIcon($id, $icon)
    {
        $icon_dir = '/data/pic/prj_qbtoutiao/tag/icon/';
        if (__ENV__ == 'DEV') {
            $icon_dir = BASEPATH_PUBLIC . 'temp/tag/icon/';
        }
        $hash = PhotoImage::getImageHash($id);
        $cfg = [
            'base' => $icon_dir,
            'thumb' => [
                'source' => [
                    'url' => '{hash}/{id}.png'
                ],
                '100' => [
                    'width' => 100,
                    'position' => 'middle',
                    'quality' => 85,
                    'watermark' => false,
                    'url' => '{hash}/{id}_100.png'
                ],
                '180' => [
                    'width' => 180,
                    'position' => 'middle',
                    'quality' => 85,
                    'watermark' => false,
                    'url' => '{hash}/{id}_180.png'
                ]
            ],
            'id' => $id,
            'hash' => $hash['hash1']
        ];

        $image_status = $icon;

        $ret = PhotoImage::imageResize($cfg, $image_status);
        if (is_array($ret) && $ret['status'] == true) {
            self::updateTagVer($id);

            return [
                'code' => self::RET_CODE_SUCC,
                'id' => $id
            ];
        }

        return false;
    }

    public static function updateTagDesc($id, $desc)
    {
        $pdo = Bridge::pdo(Bridge::DB_TAG_W);
        $rs = $pdo->update('TagName', ['TagDesc' => $desc], ['TagNameId' => $id]);
        if ($rs !== false) {
            $mc = Bridge::memcache();
            $mc->delete(self::MC_PFX . $id);

            return true;
        }

        return false;
    }

    public static function updateTagCover($id, $cover)
    {
        //todo
        $icon_dir = '/data/pic/prj_qbtoutiao/tag/cover/';
        if (__ENV__ == 'DEV') {
            $icon_dir = BASEPATH_PUBLIC . 'temp/tag/cover/';
        }
        $hash = PhotoImage::getImageHash($id);
        $cfg = [
            'base' => $icon_dir,
            'thumb' => [
                'source' => [
                    'url' => '{hash}/{id}.jpg'
                ],
                '450' => [
                    'width' => 450,
                    'position' => 'middle',
                    'quality' => 85,
                    'watermark' => false,
                    'url' => '{hash}/{id}_450.jpg'
                ]
            ],
            'id' => $id,
            'hash' => $hash['hash1']
        ];

        $image_status = $cover;

        $ret = PhotoImage::imageResize($cfg, $image_status);
        if (is_array($ret) && $ret['status'] == true) {
            self::updateTagVer($id);

            return [
                'code' => self::RET_CODE_SUCC,
                'id' => $id
            ];
        }

        return false;
    }

    public static function updateTagBannerImg($id, $img, $order = 1, $ver = 1)
    {
        //todo
        $cover_dir = '/data/pic/prj_qbtoutiao/tag/banner/';
        if (__ENV__ == 'DEV') {
            $cover_dir = BASEPATH_PUBLIC . 'temp/tag/banner/';
        }
        $hash = PhotoImage::getImageHash($id);
        $hash1 = $hash['hash1'];
        $cfg = [
            'base' => $cover_dir,
            'thumb' => [
                'source' => [
                    'url' => '{hash}/{id}_{order}_source.jpg'
                ],
                '450' => [
                    'width' => 450,
                    'height' => 290,
                    'position' => 'middle',
                    'quality' => 85,
                    'watermark' => false,
                    'url' => '{hash}/{id}_{order}.jpg'
                ]
            ],
            'id' => $id,
            'order' => $order,
            'hash' => $hash1
        ];

        $image_status = $img;

        $ret = PhotoImage::imageResize($cfg, $image_status);
        if (is_array($ret) && $ret['status'] == true) {
            $url = "http://i1.qbtoutiao.com/tag/banner/{$hash1}/{$id}_{$order}.jpg?v={$ver}";

            return $url;
        }

        return false;
    }

    public static function updateTagBanner($id, $data)
    {
        $pdo = Bridge::pdo(Bridge::DB_TAG_W);
        $rs = $pdo->update('TagName', ['Banner' => serialize($data)], ['TagNameId' => $id]);
        if ($rs !== false) {
            $mc = Bridge::memcache();
            $mc->delete(self::MC_PFX . $id);

            return true;
        }

        return false;
    }

    /**
     * 修改标签版本
     *
     * @param integer $id   标签ID
     * @return bool
     */
    public static function updateTagVer($id)
    {
        $taginfo = self::getTagsByIds($id, [self::EXTRA_OUTDATA => self::EXTRA_OUTDATA_ALL]);
        $ver = $taginfo[$id]['VerId'];
        $ver ++;
        $pdo = Bridge::pdo(Bridge::DB_TAG_W);
        $rs = $pdo->update('TagName', ['VerId' => $ver], ['TagNameId' => $id]);
        if ($rs !== false) {
            $mc = Bridge::memcache();
            $mc->delete(self::MC_PFX . $id);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 修改标签订阅数
     *
     * @param integer         $id   标签ID
     * @param string|integer  $num  变化的数值或设定的数（+表示累加，-表示累减，无符号表示设置为某值）
     * @return bool
     */
    public static function updateTagCount($id, $num)
    {
        $flag = substr($num, 0, 1);
        if ($flag == '+' || $flag == '-') {
            $row = ['FollowCount' => ['FollowCount', $num]];
        } else {
            $row = ['FollowCount' => $num];
        }
        $pdo = Bridge::pdo(Bridge::DB_TAG_W);
        $rs = $pdo->update('TagName', $row, ['TagNameId' => $id]);
        if ($rs !== false) {
            $mc = Bridge::memcache();
            $mc->delete(self::MC_PFX . $id);

            return true;
        }

        return false;
    }

    /**
     * 修改标签名
     *
     * @param integer $id   标签ID
     * @param string  $name 新的标签名
     * @return bool
     */
    public static function renameTag($id, $name)
    {
        $id = is_numeric($id) ? (int)$id : 0;
        $name = is_string($name) ? trim($name) : null;

        if ($id <= 0 || empty($name)) {
            return false;
        }

        $tag_result = self::getTagsByIds($id, [
            self::EXTRA_FROM_W => true,
            self::EXTRA_REFRESH => true,
        ]);

        // 标签不存在
        if (!isset($tag_result[$id])) {
            return false;
        }

        $name_result = self::getTagsByName($name, [
            self::EXTRA_OBJECT_TYPE => true,
            self::EXTRA_REFRESH => true,
            self::EXTRA_FROM_W => true,
        ]);

        // 已存在与新标签名同名的Tag
        if (isset($name_result[$name])) {
            return false;
        }

        $db = Bridge::pdo(Bridge::DB_TAG_W);

        $sql = "UPDATE `TagName` SET `TagName` = :name WHERE `TagNameId` = :id";
        $db->prepare($sql);
        $result = $db->execute([
            ':name' => $name,
            ':id' => $id,
        ]);

        if ($result !== false) {
            $mc = Bridge::memcache();

            $mc->delete(self::MC_PFX . $id);

            return true;
        }

        return false;
    }

    /**
     * 获取标签ID对应的标签名
     *
     * @param array|int $ids   标签ID数组
     * @param array     $extra 额外参数
     *                         EXTRA_FILTER_ITEM   - 过滤出与指定的ItemId相关的标签数据
     *                         EXTRA_OBJECT_TYPE   - 过滤指定项目类别的标签数据, 参见 self::TYPE_TAG_* 系列常量
     *                         EXTRA_FROM_W        - 是否从写库读取数据
     *                         EXTRA_REFRESH       - 是否强制刷新缓存
     *                         EXTRA_OFFSET        - 数据结果偏移
     *                         EXTRA_LIMIT         - 数据集大小
     *                         EXTRA_OUTDATA       - 输出标签的数据格式，
     *                                              参数self::EXTRA_OUTDATA_TAGNAME 表示输出 ['标签ID' => '标签名', ...]
     *                                              参数self::EXTRA_OUTDATA_ALL 表示输出标签相关所有的信息数组
     *
     * @return array
     *   EXTRA_OUTDATA = EXTRA_OUTDATA_TAGNAME时输出
     *    ['标签ID' => '标签名', ...]
     *   EXTRA_OUTDATA = EXTRA_OUTDATA_ALL时输出
     *    [
     *        '标签ID' = [
     *            'TageName' => '标签名'
     *        ],
     *        ...
     *     ]
     */
    public static function getTagsByIds($ids, $extra = [])
    {
        $mc_pfx = self::MC_PFX;
        $id_map = [];

        foreach ((array)$ids as $id) {
            if (is_numeric($id) && ($id = (int)$id) > 0) {
                $id_map[$id] = "{$mc_pfx}{$id}";
            }
        }

        if (empty($id_map)) {
            return $id_map;
        }

        $args = self::_parseExtras($extra);
        $outmod = $args[self::EXTRA_OUTDATA];
        $cache = [];
        $mc = Bridge::memcache();
        if (!$args[self::EXTRA_REFRESH]) {
            $cache = (array)$mc->get($id_map);
        }
        $rets = $no_cache = [];
        foreach ($id_map as $id => $mc_key) {
            if (isset($cache[$mc_key])) {
                $rets[$id] = $cache[$mc_key];
            } else {
                $no_cache[] = $id;
            }
        }

        if (count($no_cache) > 0) {
            $search_tag_ids = implode(',', $no_cache);
            $sql = "SELECT `TagNameId` `tid`,`TagName`,`VerId`,`Banner`,`TagDesc`, `FollowCount`  FROM `TagName` WHERE TagNameId IN ({$search_tag_ids})";

            $db = Bridge::pdo($args[self::EXTRA_FROM_W] ? Bridge::DB_TAG_W : Bridge::DB_TAG_R);

            $stmt = $db->query($sql);
            if ($stmt) {
                $result = $stmt->fetchAll();

                if (is_array($result) && count($result) > 0) {
                    foreach ($result as $row) {
                        $data = [];
                        $tagid = (int)$row['tid'];
                        $data['TagId'] = $row['tid'];
                        $data['TagName'] = $row['TagName'];
                        $data['Banner'] = $row['Banner'];
                        $data['VerId'] = $row['VerId'];
                        $data['TagDesc'] = $row['TagDesc'];
                        $data['FollowCount'] = $row['FollowCount'];
                        $rets[$tagid] = $data;

                        $mc->set($id_map[$tagid], $data);
                    }
                }
            }
        }
        $rs = [];
        //规则输出
        if (count($rets) > 0) {
            if ($outmod == self::EXTRA_OUTDATA_ALL) {
                foreach ($rets as $k => $v) {
                    $rs[$k] = $v;
                    if ($v['VerId'] > 0) {
                        $hash = sprintf('%02x', $k % 256);
                        $rs[$k]['Icon'] = autohost("http://i1.qbtoutiao.com/tag/icon/{$hash}/{$k}_180.png?v={$v['VerId']}");
                        $rs[$k]['Cover'] = autohost("http://i1.qbtoutiao.com/tag/cover/{$hash}/{$k}_450.jpg?v={$v['VerId']}");
                    } else {
                        $rs[$k]['Icon'] = autohost("http://i1.qbtoutiao.com/tag/icon/00/00_180.png");
                        $rs[$k]['Cover'] = autohost("http://i1.qbtoutiao.com/tag/cover/00/00_450.jpg");
                    }
                    $rs[$k]['Banner'] = unserialize($v['Banner']);

                }
            } else {
                foreach ($rets as $k => $v) {
                    $rs[$k] = $v['TagName'];
                }
            }

        }

        return $rs;
    }

    /**
     * 获取标签名获取对应的标签ID
     *
     * @param array $names 标签名数组
     * @param array $extra 额外参数
     *                     EXTRA_FILTER_ITEM   - 过滤出与指定的ItemId相关的标签数据
     *                     EXTRA_OBJECT_TYPE   - 过滤指定项目类别的标签数据, 参见 self::TYPE_TAG_* 系列常量
     *                     EXTRA_FROM_W        - 是否从写库读取数据
     *                     EXTRA_REFRESH       - 是否强制刷新缓存
     *                     EXTRA_OFFSET        - 数据结果偏移
     *                     EXTRA_LIMIT         - 数据集大小
     *
     * @return array
     *    ['标签名' => '标签ID', ...]
     */
    public static function getTagsByName($names, $extra = [])
    {
        $mc_pfx = "Tags_N:";
        $name_map = $origin_lower_map = $lower_origin_map = [];

        $names = (array)$names;

        foreach ($names as $name) {
            $name = trim(strval($name));
            if (mb_strlen($name) > 0) {
                // 解决同一标签大小写不同问题
                $lower_name = strtolower($name);
                if (!isset($lower_origin_map[$lower_name])) {
                    $lower_origin_map[$lower_name] = [];
                }
                $lower_origin_map[$lower_name][$name] = $name;
                $origin_lower_map[$name] = $lower_name;

                $name_map[$lower_name] = $mc_pfx . base64_encode($lower_name);
            }
        }

        if (empty($name_map)) {
            return [];
        }

        $counter = 0;
        $args = self::_parseExtras($extra);

        $mc = Bridge::memcache();
        $cache = $mc->get($name_map);

        is_array($cache) || $cache = [];
        $rets = $data = $params = [];

        foreach ($name_map as $lower_name => $mc_key) {
            if (is_numeric($cache[$mc_key])) {
                $tagid = (int)$cache[$mc_key];
                $origin_name_list = $lower_origin_map[$lower_name];

                foreach ($origin_name_list as $origin_name) {
                    $data[$origin_name] = $tagid;
                }
            } else {
                $param_key = ':Tag' . (++$counter);
                $params[$param_key] = $lower_name;
            }
        }

        if (count($params) > 0) {
            $param_keys = implode(',', array_keys($params));
            $sql = "SELECT `TagNameId` `tid`,`TagName` `name` FROM `TagName` WHERE `TagName` COLLATE utf8_unicode_ci IN ({$param_keys})";

            $db = Bridge::pdo($args[self::EXTRA_FROM_W] ? Bridge::DB_TAG_W : Bridge::DB_TAG_R);

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if ($stmt) {
                $result = $stmt->fetchAll();

                if (is_array($result)) {
                    foreach ($result as $row) {
                        $id = (int)$row['tid'];
                        $name = $row['name'];

                        $lower_name = strtolower(trim($name));
                        $origin_name_list = $lower_origin_map[$lower_name];

                        foreach ($origin_name_list as $origin_name) {
                            $data[$origin_name] = $id;
                        }

                        $data[$name] = $id;
                        $mc->set($name_map[$lower_name], $id);
                    }
                }
            }
        }

        if (count($data) > 0) {
            foreach ($origin_lower_map as $name => $lower_name) {
                if (isset($data[$name])) {
                    $rets[$name] = $data[$name];
                }
            }
        }

        return $rets;
    }

    /**
     * 获取与指定项目ID关联的标签列表
     *
     * @param integer $itemId 项目ID
     * @param array   $extra  额外参数
     *                        EXTRA_FILTER_ITEM   - 过滤出与指定的ItemId相关的标签数据
     *                        EXTRA_FROM_W        - 是否从写库读取数据
     *                        EXTRA_REFRESH       - 是否强制刷新缓存
     *                        EXTRA_OFFSET        - 数据结果偏移
     *                        EXTRA_LIMIT         - 数据集大小
     *
     * @return array
     *    ['标签ID' => '标签名', ...]
     */
    public static function getTagsByItemId($itemId, $extra = [])
    {
        if (!is_numeric($itemId) || ($itemId = (int)$itemId) <= 0) {
            return [];
        }

        $args = self::_parseExtras($extra);
        $type = self::TYPE_TAG_ARTICLE;//暂时只有一个类型

        $mc = Bridge::memcache();
        $mc_key = "Tags_M:{$type}_{$itemId}";
        $cache = $args[self::EXTRA_REFRESH] ? [] : $mc->get($mc_key);
        $cache_status = (int)$cache;
        is_array($cache) || $cache = [];

        if (empty($cache) && $cache_status !== -1) {
            $table_suffix = sprintf('%02x', $itemId % 256);
            $sql = "SELECT `TagId` rid,`TagNameId` tid FROM Tag_{$table_suffix} WHERE ItemId = {$itemId} AND Type = {$type} ORDER BY TagId DESC";

            $db = Bridge::pdo($args[self::EXTRA_FROM_W] ? Bridge::DB_TAG_W : Bridge::DB_TAG_R);

            $stmt = $db->query($sql);
            if ($stmt) {
                $result = $stmt->fetchAll();

                if (is_array($result)) {
                    foreach ($result as $row) {
                        $cache[$row['rid']] = (int)$row['tid'];
                    }
                    $mc->set($mc_key, empty($cache) ? -1 : $cache);
                }
            }
        }

        $rets = empty($cache) ? $cache : self::getTagsByIds($cache);

        return $rets;
    }

    /**
     * 通过标签ID获取关联项目列表
     *
     * @param integer $tagid 标签ID
     * @param array   $extra 额外参数
     *                       EXTRA_FILTER_ITEM   - 过滤出与指定的ItemId相关的标签数据
     *                       EXTRA_OBJECT_TYPE   - 过滤指定项目类别的标签数据, 参见 self::TYPE_TAG_* 系列常量
     *                       EXTRA_FROM_W        - 是否从写库读取数据
     *                       EXTRA_REFRESH       - 是否强制刷新缓存
     *                       EXTRA_OFFSET        - 数据结果偏移
     *                       EXTRA_LIMIT         - 数据集大小
     *
     * @return array
     *  当 $type 为 TYPE_TAG_ALL 时
     *    ['关联ID' => ['TagId' => '关联ID', 'ItemId' => '项目ID', 'Type' => 'Tag类别'], ...]
     *  当 $type 为其他值时
     *    ['关联ID' => '项目ID', ...]
     */
    public static function getItemsByTagId($tagid, $extra = [])
    {
        if (!is_numeric($tagid) || ($tagid = (int)$tagid) <= 0) {
            return [];
        }

        $args = self::_parseExtras($extra);
        $raw_items = $args[self::EXTRA_FILTER_ITEM];
        $type = $args[self::EXTRA_OBJECT_TYPE];
        $offset = $args[self::EXTRA_OFFSET];
        $limit = $args[self::EXTRA_LIMIT];

        $items = [];
        $item_cdn = '1 = 1';

        if (is_array($raw_items)) {
            foreach ($raw_items as $id) {
                if (is_numeric($id) && ($id = (int)$id) > 0) {
                    $items[$id] = $id;
                }
            }
        }

        $item_count = count($items);

        if ($item_count > 1) {
            $items = implode(',', $items);
            $item_cdn = "ItemId IN ({$items})";
        } else if ($item_count == 1) {
            $items = current($items);
            $item_cdn = "ItemId = {$items}";
        }

        if ($type === self::TYPE_TAG_ALL) {
            $type_cdn = '';
            $type_field = ',Type';
        } else {
            $type_cdn = "Type = {$type} AND";
            $type_field = '';
        }

        $rets = [];

        $_offset = $offset < 0 ? 0 : (int)$offset;
        $_limit = $limit > 1000 ? self::LIST_SIZE : (int)$limit;

        $sql = "SELECT TagId, ItemId {$type_field} FROM Tag WHERE {$item_cdn} AND {$type_cdn} TagNameId = {$tagid} ORDER BY TagId DESC LIMIT {$_offset},{$_limit}";

        $db = Bridge::pdo($args[self::EXTRA_FROM_W] ? Bridge::DB_TAG_W : Bridge::DB_TAG_R);

        $stmt = $db->query($sql);
        if ($stmt) {
            $result = $stmt->fetchAll();

            if (is_array($result)) {
                if ($type === self::TYPE_TAG_ALL) {
                    foreach ($result as $row) {
                        $rets[$row['TagId']] = $row;
                    }
                } else {
                    foreach ($result as $row) {
                        $rets[$row['TagId']] = (int)$row['ItemId'];
                    }
                }
            }
        }

        return $rets;
    }

    /**
     * 通过标签名获取关联的项目列表
     *
     * @param string $tag   标签名
     * @param array  $extra 额外参数
     *                      EXTRA_FILTER_ITEM   - 过滤出与指定的ItemId相关的标签数据
     *                      EXTRA_OBJECT_TYPE   - 过滤指定项目类别的标签数据, 参见 self::TYPE_TAG_* 系列常量
     *                      EXTRA_FROM_W        - 是否从写库读取数据
     *                      EXTRA_REFRESH       - 是否强制刷新缓存
     *                      EXTRA_OFFSET        - 数据结果偏移
     *                      EXTRA_LIMIT         - 数据集大小
     *
     * @return array
     *  当 $type 为 TYPE_TAG_ALL 时
     *    ['关联ID' => ['TagId' => '关联ID', 'ItemId' => '项目ID', 'Type' => 'Tag类别'], ...]
     *  当 $type 为其他值时
     *    ['关联ID' => '项目ID', ...]
     */
    public static function getItemsByTagName($tag, $extra = [])
    {
        $args = self::_parseExtras($extra);
        $type = $args[self::EXTRA_OBJECT_TYPE];

        $res = self::getTagsByName($tag, $type);
        if (empty($res)) {
            return [];
        }

        $tagid = array_shift($res);

        $rets = self::getItemsByTagId($tagid, array_merge($extra, $args));

        return $rets;
    }

    /**
     * 计算数据表名
     *
     * @param integer $id 对象ID
     * @return string
     */
    public static function shardTable($id)
    {
        return sprintf(self::SHARD_TABLE_NAME, $id % 256);
    }
}