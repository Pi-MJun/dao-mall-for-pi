# v1.6.5
# 分销等级多商户
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_distribution_level_shop` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `config` text COMMENT '返佣配置（json存储）',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用（0否, 1是）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `shop_id` (`shop_id`),
  KEY `is_enable` (`is_enable`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='分销等级多商户 - 应用';

# 返佣比例支持小数
ALTER TABLE `{PREFIX}plugins_distribution_profit_log` change `rate` `rate` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '等级返佣比例 0~100 的数字（创建时写入，防止发生退款重新计算时用户等级变更）';





# v1.5.0
# 分销阶梯返佣记录
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_distribution_appoint_ladder_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `order_id` int(11) unsigned NOT NULL COMMENT '订单id',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '级别记录',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='分销阶梯返佣记录 - 应用';

# 分销阶梯返佣记录商品
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_distribution_appoint_ladder_log_goods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `log_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '日志id',
  `goods_id` int(11) unsigned NOT NULL COMMENT '商品id',
  PRIMARY KEY (`id`),
  KEY `log_id` (`log_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='分销阶梯返佣记录商品 - 应用';






# v1.4.8
ALTER TABLE `{PREFIX}plugins_distribution_profit_log` change `user_level_id` `user_level_id` text COMMENT '用户等级id（扩展数据id）';






# v1.4.7
# 佣金明细字段长度修改
ALTER TABLE `{PREFIX}plugins_distribution_profit_log` change `msg` `msg` text COMMENT '描述（一般用于退款描述）';
# 积分发放明细字段长度修改
ALTER TABLE `{PREFIX}plugins_distribution_integral_log` change `msg` `msg` text COMMENT '描述（一般用于退回描述）';