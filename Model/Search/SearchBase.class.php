<?php
/**
 * SearchBase.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-8-18 下午9:19
 *
 */

namespace Model\Search;

class SearchBase
{
    const T_INT = 'int';
    const T_STRING = 'string';
    const T_STRTOTIME = 'strtotime';
    /**
     * 搜索字段
     */
    public static $_search_fields;

    /**
     * 检查字段
     *
     * @param      $data
     * @param bool $must 必须全部满足
     * @return array
     * @throws \Exception
     */
    public static function checkFields($data, $must = false)
    {
        if ($must) {
            $diff = array_diff_key(static::$_search_fields, $data);
            if (count($diff) > 0) {
                throw new \Exception('Fields miss ' . implode(',', $diff));
            }
        }
        $return = [];
        foreach (static::$_search_fields as $k => $v) {
            if (isset($data[$k])) {
                $t = $data[$k];
                switch ($v[0]) {
                    case self::T_INT:
                        $t = (int)$t;
                        break;
                    case self::T_STRING:
                        $t = (string)$t;
                        break;
                    case self::T_STRTOTIME:
                        $t = strtotime($t);
                        break;
                }
                $return[$v[1]] = $t;
            }
        }

        return $return;
    }
}