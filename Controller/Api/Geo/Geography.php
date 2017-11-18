<?php
/**
 * Geography.class.php
 *
 */

namespace Controller\Api\Geo;

use Bare\Controller;
use Classes\Geography\City;
use Classes\Geography\Area;
use Classes\Geography\Province;

/**
 * 省/市/区 地理信息
 *
 * @package Geo
 * @author camfee<camfee@foxmail.com>
 * @date 2017-07-21
 *
 */
class Geography extends Controller
{
    /**
     * 获取省份列表
     *
     * <pre>
     * GET:
     *    无参数
     * </pre>
     *
     * @return void|string 返回JSON数据
     *
     * <pre>
     * {
     *    "Code": 200,
     *    "Data": [
     *        {
     *            "Id": 11,
     *            "Name": "北京市"
     *        },
     *        {
     *            "Id": 43,
     *            "Name": "湖南省"
     *        }
     *    ]
     * }
     * </pre>
     */
    public function getProvinceList()
    {
        $data = Province::getAllProvinces();
        $province = [];

        foreach ($data as $v) {
            if (in_array($v['ProvName'], ['台湾', '香港', '澳门', '新疆', '内蒙古'])) {
                continue;
            }

            $province[] = [
                'Id' => $v['ProvId'],
                'Name' => $v['ProvFullName']
            ];
        }

        $this->output(200, $province);
    }

    /**
     * 根据省份ID, 获取城市列表
     *
     * <pre>
     * GET:
     *    provid: 必选, 省份ID
     * </pre>
     *
     * @return void|string 返回JSON数据
     *
     * <pre>
     * {
     *    "Code": 200,
     *    "Data": [
     *        {
     *            "Id": 3401,
     *            "Name": "长沙市"
     *        },
     *        {
     *            "Id": 3402,
     *            "Name": "株洲市"
     *        }
     *    ]
     * }
     *
     * 异常状态
     * 201: 省份ID错误
     * </pre>
     */
    public function getCityList()
    {
        $provid = (int)$_GET['provid'];
        if ($provid > 0) {
            $data = City::getCitiesByProvinceId($provid);
            if (is_array($data) && count($data) > 0) {
                $city = [];
                foreach ($data as $v) {
                    $city[] = [
                        'Id' => $v['CityId'],
                        'Name' => $v['CityFullName']
                    ];
                }
                $this->output(200, $city);
            }
        }

        $this->output(201, '省份ID错误');
    }

    /**
     * 根据城市ID, 获取区域列表
     *
     * <pre>
     * GET:
     *    cityid: 必选, 城市ID
     * </pre>
     *
     * @return void|string 返回JSON数据
     *
     * <pre>
     * {
     *    "Code": 200,
     *    "Data": [
     *        {
     *            "Id": 37601,
     *            "Name": "芙蓉区"
     *        },
     *        {
     *            "Id": 37602,
     *            "Name": "天心区"
     *        }
     *    ]
     * }
     *
     * 异常状态
     * 201: 城市ID错误
     * </pre>
     */
    public function getAreaList()
    {
        $cityid = (int)$_GET['cityid'];
        if ($cityid > 0) {
            $data = Area::getAreasByCityId($cityid);
            if (is_array($data) && count($data) > 0) {
                $area = [];
                foreach ($data as $v) {
                    $area[] = [
                        'Id' => $v['AreaId'],
                        'Name' => $v['AreaName']
                    ];
                }
                $this->output(200, $area);
            }
        }

        $this->output(201, '城市ID错误');
    }

    /**
     * 获取全部区域数据[省份>城市>区县] (APP缓存到本地, 一周时间)
     *
     * <pre>
     * GET:
     *      无参数
     * </pre>
     *
     * @return void|string 返回JSON数据
     *
     * <pre>
     * {
     *    "Code":200,
     *    "Data":[
     *        {
     *            "Id":13,
     *            "Name":"河北省",
     *            "Cities":[
     *                {
     *                    "Id":401,
     *                    "Name":"石家庄市",
     *                    "Areas":[
     *                        {"Id": 801, "Name": "长安区"},
     *                        {"Id": 802, "Name": "桥东区"},
     *                        {"Id": 803, "Name": "桥西区"}
     *                    ]
     *                },
     *                {
     *                    "Id":402,
     *                    "Name":"唐山市",
     *                    "Areas":[
     *                        {"Id": 1001, "Name": "路南区"},
     *                        {"Id": 1002, "Name": "路北区"},
     *                        {"Id": 1003, "Name": "古冶区"}
     *                    ]
     *                }
     *        }
     *    ]
     * }
     * </pre>
     *
     */
    public function getAllRegions()
    {
        //获取某省全部子区域
        $getCity = function ($parentid) {

            $getArea = function ($parentid) {
                $areas = Area::getAreasByCityId($parentid);
                if ($areas) {
                    $data = [];
                    foreach ($areas as $rs) {
                        $data[] = ['Id' => $rs['AreaId'], 'Name' => $rs['AreaFullName']];
                    }

                    return $data;
                } else {
                    return null;
                }
            };

            $cities = City::getCitiesByProvinceId($parentid);

            if ($cities) {
                $data = [];
                foreach ($cities as $key => $value) {
                    $city = ['Id' => $value['CityId'], 'Name' => $value['CityFullName']];
                    $areas = $getArea($value['CityId']);
                    if ($areas) {
                        $city['Areas'] = $areas;
                    } else {
                        $city['Areas'] = [];
                    }
                    $data[] = $city;
                }

                return $data;
            } else {
                return null;
            }
        };

        $provices = Province::getAllProvinces();
        $data = [];
        foreach ($provices as $key => $value) {

            if (in_array($value['ProvName'], ['台湾', '香港', '澳门', '新疆', '内蒙古'])) {
                continue;
            }

            $provice = ['Id' => $key, 'Name' => $value['ProvFullName']];
            $cities = $getCity($key);

            if ($cities) {
                $provice['Cities'] = $cities;
            } else {
                $provice['Cities'] = [];
            }

            $data[] = $provice;
        }

        $this->output(200, $data);
    }
}
