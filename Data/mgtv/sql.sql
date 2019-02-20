CREATE TABLE `tp_goods` (
  `goods_id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品id',
  `cat_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `extend_cat_id` int(11) NOT NULL DEFAULT '0' COMMENT '扩展分类id',
  `goods_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '商品编号',
  `goods_name` varchar(120) NOT NULL DEFAULT '' COMMENT '商品名称',
  `click_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击数',
  `brand_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '品牌id',
  `store_count` smallint(5) unsigned NOT NULL DEFAULT '10' COMMENT '库存数量',
  `comment_count` smallint(5) NOT NULL DEFAULT '0' COMMENT '商品评论数',
  `weight` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品重量克为单位',
  `volume` double(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '商品体积。单位立方米',
  `market_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '市场价',
  `shop_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本店价',
  `cost_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品成本价',
  `price_ladder` text COMMENT '价格阶梯',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT '商品关键词',
  `goods_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '商品简单描述',
  `goods_content` text COMMENT '商品详细描述',
  `mobile_content` text COMMENT '手机端商品详情',
  `original_img` varchar(255) NOT NULL DEFAULT '' COMMENT '商品上传原始图',
  `is_virtual` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否为虚拟商品 1是，0否',
  `virtual_indate` int(11) NOT NULL DEFAULT '0' COMMENT '虚拟商品有效期',
  `virtual_limit` smallint(6) NOT NULL DEFAULT '0' COMMENT '虚拟商品购买上限',
  `virtual_refund` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否允许过期退款， 1是，0否',
  `virtual_sales_sum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '虚拟销售量',
  `virtual_collect_sum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '虚拟收藏量',
  `collect_sum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '收藏量',
  `is_on_sale` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否上架',
  `is_free_shipping` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否包邮0否1是',
  `on_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品上架时间',
  `sort` smallint(4) unsigned NOT NULL DEFAULT '50' COMMENT '商品排序',
  `is_recommend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐',
  `is_new` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否新品',
  `is_hot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否热卖',
  `last_update` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
  `goods_type` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '商品所属类型id，取值表goods_type的cat_id',
  `spec_type` smallint(5) NOT NULL DEFAULT '0' COMMENT '商品规格类型，取值表goods_type的cat_id',
  `give_integral` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '购买商品赠送积分',
  `exchange_integral` int(10) NOT NULL DEFAULT '0' COMMENT '积分兑换：0不参与积分兑换，积分和现金的兑换比例见后台配置',
  `suppliers_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '供货商ID',
  `sales_sum` int(11) NOT NULL DEFAULT '0' COMMENT '商品销量',
  `prom_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0默认1抢购2团购3优惠促销4预售5虚拟(5其实没用)6拼团7搭配购',
  `prom_id` int(11) NOT NULL DEFAULT '0' COMMENT '优惠活动id',
  `commission` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '佣金用于分销分成',
  `spu` varchar(128) NOT NULL DEFAULT '' COMMENT 'SPU',
  `sku` varchar(128) NOT NULL DEFAULT '' COMMENT 'SKU',
  `template_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '运费模板ID',
  `video` varchar(255) NOT NULL DEFAULT '' COMMENT '视频',
  PRIMARY KEY (`goods_id`),
  KEY `idx_goods_sn` (`goods_sn`),
  KEY `idx_cat_id` (`cat_id`),
  KEY `idx_last_update` (`last_update`),
  KEY `idx_brand_id` (`brand_id`),
  KEY `idx_goods_number` (`store_count`),
  KEY `idx_goods_weight` (`weight`),
  KEY `idx_sort_order` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='商品表';

CREATE TABLE `tp_goods_activity` (
  `act_id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '活动ID',
  `act_name` varchar(255) NOT NULL DEFAULT '' COMMENT '活动名称',
  `act_desc` text COMMENT '活动描述',
  `act_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '活动类型:1预售2拼团',
  `goods_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '参加活动商品ID',
  `spec_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '商品规格ID',
  `goods_name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动开始时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动结束时间',
  `is_finished` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已结束:0,正常；1,成功结束；2，失败结束。',
  `ext_info` text COMMENT '活动扩展配置',
  `act_count` int(8) NOT NULL DEFAULT '0' COMMENT '商品购买数',
  PRIMARY KEY (`act_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8 COMMENT='商品活动表';

CREATE TABLE `tp_goods_attr` (
  `goods_attr_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品属性id自增',
  `goods_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `attr_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '属性id',
  `attr_value` text COMMENT '属性值',
  `attr_price` varchar(255) NOT NULL DEFAULT '' COMMENT '属性价格',
  PRIMARY KEY (`goods_attr_id`),
  KEY `idx_goods_id` (`goods_id`),
  KEY `idx_attr_id` (`attr_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8 COMMENT='商品属性信息表';

CREATE TABLE `tp_goods_attribute` (
  `attr_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '属性id',
  `attr_name` varchar(60) NOT NULL DEFAULT '' COMMENT '属性名称',
  `type_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '属性分类id',
  `attr_index` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0不需要检索 1关键字检索 2范围检索',
  `attr_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0唯一属性 1单选属性 2复选属性',
  `attr_input_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT ' 0 手工录入 1从列表中选择 2多行文本框',
  `attr_values` text NOT NULL COMMENT '可选值列表',
  `order` tinyint(3) unsigned NOT NULL DEFAULT '50' COMMENT '属性排序',
  PRIMARY KEY (`attr_id`),
  KEY `idx_cat_id` (`type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COMMENT='商品属性表';

CREATE TABLE `tp_goods_category` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品分类id',
  `name` varchar(90) NOT NULL DEFAULT '' COMMENT '商品分类名称',
  `mobile_name` varchar(64) NOT NULL DEFAULT '' COMMENT '手机端显示的商品分类名',
  `parent_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `parent_id_path` varchar(128) NOT NULL DEFAULT '' COMMENT '家族图谱',
  `level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '等级',
  `sort_order` tinyint(1) unsigned NOT NULL DEFAULT '50' COMMENT '顺序排序',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  `image` varchar(512) NOT NULL DEFAULT '' COMMENT '分类图片',
  `is_hot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐为热门分类',
  `cat_group` tinyint(1) NOT NULL DEFAULT '0' COMMENT '分类分组默认0',
  `commission_rate` tinyint(1) NOT NULL DEFAULT '0' COMMENT '分佣比例',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=581 DEFAULT CHARSET=utf8 COMMENT='商品分类表';


CREATE TABLE `tp_goods_collect` (
  `collect_id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id',
  `user_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `goods_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`collect_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_goods_id` (`goods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8 COMMENT='商品集合表';

CREATE TABLE `tp_goods_consult` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品咨询id',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品id',
  `username` varchar(32) NOT NULL DEFAULT '' COMMENT '网名',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '咨询时间',
  `consult_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 商品咨询 2 支付咨询 3 配送 4 售后',
  `content` varchar(1024) NOT NULL DEFAULT '' COMMENT '咨询内容',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父id 用于管理员回复',
  `is_show` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '管理员回复状态，0未回复，1已回复',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品咨询表';

CREATE TABLE `tp_goods_coupon` (
  `coupon_id` int(8) NOT NULL DEFAULT '0' COMMENT '优惠券id',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '指定的商品id：为零表示不指定商品',
  `goods_category_id` smallint(5) NOT NULL DEFAULT '0' COMMENT '指定的商品分类：为零表示不指定分类',
  PRIMARY KEY (`coupon_id`,`goods_id`,`goods_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品优惠券表';

CREATE TABLE `tp_goods_images` (
  `img_id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '图片id 自增',
  `goods_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `image_url` varchar(255) NOT NULL DEFAULT '' COMMENT '图片地址',
  PRIMARY KEY (`img_id`),
  KEY `idx_goods_id` (`goods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 COMMENT='商品图片表';

CREATE TABLE `tp_goods_type` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id自增',
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '类型名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COMMENT='商品类型表';


CREATE TABLE `tp_goods_visit` (
  `visit_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `visittime` int(11) NOT NULL DEFAULT '0' COMMENT '浏览时间',
  `cat_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品分类ID',
  `extend_cat_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品扩展分类ID',
  PRIMARY KEY (`goods_id`,`user_id`,`visit_id`),
  UNIQUE KEY `uniq_goods_id` (`goods_id`,`user_id`),
  KEY `idx_visit_id` (`visit_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=961 DEFAULT CHARSET=utf8 COMMENT='商品浏览历史表';

CREATE TABLE `tp_order` (
  `order_id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单id',
  `order_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '订单编号',
  `user_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `order_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态',
  `shipping_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '发货状态',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态',
  `consignee` varchar(60) NOT NULL DEFAULT '' COMMENT '收货人',
  `country` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '国家',
  `province` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '省份',
  `city` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '城市',
  `district` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '县区',
  `twon` int(11) NOT NULL DEFAULT '0' COMMENT '乡镇',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `zipcode` varchar(60) NOT NULL DEFAULT '' COMMENT '邮政编码',
  `mobile` varchar(60) NOT NULL DEFAULT '' COMMENT '手机',
  `shipping_code` varchar(32) NOT NULL DEFAULT '' COMMENT '物流code',
  `shipping_name` varchar(120) NOT NULL DEFAULT '' COMMENT '物流名称',
  `pay_code_num` varchar(32) NOT NULL DEFAULT '' COMMENT '支付渠道编码',
  `pay_code` varchar(32) NOT NULL DEFAULT '' COMMENT '支付code',
  `pay_name` varchar(120) NOT NULL DEFAULT '' COMMENT '支付方式名称',
  `invoice_title` varchar(256) NOT NULL DEFAULT '' COMMENT '发票抬头',
  `taxpayer` varchar(30) NOT NULL DEFAULT '' COMMENT '纳税人识别号',
  `goods_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品总价',
  `shipping_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '邮费',
  `user_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '使用余额',
  `coupon_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠券抵扣',
  `integral` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '使用积分',
  `integral_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '使用积分抵多少钱',
  `order_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '应付款金额',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单总价',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下单时间',
  `shipping_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后新发货时间',
  `confirm_time` int(10) NOT NULL DEFAULT '0' COMMENT '收货确认时间',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `transaction_id` varchar(255) NOT NULL DEFAULT '' COMMENT '第三方平台交易流水号',
  `prom_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动ID',
  `prom_type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '订单类型：0普通订单4预售订单5虚拟订单6拼团订单',
  `order_prom_id` smallint(6) NOT NULL DEFAULT '0' COMMENT '活动id',
  `order_prom_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '活动优惠金额',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格调整',
  `user_note` varchar(255) NOT NULL DEFAULT '' COMMENT '用户备注',
  `admin_note` varchar(255) NOT NULL DEFAULT '' COMMENT '管理员备注',
  `parent_sn` varchar(100) NOT NULL DEFAULT '' COMMENT '父单单号',
  `is_distribut` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已分成0未分成1已分成',
  `paid_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订金',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '自提点门店id',
  `deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '用户假删除标识,1:删除,0未删除',
  `refund_order_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '退款流水号',
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `uniq_order_sn` (`order_sn`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_add_time` (`add_time`)
) ENGINE=InnoDB AUTO_INCREMENT=604 DEFAULT CHARSET=utf8 COMMENT='订单信息表';

CREATE TABLE `tp_order_action` (
  `action_id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id',
  `order_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `action_user` int(11) NOT NULL DEFAULT '0' COMMENT '操作人 0 为用户操作，其他为管理员id',
  `order_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态',
  `shipping_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '配送状态',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态',
  `action_note` varchar(255) NOT NULL DEFAULT '' COMMENT '操作备注',
  `log_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
  `status_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '状态描述',
  PRIMARY KEY (`action_id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1248 DEFAULT CHARSET=utf8 COMMENT='订单状态表';

CREATE TABLE `tp_order_goods` (
  `rec_id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id自增',
  `order_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `goods_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `goods_name` varchar(120) NOT NULL DEFAULT '' COMMENT '商品名称',
  `goods_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '商品货号',
  `goods_num` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT '购买数量',
  `final_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品实际购买价',
  `goods_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '本店价',
  `cost_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品成本价',
  `member_goods_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '会员折扣价',
  `give_integral` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '购买商品赠送积分',
  `spec_key` varchar(128) NOT NULL DEFAULT '' COMMENT '商品规格key',
  `spec_key_name` varchar(128) NOT NULL DEFAULT '' COMMENT '规格对应的中文名字',
  `bar_code` varchar(64) NOT NULL DEFAULT '' COMMENT '条码',
  `is_comment` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否评价',
  `prom_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠,4预售',
  `prom_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动id',
  `is_send` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未发货，1已发货，2已换货，3已退货',
  `delivery_id` int(11) NOT NULL DEFAULT '0' COMMENT '发货单ID',
  `sku` varchar(128) NOT NULL DEFAULT '' COMMENT 'sku',
  `is_apply_refund` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否申请过售后',
  `from_origin` varchar(255) NOT NULL DEFAULT '' COMMENT '商品来源渠道',
  PRIMARY KEY (`rec_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_goods_id` (`goods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=597 DEFAULT CHARSET=utf8 COMMENT='订单商品表';

CREATE TABLE `tp_payment` (
  `pay_id` int(3) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id',
  `pay_code` varchar(20) NOT NULL DEFAULT '' COMMENT '支付code',
  `pay_name` varchar(120) NOT NULL DEFAULT '' COMMENT '支付方式名称',
  `pay_fee` varchar(10) NOT NULL DEFAULT '' COMMENT '手续费',
  `pay_desc` text COMMENT '描述',
  `pay_order` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'pay_coder',
  `pay_config` text COMMENT '配置',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '开启',
  `is_cod` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否货到付款',
  `is_online` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否在线支付',
  PRIMARY KEY (`pay_id`),
  UNIQUE KEY `uniq_pay_code` (`pay_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='支付方式表';

CREATE TABLE `tp_cart` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '购物车表',
  `user_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `session_id` varchar(128) NOT NULL DEFAULT '' COMMENT 'session',
  `goods_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `goods_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '商品货号',
  `goods_name` varchar(120) NOT NULL DEFAULT '' COMMENT '商品名称',
  `market_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '市场价',
  `goods_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '本店价',
  `member_goods_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '会员折扣价',
  `goods_num` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '购买数量',
  `item_id` int(11) NOT NULL DEFAULT '0' COMMENT '规格ID',
  `spec_key` varchar(64) NOT NULL DEFAULT '' COMMENT '商品规格key 对应tp_spec_goods_price 表',
  `spec_key_name` varchar(64) NOT NULL DEFAULT '' COMMENT '商品规格组合名称',
  `bar_code` varchar(64) NOT NULL DEFAULT '' COMMENT '商品条码',
  `selected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '购物车选中状态',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '加入购物车的时间',
  `prom_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠,7 搭配购',
  `prom_id` int(11) NOT NULL DEFAULT '0' COMMENT '活动id',
  `sku` varchar(128) NOT NULL DEFAULT '' COMMENT 'sku',
  `combination_group_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT ' 搭配购的组id/cart_id',
  `from_origin` varchar(255) NOT NULL DEFAULT '' COMMENT '购物来源',
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_goods_id` (`goods_id`),
  KEY `idx_spec_key` (`spec_key`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8 COMMENT='购物车表';

CREATE TABLE `tp_spec` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '规格表',
  `type_id` int(11) NOT NULL DEFAULT '0' COMMENT '规格类型',
  `name` varchar(55) NOT NULL DEFAULT '' COMMENT '规格名称',
  `order` int(11) NOT NULL DEFAULT '50' COMMENT '排序',
  `search_index` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否需要检索：1是，0否',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 COMMENT='商品规格表';

CREATE TABLE `tp_spec_goods_price` (
  `item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '规格商品id',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品id',
  `key` varchar(255) NOT NULL DEFAULT '' COMMENT '规格键名',
  `key_name` varchar(255) NOT NULL DEFAULT '' COMMENT '规格键名中文',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `market_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '市场价',
  `cost_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本价',
  `commission` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '佣金用于分销分成',
  `store_count` int(11) unsigned NOT NULL DEFAULT '10' COMMENT '库存数量',
  `bar_code` varchar(32) NOT NULL DEFAULT '' COMMENT '商品条形码',
  `sku` varchar(128) NOT NULL DEFAULT '' COMMENT 'SKU',
  `spec_img` varchar(255) NOT NULL DEFAULT '' COMMENT '规格商品主图',
  `prom_id` int(10) NOT NULL DEFAULT '0' COMMENT '活动id',
  `prom_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '参加活动类型',
  PRIMARY KEY (`item_id`),
  KEY `idx_key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8 COMMENT='商品规格信息表';

CREATE TABLE `tp_spec_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品规格图片表id',
  `spec_image_id` int(11) NOT NULL DEFAULT '0' COMMENT '规格项id',
  `src` varchar(512) NOT NULL DEFAULT '' COMMENT '商品规格图片路径',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=828 DEFAULT CHARSET=utf8 COMMENT='商品规格图片表';

CREATE TABLE `tp_spec_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '规格项id',
  `spec_id` int(11) NOT NULL DEFAULT '0' COMMENT '规格id',
  `item` varchar(54) NOT NULL DEFAULT '' COMMENT '规格项',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8 COMMENT='商品规格表';

CREATE TABLE `tp_return_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '退货申请表id自增',
  `rec_id` int(11) NOT NULL DEFAULT '0' COMMENT 'order_goods表id',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '订单id',
  `order_sn` varchar(1024) NOT NULL DEFAULT '' COMMENT '订单编号',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品id',
  `goods_num` int(10) NOT NULL DEFAULT '1' COMMENT '退货数量',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0仅退款 1退货退款 2换货',
  `reason` varchar(255) NOT NULL DEFAULT '' COMMENT '退换货原因',
  `describe` varchar(255) NOT NULL DEFAULT '' COMMENT '问题描述',
  `imgs` varchar(512) NOT NULL DEFAULT '' COMMENT '拍照图片路径',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '申请时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '-2用户取消-1不同意0待审核1通过2已发货3已收货4换货完成5退款完成',
  `remark` varchar(1024) NOT NULL DEFAULT '' COMMENT '客服备注',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `spec_key` varchar(64) NOT NULL DEFAULT '' COMMENT '商品规格key 对应tp_spec_goods_price 表',
  `seller_delivery` text COMMENT '换货服务，卖家重新发货信息',
  `refund_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退还金额',
  `refund_deposit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退还余额',
  `refund_integral` int(11) NOT NULL DEFAULT '0' COMMENT '退还积分',
  `refund_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '退款类型',
  `refund_mark` varchar(255) NOT NULL DEFAULT '' COMMENT '退款备注',
  `refund_time` int(11) NOT NULL DEFAULT '0' COMMENT '退款时间',
  `is_receive` tinyint(4) NOT NULL DEFAULT '0' COMMENT '申请售后时是否收到货物',
  `delivery` text COMMENT '用户发货信息',
  `checktime` int(11) NOT NULL DEFAULT '0' COMMENT '卖家审核时间',
  `receivetime` int(11) NOT NULL DEFAULT '0' COMMENT '卖家收货时间',
  `canceltime` int(11) NOT NULL DEFAULT '0' COMMENT '用户取消时间',
  `refund_order_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '支付退款流水号',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=183 DEFAULT CHARSET=utf8 COMMENT='退货申请表';

CREATE TABLE `tp_pre_sell` (
  `pre_sell_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '预售id',
  `goods_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `goods_name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
  `item_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '规格id',
  `item_name` varchar(255) NOT NULL DEFAULT '' COMMENT '规格名称',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '预售标题',
  `desc` varchar(255) NOT NULL DEFAULT '' COMMENT '预售描述',
  `deposit_goods_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订购商品数',
  `deposit_order_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订购订单数',
  `stock_num` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '预售库存',
  `sell_start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动开始时间',
  `sell_end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动结束时间',
  `pay_start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '尾款支付开始时间',
  `pay_end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '尾款支付结束时间',
  `deposit_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '订金',
  `price_ladder` varchar(255) NOT NULL DEFAULT '' COMMENT '价格阶梯。预定人数达到多少个时，价格为多少钱',
  `delivery_time_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '开始发货时间描述',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `is_finished` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已结束:0,正常；1，结束（待处理）；2,成功结束；3，失败结束。',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '团购状态，0待审核，1正常2拒绝3关闭',
  PRIMARY KEY (`pre_sell_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='预售商品表';

CREATE TABLE `tp_prom_goods` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '活动ID',
  `title` varchar(60) NOT NULL DEFAULT '' COMMENT '促销活动名称',
  `type` int(2) NOT NULL DEFAULT '0' COMMENT '促销类型',
  `expression` varchar(100) NOT NULL DEFAULT '' COMMENT '优惠体现',
  `description` text COMMENT '活动描述',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '活动开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '活动结束时间',
  `is_end` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已结束',
  `group` varchar(255) NOT NULL DEFAULT '' COMMENT '适用范围',
  `prom_img` varchar(150) NOT NULL DEFAULT '' COMMENT '活动宣传图片',
  `buy_limit` int(10) NOT NULL DEFAULT '0' COMMENT '每人限购数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8 COMMENT='活动商品表';

CREATE TABLE `tp_prom_order` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '活动名称',
  `type` int(2) NOT NULL DEFAULT '0' COMMENT '活动类型',
  `money` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '最小金额',
  `expression` varchar(100) NOT NULL DEFAULT '' COMMENT '优惠体现',
  `description` text COMMENT '活动描述',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '活动开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '活动结束时间',
  `is_close` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否关闭',
  `group` varchar(255) NOT NULL DEFAULT '' COMMENT '适用范围',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COMMENT='活动订单表';

CREATE TABLE `tp_brand` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '品牌表',
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '品牌名称',
  `logo` varchar(80) NOT NULL DEFAULT '' COMMENT '品牌logo',
  `desc` text COMMENT '品牌描述',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '品牌地址',
  `sort` int(3) unsigned NOT NULL DEFAULT '50' COMMENT '排序',
  `cat_name` varchar(128) NOT NULL DEFAULT '' COMMENT '品牌分类',
  `parent_cat_id` int(11) NOT NULL DEFAULT '0' COMMENT '分类id',
  `cat_id` int(10) NOT NULL DEFAULT '0' COMMENT '分类id',
  `is_hot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COMMENT='品牌表';

CREATE TABLE `tp_comment` (
  `comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '评论id',
  `goods_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `email` varchar(60) NOT NULL DEFAULT '' COMMENT 'email邮箱',
  `username` varchar(60) NOT NULL DEFAULT '' COMMENT '用户名',
  `content` text COMMENT '评论内容',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `ip_address` varchar(15) NOT NULL DEFAULT '' COMMENT 'ip地址',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否显示',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论用户',
  `img` text COMMENT '晒单图片',
  `order_id` int(8) NOT NULL DEFAULT '0' COMMENT '订单id',
  `deliver_rank` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '物流评价等级',
  `goods_rank` tinyint(1) NOT NULL DEFAULT '0' COMMENT '商品评价等级',
  `service_rank` tinyint(1) NOT NULL DEFAULT '0' COMMENT '商家服务态度评价等级',
  `zan_num` int(10) NOT NULL DEFAULT '0' COMMENT '被赞数',
  `zan_userid` varchar(255) NOT NULL DEFAULT '' COMMENT '点赞用户id',
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否匿名评价:0不是，1是',
  `rec_id` int(11) NOT NULL DEFAULT '0' COMMENT '订单商品表ID',
  `sort` int(4) unsigned NOT NULL DEFAULT '100' COMMENT '排序',
  PRIMARY KEY (`comment_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_id_value` (`goods_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COMMENT='商品评价表';

CREATE TABLE `tp_coupon` (
  `id` int(8) NOT NULL AUTO_INCREMENT COMMENT '表id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '优惠券名字',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发放类型 0下单赠送1 指定发放 2 免费领取 3线下发放',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠券金额',
  `condition` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '使用条件',
  `createnum` int(11) NOT NULL DEFAULT '0' COMMENT '发放数量',
  `send_num` int(11) NOT NULL DEFAULT '0' COMMENT '已领取数量',
  `use_num` int(11) NOT NULL DEFAULT '0' COMMENT '已使用数量',
  `send_start_time` int(11) NOT NULL DEFAULT '0' COMMENT '发放开始时间',
  `send_end_time` int(11) NOT NULL DEFAULT '0' COMMENT '发放结束时间',
  `use_start_time` int(11) NOT NULL DEFAULT '0' COMMENT '使用开始时间',
  `use_end_time` int(11) NOT NULL DEFAULT '0' COMMENT '使用结束时间',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `status` int(2) NOT NULL DEFAULT '0' COMMENT '状态：1有效,2无效',
  `use_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '使用范围：0全店通用1指定商品可用2指定分类商品可用',
  PRIMARY KEY (`id`),
  KEY `idx_use_end_time` (`use_end_time`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8 COMMENT='优惠券信息表';

CREATE TABLE `tp_coupon_list` (
  `id` int(8) NOT NULL AUTO_INCREMENT COMMENT '表id',
  `cid` int(8) NOT NULL DEFAULT '0' COMMENT '优惠券 对应coupon表id',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发放类型 1 按订单发放 2 注册 3 邀请 4 按用户发放',
  `uid` int(8) NOT NULL DEFAULT '0' COMMENT '用户id',
  `order_id` int(8) NOT NULL DEFAULT '0' COMMENT '订单id',
  `get_order_id` int(11) NOT NULL DEFAULT '0' COMMENT '优惠券来自订单ID',
  `use_time` int(11) NOT NULL DEFAULT '0' COMMENT '使用时间',
  `code` varchar(10) NOT NULL DEFAULT '' COMMENT '优惠券兑换码',
  `send_time` int(11) NOT NULL DEFAULT '0' COMMENT '发放时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未使用1已使用2已过期',
  PRIMARY KEY (`id`),
  KEY `idx_cid` (`cid`),
  KEY `idx_uid` (`uid`),
  KEY `idx_code` (`code`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8 COMMENT='优惠券表';

CREATE TABLE `tp_delivery_doc` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '发货单ID',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_sn` varchar(64) NOT NULL DEFAULT '' COMMENT '订单编号',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `consignee` varchar(64) NOT NULL DEFAULT '' COMMENT '收货人',
  `zipcode` varchar(6) NOT NULL DEFAULT '' COMMENT '邮编',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '联系手机',
  `country` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '国ID',
  `province` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '省ID',
  `city` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '市ID',
  `district` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '区ID',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `shipping_code` varchar(32) NOT NULL DEFAULT '' COMMENT '物流code',
  `shipping_name` varchar(64) NOT NULL DEFAULT '' COMMENT '快递名称',
  `shipping_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '运费',
  `invoice_no` varchar(255) NOT NULL DEFAULT '' COMMENT '物流单号',
  `tel` varchar(64) NOT NULL DEFAULT '' COMMENT '座机电话',
  `note` text COMMENT '管理员添加的备注信息',
  `best_time` int(11) NOT NULL DEFAULT '0' COMMENT '友好收货时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `is_del` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `send_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发货方式0自填快递1在线预约2电子面单3无需物流',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COMMENT='发货单';

CREATE TABLE `tp_freight_config` (
  `config_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '配置id',
  `first_unit` double(16,4) NOT NULL DEFAULT '0.0000' COMMENT '首(重：体积：件）',
  `first_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '首(重：体积：件）运费',
  `continue_unit` double(16,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '继续加（件：重量：体积）区间',
  `continue_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '继续加（件：重量：体积）的运费',
  `template_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '运费模板ID',
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否是默认运费配置.0不是，1是',
  PRIMARY KEY (`config_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COMMENT='运费配置表';

CREATE TABLE `tp_freight_region` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `template_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模板id',
  `config_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '运费模板配置ID',
  `region_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'region表id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='运费模板表';

CREATE TABLE `tp_freight_template` (
  `template_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '运费模板ID',
  `template_name` varchar(255) NOT NULL DEFAULT '' COMMENT '模板名称',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 件数；1 商品重量；2 商品体积',
  `is_enable_default` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用使用默认运费配置,0:不启用，1:启用',
  PRIMARY KEY (`template_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='运费模板表';

CREATE TABLE `tp_group_buy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '团购ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '活动名称',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品ID',
  `item_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '对应spec_goods_price商品规格id',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '团购价格',
  `goods_num` int(10) NOT NULL DEFAULT '0' COMMENT '商品参团数',
  `buy_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品已购买数',
  `order_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '已下单人数',
  `virtual_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '虚拟购买数',
  `rebate` decimal(10,1) NOT NULL DEFAULT '0.0' COMMENT '折扣',
  `intro` text COMMENT '本团介绍',
  `goods_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品原价',
  `goods_name` varchar(200) NOT NULL DEFAULT '' COMMENT '商品名称',
  `recommended` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐 0.未推荐 1.已推荐',
  `views` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '查看次数',
  `is_end` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否结束',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COMMENT='团购商品表';

CREATE TABLE `tp_message` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '管理者id',
  `message` text COMMENT '站内信内容',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '个体消息：0，全体消息1',
  `category` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT ' 系统消息：0，活动消息：1',
  `send_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送时间',
  `data` text COMMENT '消息序列化内容',
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='消息表';

CREATE TABLE `tp_mobile_block_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `block_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属板块id',
  `block_type` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '板块类型',
  `title` varchar(120) NOT NULL DEFAULT '' COMMENT '标题、描述、文字内容',
  `block_content` varchar(255) NOT NULL DEFAULT '' COMMENT '其它信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='手机模板信息表';

CREATE TABLE `tp_mobile_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `is_index` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否设为首页 0否 1是',
  `template_name` varchar(64) NOT NULL DEFAULT '' COMMENT '模板名称',
  `template_html` longtext COMMENT '保存编辑后的HTML',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示 0不显示  1显示',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `block_info` longtext COMMENT '信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='手机模板表';

CREATE TABLE `tp_shipping` (
  `shipping_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '物流公司id',
  `shipping_name` varchar(255) NOT NULL DEFAULT '' COMMENT '物流公司名称',
  `shipping_code` varchar(255) NOT NULL DEFAULT '' COMMENT '物流公司编码',
  `is_open` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `shipping_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '物流描述',
  `shipping_logo` varchar(255) NOT NULL DEFAULT '' COMMENT '物流公司logo',
  `template_width` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '运单模板宽度',
  `template_height` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '运单模板高度',
  `template_offset_x` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '运单模板左偏移量',
  `template_offset_y` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '运单模板上偏移量',
  `template_img` varchar(255) NOT NULL DEFAULT '' COMMENT '运单模板图片',
  `template_html` text COMMENT '打印项偏移校正',
  PRIMARY KEY (`shipping_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='物流表';

CREATE TABLE `tp_shipping_area` (
  `shipping_area_id` int(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id',
  `shipping_area_name` varchar(150) NOT NULL DEFAULT '' COMMENT '配送区域名称',
  `shipping_code` varchar(50) NOT NULL DEFAULT '0' COMMENT '物流id',
  `config` text COMMENT '配置首重续重等...序列化存储',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认',
  PRIMARY KEY (`shipping_area_id`),
  KEY `idx_shipping_id` (`shipping_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='配送区域表';

CREATE TABLE `tp_sms_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表id',
  `mobile` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `session_id` varchar(128) NOT NULL DEFAULT '' COMMENT 'session_id',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '发送时间',
  `code` varchar(10) NOT NULL DEFAULT '' COMMENT '验证码',
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '发送状态,1:成功,0:失败',
  `msg` varchar(255) NOT NULL DEFAULT '' COMMENT '短信内容',
  `scene` int(1) NOT NULL DEFAULT '0' COMMENT '发送场景,1:用户注册,2:找回密码,3:客户下单,4:客户支付,5:商家发货,6:身份验证',
  `error_msg` text COMMENT '发送短信异常内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='短信表';

CREATE TABLE `tp_sms_template` (
  `tpl_id` int(8) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `sms_sign` varchar(50) NOT NULL DEFAULT '' COMMENT '短信签名',
  `sms_tpl_code` varchar(100) NOT NULL DEFAULT '' COMMENT '短信模板ID',
  `tpl_content` varchar(512) NOT NULL DEFAULT '' COMMENT '发送短信内容',
  `send_scene` varchar(100) NOT NULL DEFAULT '' COMMENT '短信发送场景',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`tpl_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COMMENT='短信模板表';

CREATE TABLE `tp_special` (
  `sp_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '广告id',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '广告位置ID',
  `media_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '广告类型',
  `sp_name` varchar(60) NOT NULL DEFAULT '' COMMENT '广告名称',
  `sp_link` varchar(255) NOT NULL DEFAULT '' COMMENT '链接地址',
  `sp_code` text COMMENT '图片地址',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '投放时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '结束时间',
  `link_man` varchar(60) NOT NULL DEFAULT '' COMMENT '添加人',
  `link_email` varchar(60) NOT NULL DEFAULT '' COMMENT '添加人邮箱',
  `link_phone` varchar(60) NOT NULL DEFAULT '' COMMENT '添加人联系电话',
  `click_count` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '点击量',
  `enabled` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  `orderby` smallint(6) NOT NULL DEFAULT '50' COMMENT '排序',
  `target` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启浏览器新窗口',
  `bgcolor` varchar(20) NOT NULL DEFAULT '' COMMENT '背景颜色',
  `is_home` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否在首页显示',
  `tags_list` text COMMENT '商品标签',
  PRIMARY KEY (`sp_id`),
  KEY `idx_ienabled` (`enabled`) USING BTREE,
  KEY `idx_iposition_id` (`pid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8 COMMENT='专题';

CREATE TABLE `tp_special_position` (
  `position_id` int(3) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id',
  `position_name` varchar(60) NOT NULL DEFAULT '' COMMENT '广告位置名称',
  `position_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '广告描述',
  `position_style` text COMMENT '模板',
  `is_open` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0关闭1开启',
  `position_code` varchar(150) NOT NULL DEFAULT '' COMMENT '专题背景',
  `orderby` smallint(6) NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`position_id`)
) ENGINE=InnoDB AUTO_INCREMENT=543 DEFAULT CHARSET=utf8 COMMENT='专题位置';

CREATE TABLE `tp_stock_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `goods_name` varchar(100) NOT NULL DEFAULT '' COMMENT '商品名称',
  `goods_spec` varchar(50) NOT NULL DEFAULT '' COMMENT '商品规格',
  `order_sn` varchar(30) NOT NULL DEFAULT '' COMMENT '订单编号',
  `muid` int(11) NOT NULL DEFAULT '0' COMMENT '操作用户ID',
  `stock` int(11) NOT NULL DEFAULT '0' COMMENT '更改库存',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1067 DEFAULT CHARSET=utf8 COMMENT='商品库存表';

CREATE TABLE `tp_team_activity` (
  `team_id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `act_name` varchar(255) NOT NULL DEFAULT '' COMMENT '拼团活动标题',
  `team_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '拼团活动类型,0分享团1佣金团2抽奖团',
  `time_limit` int(11) NOT NULL DEFAULT '0' COMMENT '成团有效期。单位（秒)',
  `team_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '拼团价',
  `needer` int(10) NOT NULL DEFAULT '2' COMMENT '需要成团人数',
  `goods_name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品id',
  `item_id` int(10) NOT NULL DEFAULT '0' COMMENT '商品规格id',
  `bonus` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '团长佣金',
  `stock_limit` int(11) NOT NULL DEFAULT '0' COMMENT '抽奖限量',
  `buy_limit` smallint(4) NOT NULL DEFAULT '0' COMMENT '单次团购买限制数0为不限制',
  `sales_sum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '已拼多少件',
  `virtual_num` int(10) NOT NULL DEFAULT '0' COMMENT '虚拟销售基数',
  `share_title` varchar(100) NOT NULL DEFAULT '' COMMENT '分享标题',
  `share_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '分享描述',
  `share_img` varchar(150) NOT NULL DEFAULT '' COMMENT '分享图片',
  `sort` smallint(4) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_recommend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0关闭1正常',
  `is_lottery` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已经抽奖.1是，0否',
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COMMENT='拼团活动表';

CREATE TABLE `tp_team_follow` (
  `follow_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `follow_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '参团会员id',
  `follow_user_nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '参团会员昵称',
  `follow_user_head_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '会员头像',
  `follow_time` int(11) NOT NULL DEFAULT '0' COMMENT '参团时间',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '订单id',
  `found_id` int(10) NOT NULL DEFAULT '0' COMMENT '开团ID',
  `found_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '开团人user_id',
  `team_id` int(10) NOT NULL DEFAULT '0' COMMENT '拼团活动id',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '参团状态0:待拼单(表示已下单但是未支付)1拼单成功(已支付)2成团成功3成团失败',
  `is_win` tinyint(1) NOT NULL DEFAULT '0' COMMENT '抽奖团是否中奖',
  PRIMARY KEY (`follow_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8 COMMENT='参团表';

CREATE TABLE `tp_team_found` (
  `found_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `found_time` int(11) NOT NULL DEFAULT '0' COMMENT '开团时间',
  `found_end_time` int(11) NOT NULL DEFAULT '0' COMMENT '成团截止时间',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '团长id',
  `team_id` int(10) NOT NULL DEFAULT '0' COMMENT '拼团活动id',
  `nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '团长用户名昵称',
  `head_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '团长头像',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '团长订单id',
  `join` int(8) NOT NULL DEFAULT '1' COMMENT '已参团人数',
  `need` int(8) NOT NULL DEFAULT '1' COMMENT '需多少人成团',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '拼团价格',
  `goods_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品原价',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '拼团状态0:待开团(表示已下单但是未支付)1:已经开团(团长已支付)2:拼团成功,3拼团失败',
  `bonus_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '团长佣金领取状态：0无1领取',
  PRIMARY KEY (`found_id`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8 COMMENT='开团表';

CREATE TABLE `tp_team_lottery` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '幸运儿手机',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '订单id',
  `order_sn` varchar(50) NOT NULL DEFAULT '' COMMENT '订单编号',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '幸运儿手机',
  `team_id` int(11) NOT NULL DEFAULT '0' COMMENT '拼团活动ID',
  `nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '会员昵称',
  `head_pic` varchar(150) NOT NULL DEFAULT '' COMMENT '幸运儿头像',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='抽奖表';

CREATE TABLE `tp_topic` (
  `topic_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id',
  `topic_title` varchar(100) NOT NULL DEFAULT '' COMMENT '专题标题',
  `topic_image` varchar(100) NOT NULL DEFAULT '' COMMENT '专题封面',
  `topic_background_color` varchar(20) NOT NULL DEFAULT '' COMMENT '专题背景颜色',
  `topic_background` varchar(100) NOT NULL DEFAULT '' COMMENT '专题背景图',
  `topic_content` text COMMENT '专题详情',
  `topic_repeat` varchar(20) NOT NULL DEFAULT '' COMMENT '背景重复方式',
  `topic_state` tinyint(1) NOT NULL DEFAULT '1' COMMENT '专题状态1-草稿、2-已发布',
  `topic_margin_top` tinyint(3) NOT NULL DEFAULT '0' COMMENT '正文距顶部距离',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '专题创建时间',
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='专题表';

CREATE TABLE `tp_user_address` (
  `address_id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id',
  `user_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `consignee` varchar(60) NOT NULL DEFAULT '' COMMENT '收货人',
  `email` varchar(60) NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `country` int(11) NOT NULL DEFAULT '0' COMMENT '国家',
  `province` int(11) NOT NULL DEFAULT '0' COMMENT '省份',
  `city` int(11) NOT NULL DEFAULT '0' COMMENT '城市',
  `district` int(11) NOT NULL DEFAULT '0' COMMENT '地区',
  `twon` int(11) NOT NULL DEFAULT '0' COMMENT '乡镇',
  `address` varchar(120) NOT NULL DEFAULT '' COMMENT '地址',
  `zipcode` varchar(60) NOT NULL DEFAULT '' COMMENT '邮政编码',
  `mobile` varchar(60) NOT NULL DEFAULT '' COMMENT '手机',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '默认收货地址',
  `longitude` decimal(10,7) NOT NULL DEFAULT '0.0000000' COMMENT '地址经度',
  `latitude` decimal(10,7) NOT NULL DEFAULT '0.0000000' COMMENT '地址纬度',
  PRIMARY KEY (`address_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=238 DEFAULT CHARSET=utf8 COMMENT='用户地址表';

CREATE TABLE `tp_user_extend` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `invoice_title` varchar(200) NOT NULL DEFAULT '' COMMENT '发票抬头',
  `taxpayer` varchar(100) NOT NULL DEFAULT '' COMMENT '纳税人识别号',
  `invoice_desc` varchar(50) NOT NULL DEFAULT '' COMMENT '不开发票/明细',
  `realname` varchar(100) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `idcard` varchar(100) NOT NULL DEFAULT '' COMMENT '身份证号',
  `cash_alipay` varchar(100) NOT NULL DEFAULT '' COMMENT '提现支付宝号',
  `cash_unionpay` varchar(100) NOT NULL DEFAULT '' COMMENT '提现银行卡号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='用户信息扩展表';

CREATE TABLE `tp_user_message` (
  `rec_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `message_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '消息id',
  `category` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '系统消息0，活动消息',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '查看状态：0未查看，1已查看',
  PRIMARY KEY (`rec_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_message_id` (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8 COMMENT='用户消息表';

CREATE TABLE `tp_users` (
  `user_id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id',
  `email` varchar(60) NOT NULL DEFAULT '' COMMENT 'email',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 保密 1 男 2 女',
  `birthday` int(11) NOT NULL DEFAULT '0' COMMENT '生日',
  `user_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '用户金额',
  `frozen_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '冻结金额',
  `distribut_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '累积分佣金额',
  `underling_number` int(5) NOT NULL DEFAULT '0' COMMENT '用户下线总数',
  `pay_points` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '消费积分',
  `address_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '默认收货地址',
  `reg_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册时间',
  `last_login` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '最后登录ip',
  `qq` varchar(20) NOT NULL DEFAULT '' COMMENT 'QQ',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号码',
  `mobile_validated` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否验证手机',
  `oauth` varchar(10) NOT NULL DEFAULT '' COMMENT '第三方来源 wx weibo alipay',
  `openid` varchar(100) NOT NULL DEFAULT '' COMMENT '第三方唯一标示',
  `unionid` varchar(100) NOT NULL DEFAULT '' COMMENT 'unionid',
  `head_pic_s` varchar(255) NOT NULL DEFAULT '' COMMENT '头像s',
  `head_pic_l` varchar(255) NOT NULL DEFAULT '' COMMENT '头像l',
  `head_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `province` int(6) NOT NULL DEFAULT '0' COMMENT '省份',
  `city` int(6) NOT NULL DEFAULT '0' COMMENT '市区',
  `district` int(6) NOT NULL DEFAULT '0' COMMENT '县',
  `email_validated` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否验证电子邮箱',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '第三方返回昵称',
  `level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '会员等级',
  `discount` decimal(10,2) NOT NULL DEFAULT '1.00' COMMENT '会员折扣，默认1不享受',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '消费累计额度',
  `is_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否被锁定冻结',
  `is_distribut` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为分销商 0 否 1 是',
  `first_leader` int(11) NOT NULL DEFAULT '0' COMMENT '第一个上级',
  `second_leader` int(11) NOT NULL DEFAULT '0' COMMENT '第二个上级',
  `third_leader` int(11) NOT NULL DEFAULT '0' COMMENT '第三个上级',
  `token` varchar(64) NOT NULL DEFAULT '' COMMENT '用于app 授权类似于session_id',
  `message_mask` tinyint(1) NOT NULL DEFAULT '63' COMMENT '消息掩码',
  `push_id` varchar(30) NOT NULL DEFAULT '' COMMENT '推送id',
  `distribut_level` tinyint(2) NOT NULL DEFAULT '0' COMMENT '分销商等级',
  `is_vip` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为VIP ：0不是，1是',
  `wx_openid` varchar(100) NOT NULL DEFAULT '' COMMENT '微信开放id',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uniq_openid` (`openid`)
) ENGINE=InnoDB AUTO_INCREMENT=203 DEFAULT CHARSET=utf8 COMMENT='用户表';

CREATE TABLE `tp_withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '提现申请表',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '申请时间',
  `check_time` int(11) NOT NULL DEFAULT '0' COMMENT '审核时间',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  `refuse_time` int(11) NOT NULL DEFAULT '0' COMMENT '拒绝时间',
  `bank_name` varchar(255) NOT NULL DEFAULT '' COMMENT '银行名称 如支付宝 微信 中国银行 农业银行等',
  `bank_card` varchar(255) NOT NULL DEFAULT '' COMMENT '银行账号或支付宝账号',
  `realname` varchar(100) NOT NULL DEFAULT '' COMMENT '提款账号真实姓名',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '提现备注',
  `taxfee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '税收手续费',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：-2删除作废-1审核失败0申请中1审核通过2付款成功3付款失败',
  `pay_code` varchar(100) NOT NULL DEFAULT '' COMMENT '付款对账流水号',
  `error_code` varchar(255) NOT NULL DEFAULT '' COMMENT '付款失败错误代码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='提现申请表';

