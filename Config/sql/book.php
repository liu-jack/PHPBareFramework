<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * book.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/5/24 19:42
 *
 */

return [
    'create_book' => <<<EOT
CREATE TABLE IF NOT EXISTS `Book` (
  `BookId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `BookName` varchar(50) NOT NULL COMMENT '书名',
  `Author` varchar(50) NOT NULL COMMENT '作者',
  `Type` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '类型id',
  `TypeName` varchar(50) NOT NULL COMMENT '类型名称',
  `Cover` smallint(5) NOT NULL DEFAULT '0' COMMENT '封面',
  `BookDesc` tinytext NOT NULL COMMENT '描述',
  `Words` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '字数',
  `ViewCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '阅读量',
  `LikeCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '推荐数',
  `FavoriteCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  `UpdateTime` datetime NOT NULL COMMENT '更新时间',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '书本状态 1：正常 2：隐藏',
  `IsFinish` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否完本 0:否 1：采集完结 2：确认完本',
  `FromSite` varchar(255) NOT NULL DEFAULT '77' COMMENT '来源网站id 多个用逗号'',''分隔',
  `DefaultFromSite` tinyint(4) unsigned NOT NULL DEFAULT '77' COMMENT '默认来源',
PRIMARY KEY (`BookId`),
UNIQUE KEY `BookName_Author` (`BookName`,`Author`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `book` AUTO_INCREMENT=30;
EOT
    ,
    'create_book_collect' => <<<EOT
CREATE TABLE IF NOT EXISTS `BookCollect` (
`CollectId` int(11) unsigned NOT NULL AUTO_INCREMENT,
`BookId` int(11) unsigned NOT NULL COMMENT '书id',
`FromSite` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '来源id',
`Url` varchar(255) NOT NULL COMMENT '采集url',
`CollectTime` datetime NOT NULL COMMENT '采集时间',
`Status` tinyint(4) unsigned NOT NULL DEFAULT '2' COMMENT '1 : 采集内容  2：不采集内容 ',
PRIMARY KEY (`CollectId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `book` AUTO_INCREMENT=30;
EOT
    ,
    'create_book_column' => <<<EOT
CREATE TABLE IF NOT EXISTS `BookColumn` (
  `ChapterId` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `BookId` int(11) unsigned NOT NULL COMMENT '书id',
  `ChapterName` varchar(100) NOT NULL DEFAULT '' COMMENT '章节名称',
  `FromId` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '来源id',
  `Url` varchar(255) NOT NULL COMMENT '采集地址',
  PRIMARY KEY (`ChapterId`),
  KEY `BookId_FromId` (`BookId`,`FromId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `book_columns` AUTO_INCREMENT=30;
EOT
    ,
    'create_book_content' => <<<EOT
CREATE TABLE IF NOT EXISTS `BookContent` (
  `ContentId` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ChapterId` bigint(20) unsigned NOT NULL COMMENT '章节id',
  `Content` text NOT NULL COMMENT '章节内容',
  PRIMARY KEY (`ContentId`),
  UNIQUE KEY `ChapterId` (`ChapterId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `book_contents` AUTO_INCREMENT=30;
EOT
    ,
];