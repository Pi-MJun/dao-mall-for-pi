# V2.2.6
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