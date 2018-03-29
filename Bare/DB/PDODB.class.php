<?php

namespace Bare\DB;

use PDO;
use PDOStatement;
use PDOException;

class PDODB extends PDO
{
    /**
     * DSN 连接信息
     *
     * @var string
     */
    private $dsn;

    /**
     * 数据库 用户名
     *
     * @var string
     */
    private $user;

    /**
     * 数据库 密码
     *
     * @var string
     */
    private $pwd;

    /**
     * PDO 集
     *
     * @var PDOStatement
     */
    private $statement = null;

    /**
     * 默认选项
     *
     * @var array
     */
    private $opt = [
        // 字符集
        'charset' => 'utf8',
        // 长连接
        'persistent' => false,
        // 结果集方式
        'fetchMode' => parent::FETCH_ASSOC,
        // 异常方式
        'errorMode' => parent::ERRMODE_WARNING
    ];

    private $chained = [
        'fields' => '*',
        'table' => '',
        'order' => null,
        'limit' => null,
        'where' => '',
        'index' => null,
        'bind' => []
    ];

    /**
     * 构造函数
     *
     * @param array $params  配置信息
     * @param array $options 选项, @see self::$_opt
     */
    public function __construct($params, $options)
    {
        if (is_array($options)) {
            $this->opt = array_merge($this->opt, $options);
        }

        $params['port'] = empty($params['port']) ? 3306 : $params['port'];

        $this->dsn = $params['driver'] . ':dbname=' . $params['name'] . ';host=' . $params['host'] . ';port=' . $params['port'] . ';charset=' . $this->opt['charset'];
        $this->user = $params['user'];
        $this->pwd = $params['password'];

        $opt = [
            parent::ATTR_PERSISTENT => $this->opt['persistent'],
        ];

        parent::__construct($this->dsn, $this->user, $this->pwd, $opt);
        $this->setAttribute(\PDO::ATTR_ERRMODE, $this->opt['errorMode']);
    }

    /**
     * 关闭连接并销毁此对象
     */
    public function close()
    {
        $this->statement = null;
    }

    /**
     * @see PDO::prepare()
     *
     * @param string $statement
     * @param array  $driver_options
     * @return PDOStatement
     */
    public function prepare($statement, $driver_options = [])
    {
        $this->statement = parent::prepare($statement, $driver_options);
        $this->statement->setFetchMode($this->opt['fetchMode']);

        return $this->statement;
    }

    /**
     * @see PDO::query()
     *
     * @param string $statement
     * @return PDOStatement
     */
    public function query($statement)
    {
        $this->statement = parent::query($statement);
        $this->statement->setFetchMode($this->opt['fetchMode']);

        return $this->statement;
    }

    /**
     * SELECT 语句的快捷方式
     *
     * @param string    $tableName 表名
     * @param array     $where     WHERE 条件数组，仅支持 AND 连接
     * @param string    $fields    要查询的字段，半角逗号分隔，如：field1, field2
     * @param string    $order     排序方法，如：someField DESC
     * @param int|array $limit     限制条数，可以是单个数字或者 [$offset, $num] 格式的数组
     * @return mixed
     */
    public function find($tableName, $where = [], $fields = '*', $order = null, $limit = null)
    {
        $tableName = self::fixTable($tableName);
        $bindVals = [];
        if (is_string($where) && !empty($where)) {
            $_where = 'WHERE ' . $where;
        } elseif (is_array($where) && !empty($where)) {
            $_where = 'WHERE ' . implode(' AND ', $this->parseWhere($where, $bindVals));
        } else {
            $_where = '';
        }
        $_order = is_null($order) ? '' : 'ORDER BY ' . $order;
        if (is_numeric($limit)) {
            $_limit = 'LIMIT ' . intval($limit);
        } elseif (is_array($limit) && count($limit) == 2) {
            $_limit = sprintf('LIMIT %s, %s', $limit[0], $limit[1]);
        } else {
            $_limit = '';
        }

        $sql = sprintf("SELECT %s FROM `%s` %s %s %s", $fields, $tableName, $_where, $_order, $_limit);
        $query = $this->prepare($sql);
        if (!($query instanceof PDOStatement)) {
            return false;
        }
        $res = $query->execute($bindVals);
        if ($res !== true) {
            return false;
        }
        $ret = $query->fetchAll(PDO::FETCH_ASSOC);
        $this->close();
        $query = null;

        return $ret;
    }

    /**
     * 链式查询 - 选择字段
     *
     * @param string $fields 字段列表
     * @return $this
     */
    public function select($fields)
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        foreach ($fields as &$field) {
            $field = trim($field);
            if (preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                $field = '`' . $field . '`';
            }
        }
        unset($field);
        $this->chained['fields'] = implode(',', $fields);

        return $this;
    }

    /**
     * 链式查询 - 选择表名
     *
     * @param string $tableName 表名
     * @return $this
     */
    public function from($tableName)
    {
        $this->chained['table'] = self::fixTable($tableName);

        return $this;
    }

    /**
     * 链式查询 - 指定查询条件
     *
     * @param array|string $where 查询条件
     * @return $this
     */
    public function where($where)
    {
        if (is_string($where)) {
            $this->chained['where'] = 'WHERE ' . $where;
        } elseif (is_array($where) && !empty($where)) {
            $this->chained['where'] = 'WHERE ' . implode(' AND ', $this->parseWhere($where, $this->chained['bind']));
        } else {
            $this->chained['where'] = '';
        }

        return $this;
    }

    /**
     * 链式查询 - 指定排序方法
     *
     * @param string $order 排序方法
     * @return $this
     */
    public function order($order)
    {
        $this->chained['order'] = $order;

        return $this;
    }

    /**
     * 链式查询 - 指定 LIMIT 参数
     *
     * @param mixed   $offset LIMIT 第一个参数，也可以写成 [$offset, $num] 以省略第二个参数
     * @param integer $num    LIMIT 第二个参数
     * @return $this
     */
    public function limit($offset, $num = null)
    {
        $limit = is_null($num) ? $offset : [$offset, $num];
        if (is_numeric($limit)) {
            $this->chained['limit'] = intval($limit);
        } elseif (is_array($limit) && count($limit) == 2) {
            $this->chained['limit'] = implode(',', $limit);
        }

        return $this;
    }

    /**
     * 链式查询 - 指定索字段
     *
     * @param mixed $index 字段名，或者字段序号
     * @return $this
     */
    public function indexBy($index)
    {
        $this->chained['index'] = $index;

        return $this;
    }

    /**
     * 链式查询 - 获取结果集
     *
     * @param int $style 记录格式，PDO::FETCH_*
     * @param int $arg   与 $style 对应的参数，目前只有 PDO::FETCH_COLUMN 需要
     * @return array|bool
     */
    public function getAll($style = PDO::FETCH_ASSOC, $arg = null)
    {
        $opt = $this->chained;
        $sql = sprintf("SELECT %s FROM `%s` %s %s %s", $opt['fields'], $opt['table'], $opt['where'],
            is_null($opt['order']) ? '' : 'ORDER BY ' . $opt['order'],
            is_null($opt['limit']) ? '' : 'LIMIT ' . $opt['limit']);
        $query = $this->prepare($sql);

        if (!($query instanceof PDOStatement)) {
            return false;
        }
        $res = $query->execute($opt['bind']);
        if ($res !== true) {
            return false;
        }

        $index = $opt['index'];
        $data = ($style == PDO::FETCH_COLUMN) ? $query->fetchAll($style, $arg) : $query->fetchAll($style);

        if (empty($data)) {
            return [];
        }

        if (is_null($index)) {
            return $data;
        } else {
            $ret = [];
            if (is_int($index)) {
                $fields = ($opt['fields'] == '*') ? array_keys($data[0]) : explode(',', $opt['fields']);
                $field = $fields[$index];
            } else {
                $field = $index;
            }

            foreach ($data as $row) {
                $ret[$row[$field]] = $row;
            }

            $this->close();
            $this->clear();
            $query = null;

            return $ret;
        }
    }

    /**
     * 链式查询 - 获取第一条数据
     *
     * @param int $style 记录格式，PDO::FETCH_*
     * @return null
     */
    public function getOne($style = PDO::FETCH_ASSOC)
    {
        $res = $this->limit('1')->indexBy(null)->getAll($style);

        return (is_array($res) && !empty($res)) ? $res[0] : null;
    }

    /**
     * 链式查询 - 获取第一条数据的第一个字段值
     *
     * @return null
     */
    public function getValue()
    {
        $res = $this->getOne(PDO::FETCH_NUM);

        return is_array($res) ? $res[0] : null;
    }

    /**
     * 链式查询 - 获取某列数据
     *
     * @param int $col
     * @return array|bool
     */
    public function getColumn($col = 0)
    {
        $index = $this->chained['index'];
        if (is_null($index) && is_numeric($col)) {
            return $this->getAll(PDO::FETCH_COLUMN, intval($col));
        }

        $ret = [];
        $data = $this->getAll(PDO::FETCH_BOTH);
        if (is_null($index)) {
            foreach ($data as $row) {
                $ret[] = $row[$col];
            }
        } else {
            foreach ($data as $row) {
                $ret[$row[$index]] = $row[$col];
            }
        }

        return $ret;
    }

    /**
     * 链式查询 - 重置查询参数
     */
    public function clear()
    {
        $this->chained = [
            'fields' => '*',
            'table' => '',
            'order' => null,
            'limit' => null,
            'where' => '',
            'index' => null,
            'bind' => []
        ];

        return $this;
    }

    /**
     * INSERT 语句的快捷方式
     *
     * @param string $tableName 表名
     * @param array  $rows      要写入的数组(支持多行写入), 单个[data], 多个[[data1],[data2],...]
     * @param array  $options   (可选) INSERT 选项，目前只支持 ignore
     * @return bool|int         成功则返回影响的行数，失败则返回 false
     */
    public function insert($tableName, $rows, $options = [])
    {
        // 表名
        $tableName = self::fixTable($tableName);
        // 带`的字段名称列表
        $fields = [];
        // INSERT 选项
        $option = '';
        if (isset($options['ignore']) && $options['ignore'] == true) {
            $option = 'IGNORE';
        }

        // 绑定数据
        $bindVals = [];

        $rows = is_array(current($rows)) ? $rows : [$rows];

        $flag = true;
        $inserts = [];
        foreach ($rows as $id => $row) {
            $bindKeys = [];
            foreach ($row as $key => $val) {
                if ($flag) {
                    $fields[] = "`{$key}`";
                }
                $k = ":$key{$id}";
                $bindKeys[] = $k;
                $bindVals[$k] = $val;
            }

            $inserts[] = '(' . implode(', ', $bindKeys) . ')';
            $flag = false;
        }

        $sql = sprintf("INSERT %s INTO  `%s` (%s) VALUES %s", $option, $tableName, implode(', ', $fields),
            implode(', ', $inserts));

        $query = $this->prepare($sql);
        if (!($query instanceof PDOStatement)) {
            return false;
        }
        $res = $query->execute($bindVals);
        if ($res !== true) {
            return false;
        }
        $ret = $query->rowCount();

        $this->close();
        $query = null;

        return $ret;
    }

    /**
     * 唯一键不存在则插入, 存在则在原有数据基础上做更新操作
     *
     * @param string $tableName 表名
     * @param array  $rows      要写入的数组(支持一次性接入多行)
     * @param array  $updates   做更新操作时要更新的字段名列表
     * @return bool|int                 成功则返回影响的行数，失败则返回 false
     */
    public function upsert($tableName, $rows, $updates)
    {
        $tableName = self::fixTable($tableName);
        $updateKeys = $fieldKeys = $bindVals = $inserts = [];

        $row = current($rows);
        if (!is_array($row)) {
            $row = $rows;
            $rows = [$row];
        }

        $fields = array_keys($row);
        foreach ($fields as $key) {
            $fieldKeys[$key] = "`{$key}`";
        }

        $count = 0;

        foreach ($rows as $row) {
            ++$count;
            $bindKeys = [];

            foreach ($fields as $key) {
                $bindKey = ":{$key}{$count}";
                $bindKeys[$key] = $bindKey;
                $bindVals[$bindKey] = $row[$key];
            }

            $inserts[] = '(' . implode(',', $bindKeys) . ')';
        }

        foreach ($updates as $key) {
            if (isset($row[$key])) {
                $updateKeys[$key] = "`{$key}` = VALUES(`{$key}`)";
            }
        }

        if (empty($updateKeys)) {
            $update_clause = '';
            $ignore = 'IGNORE';
        } else {
            $update_clause = "ON DUPLICATE KEY UPDATE " . implode(', ', $updateKeys);
            $ignore = '';
        }

        $sql = sprintf("INSERT %s INTO `%s` (%s) VALUES %s {$update_clause}", $ignore, $tableName,
            implode(', ', $fieldKeys), implode(', ', $inserts));
        $query = $this->prepare($sql);
        if (!($query instanceof PDOStatement)) {
            return false;
        }

        $res = $query->execute($bindVals);
        if ($res !== true) {
            return false;
        }
        $ret = $query->rowCount();
        $this->close();
        $query = null;

        return $ret;
    }

    /**
     * UPDATE 语句的快捷方式
     *
     * @param string $tableName 表名
     * @param array  $where     一个 WHERE 条件数组，仅支持 AND
     * @param array  $row       要更新的字段及
     * @return bool|int         成功执行则返回影响的行数，失败则返回 false
     */
    public function update($tableName, $row, $where)
    {
        if (empty($row) || empty($where)) {
            return false;
        }
        // 表名
        $tableName = self::fixTable($tableName);
        // 字段更新列表
        $fields = [];
        // 绑定数据
        $bindVals = [];
        foreach ($row as $key => $val) {
            if (is_array($val)) {
                $fields[] = "`$key`=`{$val[0]}`{$val[1]}";
                continue;
            }
            $fields[] = "`$key`=:$key";
            $bindVals[":$key"] = $val;
        }
        // WHERE 条件
        $whereSQL = $this->parseWhere($where, $bindVals);


        $sql = sprintf("UPDATE `%s` SET %s WHERE %s", $tableName, implode(', ', $fields), implode(' AND ', $whereSQL));
        $query = $this->prepare($sql);
        if (!($query instanceof PDOStatement)) {
            return false;
        }
        $res = $query->execute($bindVals);
        if ($res !== true) {
            return false;
        }
        $ret = $query->rowCount();
        $this->close();
        $query = null;

        return $ret;

    }

    /**
     * DELETE 语句的快捷方式
     *
     * @param string $tableName 表名
     * @param array  $where     一个 WHERE 条件数组，仅支持 AND 连接
     * @return bool|int         成功执行则返回影响的行数，失败则返回 false
     */
    public function delete($tableName, $where)
    {
        if (empty($where)) {
            return false;
        }
        // 表名
        $tableName = self::fixTable($tableName);
        // WHERE 条件
        $bindVals = [];
        $whereSQL = $this->parseWhere($where, $bindVals);

        $sql = sprintf("DELETE FROM `%s` WHERE %s", $tableName, implode(' AND ', $whereSQL));
        $query = $this->prepare($sql);
        if (!($query instanceof PDOStatement)) {
            return false;
        }
        $res = $query->execute($bindVals);
        if ($res !== true) {
            return false;
        }
        $ret = $query->rowCount();
        $this->close();
        $query = null;

        return $ret;
    }

    /**
     * 魔术call
     *
     * @param string $name      方法名
     * @param mixed  $arguments 参数
     * @throws PDOException
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->statement, $name)) {
            return call_user_func_array([$this->statement, $name], $arguments);
        }

        throw new PDOException("Fatal error: Call to undefined method PDOQuery::{$name}", 100);
    }

    private function parseWhere($where, &$bindVals)
    {
        // 返回值
        $whereSQL = [];
        // 绑定符号计数器
        $bindCnts = [];
        // 支持的比较符号
        $operators = ['=', '<', '>', '<>', '>=', '<=', 'IN', 'LIKE'];

        foreach ($where as $key => $val) {
            if (gettype($key) == "integer") {
                $whereSQL[] = $val;
                continue;
            }

            $tmp = explode(' ', $key);
            if (count($tmp) == 1) {
                $tmp[1] = '=';
            }
            list($field, $operator) = $tmp;
            if (!in_array($operator, $operators)) {
                continue;
            }

            // 特殊处理 IN 查询
            if (strtoupper($operator) == 'IN') {
                if (gettype($val) == 'array') {
                    if (!empty($val)) {
                        $val = array_map([$this, 'quote'], $val);
                        $whereSQL[] = sprintf("`%s` IN (%s)", $field, implode(",", $val));
                    }
                } elseif (gettype($val) == 'string') {
                    $whereSQL[] = sprintf("`%s` IN (%s)", $field, $val);
                }
                continue;
            }

            if (isset($bindCnts[$field])) {
                $bindNum = ++$bindCnts[$field];
            } else {
                $bindNum = $bindCnts[$field] = 1;
            }
            $bindKey = sprintf(':%s_%s', $field, $bindNum);
            $whereSQL[] = sprintf("`%s` %s %s", $field, $operator, $bindKey);
            $bindVals[$bindKey] = $val;
        }

        return $whereSQL;
    }

    private static function fixTable($table)
    {
        return str_replace(['`', '.'], ['', '`.`'], $table);
    }
}
