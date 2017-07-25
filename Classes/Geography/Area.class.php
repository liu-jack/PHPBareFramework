<?php

/**
 * 地理  - 区县类接口
 *
 * @subpackage Geography
 *
 * $Id$
 */

namespace Classes\Geography;

class Area
{
    /**
     * 数据类型: 区域ID->区域信息映射表
     *
     * @var string
     */
    const KEY_ID_INFO_MAP = 'id_info_map';
    /**
     * 数据类型: 城市->区域映射表
     *
     * @var string
     */
    const KEY_CITY_AREA_MAP = 'city_area_map';
    /**
     * 数据类型: 区域->城市映射表
     *
     * @var string
     */
    const KEY_AREA_CITY_MAP = 'area_city_map';

    private function __construct()
    {
    }

    /**
     * 获取指定ID的区域信息
     *
     * @param integer $id 区域ID
     * @param boolean $get_city 是否获取城市信息
     * @return mixed
     *                          null - 不存在
     *                          array - array('AreaId' => 区域ID, 'AreaName' => 区域名, 'CityId' => 所属城市ID, 'CityName' =>
     *                          所属城市名, 'AreaCode' => 区号,)
     */
    public static function getAreaById($id, $get_city = false)
    {
        if (!is_numeric($id) || ($id = (int)$id) <= 0) {
            return false;
        }

        $area_cfg = self::_loadAreaConfig();

        $id_info_map = $area_cfg[self::KEY_ID_INFO_MAP];

        if (!isset($id_info_map[$id])) {
            return null;
        }

        $area = $id_info_map[$id];

        if ($get_city) {
            $city = City::getCityById($area['CityId']);

            $area += [
                'CityName' => $city['Name'],
                'AreaCode' => $city['AreaCode'],
            ];
        }

        return $area;
    }

    /**
     * 加载区域数据
     *
     * @param mixed $key 参见 self::KEY_* 系列常量
     * @return array
     */
    private static function _loadAreaConfig($key = null)
    {
        static $_static = [];

        if (empty($_static)) {
            $area_city_map = $city_area_map = $id_info_map = [];

            $area_cfg = config('geograph/area');
            $raw = $area_cfg['area'];

            foreach ($raw as $city_id => $city_area_list) {
                $map = [];

                foreach ($city_area_list as $area_id => $area) {
                    $map[$area_id] = $area_id;

                    // 区域ID -> 区域信息
                    $id_info_map[$area_id] = [
                        'AreaId' => $area_id,
                        'AreaName' => $area['Name'],
                        'AreaFullName' => $area['FullName'],
                        //'ZipCode'       => $area['ZipCode'],
                        'AreaDivision' => $area['Division'],
                        'CityId' => $city_id,
                    ];

                    // 区域ID -> 城市ID
                    $area_city_map[$area_id] = $city_id;
                }

                // 城市ID -> 区域ID列表
                $city_area_map[$city_id] = $map;
            }

            $_static[self::KEY_CITY_AREA_MAP] = $city_area_map;
            $_static[self::KEY_AREA_CITY_MAP] = $area_city_map;
            $_static[self::KEY_ID_INFO_MAP] = $id_info_map;
        }

        if (is_string($key) && isset($_static[$key])) {
            return $_static[$key];
        }

        return $_static;
    }

    /**
     * 获取所有地区
     *
     * @return array
     */
    public static function getAllAreas()
    {
        $map = self::_loadAreaConfig(self::KEY_ID_INFO_MAP);

        return $map;
    }

    /**
     * 获取指定城市的区域列表
     *
     * @param integer $id 城市ID
     * @return mixed
     *                    false - 参数错误
     *                    null - 不存在
     *                    array - 区域列表
     */
    public static function getAreasByCityId($id)
    {
        if (!is_numeric($id) || ($id = (int)$id) <= 0) {
            return false;
        }

        $area_cfg = self::_loadAreaConfig();

        $map = $area_cfg[self::KEY_CITY_AREA_MAP];
        if (!isset($map[$id])) {
            return null;
        }

        $areas = $area_cfg[self::KEY_ID_INFO_MAP];
        $areas = array_intersect_key($areas, $map[$id]);

        return $areas;
    }

    /**
     * 根据地区ID获取其所在城市信息
     *
     * @param integer $id 区域ID
     * @return mixed
     *                    false - 参数错误
     *                    null - 不存在
     *                    array - 城市信息
     */
    public static function getCityByAreaId($id)
    {
        if (!is_numeric($id) || ($id = (int)$id) <= 0) {
            return false;
        }

        $map = self::_loadAreaConfig(self::KEY_ID_INFO_MAP);

        if (!isset($map[$id])) {
            return null;
        }

        $city = City::getCityById($map[$id]['CityId']);

        return $city;
    }
}
