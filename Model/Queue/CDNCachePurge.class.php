<?php

namespace Model\Queue;

/**
 * 刷缓存队列
 */
class CDNCachePurge extends Queue
{

    /**
     *  用于处理队列返回的数据
     *
     * @param string $data 队列中存入的数据
     * @return bool
     */
    public function run($data)
    {
        $url = trim($data);

        if (!filter_var($data, FILTER_VALIDATE_URL)) {
            $log = [
                "status" => "fail",
                "url" => $url
            ];
            logs($log, $this->logPath());

            return false;
        }

        $url_item = parse_url($url);

        // 处理本地
        if (isset($url_item['query'])) {
            $url_item['query'] = '?' . $url_item['query'];
        }
        $purge_flag = strpos($url_item['path'], '/purge/') === false ? '/purge' : '';
        $purge_url = 'http://' . $url_item['host'] . $purge_flag . $url_item['path'] . $url_item['query'];

        $this->_query($purge_url, $url_item['host'], $url);

        return true;
    }

    /**
     * 请求开始
     *
     * @param string $url     请求地址
     * @param string $host    域名
     * @param string $old_url 原始地址
     * @return boolean
     */
    private function _query($url, $host, $old_url)
    {
        $flag = false;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ' . $host));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);

        $error_no = curl_errno($ch);
        if ($error_no == 0) {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code == 200 || $code == 404 || $code == 502) {
                $flag = true;
            }
        }
        curl_close($ch);

        if ($flag == false) {
            // $this->queue->add(__CLASS__, $old_url);

            $log = [
                "status" => "fail",
                "errno" => $error_no,
                "url" => $old_url,
                "purge" => $url
            ];
            logs($log, $this->logPath());

            return false;
        }

        return true;
    }
}
