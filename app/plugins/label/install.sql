# 标签管理
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_label` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` char(30) NOT NULL DEFAULT '' COMMENT '名称',
  `text_color` char(7) NOT NULL DEFAULT '' COMMENT '文字颜色值',
  `bg_color` char(7) NOT NULL DEFAULT '' COMMENT '背景颜色值',
  `icon` char(255) NOT NULL DEFAULT '' COMMENT '图标',
  `use_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '使用总数',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用（0否，1是）',
  `seo_title` char(100) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` char(130) NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `seo_desc` char(230) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `use_count` (`use_count`),
  KEY `is_enable` (`is_enable`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='标签 - 标签管理';

# 用户标签
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_label_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `label_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '标签id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `label_id` (`label_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='标签 - 用户标签';

# 商品标签
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_label_goods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `label_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '标签id',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `label_id` (`label_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='标签 - 商品标签';