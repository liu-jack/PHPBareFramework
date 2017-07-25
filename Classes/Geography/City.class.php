<?php

/**
 * 地理  - 城市类接口
 *
 * @subpackage Geography
 *
 * $Id$
 */

namespace Classes\Geography;


class City
{
    /**
     * 数据类型: 区域ID->区域信息映射表
     *
     * @var string
     */
    const KEY_ID_INFO_MAP = 'id_info_map';
    /**
     * 数据类型: 省份->城市映射表
     *
     * @var string
     */
    const KEY_PROV_CITY_MAP = 'prov_city_map';
    /**
     * 数据类型: 城市->省份映射表
     *
     * @var string
     */
    const KEY_CITY_PROV_MAP = 'city_prov_map';
    /**
     * 数据类型: 城市名->城市ID映射表
     *
     * @var string
     */
    const KEY_NAME_ID_MAP = 'name_id_map';

    private function __construct()
    {
    }

    /**
     * 获取所有城市列表
     *
     * @return array
     */
    public static function getAllCities()
    {
        $map = self::_loadCityConfig(self::KEY_ID_INFO_MAP);

        return $map;
    }

    /**
     * 加载城市数据
     *
     * @param mixed $key 参见 self::KEY_* 系列常量
     * @return array
     */
    private static function _loadCityConfig($key = null)
    {
        static $_static = [];

        if (empty($_static)) {
            $prov_city_map = $city_prov_map = $name_city_map = $id_info_map = [];

            $prov_cfg = config('geograph/city');
            $raw = $prov_cfg['city'];

            foreach ($raw as $prov_id => $prov_city_list) {
                $map = [];

                foreach ($prov_city_list as $city_id => $city) {
                    $map[$city_id] = $city_id;

                    // 城市ID -> 城市信息
                    $id_info_map[$city_id] = [
                        'CityId' => $city_id,
                        'CityName' => $city['Name'],
                        'AreaCode' => $city['AreaCode'],
                        //'ZipCode'       => $city['ZipCode'],
                        'CityFullName' => $city['FullName'],
                        'CityPinyin' => $city['Pinyin'],
                        'CityAcronym' => $city['Acronym'],
                        'CityDivision' => $city['Division'],
                        'ProvId' => $prov_id,
                    ];

                    // 城市ID -> 省份ID
                    $city_prov_map[$city_id] = $prov_id;

                    // 城市名 -> 城市ID
                    $name_city_map[$city['Name']] = $city_id;
                    $name_city_map[$city['FullName']] = $city_id;
                }

                // 省份ID => 城市ID列表
                $prov_city_map[$prov_id] = $map;
            }

            $_static[self::KEY_PROV_CITY_MAP] = $prov_city_map;
            $_static[self::KEY_CITY_PROV_MAP] = $city_prov_map;
            $_static[self::KEY_NAME_ID_MAP] = $name_city_map;
            $_static[self::KEY_ID_INFO_MAP] = $id_info_map;
        }

        if (is_string($key) && isset($_static[$key])) {
            return $_static[$key];
        }

        return $_static;
    }

    /**
     * 通过省份ID获取城市列表
     *
     * @param integer $id 省份ID
     * @return mixed
     *                    false - 参数错误
     *                    null - 不存在
     *                    array - 城市列表信息
     */
    public static function getCitiesByProvinceId($id)
    {
        if (!is_numeric($id) || ($id = (int)$id) <= 0) {
            return false;
        }

        $city_cfg = self::_loadCityConfig();

        $map = $city_cfg[self::KEY_PROV_CITY_MAP];
        if (!isset($map[$id])) {
            return null;
        }

        $cities = $city_cfg[self::KEY_ID_INFO_MAP];
        $cities = array_intersect_key($cities, $map[$id]);

        return $cities;
    }

    /**
     * 通过城市ID获取省份信息
     *
     * @param integer $id 城市ID
     * @return mixed
     *                    false - 参数错误
     *                    null - 不存在
     *                    array - 省份信息
     */
    public static function getProvinceByCityId($id)
    {
        if (!is_numeric($id) || ($id = (int)$id) <= 0) {
            return false;
        }

        $id_info_map = self::_loadCityConfig(self::KEY_ID_INFO_MAP);

        if (!isset($id_info_map[$id])) {
            return null;
        }

        $prov = Province::getProvinceById($id_info_map[$id]['ProvId']);

        return $prov;
    }

    /**
     * 根据城市名精确获取城市信息
     *
     * @param string $name 城市名
     * @return mixed
     *                     false - 参数错误
     *                     null - 不存在
     *                     array - 城市信息
     */
    public static function getCityByName($name)
    {
        $name = is_string($name) ? trim($name) : null;
        if (empty($name)) {
            return false;
        }

        $map = self::_loadCityConfig(self::KEY_NAME_ID_MAP);

        if (!isset($map[$name])) {
            return null;
        }

        $city = self::getCityById($map[$name]);

        return $city;
    }

    /**
     * 获取指定ID的城市信息
     *
     * @param integer $id 城市ID
     * @return mixed
     *                    null - 不存在
     *                    array - 城市信息
     */
    public static function getCityById($id)
    {
        if (!is_numeric($id) || ($id = (int)$id) <= 0) {
            return false;
        }

        $map = self::_loadCityConfig(self::KEY_ID_INFO_MAP);

        if (!isset($map[$id])) {
            return null;
        }

        return $map[$id];
    }

    /**
     * 根据城市名模糊查找城市信息
     *
     * @param string $str 包含城市名信息的字符串
     * @param boolean $only_id 只取城市ID
     * @return mixed
     *                         integer - 城市ID
     *                         array - 城市信息
     *                         boolean - 失败返回false
     */
    public static function getCityByFuzzyName($str, $only_id = true)
    {
        $str = is_string($str) ? trim($str) : null;
        if (empty($str)) {
            return false;
        }

        $map = self::_loadCityConfig(self::KEY_NAME_ID_MAP);

        foreach ($map as $name => $city_id) {
            $pos = mb_strpos($str, $name);

            if ($pos !== false && $pos <= 4) {
                if ($only_id) {
                    return $city_id;
                }

                $city = self::getCityById($city_id);

                return $city;
            }
        }

        return 0;
    }
}
