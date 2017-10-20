<?php defined('ROOT_PATH') or exit('Access deny');
/**
 * picture.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2017/10/17 20:04
 *
 */

return [
    'create_atlas' => <<<EOT
CREATE TABLE IF NOT EXISTS `Atlas` (
  `AtlasId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(50) NOT NULL COMMENT '相册标题',
  `Description` varchar(255) NOT NULL COMMENT '相册描述',
  `Cover` varchar(255) NOT NULL COMMENT '封面地址',
  `AtlasTime` datetime NOT NULL COMMENT '相册时间',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`AtlasId`),
  KEY `Idx_AtlasTime` (`AtlasTime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='相册表';
EOT
    ,
    'create_photo' => <<<EOT
CREATE TABLE IF NOT EXISTS `Photo` (
  `PhotoId` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `AtlasId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '相册ID',
  `Title` varchar(50) NOT NULL COMMENT '图片标题',
  `Description` varchar(255) NOT NULL COMMENT '图片描述',
  `ImgUrl` varchar(255) NOT NULL COMMENT '图片url',
  `PhotoTime` datetime NOT NULL COMMENT '图片时间',
  `PhotoAddress` varchar(100) NOT NULL COMMENT '图片地址',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`PhotoId`),
  KEY `Idx_AtlasId` (`AtlasId`) USING BTREE,
  KEY `Idx_PhotoTime` (`PhotoTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='相片表';
EOT
    ,
];