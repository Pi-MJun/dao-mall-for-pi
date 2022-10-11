# 限时秒杀商品 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_seckill_goods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `discount_rate` decimal(3,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '折扣系数 0.00~0.99',
  `dec_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '减金额',
  `is_recommend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐（0否，1是）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `goods_id` (`goods_id`),
  KEY `is_recommend` (`is_recommend`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='限时秒杀商品 - 应用';

# 限时秒杀轮播图 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_seckill_slider` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` char(60) NOT NULL DEFAULT '' COMMENT '别名',
  `images_url` char(255) NOT NULL DEFAULT '' COMMENT '图片地址',
  `url` char(255) NOT NULL DEFAULT '' COMMENT 'url地址',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用（0否，1是）',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `is_enable` (`is_enable`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='限时秒杀轮播图 - 应用';