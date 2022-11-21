# 汇率货币配置 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_exchangerate_currency` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` char(80) NOT NULL DEFAULT '' COMMENT '名称',
  `code` char(60) NOT NULL DEFAULT '' COMMENT '代码',
  `symbol` char(60) NOT NULL DEFAULT '' COMMENT '符号',
  `rate` decimal(12,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '汇率',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '顺序',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用（0否，1是）',
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否默认（0否，1是）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `code` (`code`),
  KEY `rate` (`rate`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='汇率货币配置 - 应用';