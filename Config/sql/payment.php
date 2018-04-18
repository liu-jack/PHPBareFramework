<?php
/**
 * payment.php
 * 支付平台
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-4-12 下午5:00
 *
 */

return [
    'create_app' => <<<EOT
CREATE TABLE IF NOT EXISTS `Application` (
  `AppId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `UserId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开发者用户id',
  `AppSecret` varchar(64) NOT NULL DEFAULT '' COMMENT '应用秘钥',
  `AppName` varchar(50) NOT NULL DEFAULT '' COMMENT '应用名称',
  `AppDesc` varchar(255) NOT NULL DEFAULT '' COMMENT '应用描述',
  `AppType` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '应用类型',
  `MerchantId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户id',
  `UpdateTime` datetime DEFAULT NULL,
  `CreateTime` datetime DEFAULT NULL,
  PRIMARY KEY (`AppId`),
  KEY `Idx_UserId` (`UserId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户应用表';
EOT
    ,
    'create_merchant' => <<<EOT
CREATE TABLE IF NOT EXISTS `Merchant` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '商户id',
  `UserId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `RsaPublicKey` varchar(255) NOT NULL COMMENT 'rsa公钥 路径',
  `RsaPrivateKey` varchar(255) NOT NULL COMMENT 'rsa私钥 路径',
  `RsaType` tinyint(4) unsigned NOT NULL DEFAULT '2' COMMENT 'rsa 加密类型 1：RSA 2:RSA256',
  `UpdateTime` datetime DEFAULT NULL,
  `CreateTime` datetime DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT
    ,
    'create_user' => <<<EOT
CREATE TABLE IF NOT EXISTS `User` (
  `UserId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `UserNick` varchar(50) NOT NULL COMMENT '昵称',
  `HeadUrl` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `RealName` varchar(20) NOT NULL DEFAULT '' COMMENT '姓名',
  `Balance` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '余额',
  `UpdateTime` datetime DEFAULT NULL COMMENT '更新时间',
  `CreateTime` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';
EOT
    ,
    'create_order' => <<<EOT
CREATE TABLE IF NOT EXISTS `Order` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `AppId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '应用id',
  `OutTradeNo` varchar(64) NOT NULL DEFAULT '' COMMENT '商户生成订单号',
  `Body` varchar(255) NOT NULL DEFAULT '' COMMENT '主体信息',
  `TotalFee` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单金额 分',
  `NotifyUrl` varchar(255) NOT NULL DEFAULT '' COMMENT '通知地址',
  `CreateIp` varchar(50) NOT NULL DEFAULT '' COMMENT 'ip地址',
  `ExpireTime` datetime NOT NULL COMMENT '有效截止日期',
  `Status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '状态 0：待支付 1：支付成功 2：取消支付 3:支付失败 4：已退款',
  `NotifyStatus` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '通知状态 0:待通知 1：通知成功 2：通知失败',
  `NotifyTimes` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '通知次数',
  `OrderNo` varchar(64) NOT NULL DEFAULT '' COMMENT '平台订号',
  `PayTime` datetime DEFAULT NULL COMMENT '支付时间',
  `NotifyTime` datetime DEFAULT NULL COMMENT '通知时间',
  `RefundTime` datetime DEFAULT NULL COMMENT '退款时间',
  `CreateTime` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`Id`),
  KEY `Idx_Appid` (`Appid`) USING BTREE,
  KEY `Idx_OutTradeNo` (`OutTradeNo`) USING BTREE,
  KEY `Idx_Status` (`Status`,`NotifyStatus`,`NotifyTimes`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单表';
EOT
    ,

];