<?php
/**
 * RSA 加解密
 *
 */

namespace Classes\Encrypt;

class Rsa
{
    /**
     * 公钥
     *
     * @var string
     */
    private static $public_key = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAy61XYxT+z1bFSBoq+mG2
Hibu0SQKfPLuzn7vD4qWBWMUKxPgBbbEMxMQcC+OAcsEA11Ipd6X3SvtFmPBiVB7
0Sftao1S/wjzvYIEli8JmuzU7liPuEr8+zbKQ2knrf59qipOH4S3aELl2Ef4SLbU
7FtDRsQGNn/T+inljir9cPJRaAAGoLBfDDllS0SFOESqsH7kR7wtunnqr9QhMhUl
P+IgFEPeXS3RztE4SMIYVMu99iH6CLZf0Cj7SsFr2/KLNI/NHXemk0sDlD2lrrZh
vlaJtJN+w905J+3eUESDjfJT0srsT42rGCQwWclniME+qk2VIlnGTQGVpNLRpVZy
BwIDAQAB
-----END PUBLIC KEY-----';

    /**
     * 私钥
     *
     * @var string
     */
    private static $private_key = '-----BEGIN RSA PRIVATE KEY-----
MIIEogIBAAKCAQEAy61XYxT+z1bFSBoq+mG2Hibu0SQKfPLuzn7vD4qWBWMUKxPg
BbbEMxMQcC+OAcsEA11Ipd6X3SvtFmPBiVB70Sftao1S/wjzvYIEli8JmuzU7liP
uEr8+zbKQ2knrf59qipOH4S3aELl2Ef4SLbU7FtDRsQGNn/T+inljir9cPJRaAAG
oLBfDDllS0SFOESqsH7kR7wtunnqr9QhMhUlP+IgFEPeXS3RztE4SMIYVMu99iH6
CLZf0Cj7SsFr2/KLNI/NHXemk0sDlD2lrrZhvlaJtJN+w905J+3eUESDjfJT0srs
T42rGCQwWclniME+qk2VIlnGTQGVpNLRpVZyBwIDAQABAoIBAERtjkhpi1oZatpY
gEvye/8RUXbORv/Hllel6actBk31BZeba84/oxcNtp4aO/l6PXTr6DZh3F+gHF1D
UohRk+enJGqB5HpuhYULo4LZdr6oBrm040QlXW0A8V9Iet5H++wpnmmUaxIKN7I8
K7wyQMLPvoCN5xB0TcFkRg1HgDJrfWJtXAGkcnKtp7SR6TxnxfA3PEvcCeKsRFUE
DUOq6U2v9OGGW6yhR9xMVepVuQ2BlnGbFEsbNswDQ74lCX/mt711eVSDvx8HlFW5
AS0rLo7C3x7BJ1/gTsirud8r38h5v0PuVAhntTaMJcQqTVvCSh8Fj1K3FXzvoF09
x4+SZMECgYEA5csdotudBNSCOLcyjzlcYEwah9apYTfd4BjTHf77q6D2HyrTNxQl
OTKa+wIrXxtf6PjztEZqJ4I3Z3JJEIQvlXaBMhEUm8W+nqPN9l5wBuUGDzhew4Qk
+I2EDyDQfZs2mTdkp2aEiW5S4hJbnqdVgiOO4bed44RgMiEShu1QxhcCgYEA4ufA
qrSJdPg1XhU5xgUI3XLdja4oMfsnqwx6KTkBADrA6GiTrs7Y94NxxC+YVczpeOno
stcFwylLjIcMxBzzTvzxtyOU4Q2yfcJPAL77RZh9XAb1J4GT8hZZgI3dvyjrfruE
f9mTMXMb7FqitzT/rYkkzciO0V3QLu2NxodyGZECgYAQn5n8PxDj2Y+2FKKms8qX
+MlujDvimY0GeiVDpT+FkfZcGNgAwwdSVPPbNrP2hF9y1hejMfjZ9NSXOjBI7pcW
o+UCvzOTdUGt7kGnEfves9C1ZymL0VJvXKVyBriubX0MpnI0nfj2jDGYwyU3m/lW
mcXrVEIqxFfFKJlTg3V1bwKBgCeo+F0eBzShubpDHYEGGkGStTTxbucljg5wtN2F
sC4ZFuTIep/AWd4RZI40/3xnv5s5z0mLGd+91Q2wAUQ6BzEUNy+akYgwu7UNhH+N
4h+NAsRWFv2bwX879tLoeQzTmy3gms05+2dWRlguk2hQZCwx33P0jTn9GslJDlHY
FNJxAoGASwANLIoEDm5QJrXsr+jBeHlCZo+vCg6NspLO5HFoy4UkNYK32qFyp6cj
IpqGahj2O3VB+H85lgW0h3iDMDrAdigU10CGaIpzn4zhvn83A9JrC7tmC0Aprj8B
/mhyMLc2TLy6f+3DysrLGoUBqZhBfjsPl+ILK3SEhDJ7wDvntz4=
-----END RSA PRIVATE KEY-----';

    /**
     * 私钥解密
     *
     * @param string $code 加密代码
     * @param string $key 私钥, 可选
     * @return bool|string    解密成功返回明文, 失败返回false
     */
    public static function private_decode($code, $key = '')
    {
        openssl_private_decrypt(base64_decode($code), $private_decode, $key == '' ? self::$private_key : $key);
        return empty($private_decode) ? false : $private_decode;
    }

    /**
     * 私钥加密
     *
     * @param string $data 明文
     * @param string $key 私钥, 可选
     * @return bool|string    加密成功返回密文, 失败返回false
     */
    public static function private_encode($data, $key = '')
    {
        openssl_private_encrypt($data, $private_encode, $key == '' ? self::$private_key : $key);
        return empty($private_encode) ? false : base64_encode($private_encode);
    }

    /**
     * 公钥解密
     *
     * @param string $code 加密代码
     * @param string $key 公钥, 可选
     * @return bool|string    解密成功返回明文, 失败返回false
     */
    public static function public_decode($code, $key = '')
    {
        openssl_public_decrypt(base64_decode($code), $public_decode, $key == '' ? self::$public_key : $key);
        return empty($public_decode) ? false : $public_decode;
    }

    /**
     * 公钥加密
     *
     * @param string $data 明文
     * @param string $key 公钥, 可选
     * @return bool|string    加密成功返回密文, 失败返回false
     */
    public static function public_encode($data, $key = '')
    {
        openssl_public_encrypt($data, $public_encode, $key == '' ? self::$public_key : $key);
        return empty($public_encode) ? false : base64_encode($public_encode);
    }
}