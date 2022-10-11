# 多语言翻译数据
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_multilingual_tr_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `md5_key` char(32) NOT NULL DEFAULT '' COMMENT 'md5key值',
  `from_type` char(30) NOT NULL DEFAULT '' COMMENT '原始类型',
  `to_type` char(30) NOT NULL DEFAULT '' COMMENT '翻译类型',
  `from_value` text COMMENT '原始的值',
  `to_value` text COMMENT '翻译的值',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `md5_key` (`md5_key`),
  KEY `from_type` (`from_type`),
  KEY `to_type` (`to_type`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多语言 - 翻译数据';