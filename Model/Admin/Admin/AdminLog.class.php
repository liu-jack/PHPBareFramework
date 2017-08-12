<?php
/**
 * AdminLog.class.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date   2017/5/24 12:33
 *
 */

namespace Model\Admin\Admin;

use Bare\Model;
use Bare\DB;

/**
 * 后台操作日志模型
 * Class AdminLog
 *
 * @package Model\Admin
 */
class AdminLog extends Model
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
        'table' => 'AdminLog',
        // 必选, 字段信息
        'fields' => [
            'LogId' => self::VAR_TYPE_KEY,
            'UserId' => self::VAR_TYPE_INT,
            'UserName' => self::VAR_TYPE_STRING,
            'ItemId' => self::VAR_TYPE_INT,
            'ItemName' => self::VAR_TYPE_STRING,
            'MenuKey' => self::VAR_TYPE_STRING,
            'MenuName' => self::VAR_TYPE_STRING,
            'LogFlag' => self::VAR_TYPE_STRING,
            'Log' => self::VAR_TYPE_STRING,
            'CreateTime' => self::VAR_TYPE_STRING,
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
        'UserId' => 1,
        'ItemId' => 1,
        'Log' => 1,
    ];

    /**
     * 记录后台日志
     *
     * @param string $title    操作名称
     * @param string $option   操作细分
     * @param int    $itemid   操作项目id
     * @param array  $data     操作数据
     * @param string $itemname 项目名称（数据表名）
     * @return bool|int|string
     */
    public static function log($title, $option, $itemid = 0, $data = [], $itemname = '')
    {
        if (isset($data['Password'])) {
            unset($data['Password']);
        }
        $adddata = [
            'UserId' => $_SESSION['_admin_info']['AdminUserId'],
            'UserName' => $_SESSION['_admin_info']['AdminRealName'],
            'ItemId' => $itemid,
            'ItemName' => $itemname,
            'MenuKey' => $GLOBALS['_PATH'],
            'MenuName' => $title,
            'LogFlag' => $option,
            'Log' => is_array($data) ? serialize($data) : $data,
        ];

        return self::addLog($adddata);
    }

    /**
     * 新增
     *
     * @param      $data
     * @param bool $ignore
     * @return bool|int|string
     */
    public static function addLog($data, $ignore = true)
    {
        if (count(array_diff_key(self::$_add_must_fields, $data)) > 0) {
            return false;
        }
        if (empty($data['CreateTime'])) {
            $data['CreateTime'] = date('Y-m-d H:i:s');
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
    public static function updateLog($id, $data)
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
    public static function geLogByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }

        return parent::getDataById($ids);
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
    public static function getLogs($where = [], $offset = 0, $limit = 0, $order = '', $fields = '*')
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
     *
     * @param $id
     * @return bool
     */
    public static function delLog($id)
    {
        if ($id > 0) {
            //return parent::delData($id);
        }

        return false;
    }
}