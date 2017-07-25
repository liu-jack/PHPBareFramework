<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * user.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/5/24 20:17
 *
 */
return [
    'create_passport' => <<<EOT
CREATE TABLE IF NOT EXISTS `User` (
  `UserId` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `UserName` varchar(32) NOT NULL DEFAULT '' COMMENT '用户名',
  `Email` varchar(128) NOT NULL DEFAULT '' COMMENT '邮箱',
  `Mobile` varchar(16) NOT NULL DEFAULT '' COMMENT '手机',
  `Password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `RegTime` datetime NOT NULL COMMENT '注册时间',
  `RegIp` varchar(64) NOT NULL DEFAULT '' COMMENT '注册ip',
  `LoginTime` datetime NOT NULL COMMENT '最后登录时间',
  `LoginIp` varchar(64) NOT NULL COMMENT '最后登录ip',
  `LoginCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '登录计数',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '用户状态 0：禁用 1：正常',
  `FromWay` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '注册方式 0：用户名 1：邮箱 2：手机',
  `FromPlatform` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '注册平台 0:wep 1:wap 2:andriod 3:iphone',
  `FromProduct` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '注册产品来源 0：通行证',
  PRIMARY KEY (`UserId`),
  UNIQUE KEY `UserName` (`UserName`) USING BTREE,
  KEY `Email` (`Email`) USING BTREE,
  KEY `Mobile` (`Mobile`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT
    ,
    'create_account' => <<<EOT
CREATE TABLE IF NOT EXISTS `User` (
  `Id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Userid` bigint(20) unsigned NOT NULL COMMENT '用户ID',
  `LoginName` varchar(32) NOT NULL DEFAULT '' COMMENT '登录名',
  `UserNick` varchar(32) NOT NULL DEFAULT '' COMMENT '昵称',
  `Gender` tinyint(4) NOT NULL DEFAULT '0' COMMENT '性别 0:未填 1：男 2：女',
  `Avatar` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '头像版本',
  `Birthday` date NOT NULL COMMENT '生日',
  `LoginCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '登录计数',
  `LoginTime` datetime NOT NULL COMMENT '最后登录时间',
  `CreateTime` datetime NOT NULL COMMENT '注册时间',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '状态: 0: 禁止 1: 正常',
  `BookCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '书本收藏数',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UserId` (`Userid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT
    ,

];