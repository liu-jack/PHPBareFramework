<?php

/**
 * IP相关类接口
 *
 * @subpackage Geography
 *
 * $Id$
 */

namespace Classes\Geography;

use Bare\M\CommonModel;

class IpUtil extends CommonModel
{
    /**
     * 默认城市ID
     *
     * @var integer
     */
    const DEFAULT_CITY_ID = 1;

    private function __construct()
    {
    }

    /**
     * 根据IP获得城市ID
     *
     * @param string $ip IP地址, 为0时自动取IP
     * @param boolean $only_id 只取城市ID
     * @return mixed
     *                         integer - 成功返回城市ID
     *                         boolean - 失败返回false
     */
    public static function getCityByIp($ip = null, $only_id = true)
    {
        if (!is_string($ip) || empty($ip)) {
            global $app;
            $ip = $app->ip();
        }

        $city_id = 0;

        if ($ip !== '127.0.0.1' && strpos($ip, '192.168.') === false) {
            $info = self::getInfoByCzdat($ip);

            if (!($info < 0) && mb_strlen($info) > 2) {
                $city_id = City::getCityByFuzzyName($info, true);
            }
        }

        if (empty($city_id)) {
            $city_id = self::DEFAULT_CITY_ID;
        }

        if (!$only_id) {
            return City::getCityById($city_id);
        }

        return $city_id;
    }

    /**
     * 根据IP 从IP库获取信息
     *
     * @param string $ip IP地址
     * @param boolean $parse_full 是否返回额外地址信息
     * @throws \Exception
     * @return mixed
     *                            string - 成功返回地址信息
     *                            integer - 失败返回 0:错误, -1:库文件出错, -2:未知
     */
    public static function getInfoByCzdat($ip, $parse_full = false)
    {
        $ip = is_string($ip) ? trim($ip) : null;

        if (empty($ip) || !preg_match('/^\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}$/', $ip)) {
            return 0;
        }

        $dat_path = BASEPATH_CONFIG . 'geograph/qqwry.dat';
        if (!$fd = fopen($dat_path, 'rb')) {
            throw new \Exception('IP date file not exists or access denied.');
        }

        // QQWry.dat 里面全部采用了 little-endian 字节序
        // 首四个字节是第一条索引的绝对偏移, 后四个字节是最后一条索引的绝对偏移
        $ip_data = fread($fd, 8);
        $ip_range = unpack('L*', $ip_data);

        list(, $ip_begin, $ip_end) = $ip_range;

        static $FIXED = 4294967296; // pow(2, 32)

        if ($ip_begin < 0) {
            $ip_begin += $FIXED;
        }
        if ($ip_end < 0) {
            $ip_end += $FIXED;
        }

        $ip_amount = ($ip_end - $ip_begin) / 7 + 1;

        $BeginNum = 0;
        $EndNum = $ip_amount;
        $ip1num = $ip2num = 0;

        $ip_long = sprintf('%u', ip2long($ip));

        $NUL = chr(0); // 字符串结束字符
        $STX = chr(2); // 文本开始字符

        // 采用二分查找搜索定位IP
        while ($ip1num > $ip_long || $ip2num < $ip_long) {
            $Middle = intval(($EndNum + $BeginNum) / 2);

            // 7 = 4字节起始IP + 3字节记录偏移
            fseek($fd, $ip_begin + 7 * $Middle);

            // 偏移指针到索引位置读取4字节起始IP
            $ipData1 = fread($fd, 4);
            if (strlen($ipData1) < 4) {
                fclose($fd);

                return -1;
            }

            $ip1num = current(unpack('L', $ipData1));
            if ($ip1num < 0) {
                $ip1num += $FIXED;
            }

            // IP在前半段范围内
            if ($ip1num > $ip_long) {
                $EndNum = $Middle;
                continue;
            }

            // 3字节记录偏移索引
            $offset_seek = fread($fd, 3);
            if (strlen($offset_seek) < 3) {
                fclose($fd);

                return -1;
            }
            $ip_offset = current(unpack('L', $offset_seek . $NUL));

            fseek($fd, $ip_offset);
            $ipData2 = fread($fd, 4);
            if (strlen($ipData2) < 4) {
                fclose($fd);

                return -1;
            }

            $ip2num = current(unpack('L', $ipData2));
            if ($ip2num < 0) {
                $ip2num += $FIXED;
            }

            if ($ip2num < $ip_long) {
                // 没找到提示未知
                if ($Middle == $BeginNum) {
                    fclose($fd);

                    return -2;
                }
                $BeginNum = $Middle;
            }
        }

        $flag = fread($fd, 1);

        if ($flag == chr(1)) {
            $offset_seek = fread($fd, 3);
            if (strlen($offset_seek) < 3) {
                fclose($fd);

                return -1;
            }
            $ip_offset = current(unpack('L', $offset_seek . $NUL));

            fseek($fd, $ip_offset);
            $flag = fread($fd, 1);
        }

        $location = $other_info = '';

        if ($flag == $STX) {
            $AddrSeek = fread($fd, 3);
            if (strlen($AddrSeek) < 3) {
                fclose($fd);

                return -1;
            }
            $flag = fread($fd, 1);

            if ($flag == $STX) {
                $AddrSeek2 = fread($fd, 3);
                if (strlen($AddrSeek2) < 3) {
                    fclose($fd);

                    return -1;
                }
                $AddrSeek2 = current(unpack('L', $AddrSeek2 . $NUL));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }

            if ($parse_full) {
                while (($char = fread($fd, 1)) != $NUL) {
                    $other_info .= $char;
                }
            }

            $AddrSeek = current(unpack('L', $AddrSeek . $NUL));

            fseek($fd, $AddrSeek);

            while (($char = fread($fd, 1)) != $NUL) {
                $location .= $char;
            }
        } else {
            fseek($fd, -1, SEEK_CUR);

            while (($char = fread($fd, 1)) != $NUL) {
                $location .= $char;
            }

            $flag = fread($fd, 1);
            if ($flag == $STX) {
                $AddrSeek2 = fread($fd, 3);
                if (strlen($AddrSeek2) < 3) {
                    fclose($fd);

                    return -1;
                }
                $AddrSeek2 = current(unpack('L', $AddrSeek2 . $NUL));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }

            if ($parse_full) {
                while (($char = fread($fd, 1)) != $NUL) {
                    $other_info .= $char;
                }
            }
        }
        fclose($fd);

        $location = trim($location);

        if (preg_match('/http/i', $location) || $location == '') {
            $addr = -2;
        } else {
            $addr = iconv('gbk', 'utf-8', trim($location));

            if ($parse_full) {
                $other_info = trim($other_info);
                if (!empty($other_info) && !preg_match('/http/i', $other_info)) {
                    $other_info = iconv('gbk', 'utf-8', trim($other_info));
                    $other_info = str_ireplace('CZ88.NET', '', $other_info);
                    if (!empty($other_info)) {
                        $addr = "{$addr} {$other_info}";
                    }
                }
            }
        }

        return $addr;
    }
}
