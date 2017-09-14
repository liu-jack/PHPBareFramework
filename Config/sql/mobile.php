<?php  defined('ROOT_PATH') or exit('Access deny');
/**
 * mobile.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/7/18 10:53
 *
 */

return [
    'create_version' => <<<EOT
CREATE TABLE IF NOT EXISTS `AppVersion` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `AppId` int(11) NOT NULL DEFAULT '0' COMMENT 'appid',
  `VersionCode` varchar(16) NOT NULL DEFAULT '' COMMENT '版本号',
  `Description` varchar(2048) NOT NULL DEFAULT '' COMMENT '版本更新说明',
  `DownUrl` varchar(1024) NOT NULL DEFAULT '' COMMENT '下载地址',
  `CreateTime` datetime DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `Idx_All` (`AppId`,`VersionCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='版本管理';
EOT
    ,
    'create_image' => <<<EOT
CREATE TABLE IF NOT EXISTS `AppScreenImage` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `AppId` int(11) NOT NULL COMMENT 'appid',
  `Name` varchar(45) DEFAULT NULL COMMENT '启动图名称',
  `Channel` varchar(512) NOT NULL COMMENT '特殊渠道名字，多个渠道用半角逗号隔开',
  `Description` varchar(512) DEFAULT NULL COMMENT '启动图描述',
  `StartTime` datetime DEFAULT NULL COMMENT '启动图显示开始时间',
  `EndTime` datetime DEFAULT NULL COMMENT '启动图显示结束时间',
  `CreateTime` datetime DEFAULT NULL COMMENT '创建时间',
  `LastTime` datetime DEFAULT NULL COMMENT '最后更新时间',
  `Url` varchar(255) DEFAULT NULL COMMENT '点击Url, 可以为空',
  `Status` int(11) NOT NULL DEFAULT '1' COMMENT '状态 1：正常 2：删除',
  PRIMARY KEY (`Id`),
  KEY `Index_id_type` (`AppId`,`Channel`(255),`Status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='启动图';
EOT
    ,
    'create_recommend' => <<<EOT
CREATE TABLE IF NOT EXISTS `RecomData` (
  `RecomId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `RecomType` varchar(255) DEFAULT '' COMMENT '推荐数据类型',
  `Content` text COMMENT '推荐内容',
  `UpdateTime` datetime DEFAULT NULL COMMENT '更新时间',
  `CreateTime` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`RecomId`),
  UNIQUE KEY `Idx_RD_RecomType` (`RecomType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='推荐数据集 1.0';
EOT
    ,
    'create_device_adr' => <<<EOT
CREATE TABLE IF NOT EXISTS `AndroidDevice` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UserId` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `DeviceId` varchar(64) NOT NULL COMMENT '设备ID',
  `Token` varchar(128) NOT NULL DEFAULT '' COMMENT '个推的Token',
  `Channel` varchar(16) NOT NULL COMMENT '渠道',
  `Status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态: 0: 失效 1: 正常',
  `CreateTime` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Idx_DeviceId` (`DeviceId`),
  KEY `Idx_UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Android 设备表';
EOT
    ,
    'create_device_ios' => <<<EOT
CREATE TABLE IF NOT EXISTS `AndroidDevice` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `UserId` bigint(20) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `DeviceId` varchar(64) NOT NULL COMMENT '设备ID',
  `Token` varchar(255) NOT NULL DEFAULT '' COMMENT '推送Token',
  `iOSToken` varchar(255) NOT NULL DEFAULT '' COMMENT 'iOS推送令牌',
  `Channel` varchar(16) NOT NULL DEFAULT '' COMMENT '渠道',
  `Status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态: 0: 失效 1: 正常',
  `CreateTime` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Idx_DeviceId` (`DeviceId`),
  KEY `Idx_UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='iOS 设备表';
EOT
    ,
];