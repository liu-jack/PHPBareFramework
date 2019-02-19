<?php
/**
 * Address.class.php
 * 用户地址管理
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-4 上午10:43
 *
 */

namespace Model\Application;

use Bare\M\Model;
use Bare\DB;

class Address extends Model
{
    protected static $_conf = [
        // 必选, 数据库代码 (来自DB配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DB::DB_APPLICATION_W,
            self::CF_DB_R => DB::DB_APPLICATION_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => 'Address',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'Id' => self::VAR_TYPE_KEY,
            'UserId' => self::VAR_TYPE_INT,
            'Country' => self::VAR_TYPE_INT,
            'Province' => self::VAR_TYPE_STRING,
            'City' => self::VAR_TYPE_STRING,
            'Area' => self::VAR_TYPE_STRING,
            'Address' => self::VAR_TYPE_STRING,
            'Mobile' => self::VAR_TYPE_STRING,
            'IsDefault' => self::VAR_TYPE_INT,
            'Status' => self::VAR_TYPE_INT,
            'UpdateTime' => self::VAR_TYPE_STRING,
            'CreateTime' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        self::CF_MC => DB::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => '',
        // 可选, 超时时间, 默认不过期
        self::CF_MC_TIME => 86400,
        // 可选, redis (来自DB配置), w: 写, r: 读
        self::CF_RD => [
            self::CF_DB_W => '',
            self::CF_DB_R => '',
            self::CF_RD_INDEX => 0,
            self::CF_RD_TIME => 0,
        ],
    ];

    /**
     * @see \Bare\M\Model::add() 新增
     * @see \Bare\M\Model::update() 更新
     * @see \Bare\M\Model::getInfoByIds() 按主键id查询
     * @see \Bare\M\Model::getList() 条件查询
     * @see \Bare\M\Model::delete() 删除
     */

    protected static $_add_must_fields = [
        'Province' => 1,
        'City' => 1,
        'Area' => 1,
        'Address' => 1,
        'Mobile' => 1,
    ];

    // 列表缓存数组
    const UPDATE_DEL_CACHE_LIST = true; // 更新是否清除列表缓存
    const MC_LIST_USER_ADDRESS = 'MC_LIST_USER_ADDRESS:{UserId}';
    protected static $_cache_list_keys = [
        self::MC_LIST_USER_ADDRESS => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_MC,
            self::CACHE_LIST_FIELDS => 'UserId',
        ],
    ];

    /**
     * 获取用户所有地址
     *
     * @param $uid
     * @return array|bool|string
     */
    public static function getListByUid($uid)
    {
        if (empty($uid)) {
            return false;
        }
        $mc_key = str_replace('{UserId}', $uid, self::MC_LIST_USER_ADDRESS);
        $data = self::getMC()->get($mc_key);
        if (empty($data)) {
            $data = self::getPdo()->select('*')->from(self::$_conf[self::CF_TABLE])->where(['UserId' => $uid])->order('Id DESC')->limit(99)->getAll();
            if (!empty($data)) {
                self::getMC()->set($mc_key, $data, self::$_conf[self::CF_MC_TIME]);
            }
        }

        return $data;
    }

    /**
     * 设置默认地址
     *
     * @param $id
     * @param $uid
     * @return bool
     */
    public static function setDefault($id, $uid)
    {
        if (empty($id) || empty($uid)) {
            return false;
        }
        self::getPdo(true)->update(self::$_conf[self::CF_TABLE], ['IsDefault' => 0], [
            'UserId' => $uid,
            'IsDefault' => 1
        ]);

        return self::update($id, ['IsDefault' => 1]);
    }
}