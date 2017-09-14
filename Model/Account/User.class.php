<?php

/**
 * 用户 - 信息类
 *
 */

namespace Model\Account;

use Bare\CommonModel;
use Bare\DB;
use Classes\Image\PhotoImage;
use Common\RedisConst;

class User extends CommonModel
{
    // 日志路径定义
    const LOG_FAIL_PATH = 'Account/User/Fail';
    const LOG_SUCC_PATH = 'Account/User/Succ';
    // redis 前缀定义
    const RD_PFX = 'UNA:';
    // MC 前缀定义
    const MC_PFX = 'AU:';
    // 表名/分表名
    const ENTIRE_TABLE_NAME = 'User';
    const SHARD_TABLE_NAME = 'User_%02x';
    // 头像的图片尺寸
    const AVATAR_MIDDLE = 100;
    const AVATAR_LARGE = 180;
    // 计数字段
    const COUNT_AVATAR = 1;  // 头像版本号
    const COUNT_LOGIN = 2;  // 登录计数
    const COUNT_FAVORITE = 3;  // 登录计数

    protected static $_count_fields = [
        self::COUNT_AVATAR => 'Avatar',
        self::COUNT_LOGIN => 'LoginCount',
        self::COUNT_FAVORITE => 'BookCount',
    ];

    protected static $_avatar_sizes = [
        self::AVATAR_MIDDLE => 100,
        self::AVATAR_LARGE => 180,
    ];
    /**
     * 用户信息表字段
     *
     * @var array
     */
    protected static $_field_schema = [
        'UserId' => self::VAR_TYPE_INT,
        'LoginName' => self::VAR_TYPE_STRING,
        'UserNick' => self::VAR_TYPE_STRING,
        'Gender' => self::VAR_TYPE_INT,
        'Avatar' => self::VAR_TYPE_INT,
        'Birthday' => self::VAR_TYPE_STRING,
        'LoginCount' => self::VAR_TYPE_INT,
        'LoginTime' => self::VAR_TYPE_STRING,
        'CreateTime' => self::VAR_TYPE_STRING,
        'Status' => self::VAR_TYPE_INT,
        'BookCount' => self::VAR_TYPE_INT,
    ];

    protected static $_property_user_fields = [
        'Avatar' => true
    ];

    protected static $_unalterable_user_fields = [
        'UserId' => true,
        'CreateTime' => true,
    ];

    protected static $_user_cache = [];
    protected static $_nick_cache = [];

    /**
     * 添加用户信息
     *
     * @param array $info ['UserId' => '用户ID', 'LoginName' => '登录名', 'UserNick' => '昵称']
     * @return mixed
     *                    integer - 总表数据成功插入,分表数据成功插入且受影响行数为 1,返回此次操作插入数据的用户ID.
     *                    true    - 总表数据成功插入,分表数据成功插入,但两者受影响行数都为 0.
     *                    false   - 总表数据插入失败,或分表数据插入失败.
     */
    public static function addUser($info)
    {
        static $required_fields = [
            'UserId' => true,
            'LoginName' => true,
            'UserNick' => true,
        ];

        $data = self::checkFields($info, self::$_field_schema);
        $diff = array_diff_key($required_fields, $data);
        // 用户必填信息不完整
        if (count($diff) > 0) {
            return ['code' => 201, 'msg' => '用户必填信息不完整'];
        }
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'Avatar' => 0,
            'LoginCount' => 0,
            'CreateTime' => $now,
        ], $data);

        $id = (int)$data['UserId'];
        $ins_result = $commit_result = false;

        $tables = [self::ENTIRE_TABLE_NAME, self::shardTable($id)];

        $db = DB::pdo(DB::DB_ACCOUNT_W, [
            'errorMode' => \PDO::ERRMODE_EXCEPTION,
        ]);

        $exception = '';
        try {
            $db->beginTransaction();
            foreach ($tables as $table) {
                $ins_result = $db->insert($table, $data, ['ignore' => true]);
                if ($ins_result === false) {
                    break;
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
        if ($commit_result) {
            self::_updateNickAvatarCache($id, $data);
            $rets = ['code' => 200, 'msg' => '成功'];
            $rets['id'] = $id;

            return $rets;
        }

        logs(__METHOD__ . ": " . serialize($data) . ", exception: {$exception} @ {$now}",
            self::LOG_FAIL_PATH . '_addUser');

        return ['code' => 202, 'msg' => '失败'];
    }

    /**
     * 更新单个用户信息
     *
     * @param integer $id   用户ID
     * @param array   $info 新的用户信息
     * @return mixed
     *                      nulll - 用户不存在或没有合法的更新字段
     *                      true  - 更新成功
     *                      flase - 更新失败
     */
    public static function updateUser($id, $info)
    {
        $data = self::getUserById($id, [
            self::EXTRA_REFRESH => true,
            self::EXTRA_FROM_W => true,
        ]);
        if (empty($data)) {
            return null;
        }

        $newdata = array_diff_key((array)$info, self::$_unalterable_user_fields, self::$_property_user_fields);
        $newdata = self::checkFields($newdata);
        if (empty($newdata)) {
            return null;
        }

        $params = $param_fields = [];

        foreach ($newdata as $field => $val) {
            if (true || $data[$field] != $val) {
                $param_fields[$field] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $val;
            }
        }

        if (empty($param_fields)) {
            return true;
        }

        $newdata_str = implode(', ', $param_fields);

        $upd_result = $commit_result = false;

        $tables = [self::ENTIRE_TABLE_NAME, self::shardTable($id)];

        $db = DB::pdo(DB::DB_ACCOUNT_W, [
            'errorMode' => \PDO::ERRMODE_EXCEPTION,
        ]);

        $sql = '';
        $exception = '';
        try {
            $db->beginTransaction();
            foreach ($tables as $table) {
                $sql = "UPDATE `{$table}` SET {$newdata_str} WHERE UserId = {$id}";
                $stmt = $db->prepare($sql);
                $upd_result = $stmt->execute($params);
                if ($upd_result === false) {
                    break;
                }
            }
            if ($upd_result === false) {
                $db->rollBack();
            } else {
                $commit_result = $db->commit();
            }
        } catch (\Exception $e) {
            $exception = $e->getMessage();
        }

        if ($commit_result) {
            self::_updateNickAvatarCache($id, $newdata);
            $mc = DB::memcache();
            // 清除MC缓存: 用户详情
            $mc->delete(self::MC_PFX . $id);
            unset(self::$_user_cache[$id]);

            return true;
        }

        logs(__METHOD__ . ": {$sql}, params: [" . serialize($params) . "], exception: {$exception} @ ",
            self::LOG_FAIL_PATH);

        return false;
    }


    /**
     * 上传用户头像
     *
     * @param integer $uid          用户ID
     * @param array   $image_status 图片校验结果(PhotoImage::checkImage 返回对象)
     * @param array   $crop_info    图片裁剪区域信息
     * @return array
     */
    public static function uploadAvatar($uid, $image_status, $crop_info = [])
    {
        $avatar_dir = UPLOAD_PATH . 'head/';
        $hash_arr = PhotoImage::getImageHash($uid);
        $tmp_name = $image_status['tmp_name'];
        switch ($image_status['image_type']) {
            case 'jpg':
                $image = imagecreatefromjpeg($tmp_name);
                break;
            case 'gif':
                $image = imagecreatefromgif($tmp_name);
                break;
            case 'png':
                $image = imagecreatefrompng($tmp_name);
                break;
            case 'bmp':
                $image = PhotoImage::imagecreatefrombmp($tmp_name);
                break;
            default:
                $log_path = self::LOG_FAIL_PATH . '_uploadAvatar';

                logs([
                    'error' => 'image_type error!',
                    'status' => serialize($image_status),
                ], $log_path);

                return [
                    'code' => 201,
                    'msg' => '无法确定图片类别，请更换图片后重试',
                ];
        }
        $image_type = $image_status['image_type'];

        // 原图宽高
        $image_width = (int)$image_status['image_width'];
        $image_height = (int)$image_status['image_height'];

        is_array($crop_info) || $crop_info = [];

        $not_required = [
            'x' => 0,
            'y' => 0,
            'w' => 0,
            'h' => 0,
            'bw' => 0,
            'bh' => 0,
        ];
        $diff = array_diff_key($not_required, $crop_info);

        // 手机端调用不提供 $not_required 中的参数
        if (count($diff) > 0) {
            $crop_info += $not_required;

            $crop_size = $image_width > $image_height ? $image_height : $image_width;

            // 裁剪区域的宽高
            $crop_width = $crop_height = $crop_size;

            // 从原图载入的区域起始x,y坐标
            $src_x = (int)round(($image_width - $crop_width) / 2);
            $src_y = (int)round(($image_height - $crop_height) / 2);
        } else {
            // 裁剪区域的宽高
            $crop_width = (int)$crop_info['w'];
            $crop_height = (int)$crop_info['h'];

            // 从原图载入的区域起始x,y坐标
            $src_x = (int)$crop_info['x'];
            $src_y = (int)$crop_info['y'];
        }

        // 原图缩放后的宽高,不缩放则为0 (网页中是对缩放后的图片进行裁剪)
        $zoom_width = (int)$crop_info['bw'];
        $zoom_height = (int)$crop_info['bh'];

        $dest_dir = $avatar_dir . implode(DIRECTORY_SEPARATOR, $hash_arr) . DIRECTORY_SEPARATOR;
        if (!file_exists($dest_dir)) {
            mkdir($dest_dir, 0777, true);
        }

        $quality = 90;
        $size_map = self::$_avatar_sizes;

        foreach ($size_map as $size) {
            // 裁剪后的宽高
            $dst_w = $size;
            $dst_h = $size;

            $name = "{$uid}_{$dst_w}.jpg";
            $dest_path = "{$dest_dir}{$name}";

            $result = PhotoImage::crop($image_type, $image, $dest_path, $image_width, $image_height, $crop_width,
                $crop_height, $dst_w, $dst_h, $src_x, $src_y, $zoom_width, $zoom_height, $quality);

            if (!$result) {
                return [
                    'code' => 202,
                    'msg' => '个人头像保存失败，请重试',
                ];
            }
        }

        // 更新原图
        $dest_path = "{$dest_dir}{$uid}.jpg";
        $cp_result = copy($image_status['tmp_name'], $dest_path);
        if (!$cp_result) {
            logs("{$image_status['tmp_name']} -> {$dest_path}", self::LOG_FAIL_PATH . '_Avatar');
        }

        $user = self::getUserById($uid, [
            self::EXTRA_REFRESH => true,
            self::EXTRA_FROM_W => true,
        ]);

        $avatar = (int)$user['Avatar'];

        $avatar += 1;

        $upd_result = self::updateCount($uid, [
            self::COUNT_AVATAR => '+1',
        ]);
        if ($upd_result['code'] === 200) {
            $redis = DB::redis(RedisConst::ACCOUNT_DB_W, RedisConst::ACCOUNT_DB_INDEX);
            // 更新REDIS缓存中的头像版本号
            $redis->hSet(self::RD_PFX . $uid, 'avatar', $avatar);

            unset(self::$_nick_cache[$uid]);
        }

        return [
            'code' => 200,
            'msg' => '保存头像成功',
            'url' => head($uid, self::AVATAR_MIDDLE, $avatar),
        ];
    }

    /**
     * 从第三方平台同步头像
     *
     * @param integer $id  用户ID
     * @param string  $url 头像URL
     * @return array
     */
    public static function crawlAvatar($id, $url)
    {
        $rets = ['code' => 201, 'msg' => '失败'];

        do {
            $ch = curl_init();
            if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $content = curl_exec($ch);

            $info = curl_getinfo($ch);
            curl_close($ch);

            if ($info['http_code'] == 200) {
                static $MAX_AVATAR_SIZE = 2097152; // 2M

                // 限定图片最大体积
                if ($info['size_download'] <= $MAX_AVATAR_SIZE) {
                    $filename = tempnam(ini_get('upload_tmp_dir'), 'avatar');

                    $result = file_put_contents($filename, $content);
                    if (!$result) {
                        logs("[crawlAvatar] uid: {$id}, url: {$url} @ ", self::LOG_FAIL_PATH);

                        return ['code' => 202, 'msg' => '获取图片失败'];
                    }

                    $image = [
                        'tmp_name' => $filename,
                    ];

                    $image_status = PhotoImage::checkImage($image, 1, 1, $MAX_AVATAR_SIZE);
                    $check_code = $image_status['code'];
                    if ($check_code != 0) {
                        static $_msgs = [
                            2 => '图片上传失败',
                            3 => '图片类型错误',
                            4 => '无法获取图片尺寸',
                            5 => '图片尺寸不可小于1*1像素',
                            6 => '图片大小不能大于2M',
                        ];
                        $rets = ['code' => 203, 'msg' => '上传图片失败'];
                        $rets['msg'] = isset($check_code) ? $_msgs[$check_code] : $image_status['msg'];

                        return $rets;
                    }

                    $rets = self::uploadAvatar($id, $image_status);
                }
            }
        } while (false);

        return $rets;
    }

    /**
     * 更新单个用户的计数器信息
     *
     * @param integer $id   用户ID
     * @param array   $info 计数器类别,可以为包含类别的数组. 请参考 User::COUNT_* 系列常量
     * @return boolean
     */
    public static function updateCount($id, $info)
    {
        $data = self::getUserById($id, [
            self::EXTRA_FROM_W => true,
            self::EXTRA_REFRESH => true,
        ]);
        if (empty($data)) {
            return -1;
        }

        $_info = self::_parseCount($info, self::$_count_fields);
        if (empty($_info)) {
            return -2;
        }

        $newdata = implode(',', $_info);

        $upd_result = $commit_result = false;

        $tables = [self::ENTIRE_TABLE_NAME, self::shardTable($id)];

        $db = DB::pdo(DB::DB_ACCOUNT_W, [
            'errorMode' => \PDO::ERRMODE_EXCEPTION,
        ]);

        $sql = '';
        $exception = '';

        try {
            $db->beginTransaction();
            foreach ($tables as $table) {
                $sql = "UPDATE `{$table}` SET {$newdata} WHERE `UserId` = {$id}";
                $upd_result = $db->exec($sql);
                if ($upd_result === false) {
                    break;
                }
            }
            if ($upd_result === false) {
                $db->rollBack();
            } else {
                $commit_result = $db->commit();
            }
        } catch (\Exception $e) {
            $exception = $e->getMessage();
        }

        if ($commit_result) {
            $mc = DB::memcache();
            // 清除MC缓存: 用户详情
            $mc->delete(self::MC_PFX . $id);

            unset(self::$_user_cache[$id]);

            return true;
        }

        logs(__METHOD__ . ": {$sql}, exception: {$exception} @ ", self::LOG_FAIL_PATH);

        return false;
    }

    /**
     * 获取单个用户的信息
     *
     * @param integer $id    用户ID
     * @param array   $extra 额外参数
     *                       EXTRA_AVATAR  - 计算指定大小的用户头像(请参考 head()), 0表不计算头像, 默认不获取头像
     *                       EXTRA_REFRESH - 为true时表示不使用缓存,直接从数据库中取值, 默认为false
     *                       EXTRA_FROM_W  - 是否从写库取数据
     * @return mixed
     *                       null  - 参数有误或用户不存在
     *                       array - 用户信息
     *                       ['UserId' => 'xx', 'UserNick' => 'xx', ..]
     */
    public static function getUserById($id, $extra = [])
    {
        if (!is_numeric($id) || ($id = (int)$id) <= 0) {
            return null;
        }
        $static_cache = &self::$_user_cache;
        $extra = (array)$extra;
        $args = self::_parseExtras($extra);
        $refresh = $args[self::EXTRA_REFRESH];
        if (isset($static_cache[$id]) && !$refresh) {
            $data = $static_cache[$id];
        } else {
            $mc = DB::memcache();
            $mc_key = self::MC_PFX . $id;
            $data = $refresh ? [] : $mc->get($mc_key);
            if (!is_array($data) || !isset($data['UserId'])) {
                $table = self::shardTable($id);
                $sql = "SELECT * FROM `{$table}` WHERE `UserId` = {$id}";
                $db = DB::pdo($args[self::EXTRA_FROM_W] ? DB::DB_ACCOUNT_W : DB::DB_ACCOUNT_R);
                $stmt = $db->query($sql);
                if ($stmt) {
                    $data = $stmt->fetch();
                    if (is_array($data) && isset($data['UserId'])) {
                        $mc->set($mc_key, $data);
                        $static_cache[$id] = $data;
                    }
                }
            }
        }
        $avatar = (int)$args[self::EXTRA_AVATAR];
        // 头像
        if (($avatar = (int)$avatar) > 0 && isset(self::$_avatar_sizes[$avatar])) {
            $data['AvatarUrl'] = head($id, $avatar, (int)$data['Avatar']);
        }

        return $data;
    }

    /**
     * 获取多个用户的信息
     *
     * @param array $ids   用户ID数组
     * @param array $extra 额外参数
     *                     EXTRA_AVATAR  - 计算指定大小的用户头像(请参考 head()), 0表不计算头像, 默认不获取头像
     *                     EXTRA_REFRESH - 为true时表示不使用缓存,直接从数据库中取值, 默认为false
     *                     EXTRA_FROM_W  - 是否从写库取数据
     * @return array
     *                     ['用户ID' => ['UserId'=>'xx', 'UserNick'=>'xx', ..], ..]
     */
    public static function getUsersByIds($ids, $extra = [])
    {
        $id_map = [];
        foreach ((array)$ids as $id) {
            if (is_numeric($id) && ($id = (int)$id) > 0) {
                $id_map[$id] = self::MC_PFX . $id;
            }
        }
        if (empty($id_map)) {
            return $id_map;
        }
        $static_cache = &self::$_user_cache;
        $args = self::_parseExtras($extra);
        $refresh = $args[self::EXTRA_REFRESH];
        $avatar = (int)$args[self::EXTRA_AVATAR];
        $mc = DB::memcache();
        $rets = $cache = [];
        if (!$refresh) {
            $cache = array_intersect_key($static_cache, $id_map);
            $diff_ids = array_diff_key($id_map, $cache);
            if (count($diff_ids) > 0) {
                $mcache = $mc->get($diff_ids);
                if (is_array($mcache) && count($mcache) > 0) {
                    foreach ($mcache as $mc_key => $single) {
                        if (is_array($single) && isset($single['UserId'])) {
                            $cache[$single['UserId']] = $single;
                        }
                    }
                    unset($mcache);
                }
            }
        }
        $nocache_ids = array_diff_key($id_map, $cache);
        if (count($nocache_ids) > 0) {
            $ids_cdn = implode(',', array_keys($nocache_ids));
            $sql = "SELECT * FROM `User` WHERE `UserId` IN ({$ids_cdn})";
            $db = DB::pdo($args[self::EXTRA_FROM_W] ? DB::DB_ACCOUNT_W : DB::DB_ACCOUNT_R);
            $stmt = $db->query($sql);
            if ($stmt) {
                $result = $stmt->fetchAll();
                if (is_array($result) && count($result) > 0) {
                    foreach ($result as $row) {
                        $id = (int)$row['UserId'];
                        $mc->set($id_map[$id], $row);
                        $static_cache[$id] = $cache[$id] = $row;
                    }
                    unset($result);
                }
            }
        }
        foreach ($id_map as $id => $mc_key) {
            $data = isset($cache[$id]) ? $cache[$id] : null;
            if (is_array($data) && isset($data['UserId'])) {
                if ($avatar) { // 头像
                    $data['AvatarUrl'] = head($id, $avatar, (int)$data['Avatar']);
                }
                $rets[$id] = $data;
            }
        }

        return $rets;
    }

    /**
     * 通过用户ID获取昵称
     *
     * @param         mixed   (integer|array) $uids 单个用户ID/用户ID数组
     * @param integer $avatar 头像尺寸,0与-1均表不获取头像. 默认值为0
     *
     * @return mixed
     *    false - 参数错误
     *
     *    当 $uids 为单个用户ID时
     *       a). 当$avatar为合法头像尺寸时
     *          ['UserId' => '用户ID', 'UserName' => '用户名称', 'UserNick' => '用户昵称', 'AvatarUrl' => '用户头像地址']
     *       b). 当$avatar为0时
     *          ['UserId' => '用户ID', 'UserName' => '用户名称', 'UserNick' => '用户昵称']
     *
     *    当 $uids 为用户ID数组时
     *       ['用户ID' => [array|string] @此处规则跟 $uids 为单个用户时一致@, ...]
     */
    public static function getNickByUserId($uids, $avatar = 0)
    {
        $single = !is_array($uids);
        $rets = $id_map = [];
        foreach ((array)$uids as $id) {
            if (is_numeric($id) && ($id = (int)$id) > 0) {
                $id_map[$id] = self::RD_PFX . $id;
            }
        }
        if (empty($id_map)) {
            return $rets;
        }
        $static_cache = &self::$_nick_cache;
        $nocache_ids = array_diff_key($id_map, $static_cache);
        if (count($nocache_ids) > 0) {
            static $fields = ['name', 'nick', 'avatar'];
            $redis = DB::redis(RedisConst::ACCOUNT_DB_R, RedisConst::ACCOUNT_DB_INDEX);
            $redis->multi(\Redis::PIPELINE);
            foreach ($nocache_ids as $id => $rds_key) {
                $redis->hMget($rds_key, $fields);
            }
            $rds_result = $redis->exec();
            $idx_id_map = array_keys($nocache_ids);
            foreach ($idx_id_map as $idx => $id) {
                $item = $rds_result[$idx];
                if (!empty($item['name'])) {
                    $static_cache[$id] = [
                        'name' => $item['name'],
                        'nick' => $item['nick'],
                        'avatar' => $item['avatar'] ? $item['avatar'] : 0,
                    ];
                }
            }
        }
        $avatar = ($avatar = (int)$avatar) >= 0 ? (isset(self::$_avatar_sizes[$avatar]) ? $avatar : 0) : -1;
        if ($avatar > 0) {
            foreach ($id_map as $id => $rds_key) {
                if (isset($static_cache[$id])) {
                    $user = $static_cache[$id];
                    $rets[$id] = [
                        'UserId' => $id,
                        'UserNick' => $user['nick'],
                        'UserName' => $user['name'],
                        'AvatarUrl' => head($id, $avatar, $user['avatar']),
                    ];
                }
            }
        } else {
            foreach ($id_map as $id => $rds_key) {
                if (isset($static_cache[$id])) {
                    $user = $static_cache[$id];
                    $rets[$id] = [
                        'UserId' => $id,
                        'UserNick' => $user['nick'],
                        'UserName' => $user['name'],
                    ];
                }
            }
        }

        return ($single ? current($rets) : $rets);
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

    /**
     * 更新用户redis缓存
     *
     * @param $id
     * @param $data
     */
    private static function _updateNickAvatarCache($id, $data)
    {
        static $_field_map = [
            'UserNick' => 'nick',
            'LoginName' => 'name',
            'Avatar' => 'avatar',
        ];

        $fresh_data = [];
        foreach ($_field_map as $src => $dest) {
            if (isset($data[$src])) {
                $fresh_data[$dest] = $data[$src];
            }
        }

        if (count($fresh_data) > 0) {
            $redis = DB::redis(RedisConst::ACCOUNT_DB_W, RedisConst::ACCOUNT_DB_INDEX);
            $redis->hMset(self::RD_PFX . $id, $fresh_data);
        }
    }
}
