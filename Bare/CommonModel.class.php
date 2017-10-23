<?php

/**
 * 公共基础类
 *
 */

namespace Bare;

class CommonModel
{
    // 主键/字段类型
    const VAR_TYPE_KEY = 'PRIMARY KEY';
    const VAR_TYPE_INT = 'int';
    const VAR_TYPE_STRING = 'string';
    const VAR_TYPE_ARRAY = 'array';

    const LIST_SIZE = 10;
    const TEN_MINUTE = 600; // 时间: 10 分钟
    const HALF_HOUR = 1800; // 时间: 30 分钟
    const ONE_HOUR = 3600; // 时间: 1 小时
    const HALF_DAY = 43200; // 时间: 半天
    const ONE_DAY = 86400; // 时间: 1 天
    const ONE_WEEK = 604800; // 时间: 1 周
    const SORT_ORDER_ASC = 0; // 排序方式: 升序
    const SORT_ORDER_DESC = 1; // 排序方式: 降序
    const EXTRA_FROM_W = 'from_w';
    const EXTRA_REFRESH = 'refresh';
    const EXTRA_AVATAR = 'avatar';
    const EXTRA_OFFSET = 'offset';
    const EXTRA_LIMIT = 'limit';
    const EXTRA_STATUS = 'status';
    const EXTRA_SORT_ORDER = 'sort_order';

    protected static $_extra_meta = [
        self::EXTRA_AVATAR => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => [
                'regexp' => '/^(?:0|100|180)$/',
            ],
            'default' => 100,
        ],
        self::EXTRA_FROM_W => [
            'filter' => FILTER_VALIDATE_BOOLEAN,
            'default' => false,
        ],
        self::EXTRA_REFRESH => [
            'filter' => FILTER_VALIDATE_BOOLEAN,
            'default' => false,
        ],
        self::EXTRA_OFFSET => [
            'filter' => FILTER_VALIDATE_INT,
            'default' => 0,
        ],
        self::EXTRA_LIMIT => [
            'filter' => FILTER_VALIDATE_INT,
            'default' => self::LIST_SIZE,
        ],
        self::EXTRA_STATUS => [
            'filter' => FILTER_VALIDATE_INT,
            'default' => 1,
        ],
    ];

    protected static function checkFields($rows = [], $fields = [])
    {
        foreach ($rows as $k => &$v) {
            if (is_numeric($k)) {
                $v = static::checkFields($v);
            } else {
                if (!isset($fields[$k])) {
                    unset($rows[$k]);
                } else {
                    switch ($fields[$k]) {
                        case self::VAR_TYPE_KEY:
                        case self::VAR_TYPE_INT:
                            $v = is_array($v)? $v : intval($v);
                            break;
                        case self::VAR_TYPE_ARRAY:
                            $v = is_array($v) ? serialize($v) : $v;
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * 较验额外参数
     *
     * @param array $extra 参数列表
     * @param array $metas 较验模板,若为空则使用默认的
     * @return array
     */
    protected static function _parseExtras($extra, $metas = null)
    {
        $metas = empty($metas) ? static::$_extra_meta : $metas;
        $args = filter_var_array((array)$extra, $metas);
        foreach ($metas as $fkey => $fval) {
            $val = isset($args[$fkey]) ? $args[$fkey] : null;
            if ($val === null || ($val === false && $fval['filter'] !== FILTER_VALIDATE_BOOLEAN)) {
                $args[$fkey] = $fval['default'];
            }
        }

        return $args;
    }

    /**
     * 处理计数字段更新
     *
     * @param mixed $info   要更新的数据
     * @param array $fields 计数字段schema
     * @return array
     */
    protected static function _parseCount($info, $fields)
    {
        $data = [];
        if (!is_array($info) || !is_array($fields)) {
            return $data;
        }
        $info = array_intersect_key($info, $fields);
        foreach ($info as $type => $seed) {
            if (isset($fields[$type]) && is_numeric($seed)) {
                $field = $fields[$type];
                $seed_int = (int)$seed;
                $seed_str = "{$seed}";
                $sign = $seed_str[0];
                if ($seed_int !== 0) {
                    // 在原值基础上进行加减
                    if ($sign === '+' || $sign === '-') {
                        $expr = $seed_int > 0 ? "`{$field}`+{$seed_int}" : "GREATEST(`{$field}`{$seed_int}, 0)";
                    } else {
                        $expr = $seed_int;
                    }
                    $data[$field] = "`{$field}` = {$expr}";
                } else {
                    if ($sign !== '+' && $sign !== '-') {
                        $data[$field] = "`{$field}` = 0";
                    }
                }
            }
        }

        return $data;
    }
}
