<?php
/**
 * payment.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-12 下午5:00
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
];