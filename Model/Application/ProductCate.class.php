<?php
/**
 * ProductCategory.class.php
 * 商品分类
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-4 下午3:51
 *
 */

namespace Model\Application;

use Bare\Model;
use Bare\DB;

class ProductCate extends Model
{
    protected static $_conf = [
        // 必选, 数据库代码 (来自DB配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DB::DB_APPLICATION_W,
            self::CF_DB_R => DB::DB_APPLICATION_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => 'ProductCate',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'Id' => self::VAR_TYPE_KEY,
            'ParentId' => self::VAR_TYPE_INT,
            'Title' => self::VAR_TYPE_STRING,
            'Cover' => self::VAR_TYPE_STRING,
            'Sort' => self::VAR_TYPE_INT,
            'Status' => self::VAR_TYPE_INT,
            'CreateTime' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        self::CF_MC => DB::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => 'ProductCate:%d',
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
     * @see \Bare\Model::add() 新增
     * @see \Bare\Model::update() 更新
     * @see \Bare\Model::getInfoByIds() 按主键id查询
     * @see \Bare\Model::getList() 条件查询
     * @see \Bare\Model::delete() 删除
     */

    // 列表缓存数组
    const UPDATE_DEL_CACHE_LIST = true; // 更新是否清除列表缓存
    const MC_LIST_PID_CATE_LIST = 'MC_LIST_PID_CATE_LIST:{ParentId}';
    protected static $_cache_list_keys = [
        self::MC_LIST_PID_CATE_LIST => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_MC,
            self::CACHE_LIST_FIELDS => 'ParentId',
        ],
    ];

    /**
     * 获取父级分类下的所有下级分类
     *
     * @param int $pid
     * @return array|bool|string
     */
    public static function getListByPid($pid = 0)
    {
        $mc_key = str_replace('{ParentId}', $pid, self::MC_LIST_PID_CATE_LIST);
        $data = self::getMC()->get($mc_key);
        if (empty($data)) {
            $data = self::getPdo()->select('*')->from(self::$_conf[self::CF_TABLE])->where(['ParentId' => $pid])->order('Sort DESC, Id DESC')->limit(999)->getAll();
            if (!empty($data)) {
                self::getMC()->set($mc_key, $data, self::$_conf[self::CF_MC_TIME]);
            }
        }

        return $data;
    }
}