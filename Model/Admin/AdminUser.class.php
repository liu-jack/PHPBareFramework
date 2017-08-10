<?php
/**
 * AdminUser.class.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/5/24 12:33
 *
 */

namespace Model\Admin;

use Bare\Model;
use Bare\DB;

/**
 * 后台用户模型
 * Class AdminUser
 * @package Model\Admin
 */
class AdminUser extends Model
{
    /**
     * 配置文件
     *
     * @var array
     */
    protected static $_conf = [
        // 必选, 数据库连接(来自DBConfig配置), w: 写, r: 读
        'db' => [
            'w' => DB::DB_ADMIN_W,
            'r' => DB::DB_ADMIN_R
        ],
        // 必选, 数据表名
        'table' => 'AdminUser',
        // 必选, 字段信息
        'fields' => [
            'UserId' => self::VAR_TYPE_KEY,
            'UserName' => self::VAR_TYPE_STRING,
            'Password' => self::VAR_TYPE_PASSWORD,
            'RealName' => self::VAR_TYPE_STRING,
            'UserGroup' => self::VAR_TYPE_INT,
            'SpecialGroups' => self::VAR_TYPE_ARRAY,
            'Status' => self::VAR_TYPE_INT,
        ],
        // 可选, MC连接参数
        'mc' => '',
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        'mckey' => '',
        // 可选, 超时时间, 默认不过期
        'mctime' => 86400
    ];

    /**
     * 新增必须字段
     * @var array
     */
    private static $_add_must_fields = [
        'UserName' => 1,
        'Password' => 1,
        'RealName' => 1,
    ];

    /**
     * 新增
     * @param $data
     * @param bool $ignore
     * @return bool|int|string
     */
    public static function addUser($data, $ignore = true)
    {
        if (count(array_diff_key(self::$_add_must_fields, $data)) > 0) {
            return false;
        }
        return parent::addData($data, $ignore);
    }

    /**
     * 更新
     * @param $uid
     * @param $data
     * @return bool
     */
    public static function updateUser($uid, $data)
    {
        if ($uid > 0 && !empty($data)) {
            return parent::updateData($uid, $data);
        }
        return false;
    }

    /**
     * 根据id获取详细信息
     * @param int|array $uids
     * @return array
     */
    public static function getUserByIds($uids)
    {
        if (empty($uids)) {
            return [];
        }
        return parent::getDataById($uids);
    }

    /**
     * 根据用户获取详细信息
     * @param string $name
     * @return array|bool
     */
    public static function getUserByName($name)
    {
        $name = trim($name);
        if (empty($name)) {
            return false;
        }
        $where = ['UserName' => $name];
        $extra = [
            'fields' => '*',
            'limit' => 1,
        ];
        $ret = parent::getDataByFields($where, $extra);
        return !empty(current($ret['data'])) ? current($ret['data']) : [];
    }

    /**
     * 根据用户分组获取详细信息
     * @param int $group
     * @param int $offset
     * @param int $limit
     * @return array|bool
     */
    public static function getUsersByGroupId($group, $offset = 0, $limit = 0)
    {
        $group = intval($group);
        if (empty($group)) {
            return false;
        }
        $where = ['UserGroup' => $group];
        $extra = [
            'fields' => '*',
            'offset' => $offset,
            'limit' => $limit,
        ];
        return parent::getDataByFields($where, $extra);
    }

    /**
     * 查询
     * @param array $where
     * @param int $offset
     * @param int $limit
     * @param string $order
     * @param string $fields
     * @return array
     */
    public static function getUsers($where = [], $offset = 0, $limit = 0, $order = '', $fields = '*')
    {
        $extra = [
            'fields' => $fields,
            'offset' => $offset,
            'limit' => $limit,
            'order' => $order,
        ];
        return parent::getDataByFields($where, $extra);
    }

    /**
     * 删除
     * @param $uid
     * @return bool
     */
    public static function delUser($uid)
    {
        if ($uid > 0) {
            return parent::delData($uid);
        }
        return false;
    }
}