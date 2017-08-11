<?php
/**
 * AdminGroup.class.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/5/24 12:33
 *
 */

namespace Model\Admin;

use Bare\Model;
use Bare\DB;

/**
 * 后台权限组模型
 * Class AdminGroup
 *
 * @package Model\Admin
 */
class AdminGroup extends Model
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
        'table' => 'AdminGroup',
        // 必选, 字段信息
        'fields' => [
            'GroupId' => self::VAR_TYPE_KEY,
            'GroupName' => self::VAR_TYPE_STRING,
            'AdminAuth' => self::VAR_TYPE_ARRAY,
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
     *
     * @var array
     */
    private static $_add_must_fields = [
        'GroupName' => 1,
    ];

    /**
     * 新增
     *
     * @param      $data
     * @param bool $ignore
     * @return bool|int|string
     */
    public static function addGroup($data, $ignore = true)
    {
        if (count(array_diff_key(self::$_add_must_fields, $data)) > 0) {
            return false;
        }

        return parent::addData($data, $ignore);
    }

    /**
     * 更新
     *
     * @param $id
     * @param $data
     * @return bool
     */
    public static function updateGroup($id, $data)
    {
        if ($id > 0 && !empty($data)) {
            return parent::updateData($id, $data);
        }

        return false;
    }

    /**
     * 根据id获取详细信息
     *
     * @param int|array $ids
     * @return array
     */
    public static function getGroupByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }

        return parent::getDataById($ids);
    }

    /**
     * 查询
     *
     * @param string $name
     * @return array
     */
    public static function getGroupByName($name)
    {
        $where = [
            'GroupName' => $name
        ];
        $extra = [
            'fields' => '*',
            'offset' => 0,
            'limit' => 1,
        ];
        $ret = parent::getDataByFields($where, $extra);

        return !empty($ret['data']) ? current($ret['data']) : [];
    }

    /**
     * 查询
     *
     * @param array  $where
     * @param int    $offset
     * @param int    $limit
     * @param string $order
     * @param string $fields
     * @return array
     */
    public static function getGroups($where = [], $offset = 0, $limit = 0, $order = '', $fields = '')
    {
        $extra = [
            'fields' => empty($fields) ? 'GroupId,GroupName,Status' : $fields,
            'offset' => $offset,
            'limit' => $limit,
            'order' => $order,
        ];

        return parent::getDataByFields($where, $extra);
    }

    /**
     * 删除
     *
     * @param $id
     * @return bool
     */
    public static function delGroup($id)
    {
        if ($id > 0) {
            return parent::delData($id);
        }

        return false;
    }
}