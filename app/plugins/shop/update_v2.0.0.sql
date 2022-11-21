# v2.2.2
# 店铺表 - 系统类型
ALTER TABLE `{PREFIX}plugins_shop` add `system_type` char(60) NOT NULL DEFAULT 'default' COMMENT '系统类型（默认 default, 其他按照SYSTEM_TYPE常量类型）' after `id`;

# 店铺表 - 二级域名
ALTER TABLE `{PREFIX}plugins_shop` add `domain` char(60) NOT NULL DEFAULT '' COMMENT '店铺二级域名' after `data_model`;
ALTER TABLE `{PREFIX}plugins_shop` add `domain_edit_number` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '域名修改次数' after `domain`;

# 店铺表 - 保证金
ALTER TABLE `{PREFIX}plugins_shop` add `bond_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '保证金状态（0未缴，1已缴，2已退）' after `idcard_back`;
ALTER TABLE `{PREFIX}plugins_shop` add `bond_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '保证金缴纳金额' after `bond_status`;
ALTER TABLE `{PREFIX}plugins_shop` add `bond_expire_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '保证金过期时间' after `bond_price`;
ALTER TABLE `{PREFIX}plugins_shop` add `bond_pay_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '保证金支付时间' after `bond_expire_time`;

# 认证类型
ALTER TABLE `{PREFIX}plugins_shop` change `auth_type` `auth_type` tinyint(1) NOT NULL DEFAULT '-1' COMMENT '认证类型（-1未认证，0个人，1企业）';


# 索引
ALTER TABLE `{PREFIX}plugins_shop` ADD INDEX system_type(`system_type`);
ALTER TABLE `{PREFIX}plugins_shop` ADD INDEX domain(`domain`);








# v1.0.1-> v2.0.0
# 页面设计
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_shop_design` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `logo` char(255) NOT NULL DEFAULT '' COMMENT 'logo',
  `name` char(60) NOT NULL DEFAULT '' COMMENT '名称',
  `config` longtext COMMENT '页面配置信息',
  `access_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '访问次数',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用（0否，1是）',
  `is_header` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否含头部（0否，1是）',
  `is_footer` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否含尾部（0否，1是）',
  `seo_title` char(100) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` char(130) NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `seo_desc` char(230) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `shop_id` (`shop_id`),
  KEY `name` (`name`),
  KEY `is_enable` (`is_enable`),
  KEY `is_header` (`is_header`),
  KEY `is_footer` (`is_footer`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多商户 - 页面设计';

# 导航管理
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_shop_navigation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `name` char(30) NOT NULL DEFAULT '' COMMENT '导航名称',
  `url` char(255) NOT NULL DEFAULT '' COMMENT '自定义url地址',
  `value` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '数据 id',
  `data_type` char(30) NOT NULL DEFAULT '' COMMENT '数据类型（custom:自定义导航, design:页面设计, category:店铺商品分类）',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示（0否，1是）',
  `is_new_window_open` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否新窗口打开（0否，1是）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `user_id` (`user_id`),
  KEY `shop_id` (`shop_id`),
  KEY `is_show` (`is_show`),
  KEY `sort` (`sort`),
  KEY `data_type` (`data_type`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多商户 - 导航管理';


# 字段添加
ALTER TABLE `{PREFIX}plugins_shop` add `more_prove` text COMMENT '更多材料附件、json存储' after `company_license`;
ALTER TABLE `{PREFIX}plugins_shop` add `data_model` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '数据模式（0自动模式，1拖拽设计）' after `category_id`;
ALTER TABLE `{PREFIX}plugins_shop` add `layout_config` longtext COMMENT '页面配置信息' after `data_model`;

# 字段更新
ALTER TABLE `{PREFIX}plugins_shop` change `contact_name` `contacts_name` char(60) NOT NULL DEFAULT '' COMMENT '联系人';
ALTER TABLE `{PREFIX}plugins_shop` change `contact_tel` `contacts_tel` char(15) NOT NULL DEFAULT '' COMMENT '联系电话';

# 索引
ALTER TABLE `{PREFIX}plugins_shop` DROP INDEX `contact_name`;
ALTER TABLE `{PREFIX}plugins_shop` DROP INDEX `contact_tel`;
ALTER TABLE `{PREFIX}plugins_shop` add INDEX contacts_name(`contacts_name`);
ALTER TABLE `{PREFIX}plugins_shop` add INDEX contacts_tel(`contacts_tel`);