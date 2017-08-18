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
    const FIELD_TYPE_INT = 'int';
    const FIELD_TYPE_STRING = 'string';
    const FIELD_TYPE_STRTOTIME = 'strtotime';
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
            if (count(array_diff_key(static::$_search_fields, $data)) > 0) {
                throw new \Exception('Fields miss');
            }
        }
        $return = [];
        foreach (static::$_search_fields as $k => $v) {
            $t = $data[$k];
            switch ($v[0]) {
                case (self::FIELD_TYPE_INT):
                    $t = (int)$t;
                    break;
                case (self::FIELD_TYPE_STRING):
                    $t = (string)$t;
                    break;
                case (self::FIELD_TYPE_STRTOTIME):
                    $t = strtotime($t);
                    break;
            }
            $return[$v[1]] = $t;
        }

        return $return;
    }
}