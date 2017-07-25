<?php
/**
 * 常用文本处理函数
 *
 * @author 苏宁 <snsnsky@gmail.com>
 *
 * $Id: String.class.php 393 2014-03-18 02:35:43Z yixian $
 */

namespace Classes\Util;

class Strings
{
    /**
     * 截取字符串。英文算1个，中文算两个。
     * @param string $string
     * @param int $length
     * @param string $etc
     * @return string
     */
    public static function truncate($string, $length, $etc = '')
    {
        if ($length == 0) {
            return '';
        }

        $l = strlen($string);
        if ($l <= $length) {
            return $string;
        }

        $tmp = '';

        $i = 0;
        $j = 0;
        do {
            $char = $string[$i];
            $ord = ord($char);
            if ($ord < 128) {
                $tmp .= $char;
                $i++;
                $j++;
            } elseif ($j + 1 < $length) {
                if ($ord < 224) {
                    $tmp .= $char . $string[++$i];
                } elseif ($ord < 240) {
                    $tmp .= $char . $string[++$i] . $string[++$i];
                } else {
                    $tmp .= $char . $string[++$i] . $string[++$i] . $string[++$i];
                }
                $i++;
                $j += 2;
            } else {
                break;
            }
        } while ($i < $l && $j < $length);

        ($i + 1 < $l) && $tmp .= $etc;
        return $tmp;
    }

    /**
     * 验证是否为合法的邮箱地址
     * @param string $email
     * @return bool
     */
    public static function isValidEmail($email)
    {
        return strlen($email) > 6 && preg_match('/^[a-z\d][\w-.]{0,31}@(?:[a-z\d][a-z\d-]{0,30}[a-z\d]\.){1,4}[a-z]{2,4}$/i',
                $email);
    }

    /**
     * 验证是否为合法的手机号
     * @param string $mobile
     * @return bool
     */
    public static function isValidMobile($mobile)
    {
        return preg_match('/^1[0-9]{10}$/', $mobile);
    }

    /**
     * 验证是否为合法的URL
     * @param string $url 待检查URL
     * @return bool
     */
    public static function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * 验证是否为合法的IP地址
     * @param string $ip 待检查IP地址
     * @return bool
     */
    public static function isValidIp($ip, $ipv6 = false)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, $ipv6 ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4);
    }

    /**
     * 检查密码强度
     * @param string $password
     * @return int
     */
    public static function passwordStrength($password)
    {
        // todo:
        return 0;
    }

    /**
     * 检查 $string 是否以 $needle 打头
     * @param string $string
     * @param string $needle
     * @return bool
     */
    public static function startWith($string, $needle)
    {
        return strncmp($string, $needle, strlen($needle)) === 0;
    }

    /**
     * 检查 $string 是否以 $needle 结尾
     * @param string $string
     * @param string $needle
     * @return bool
     */
    public static function endWith($string, $needle)
    {
        return substr($string, 0 - strlen($needle)) === $needle;
    }
}