<?php

/**
 * 地理  - 省份类接口
 *
 * @subpackage Geography
 *
 * $Id$
 */

namespace Classes\Geography;

class Province
{
    /**
     * 数据类型: 省份ID->省份信息映射表
     *
     * @var string
     */
    const KEY_ID_INFO_MAP = 'id_info_map';

    private function __construct()
    {
    }

    /**
     * 获取所有省份列表
     *
     * @return array
     */
    public static function getAllProvinces()
    {
        return self::_loadProvinceConfig(self::KEY_ID_INFO_MAP);
    }

    /**
     * 加载省份数据
     *
     * @param mixed $key 参见 self::KEY_* 系列常量
     * @return array
     */
    private static function _loadProvinceConfig($key = null)
    {
        static $_static = [];

        if (empty($_static)) {
            $id_info_map = [];

            $prov_cfg = config('geograph/province');
            $raw = $prov_cfg['prov'];

            foreach ($raw as $prov_id => $prov) {
                $id_info_map[$prov_id] = [
                    'ProvId' => $prov_id,
                    'ProvName' => $prov['Name'],
                    'ProvFullName' => $prov['FullName'],
                    'ProvAbbr' => $prov['Abbr'],
                    'ProvDivision' => $prov['Division'],
                    'ProvRegion' => $prov['Region'],
                ];
            }

            $_static[self::KEY_ID_INFO_MAP] = $id_info_map;
        }

        if (is_string($key) && isset($_static[$key])) {
            return $_static[$key];
        }

        return $_static;
    }

    /**
     * 通过城市ID获取省份名称
     *
     * @param integer $id
     * @return mixed
     *      false - 参数错误
     *      null - 不存在
     *      string - 省份名
     */
    public static function getProvinceById($id)
    {
        if (!is_numeric($id) || ($id = (int)$id) <= 0) {
            return false;
        }

        $map = self::_loadProvinceConfig(self::KEY_ID_INFO_MAP);

        if (!isset($map[$id])) {
            return null;
        }

        return $map[$id];
    }
}
