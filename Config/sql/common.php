<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * common.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/5/24 20:22
 *
 */

return [
    'create_favorite' => <<<EOT
CREATE TABLE IF NOT EXISTS `Favorite` (
  `FavoriteId` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `UserId` bigint(20) unsigned NOT NULL COMMENT '用户ID',
  `ItemId` bigint(20) unsigned NOT NULL COMMENT '项目ID',
  `ItemType` tinyint(4) unsigned NOT NULL COMMENT '收藏类型. 以主类配置文件为准.',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`FavoriteId`),
  UNIQUE KEY `Idx_favorite` (`UserId`,`ItemType`,`ItemId`),
  KEY `Idx_ItemType` (`ItemId`,`ItemType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='收藏/喜欢';
EOT
    ,
    'create_comment' => <<<EOT
CREATE TABLE IF NOT EXISTS `Comment` (
  `CommentId` bigint(20) NOT NULL AUTO_INCREMENT,
  `ItemId` bigint(20) unsigned NOT NULL COMMENT '评论对象的编号',
  `UserId` bigint(20) NOT NULL COMMENT '评论的用户ID',
  `ReplyId` bigint(20) NOT NULL DEFAULT '0' COMMENT '回复的评论的编号， 默认为0，主评论',
  `Type` tinyint(4) NOT NULL COMMENT '评论类型 以配置文件为准',
  `IsGood` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否优质评论',
  `AtUserId` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '回复评论时@的用户编号',
  `Content` text CHARACTER SET utf8mb4 NOT NULL COMMENT '评论内容',
  `Platform` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '来源平台 0. Web 1.wap 2. Android 3. iPhone',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '评论当前状态，默认为1  0：已删除 1：正常状态',
  `SubCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '子评论数量，默认为0',
  `ExtraInfo` varchar(4096) NOT NULL COMMENT '扩展字段, 根据不同Type存储不同数据',
  `CreateTime` datetime NOT NULL COMMENT '评论创建时间',
  PRIMARY KEY (`CommentId`),
  KEY `Idx_All` (`ItemId`,`Type`,`Status`,`ReplyId`),
  KEY `Idx_Admin` (`Type`,`Status`,`CreateTime`),
  KEY `Idx_UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='评论系统';
EOT
    ,
    'create_tag' => <<<EOT
CREATE TABLE IF NOT EXISTS `Tag` (
  `TagId` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `UserId` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `TagNameId` int(11) unsigned NOT NULL COMMENT '标签ID',
  `ItemId` bigint(20) unsigned NOT NULL COMMENT '项目ID',
  `Type` tinyint(4) unsigned NOT NULL COMMENT 'Tag类型',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`TagId`),
  UNIQUE KEY `Idx_Tag_All` (`ItemId`,`Type`,`TagNameId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Tag系统 - 关系表';
EOT
    ,
    'create_tagname' => <<<EOT
CREATE TABLE IF NOT EXISTS `TagName` (
  `TagNameId` bigint(20) NOT NULL AUTO_INCREMENT,
  `TagName` varchar(32) NOT NULL DEFAULT '' COMMENT 'Tag名称',
  `Banner` text NOT NULL COMMENT '广告图片, 序列化存储',
  `VerId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图片版本ID 0: 未传 >0: 已传',
  `TagDesc` varchar(2048) NOT NULL DEFAULT '' COMMENT '标签简介',
  `FollowCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '标签订阅数',
  PRIMARY KEY (`TagNameId`),
  UNIQUE KEY `Idx_TagName` (`TagName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Tag系统 - 名称';
EOT
    ,
];