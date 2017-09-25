<?php
/**
 * Created by PhpStorm.
 * User: camfee
 * Date: 17-7-28
 * Time: 下午10:53
 */

return [
    'createdb' => <<<EOT
CREATE DATABASE IF NOT EXISTS 29shu_book default character set utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS 29shu_content default character set utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS zf_passport default character set utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS zf_account default character set utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS zf_favorite default character set utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS zf_application default character set utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS zf_comment default character set utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS zf_tag default character set utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS zf_admin default character set utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS zf_collect default character set utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS zf_mobile default character set utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS zf_picture default character set utf8 COLLATE utf8_general_ci;
EOT
];
