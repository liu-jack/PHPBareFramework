<?php
/**
 * Product.class.php
 * 商品表
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-4 下午3:13
 *
 */

namespace Model\Application;

use Bare\Model;
use Bare\DB;

class Product extends Model
{
    protected static $_conf = [
        // 必选, 数据库代码 (来自DB配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DB::DB_APPLICATION_W,
            self::CF_DB_R => DB::DB_APPLICATION_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => 'Product',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'ProductId' => self::VAR_TYPE_KEY,
            'ShopId' => self::VAR_TYPE_INT,
            'CateId' => self::VAR_TYPE_INT,
            'Title' => self::VAR_TYPE_STRING,
            'Cover' => self::VAR_TYPE_STRING,
            'Pictures' => self::VAR_TYPE_ARRAY,
            'OriginPrice' => self::VAR_TYPE_STRING,
            'Price' => self::VAR_TYPE_STRING,
            'GroupPrice' => self::VAR_TYPE_STRING,
            'DiscountPrice' => self::VAR_TYPE_STRING,
            'IsGroup' => self::VAR_TYPE_INT,
            'GroupNum' => self::VAR_TYPE_INT,
            'Content' => self::VAR_TYPE_STRING,
            'BuyCount' => self::VAR_TYPE_INT,
            'CollectCount' => self::VAR_TYPE_INT,
            'Status' => self::VAR_TYPE_INT,
            'UpdateTime' => self::VAR_TYPE_STRING,
            'CreateTime' => self::VAR_TYPE_STRING,
        ],
        // 可选, MC连接参数
        self::CF_MC => DB::MEMCACHE_DEFAULT,
        // 可选, MC KEY, "KeyName:%d", %d会用主键ID替代
        self::CF_MC_KEY => 'Product:%d',
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

    /**
     * 更新商品购买数
     *
     * @param     $id
     * @param int $num
     * @return bool
     */
    public static function updateBuyCount($id, $num = 1)
    {
        return self::update($id, ['BuyCount' => ['BuyCount', '+' . $num]]);
    }

    /**
     * 更新商品收藏数
     *
     * @param     $id
     * @param int $num
     * @return bool
     */
    public static function updateCollectCount($id, $num = 1)
    {
        return self::update($id, ['CollectCount' => ['CollectCount', '+' . $num]]);
    }
}