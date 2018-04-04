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
  `ProductId` varchar(32) NOT NULL COMMENT '商品ID, 同支付配置',
  `PaymentType` tinyint(4) NOT NULL COMMENT '支付平台: 0 appstore, 1 支付宝, 2 微信js 3 微信app 4 微信小程序',
  `TradeNo` varchar(64) DEFAULT NULL COMMENT '交易号',
  `TotalFee` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '支付金额',
  `ThirdSN` varchar(64) DEFAULT NULL COMMENT '第三方支付流水号',
  `Content` text COMMENT '其他支付信息',
  `Status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '支付状态，0 等待支付， 1支付成功， 2 支付中，3 取消支付，4 支付失败',
  `CreateTime` datetime NOT NULL COMMENT '创建时间',
  `UpdateTime` datetime NOT NULL COMMENT '最后状态更新时间',
  `FAId` int(10) unsigned DEFAULT '0' COMMENT '来源AppId id',
  `InviteUserId` int(10) DEFAULT '0' COMMENT '邀请用户Id',
  `Channel` varchar(64) DEFAULT '' COMMENT '来源渠道',
  `Coupon` int(11) unsigned DEFAULT '0' COMMENT '优惠金额 单位分',
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
];