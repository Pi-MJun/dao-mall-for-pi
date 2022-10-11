# 会员等级介绍
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_membershiplevelvip_introduce` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` char(30) NOT NULL DEFAULT '' COMMENT '名称',
  `desc` char(60) NOT NULL DEFAULT '' COMMENT '描述',
  `images_url` char(255) NOT NULL DEFAULT '' COMMENT '图标',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用（0否, 1是）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `is_enable` (`is_enable`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='会员等级增强版插件 - 会员等级介绍';

# 会员等级
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_membershiplevelvip_level` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` char(30) NOT NULL DEFAULT '' COMMENT '名称',
  `images_url` char(255) NOT NULL DEFAULT '' COMMENT '图标',
  `rules_min` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '自动计算会员（规则最小值）',
  `rules_max` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '自动计算会员（规则最大值）',
  `order_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '满减优惠（订单满额条件）',
  `full_reduction_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '满减优惠（优惠金额）',
  `discount_rate` decimal(3,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '折扣率（0~0.99）',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用（0否, 1是）',
  `is_supported_pay_buy` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否支持付费购买（0否, 1是）',
  `is_supported_renew` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否支持会员续费（0否, 1是）',
  `pay_period_rules` text COMMENT '周期费用规则（json存储）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `is_enable` (`is_enable`),
  KEY `is_supported_pay_buy` (`is_supported_pay_buy`),
  KEY `is_supported_renew` (`is_supported_renew`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='会员等级增强版插件 - 会员等级';

# 会员等级付费用户
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_membershiplevelvip_payment_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `level_id` char(60) NOT NULL DEFAULT '' COMMENT '会员等级id',
  `level_name` char(60) NOT NULL DEFAULT '' COMMENT '等级名称',
  `is_permanent` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否永久/终生（0否, 1是）',
  `is_supported_renew` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否支持续费（0否, 1是）',
  `expire_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '到期时间',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `level_id` (`level_id`),
  KEY `is_supported_renew` (`is_supported_renew`),
  KEY `expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='会员等级增强版插件 - 付费用户';

# 会员等级付费订单纪录
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_membershiplevelvip_payment_user_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `payment_user_order_no` char(60) NOT NULL DEFAULT '' COMMENT '购买单号',
  `payment_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '购买纪录id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '购买周期（单位 天）',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '购买价格（单位 元）',
  `level_id` char(60) NOT NULL DEFAULT '' COMMENT '会员等级id',
  `level_data` text COMMENT '等级数据（json存储）',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '状态（0待支付, 1已支付, 2已取消, 3已关闭）',
  `settlement_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '结算状态（0待结算, 1结算中, 2已结算）',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型（0正常购买, 1续费）',
  `pay_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `payment_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '支付方式id',
  `payment` char(60) NOT NULL DEFAULT '' COMMENT '支付方式标记',
  `payment_name` char(60) NOT NULL DEFAULT '' COMMENT '支付方式名称',
  `pay_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `payment_user_order_no` (`payment_user_order_no`),
  KEY `payment_user_id` (`payment_user_id`),
  KEY `user_id` (`user_id`),
  KEY `level_id` (`level_id`),
  KEY `status` (`status`),
  KEY `settlement_status` (`settlement_status`),
  KEY `pay_price` (`pay_price`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='会员等级增强版插件 - 付费订单纪录';

# 会员佣金明细
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_membershiplevelvip_user_profit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `payment_user_order_id` int(11) unsigned NOT NULL COMMENT '会员付费订单id',
  `payment_user_order_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员付费订单用户id',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `profit_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '收益金额',
  `commission_rules` char(60) NOT NULL DEFAULT '' COMMENT '佣金规则',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '结算状态（0待结算, 1已结算, 2已失效）',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '当前级别（1~3）',
  `user_level_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '插件会员等级id',
  `msg` char(255) NOT NULL DEFAULT '' COMMENT '描述（一般用于失效后的描述）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `payment_user_order_id` (`payment_user_order_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='会员等级增强版插件 - 会员佣金明细';



# 用户会员等级
ALTER TABLE `{PREFIX}user` add `plugins_user_level` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员等级增强版' after `referrer`;

# 商品会员价格扩展数据
ALTER TABLE `{PREFIX}goods` add `plugins_membershiplevelvip_price_extends` mediumtext COMMENT '会员等级增强版价格扩展数据' after `fictitious_goods_value`;