# 店铺
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_shop` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `system_type` char(60) NOT NULL DEFAULT 'default' COMMENT '系统类型（默认 default, 其他按照SYSTEM_TYPE常量类型）',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态（0待提交，1待审核，2已审核，3已拒绝，4已关闭）',
  `logo` char(255) NOT NULL DEFAULT '' COMMENT '正方形店铺loog',
  `logo_long` char(255) NOT NULL DEFAULT '' COMMENT '长方形店铺loog',
  `banner` char(255) NOT NULL DEFAULT '' COMMENT '店铺banner',
  `name` char(30) NOT NULL DEFAULT '' COMMENT '名称',
  `describe` char(255) NOT NULL DEFAULT '' COMMENT '简介',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺分类id',
  `data_model` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '数据模式（0自动模式，1拖拽设计）',
  `domain` char(60) NOT NULL DEFAULT '' COMMENT '店铺二级域名',
  `domain_edit_number` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '域名修改次数',
  `layout_config` longtext COMMENT '页面配置信息',
  `expire_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '到期时间（0则永久）',
  `service_weixin_qrcode` char(255) NOT NULL DEFAULT '' COMMENT '客服微信二维码',
  `service_qq` char(30) NOT NULL DEFAULT '' COMMENT '客服QQ',
  `service_tel` char(20) NOT NULL DEFAULT '' COMMENT '客服电话',
  `notice_mobile` char(11) NOT NULL DEFAULT '' COMMENT '接收通知手机号码',
  `notice_email` char(60) NOT NULL DEFAULT '' COMMENT '接收通知电子邮箱',
  `open_week` tinyint(1) NOT NULL DEFAULT '-1' COMMENT ' 工作日起始',
  `close_week` tinyint(1) NOT NULL DEFAULT '-1' COMMENT '工作日结束',
  `open_time` time NOT NULL DEFAULT '00:00:00' COMMENT '在线时间起始',
  `close_time` time NOT NULL DEFAULT '00:00:00' COMMENT '在线时间结束',
  `is_extraction` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT ' 是否支持自提（0否，1是）',
  `contacts_name` char(60) NOT NULL DEFAULT '' COMMENT '联系人',
  `contacts_tel` char(15) NOT NULL DEFAULT '' COMMENT '联系电话',
  `province` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所在省',
  `city` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所在市',
  `county` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所在县/区',
  `address` char(80) NOT NULL DEFAULT '' COMMENT '详细地址',
  `lng` decimal(13,10) NOT NULL DEFAULT '0.0000000000' COMMENT '经度',
  `lat` decimal(13,10) NOT NULL DEFAULT '0.0000000000' COMMENT '纬度',
  `auth_type` tinyint(1) NOT NULL DEFAULT '-1' COMMENT '认证类型（-1未认证，0个人，1企业）',
  `company_name` char(60) NOT NULL DEFAULT '' COMMENT '企业名称',
  `company_number` char(60) NOT NULL DEFAULT '' COMMENT '企业统一社会信用代码',
  `company_license` char(255) NOT NULL DEFAULT '' COMMENT '企业执照图片',
  `more_prove` text COMMENT '更多材料附件、json存储',
  `idcard_name` char(60) NOT NULL DEFAULT '' COMMENT '身份证姓名',
  `idcard_number` char(30) NOT NULL DEFAULT '' COMMENT '身份证号码',
  `idcard_front` char(255) NOT NULL DEFAULT '' COMMENT '身份证人像面图片',
  `idcard_back` char(255) NOT NULL DEFAULT '' COMMENT '身份证国微面图片',
  `bond_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '保证金状态（0未缴，1已缴，2已退）',
  `bond_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '保证金缴纳金额',
  `bond_expire_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '保证金过期时间',
  `bond_pay_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '保证金支付时间',
  `settle_type` tinyint(1) NOT NULL DEFAULT '-1' COMMENT '结算类型（-1默认不结算，0统一比例，1商品配置）',
  `settle_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '结算比例0~100的值',
  `is_user_settle` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户是否可以配置结算信息（针对商品中）',
  `fail_reason` char(230) NOT NULL DEFAULT '' COMMENT '失败原因',
  `seo_title` char(100) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` char(130) NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `seo_desc` char(230) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `system_type` (`system_type`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `name` (`name`),
  KEY `domain` (`domain`),
  KEY `province` (`province`),
  KEY `city` (`city`),
  KEY `contacts_name` (`contacts_name`),
  KEY `contacts_tel` (`contacts_tel`),
  KEY `lng` (`lng`),
  KEY `lat` (`lat`),
  KEY `idcard_name` (`idcard_name`),
  KEY `idcard_number` (`idcard_number`),
  KEY `expire_time` (`expire_time`),
  KEY `add_time` (`add_time`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多商户 - 店铺';

# 店铺分类
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_shop_category` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `icon` char(255) NOT NULL DEFAULT '' COMMENT 'icon图标',
  `name` char(60) NOT NULL DEFAULT '' COMMENT '名称',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用（0否，1是）',
  `seo_title` char(100) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` char(130) NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `seo_desc` char(230) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `name` (`name`),
  KEY `is_enable` (`is_enable`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多商户 - 店铺分类';

# 店铺收藏
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_shop_favor` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `shop_id` (`shop_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多商户 - 店铺收藏';

# 商品分类
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_shop_goods_category` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `icon` char(255) NOT NULL DEFAULT '' COMMENT 'icon图标',
  `name` char(60) NOT NULL DEFAULT '' COMMENT '名称',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用（0否，1是）',
  `seo_title` char(100) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` char(130) NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `seo_desc` char(230) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `user_id` (`user_id`),
  KEY `shop_id` (`shop_id`),
  KEY `name` (`name`),
  KEY `is_enable` (`is_enable`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多商户 - 商品分类';

# 收益明细
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_shop_order_profit` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `shop_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺用户id',
  `order_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单用户id',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `profit_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '收益金额',
  `settle_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '结算类型（0统一比例，1商品配置）',
  `settle_rate` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '结算比例 0~100 的数字（创建时写入，防止发生退款重新计算时收益比例配置变更）',
  `settle_rules` longtext COMMENT '订单商品、json存储（结算计算规、防止发生退款重新计算时收益比例配置变更）',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '结算状态（0待生效, 1生效中, 2待结算, 3已结算, 4已失效）',
  `msg` char(255) NOT NULL DEFAULT '' COMMENT '描述（一般用于订单发生改变描述）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `shop_id` (`shop_id`),
  KEY `shop_user_id` (`shop_user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多商户 - 收益明细';

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

# 多商户店铺轮播图片
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_shop_slider` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `platform` char(30) NOT NULL DEFAULT 'pc' COMMENT '所属平台（pc PC网站, h5 H5手机网站, ios 苹果APP, android 安卓APP, alipay 支付宝小程序, weixin 微信小程序, baidu 百度小程序, toutiao 头条小程序, qq QQ小程序）',
  `event_type` tinyint(2) NOT NULL DEFAULT '-1' COMMENT '事件类型（0 WEB页面, 1 内部页面(小程序或APP内部地址), 2 外部小程序(同一个主体下的小程序appid), 3 打开地图, 4 拨打电话）',
  `event_value` char(255) NOT NULL DEFAULT '' COMMENT '事件值',
  `images_url` char(255) NOT NULL DEFAULT '' COMMENT '图片地址',
  `name` char(60) NOT NULL DEFAULT '' COMMENT '名称',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用（0否，1是）',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `shop_id` (`shop_id`),
  KEY `platform` (`platform`),
  KEY `is_enable` (`is_enable`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多商户 - 店铺轮播图片';

# 多商户首页推荐
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_shop_recommend` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `title` char(30) NOT NULL DEFAULT '' COMMENT '标题',
  `vice_title` char(60) NOT NULL DEFAULT '' COMMENT '副标题',
  `color` char(30) NOT NULL DEFAULT '' COMMENT 'css颜色值',
  `more_category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更多分类指向地址',
  `keywords` text COMMENT '推荐关键字（英文逗号分割）',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用（0否，1是）',
  `is_home` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否首页展示（0否，1是）',
  `style_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '样式类型（0图文，1九方格，2一行滚动）',
  `data_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '数据类型（0自动模式，1手动模式）',
  `order_by_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '自动数据-排序类型（0最新，1热度，2更新）',
  `order_by_rule` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '自动数据-排序规则（0降序(desc)，1升序(asc)）',
  `data_appoint_goods_ids` char(255) NOT NULL DEFAULT '' COMMENT '手动数据（商品id英文逗号分隔）',
  `data_auto_category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '自动数据-指定分类条件',
  `data_auto_number` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '自动数据-展示数量',
  `time_start` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `time_end` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `shop_id` (`shop_id`),
  KEY `title` (`title`),
  KEY `is_enable` (`is_enable`),
  KEY `is_home` (`is_home`),
  KEY `style_type` (`style_type`),
  KEY `data_type` (`data_type`),
  KEY `order_by_type` (`order_by_type`),
  KEY `order_by_rule` (`order_by_rule`),
  KEY `add_time` (`add_time`),
  KEY `upd_time` (`upd_time`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多商户 - 首页推荐';

# 多商户平台商品添加关联日志
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_shop_goods_copy_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `main_goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '主商品id',
  `shop_goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺商品id',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `shop_id` (`shop_id`),
  KEY `main_goods_id` (`main_goods_id`),
  KEY `shop_goods_id` (`shop_goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多商户 - 平台商品添加关联日志';

# 多商户店铺订单确认接收
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_shop_order_confirm` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态（0待确认，1已确认，2已拒绝）',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '扣款状态（0待扣款，1已扣款，2已退回）',
  `order_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单支付金额',
  `frozen_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '冻结金额',
  `reason` char(230) NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `shop_id` (`shop_id`),
  KEY `status` (`status`),
  KEY `pay_status` (`pay_status`),
  KEY `order_price` (`order_price`),
  KEY `frozen_price` (`frozen_price`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='多商户 - 店铺订单确认接收';


# 商品表
ALTER TABLE `{PREFIX}goods` add `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id' after `id`;
ALTER TABLE `{PREFIX}goods` add `shop_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺用户id' after `shop_id`;
ALTER TABLE `{PREFIX}goods` add `shop_category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺商品分类id' after `shop_user_id`;
ALTER TABLE `{PREFIX}goods` add `shop_settle_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '结算固定金额' after `shop_category_id`;
ALTER TABLE `{PREFIX}goods` add `shop_settle_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '结算比例0~100的值' after `shop_settle_price`;


# 订单表
ALTER TABLE `{PREFIX}order` add `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id' after `user_id`;
ALTER TABLE `{PREFIX}order` add `shop_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺用户id' after `shop_id`;
ALTER TABLE `{PREFIX}order` add `shop_is_delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺是否已删除（0否, 大于0删除时间）' after `user_is_delete_time`;

# 仓库表
ALTER TABLE `{PREFIX}warehouse` add `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id' after `id`;
ALTER TABLE `{PREFIX}warehouse` add `shop_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺用户id' after `shop_id`;