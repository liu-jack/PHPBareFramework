<?php
/**
 * SmsLog.class.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date  2017/5/24 14:18
 *
 */

namespace Model\Admin;

use Bare\DB;
use Bare\Model;

/**
 * 发送短信模型
 * Class SmsLog
 * @package Model\Admin
 */
class SmsLog extends Model
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
        'table' => 'SmsLog',
        // 必选, 字段信息
        'fields' => [
            'SmsId' => self::VAR_TYPE_KEY,
            'Mobile' => self::VAR_TYPE_STRING,
            'Content' => self::VAR_TYPE_STRING,
            'Type' => self::VAR_TYPE_INT,
            'Flag' => self::VAR_TYPE_STRING,
            'Ip' => self::VAR_TYPE_STRING,
            'Used' => self::VAR_TYPE_INT,
            'Status' => self::VAR_TYPE_INT,
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
     * @var array
     */
    private static $_add_must_fields = [
        'Mobile' => 1,
        'Content' => 1,
    ];

    /**
     * 新增
     * @param $data
     * @param bool $ignore
     * @return bool|int|string
     */
    public static function addSmsLog($data, $ignore = true)
    {
        if (count(array_diff_key(self::$_add_must_fields, $data)) > 0) {
            return false;
        }
        if (empty($data['Ip'])) {
            $data['Ip'] = ip();
        }
        if (empty($data['CreateTime'])) {
            $data['CreateTime'] = date('Y-m-d H:i:s');
        }
        return parent::addData($data, $ignore);
    }

    /**
     * 更新
     * @param $id
     * @param $data
     * @return bool
     */
    public static function updateSmsLog($id, $data)
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
    public static function geSmsLogByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }
        return parent::getDataById($ids);
    }

    /**
     * @param $mobile
     * @param $type
     * @return array
     */
    public static function getSmsLogByMobile($mobile, $type = 0)
    {
        $where = [
            'Mobile' => $mobile,
            'Type' => (int)$type
        ];
        $extra = [
            'fields' => '*',
            'offset' => 0,
            'limit' => 1,
        ];
        $ret = parent::getDataByFields($where, $extra);
        return !empty($ret['data'][0]) ? $ret['data'][0] : [];
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
    public static function getSmsLogs($where = [], $offset = 0, $limit = 0, $order = '', $fields = '*')
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
     * @param $id
     * @return bool
     */
    public static function delSmsLog($id)
    {
        if ($id > 0) {
            return parent::delData($id);
        }
        return false;
    }
}