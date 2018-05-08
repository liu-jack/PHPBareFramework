<?php

namespace Bare\DataDriver;

class ElasticSearch
{
    // HTTP METHOD
    const HTTP_HEAD = 'HEAD';
    const HTTP_GET = 'GET';
    const HTTP_PUT = 'PUT';
    const HTTP_POST = 'POST';
    const HTTP_DELETE = 'DELETE';

    /**
     * 允许的HTTP 方法
     *
     * @var array
     */
    private static $method = [
        self::HTTP_HEAD => self::HTTP_HEAD,
        self::HTTP_GET => self::HTTP_GET,
        self::HTTP_PUT => self::HTTP_PUT,
        self::HTTP_POST => self::HTTP_POST,
        self::HTTP_DELETE => self::HTTP_DELETE,
    ];

    /**
     * 连接超时时间
     *
     * @var integer
     */
    const CONNECT_TIMEOUT = 3;

    /**
     * 错误
     *
     * @var array
     */
    private $error = ['code' => 0, 'msg' => ''];

    /**
     * IP地址
     *
     * @var string
     */
    private $host = '';

    /**
     * 端口号
     *
     * @var string
     */
    private $port = '';

    /**
     * 构造函数, 加载配置
     *
     * @param array $config 连接配置, 形如: ['host' => 'IP地址', 'port' => '端口号']
     */
    public function __construct(array $config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
    }

    /**
     * 请求并输出搜索结果
     *
     * @param string       $query_string 请求参数, 起始位置不要带/
     * @param string       $method       请求方法
     * @param string|array $data         请求内容数据参数
     * @return bool|array                  失败false 用getLastError()获取错误, 成功结果array
     */
    public function query($query_string, $method = self::HTTP_GET, $data = '')
    {
        if (!isset(self::$method[$method])) {
            $this->_setError(1, 'Unsupported methods!');

            return false;
        }

        $url = 'http://' . $this->host . ':' . $this->port . '/' . $query_string;
        $data = is_string($data) || $data == '' ? $data : json_encode($data);
        $ret = $this->_curl($url, $data, $method);

        if (!is_array($ret)) {
            $this->_setError(2, 'Response format error!');

            return false;
        } else {
            if (isset($ret['status']) && isset($ret['error'])) {
                $this->_setError($ret['status'], $ret['error']);

                return false;
            }
        }

        $this->_setError(0, '');

        return $ret;
    }

    /**
     * 获取最后一次错误信息
     *
     * @return array    ['code' => 错误代码, 'msg' => 错误原因]
     */
    public function getLastError()
    {
        return $this->error;
    }

    private function _setError($errno, $error)
    {
        $this->error = ['code' => $errno, 'msg' => $error];
    }

    private function _curl($url, $data = '', $method)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        if (!empty($data) || '0' === $data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_NOBODY, $method == self::HTTP_HEAD);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}