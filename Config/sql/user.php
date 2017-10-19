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
    'create_passport_connect' => <<<EOT
CREATE TABLE IF NOT EXISTS `Connect` (
  `ConnectId` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `UserId` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `PlatformId` tinyint(3) unsigned NOT NULL COMMENT '第三方平台 20: 新浪微博 22: QQ 26: 微信 ',
  `SiteId` int(11) unsigned NOT NULL COMMENT '站点ID 1000000',
  `OpenId` varchar(128) NOT NULL DEFAULT '' COMMENT '开放平台唯一ID',
  `UnionId` varchar(128) DEFAULT NULL COMMENT '多平台统一ID(预留)',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`ConnectId`),
  UNIQUE KEY `Connect_OpenId_SiteId` (`OpenId`,`PlatformId`,`SiteId`) USING BTREE,
  UNIQUE KEY `Connect_UserId_SiteId` (`UserId`,`SiteId`,`PlatformId`) USING BTREE,
  KEY `UnionId` (`UnionId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='第三方连接通行证 1.0';
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
    'create_connect' => <<<EOT
CREATE TABLE IF NOT EXISTS `Connect` (
  `ConnectId` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `UserId` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `SiteId` int(11) unsigned NOT NULL COMMENT '站点ID. 见类中定义',
  `AccessToken` varchar(255) NOT NULL DEFAULT '' COMMENT '授权信息',
  `RefreshToken` varchar(255) NOT NULL DEFAULT '' COMMENT '授权更新密码 (OAuth 2.0)',
  `OpenId` varchar(128) NOT NULL DEFAULT '' COMMENT '开放平台唯一ID',
  `UnionId` varchar(128) NOT NULL DEFAULT ''  COMMENT '多平台统一ID(预留)',
  `NickName` varchar(32) NOT NULL DEFAULT '' COMMENT '开放平台的昵称',
  `ExpiredAt` datetime NOT NULL COMMENT 'Token过期时间',
  `UpdatedAt` datetime NOT NULL COMMENT 'Token更新时间',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`ConnectId`),
  UNIQUE KEY `Connect_UserId_SiteId` (`UserId`,`SiteId`) USING BTREE,
  UNIQUE KEY `Connect_OpenId_SiteId` (`OpenId`,`SiteId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='第三方连接表';
EOT
    ,

];