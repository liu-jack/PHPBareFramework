<?php
/**
 * AdminMenu.class.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/5/24 12:33
 *
 */

namespace Model\Admin;


use Bare\Model;
Use Bare\DB;

/**
 * 后台菜单模型
 * Class AdminMenu
 * @package Model\Admin
 */
class AdminMenu extends Model
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
        'table' => 'AdminMenu',
        // 必选, 字段信息
        'fields' => [
            'AdminMenuId' => self::VAR_TYPE_KEY,
            'Name' => self::VAR_TYPE_STRING,
            'Key' => self::VAR_TYPE_STRING,
            'Url' => self::VAR_TYPE_STRING,
            'ParentId' => self::VAR_TYPE_INT,
            'DisplayOrder' => self::VAR_TYPE_INT,
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
        'Name' => 1,
        'Url' => 1,
    ];

    /**
     * 新增
     * @param $data
     * @param bool $ignore
     * @return bool|int|string
     */
    public static function addMenu($data, $ignore = true)
    {
        if (count(array_diff_key(self::$_add_must_fields, $data)) > 0) {
            return false;
        }
        return parent::addData($data, $ignore);
    }

    /**
     * 更新
     * @param $id
     * @param $data
     * @return bool
     */
    public static function updateMenu($id, $data)
    {
        if ($id > 0 && !empty($data)) {
            return parent::updateData($id, $data);
        }
        return false;
    }

    /**
     * 根据id获取详细信息
     * @param int|array $ids
     * @return array
     */
    public static function geMenuByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }
        return parent::getDataById($ids);
    }

    /**
     * 查询
     * @param int $pid
     * @return array
     */
    public static function getMenusByParentId($pid = 0)
    {
        $where = [
            'ParentId' => $pid
        ];
        $extra = [
            'fields' => '*',
            'offset' => '0',
            'limit' => '0',
            'order' => 'DisplayOrder DESC',
        ];
        $ret = parent::getDataByFields($where, $extra);
        return !empty($ret['data']) ? $ret['data'] : [];
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
    public static function getMenus($where = [], $offset = 0, $limit = 0, $order = '', $fields = '*')
    {
        $extra = [
            'fields' => $fields,
            'offset' => $offset,
            'limit' => $limit,
            'order' => !empty($order) ? $order : 'ParentId ASC,DisplayOrder DESC',
        ];
        return parent::getDataByFields($where, $extra);
    }

    /**
     * 删除
     * @param $id
     * @return bool
     */
    public static function delMenu($id)
    {
        if ($id > 0) {
            return parent::delData($id);
        }
        return false;
    }
}