<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * collect.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/6/17 20:04
 *
 */

return [
    'create_collect_web' => <<<EOT
CREATE TABLE IF NOT EXISTS `CollectWeb` (
  `Id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ItemId` varchar(255) NOT NULL DEFAULT '' COMMENT '文章唯一ID',
  `SiteId` int(10) NOT NULL COMMENT ' 站点ID',
  `Title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `Description` varchar(2048) NOT NULL DEFAULT '' COMMENT '描述',
  `Type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '文章类型 1:文章 2:图集 3:视频',
  `TypeLen` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '视频时长(s)',
  `TypeData` varchar(2048) NOT NULL DEFAULT '' COMMENT '额外的类型数据',
  `Cover` varchar(2048) NOT NULL DEFAULT '' COMMENT '封面地址，多个用半角逗号分隔',
  `Content` longtext NOT NULL COMMENT '内容',
  `ArticleTime` datetime NOT NULL COMMENT '文章本身的时间',
  `CreateTime` datetime NOT NULL COMMENT '采集创建时间',
  `Tags` varchar(2048) NOT NULL DEFAULT '' COMMENT '文章的标签，多个用半角逗号分隔',
  `Channel` varchar(255) NOT NULL DEFAULT '' COMMENT '文章所属频道名称',
  `FromUrl` varchar(512) NOT NULL DEFAULT '' COMMENT '原文地址',
  `Author` varchar(255) NOT NULL DEFAULT '' COMMENT '作者',
  `Status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0: 未导入 1: 已导入 2: 导入失败',
  `CollectStatus` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0: 未采集 1: 已采集 2: 图片采集失败 3. 内容采集失败',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Idx` (`ItemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采集 1.0 - web站采集';
EOT
    ,
    'create_picinfo' => <<<EOT
CREATE TABLE IF NOT EXISTS `PicInfo` (
  `Id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `PicUrl` varchar(255) NOT NULL DEFAULT '' COMMENT '图片地址',
  `ItemId` varchar(255) NOT NULL DEFAULT '' COMMENT '图片关联文章的唯一ID',
  `Width` int(11) NOT NULL DEFAULT '0' COMMENT '图片宽度',
  `Height` int(11) NOT NULL DEFAULT '0' COMMENT '图片高度',
  `SavePath` varchar(1024) NOT NULL DEFAULT '' COMMENT '图片存储路径',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '状态： 1: 采集成功 2: 采集失败',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Idx_PicUrl` (`PicUrl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='图片信息表1.0';
EOT
    ,
    'create_article' => <<<EOT
CREATE TABLE IF NOT EXISTS `Article` (
  `ArticleId` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `CateId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '0: 未分类 文章大分类ID',
  `SiteId` int(11) NOT NULL DEFAULT '0' COMMENT '站点ID 0: 官方站点',
  `SiteName` varchar(32) NOT NULL DEFAULT '' COMMENT '站点别名',
  `UserId` bigint(20) unsigned NOT NULL DEFAULT '1' COMMENT '创建者用户ID 1: 官方创建',
  `AdminUserId` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '最后一次操作管理员用户ID',
  `Title` varchar(128) NOT NULL COMMENT '文章标题',
  `Description` varchar(2048) NOT NULL DEFAULT '' COMMENT '文章描述',
  `TagCache` varchar(2048) NOT NULL DEFAULT '' COMMENT '标签缓存',
  `ViewTag` varchar(2048) NOT NULL DEFAULT '' COMMENT '展示用标签',
  `ViewCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '浏览次数',
  `CommentCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评论次数',
  `LikeCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '喜欢次数',
  `Covers` varchar(2048) NOT NULL DEFAULT '' COMMENT '封面图片地址,多张，序列化存储',
  `CoverType` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '封面图类型 0: 默认 1: 单张大图模式',
  `Type` int(11) NOT NULL DEFAULT '1' COMMENT '文章类型 1: 文章 2: 图集 3: 视频 4: Scheme转跳 5: HTTP链接转跳 6: 专辑',
  `TypeLen` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '视频时长(s)',
  `TypeData` varchar(2048) NOT NULL DEFAULT '' COMMENT '额外的类型数据',
  `Author` varchar(32) NOT NULL DEFAULT '' COMMENT '文章作者',
  `FromId` varchar(32) NOT NULL COMMENT '来源ID',
  `FromUrl` varchar(255) NOT NULL DEFAULT '' COMMENT '来源地址',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '2' COMMENT '状态 1: 显示 2: 待审核 3: 审核不通过 4: 隐藏',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  `UpdateTime` datetime NOT NULL COMMENT '最后更新时间',
  `EditTime` datetime NOT NULL COMMENT '最后一次编辑时间',
  PRIMARY KEY (`ArticleId`),
  KEY `Idx_Status` (`Status`),
  KEY `Idx_SiteId` (`SiteId`,`FromId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文章列表 1.0';
EOT
    ,
    'create_content' => <<<EOT
CREATE TABLE IF NOT EXISTS `ArticleContent` (
  `Id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ArticleId` bigint(20) NOT NULL DEFAULT '0',
  `Content` longtext NOT NULL COMMENT '文章内容',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Idx_ ArticleId` (`ArticleId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文章内容表 1.0';
EOT
    ,
    'create_atlas' => <<<EOT
CREATE TABLE IF NOT EXISTS `Atlas` (
  `AtlasId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(100) NOT NULL,
  `CollectUrl` varchar(255) NOT NULL COMMENT '图集url',
  `CreateTime` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`AtlasId`),
  UNIQUE KEY `Idx_Curl` (`CollectUrl`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='图集表';
EOT
    ,
    'create_picture' => <<<EOT
CREATE TABLE IF NOT EXISTS `Picture` (
  `PictureId` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `AtlasId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图集ID',
  `PicUrl` varchar(255) NOT NULL COMMENT '原图片地址',
  `Url` varchar(255) NOT NULL COMMENT '图片url',
  PRIMARY KEY (`PictureId`),
  UNIQUE KEY `Idx_PicUrl` (`PicUrl`) USING BTREE,
  KEY `Idx_AtlasId` (`AtlasId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='图片表';
EOT
    ,
];