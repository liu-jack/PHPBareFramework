<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * admin.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/5/24 20:29
 *
 */

return [
    'create_group' => <<<EOT
CREATE TABLE IF NOT EXISTS `AdminGroup` (
  `GroupId` tinyint(4) NOT NULL AUTO_INCREMENT,
  `GroupName` varchar(20) NOT NULL COMMENT '权限分组名称',
  `AdminAuth` text NOT NULL COMMENT '权限组包含项目 序列化存储',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '权限组状态 0：禁用 1：正常',
  PRIMARY KEY (`GroupId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='后台 - 权限分组';
EOT
    ,
    'create_user' => <<<EOT
CREATE TABLE IF NOT EXISTS `AdminUser` (
  `UserId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `UserName` varchar(20) NOT NULL COMMENT '管理员登录名',
  `Password` varchar(255) NOT NULL,
  `RealName` varchar(20) NOT NULL DEFAULT '' COMMENT '管理员真实姓名',
  `UserGroup` tinyint(4) NOT NULL DEFAULT '0' COMMENT '用户所在组权限',
  `SpecialGroups` text NOT NULL COMMENT '用户特别指派权限. 序列化存储',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '用户状态 1：正常 0：禁用',
  PRIMARY KEY (`UserId`),
  UNIQUE KEY `Idx_UserName` (`UserName`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='后台 - 用户表';
EOT
    ,
    'create_menu' => <<<EOT
CREATE TABLE IF NOT EXISTS `AdminMenu` (
  `AdminMenuId` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(16) NOT NULL,
  `Key` varchar(255) NOT NULL DEFAULT '' COMMENT '菜单唯一Key',
  `Url` varchar(255) NOT NULL DEFAULT '' COMMENT '菜单URL',
  `ParentId` int(11) NOT NULL DEFAULT '0' COMMENT '父级ID, 若为0则表示顶级',
  `DisplayOrder` int(11) NOT NULL DEFAULT '0' COMMENT '数值越大越靠前',
  PRIMARY KEY (`AdminMenuId`),
  UNIQUE KEY `Idx_AdminMenu_Key` (`Key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='后台 - 菜单表';
EOT
    ,
    'create_log' => <<<EOT
CREATE TABLE IF NOT EXISTS `AdminLog` (
  `LogId` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `UserId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `UserName` varchar(50) NOT NULL DEFAULT '' COMMENT '用户昵称',
  `ItemId` int(20) unsigned NOT NULL DEFAULT '0' COMMENT '对象类型ID',
  `ItemName` varchar(200) NOT NULL DEFAULT '' COMMENT '操作对象名称',
  `MenuKey` varchar(255) NOT NULL DEFAULT '' COMMENT '菜单key',
  `MenuName` varchar(255) NOT NULL COMMENT '菜单名称',
  `LogFlag` varchar(255) NOT NULL DEFAULT '' COMMENT '细分操作标识 如: edit/add/update',
  `Log` text NOT NULL COMMENT '详细日志',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`LogId`),
  KEY `Idx_AdminLog_CreateTime` (`CreateTime`),
  KEY `Idx_AdminLog_UserId` (`UserId`),
  KEY `Idx_AdminLog_MenuKey` (`MenuKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='后台 - 日志记录';
EOT
    ,
    'create_sms' => <<<EOT
CREATE TABLE IF NOT EXISTS `SmsLog` (
  `SmsId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Mobile` varchar(16) NOT NULL COMMENT '手机号码',
  `Content` varchar(512) NOT NULL COMMENT '短信内容',
  `Type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '分类 0: 表示无需分类和反查',
  `Flag` varchar(255) NOT NULL DEFAULT '' COMMENT '标志记录,如验证码',
  `Ip` varchar(32) NOT NULL DEFAULT '' COMMENT 'IP地址',
  `Used` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否使用 0: 未使用 1: 已使用',
  `Status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态: 0: 未发送 1: 已发送',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`SmsId`),
  KEY `Idx_Mobile` (`Mobile`,`Type`),
  KEY `Idx_CreateTime` (`CreateTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='后台 - 短信记录';
EOT
    ,
];