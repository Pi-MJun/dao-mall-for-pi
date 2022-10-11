# 智能工具箱 - 订单备注
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_intellectstools_order_note` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `content` text COMMENT '备注信息',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='智能工具箱 - 订单备注 - 应用';

# 智能工具箱 - 商品备注
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_intellectstools_goods_note` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `content` text COMMENT '备注信息',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='智能工具箱 - 商品备注 - 应用';

# 智能工具箱 - 批量评价
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_intellectstools_comments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `goods_category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品分类id',
  `content` char(230) NOT NULL DEFAULT '' COMMENT '评价信息',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `goods_category_id` (`goods_category_id`),
  KEY `content` (`content`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='智能工具箱 - 批量评价 - 应用';

# 智能工具箱 - 批量评价商品增加记录
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_intellectstools_comments_goods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `inc_count` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '增加次数',
  `data_count` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '数据条数',
  `last_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后时间',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `goods_id` (`goods_id`),
  KEY `inc_count` (`inc_count`),
  KEY `data_count` (`data_count`),
  KEY `last_time` (`last_time`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='智能工具箱 - 批量评价商品增加记录 - 应用';

# 智能工具箱 - 批量评价商品独立配置
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_intellectstools_comments_goods_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `first_number_min` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '首次随机范围最小值',
  `first_number_max` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '首次随机范围最大值',
  `last_number_min` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '续增随机范围最小值',
  `last_number_max` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '续增随机范围最大值',
  `last_interval_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '续增间隔时间',
  `time_interval_min` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '每条时间间隔最小值',
  `time_interval_max` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '每条时间间隔最大值',
  `rating_rand_min` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '数据评分随机最小值',
  `auto_control_number` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '自动控制',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='智能工具箱 - 批量评价商品独立配置 - 应用';