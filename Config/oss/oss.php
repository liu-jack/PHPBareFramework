<?php defined('ROOT_PATH') or exit('Access deny');

if (IS_ONLINE === false) {
    return [
        'accessKeyId' => "LTAI4FSEBwPFQ2BA",
        'accessSecret' => "KxgES4R6RnlOzhchkjsjY1tA9TmuTT",
        'roleArn' => "acs:ram::1858458283000583:role/ossrole",
        'regionId' => "cn-hangzhou",
        'bucket' => "meitetest",
        'endPoint' => "http://oss-cn-hangzhou.aliyuncs.com"
    ];
} else {
    return [
        'accessKeyId' => "LTAI4FSEBwPFQ2BA",
        'accessSecret' => "KxgES4R6RnlOzhchkjsjY1tA9TmuTT",
        'roleArn' => "acs:ram::1858458283000583:role/ossrole",
        'regionId' => "cn-hangzhou",
        'bucket' => "jfmeite",
        'endPoint' => "http://oss-cn-hangzhou.aliyuncs.com"
    ];
}