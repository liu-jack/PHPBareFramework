<?php  defined('ROOT_PATH') or exit('Access deny');
/**
 * application.php
 *
 * @author camfee<camfee@foxmail.com>
 * @date 2018/3/27
 *
 */

return [
    'create_payment' => <<<EOT
CREATE TABLE IF NOT EXISTS `Payment` (
  `PaymentId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `AppId` int(11) unsigned NOT NULL COMMENT '应用Id',
  `DeviceId` varchar(64) NOT NULL COMMENT '设备ID',
  `UserId` int(11) unsigned NOT NULL COMMENT '用户Id',
  `GoodsInfo` varchar(256) NOT NULL COMMENT '支付商品信息',
  `SN` varchar(64) NOT NULL COMMENT '支付流水号,自己生成',
  `ProductId` int(11) unsigned NOT NULL COMMENT '商品ID',
  `PaymentType` tinyint(4) unsigned NOT NULL COMMENT '支付平台: 0 appstore, 1 支付宝, 2 微信js 3 微信app 4 微信小程序',
  `TradeNo` varchar(64) DEFAULT NULL COMMENT '交易号',
  `TotalFee` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '支付金额',
  `ThirdSN` varchar(64) DEFAULT NULL COMMENT '第三方支付流水号',
  `Content` text COMMENT '其他支付信息',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态，0 等待支付， 1支付成功， 2 支付中，3 取消支付，4 支付失败',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  `UpdateTime` datetime NOT NULL COMMENT '最后状态更新时间',
  `FAId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '来源AppId id',
  `InviteUserId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '邀请用户Id',
  `Channel` varchar(64) NOT NULL DEFAULT '' COMMENT '来源渠道',
  `Coupon` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '优惠金额 单位分',
  `GroupId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '团购id',
  PRIMARY KEY (`PaymentId`),
  KEY `Idx_SN` (`SN`) USING BTREE,
  KEY `Idx_ThirdSN_Type` (`ThirdSN`,`PaymentType`) USING BTREE,
  KEY `Idx_CreateTimeStatus` (`CreateTime`,`Status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='支付订单表1.0';
EOT
    ,
    'create_address' => <<<EOT
CREATE TABLE IF NOT EXISTS `Address` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `UserId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `Country` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '国别 0：中国',
  `Province` varchar(20) NOT NULL DEFAULT '' COMMENT '省份',
  `City` varchar(50) NOT NULL DEFAULT '' COMMENT '城市',
  `Area` varchar(50) NOT NULL DEFAULT '' COMMENT '区县',
  `Address` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `IsDefault` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认地址 0：否 1：是',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0：隐藏 1：显示',
  `UpdateTime` datetime DEFAULT NULL COMMENT '最后修改时间',
  `CreateTime` datetime DEFAULT NULL COMMENT '添加时间',
  PRIMARY KEY (`Id`),
  KEY `Idx_UserId` (`UserId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户地址表';
EOT
    ,
    'create_product' => <<<EOT
CREATE TABLE IF NOT EXISTS `Product` (
  `ProductId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ShopId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商家id 默认0',
  `CateId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `Title` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
  `Cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
  `Pictures` varchar(2048) NOT NULL DEFAULT '' COMMENT '商品图集',
  `OriginPrice` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '原价',
  `Price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `DiscountPrice` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '折扣价',
  `GroupPrice` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '拼团价格',
  `IsGroup` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启团购 0：否 1：是',
  `GroupNum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '拼团人数',
  `GroupStartTime` datetime DEFAULT NULL COMMENT '团购开始时间',
  `GroupEndTime` datetime DEFAULT NULL COMMENT '团购结束时间',
  `Inventory` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '库存',
  `Content` text NOT NULL COMMENT '图文详情',
  `BuyCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '购买人数',
  `CollectCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '收藏人数',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '商品状态 0：隐藏 1：显示',
  `UpdateTime` datetime DEFAULT NULL,
  `CreateTime` datetime DEFAULT NULL,
  PRIMARY KEY (`ProductId`),
  KEY `Idx_ShopId` (`ShopId`) USING BTREE,
  KEY `Idx_CateId` (`CateId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品表';
EOT
    ,
    'create_product_cate' => <<<EOT
CREATE TABLE IF NOT EXISTS `ProudctCategory` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ParentId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级分类id',
  `Title` varchar(20) NOT NULL DEFAULT '' COMMENT '分类名称',
  `Cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
  `Sort` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '排序 大的排在前',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0：隐藏 1：显示',
  `CreateTime` datetime DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品分类表';
EOT
    ,
    'create_group_buy' => <<<EOT
CREATE TABLE IF NOT EXISTS `GroupBuy` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `ProductId` int(11) unsigned NOT NULL COMMENT '套餐编号',
  `GroupPrice` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '团购价格',
  `GroupCount` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '需要拼团人数',
  `JoinCount` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '加入团人数',
  `UserId` int(11) unsigned NOT NULL COMMENT '用户编号',
  `Status` tinyint(4) unsigned NOT NULL COMMENT '状态：1拼团进行中，2拼团成功，3拼团失败',
  `ExpireTime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '拼团有效期 s',
  `ActStartTime` datetime NOT NULL COMMENT '商品团购活动开始时间',
  `ActEndTime` datetime NOT NULL COMMENT '商品团购活动结束时间',
  `StartTime` datetime NOT NULL COMMENT '团购开始时间',
  `EndTime` datetime NOT NULL COMMENT '团购结束时间',
  `SuccessTime` datetime NOT NULL COMMENT '拼团成功时间',
  `CreateTime` datetime NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`Id`),
  KEY `Idx_EndTime` (`EndTime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='团购';
EOT
    ,
    'create_group_list' => <<<EOT
CREATE TABLE IF NOT EXISTS `GroupBuyList` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `GroupId` int(11) unsigned NOT NULL COMMENT '团编号',
  `UserId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `Type` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '成员类型:1团长，2成员',
  `PayState` tinyint(4) unsigned NOT NULL COMMENT '支付状态:1已支付，0未支付',
  `PayTime` datetime DEFAULT NULL COMMENT '支付时间',
  `CreateTime` datetime NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`Id`),
  KEY `Idx_GroupId` (`GroupId`) USING BTREE,
  KEY `Idx_UserId` (`UserId`) USING BTREE,
  KEY `Idx_CreateTime` (`CreateTime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='团购成员';

EOT
    ,
];