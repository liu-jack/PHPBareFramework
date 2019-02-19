<?php
/**
 * Area.class.php
 * 城市区域经纬度信息
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-3-29 下午2:22
 *
 */

namespace Model\Application;

use Bare\M\Model;
use Bare\DB;
use Classes\Algorithm\Math;

class Area extends Model
{
    protected static $_conf = [
        // 必选, 数据库代码 (来自DB配置), w: 写, r: 读
        self::CF_DB => [
            self::CF_DB_W => DB::DB_APPLICATION_W,
            self::CF_DB_R => DB::DB_APPLICATION_R
        ],
        // 必选, 数据表名
        self::CF_TABLE => 'Area',
        // 必选, 字段信息
        self::CF_FIELDS => [
            'Id' => self::VAR_TYPE_KEY,
            'Pid' => self::VAR_TYPE_INT,
            'ShortName' => self::VAR_TYPE_STRING,
            'Name' => self::VAR_TYPE_STRING,
            'LongName' => self::VAR_TYPE_STRING,
            'Level' => self::VAR_TYPE_INT,
            'PinYin' => self::VAR_TYPE_STRING,
            'Code' => self::VAR_TYPE_STRING,
            'ZipCode' => self::VAR_TYPE_STRING,
            'First' => self::VAR_TYPE_STRING,
            'Longitude' => self::VAR_TYPE_STRING,
            'Latitude' => self::VAR_TYPE_STRING,
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

    // 列表缓存数组
    const UPDATE_DEL_CACHE_LIST = true; // 更新是否清除列表缓存
    const MC_LIST_SHORT_NAME = 'MC_LIST_SHORT_NAME:{ShortName}';
    const MC_LIST_NAME = 'MC_LIST_NAME:{Name}';
    protected static $_cache_list_keys = [
        self::MC_LIST_SHORT_NAME => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_MC,
            self::CACHE_LIST_FIELDS => 'ShortName',
        ],
        self::MC_LIST_NAME => [
            self::CACHE_LIST_TYPE => self::CACHE_LIST_TYPE_MC,
            self::CACHE_LIST_FIELDS => 'Name',
        ],
    ];

    /**
     * 获取区域距离内地区
     *
     * @param     $name
     * @param int $range
     * @param int $limit
     * @return array|bool
     */
    public static function getAreaRange($name, $range = 10, $limit = 999)
    {
        $a = self::getInfoByName($name);
        if (empty($a)) {
            return false;
        }
        //$distance = "sqrt(((({$a['Longitude']} - Longitude) * PI() * 12742 * cos((({$a['Latitude']} + Latitude) / 2) * PI() /180)/ 180) * (({$a['Longitude']} - Longitude) * PI() * 12742 * cos((({$a['Latitude']} + Latitude) / 2) * PI() / 180) / 180)) + ((({$a['Latitude']} - Latitude) * PI() * 12742 / 180) * (({$a['Latitude']} - Latitude) * PI() * 12742/180)))";
        $distance = "ACOS(SIN(({$a['Latitude']} * PI()) / 180) *SIN((Latitude * PI()) / 180 ) +COS(({$a['Latitude']} * PI()) / 180 ) * COS((Latitude * PI()) / 180 ) *COS(({$a['Longitude']} * PI()) / 180 - (Longitude * PI()) / 180 ) ) * 6371";
        $field = "*,{$distance} as Distance";
        $where = $distance . " < {$range}";
        $data = self::getPdo()->select($field)->from(self::$_conf[self::CF_TABLE])->where($where)->order($distance . ' ASC')->limit($limit)->getAll();

        return $data;
    }

    /**
     * 获取区域距离最近的地区
     *
     * @param     $name
     * @param int $limit
     * @return array|bool
     */
    public static function getAreaNear($name, $limit = 20)
    {
        $a = self::getInfoByName($name);
        if (empty($a)) {
            return false;
        }
        $distance = "ACOS(SIN(({$a['Latitude']} * PI()) / 180) *SIN((Latitude * PI()) / 180 ) +COS(({$a['Latitude']} * PI()) / 180 ) * COS((Latitude * PI()) / 180 ) *COS(({$a['Longitude']} * PI()) / 180 - (Longitude * PI()) / 180 ) ) * 6371";
        $field = "*,{$distance} as Distance";
        $where = "Latitude > {$a['Latitude']}-1 and Latitude < {$a['Latitude']}+1 and Longitude > {$a['Longitude']}-1 and Longitude < {$a['Longitude']}+1";
        $sort = $distance . " ASC";
        $data = self::getPdo()->select($field)->from(self::$_conf[self::CF_TABLE])->where($where)->order($sort)->limit($limit)->getAll();

        return $data;
    }

    /**
     * 获取两个地址的距离
     *
     * @param $name1
     * @param $name2
     * @return bool|float|int
     */
    public static function getAreasDistance($name1, $name2)
    {
        $a1 = self::getInfoByName($name1);
        $a2 = self::getInfoByName($name2);
        if (empty($a1) || empty($a2)) {
            return false;
        }

        return Math::dot2dot([$a1['Longitude'], $a1['Latitude']], [$a2['Longitude'], $a2['Latitude']]);
    }

    /**
     * 根据简称或者全称获取区域详情
     *
     * @param string $name
     * @return array|mixed
     */
    public static function getInfoByName($name)
    {
        $data = self::getInfoByShortNames($name);
        if (empty($data)) {
            $data = self::getInfoByNames($name);
        }

        return !empty($data) ? $data : [];
    }

    /**
     * 根据城市全称获取城市信息
     *
     * @param array|string $name
     * @return array|mixed
     */
    public static function getInfoByNames($name)
    {
        static $_cache;
        $names = is_array($name) ? $name : [$name];
        $mc_key = [];
        foreach ($names as $v) {
            $mc_key[$v] = str_replace('{Name}', $v, self::MC_LIST_NAME);
        }
        $mc_data = self::getMC()->get($mc_key);
        if (!empty($mc_data)) {
            foreach ($mc_data as $v) {
                $_cache[$v['Name']] = $v;
                unset($mc_key[$v['Name']]);
            }
        }
        if (!empty($mc_key)) {
            $_data = self::getPdo()->select('*')->from(self::$_conf[self::CF_TABLE])->where(['Name IN' => array_keys($mc_key)])->order('Id DESC')->getAll();
            if (!empty($_data)) {
                foreach ($_data as $v) {
                    $_cache[$v['Name']] = $v;
                    self::getMC()->set($mc_key[$v['Name']], $v, self::$_conf[self::CF_MC_TIME]);
                }
            }
        }
        $data = [];
        foreach ($names as $v) {
            if (isset($_cache[$v])) {
                $data[$v] = $_cache[$v];
            }
        }
        if (is_array($name)) {
            return $data;
        } else {
            return isset($data[$name]) ? $data[$name] : [];
        }
    }

    /**
     * 根据城市简称获取城市信息
     *
     * @param array|string $name
     * @return array|mixed
     */
    public static function getInfoByShortNames($name)
    {
        static $_cache;
        $names = is_array($name) ? $name : [$name];
        $mc_key = [];
        foreach ($names as $v) {
            $mc_key[$v] = str_replace('{ShortName}', $v, self::MC_LIST_SHORT_NAME);
        }
        $mc_data = self::getMC()->get($mc_key);
        if (!empty($mc_data)) {
            foreach ($mc_data as $v) {
                $_cache[$v['ShortName']] = $v;
                unset($mc_key[$v['ShortName']]);
            }
        }
        if (!empty($mc_key)) {
            $_data = self::getPdo()->select('*')->from(self::$_conf[self::CF_TABLE])->where(['ShortName IN' => array_keys($mc_key)])->order('Id DESC')->getAll();
            if (!empty($_data)) {
                foreach ($_data as $v) {
                    $_cache[$v['ShortName']] = $v;
                    self::getMC()->set($mc_key[$v['ShortName']], $v, self::$_conf[self::CF_MC_TIME]);
                }
            }
        }
        $data = [];
        foreach ($names as $v) {
            if (isset($_cache[$v])) {
                $data[$v] = $_cache[$v];
            }
        }
        if (is_array($name)) {
            return $data;
        } else {
            return isset($data[$name]) ? $data[$name] : [];
        }
    }

    /**
     * @see \Bare\M\Model::add() 新增
     * @see \Bare\M\Model::update() 更新
     * @see \Bare\M\Model::getInfoByIds() 按主键id查询
     * @see \Bare\M\Model::getList() 条件查询
     * @see \Bare\M\Model::delete() 删除
     */
}