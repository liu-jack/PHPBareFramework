<?php
/**
 * Blowfish 加解密
 *
 */

namespace Classes\Encrypt;

class Blowfish
{
    /**
     * 向量混淆密钥
     *
     * @var string
     */
    private static $key = 'e106bfbf8d76e1dd733411c090b67f78';

    /**
     * 加密
     *
     * @param string $data 明文
     * @param string $key 密钥
     * @return string        密文
     */
    public static function encode($data, $key = __KEY__)
    {
        $iv_size = openssl_cipher_iv_length('BF-CBC');
        $iv = openssl_random_pseudo_bytes($iv_size);
        $encoded = openssl_encrypt($data, 'BF-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return bin2hex($iv ^ self::$key) . bin2hex($encoded);
    }

    /**
     * 解密
     *
     * @param string $code 密文
     * @param string $key 密钥
     * @return string        明文 (需要检查正确性)
     */
    public static function decode($code, $key = __KEY__)
    {
        $length = strlen($code);
        if ($length < 18 || $length % 2 == 1) {
            return false;
        }

        $iv = hex2bin(substr($code, 0, 16)) ^ self::$key;
        $encoded = hex2bin(substr($code, 16));
        $decode = rtrim(openssl_decrypt($encoded, 'BF-CBC', $key, OPENSSL_RAW_DATA, $iv), "\0");

        return $decode;
    }
}