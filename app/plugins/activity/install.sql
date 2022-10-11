# 活动配置 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_activity` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `activity_category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动分类',
  `title` char(30) NOT NULL DEFAULT '' COMMENT '标题',
  `vice_title` char(60) NOT NULL DEFAULT '' COMMENT '副标题',
  `color` char(30) NOT NULL DEFAULT '' COMMENT 'css颜色值',
  `describe` char(255) NOT NULL DEFAULT '' COMMENT '描述',
  `cover` char(255) NOT NULL DEFAULT '' COMMENT '封面图片',
  `keywords` text COMMENT '推荐关键字（英文逗号分割）',
  `goods_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联商品数量',
  `access_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '访问次数',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用（0否，1是）',
  `home_data_location` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '首页数据位置（0楼层数据上面，1楼层数据下面）',
  `style_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '样式类型（0图文，1九方格，2一行滚动）',
  `is_home` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否首页展示（0否，1是）',
  `is_goods_detail` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否商品详情页展示（0否，1是）',
  `time_start` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `time_end` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `seo_title` char(100) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` char(130) NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `seo_desc` char(230) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `is_enable` (`is_enable`),
  KEY `is_home` (`is_home`),
  KEY `home_data_location` (`home_data_location`),
  KEY `style_type` (`style_type`),
  KEY `is_goods_detail` (`is_goods_detail`),
  KEY `access_count` (`access_count`),
  KEY `goods_count` (`goods_count`),
  KEY `activity_category_id` (`activity_category_id`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='活动配置 - 应用';

# 活动配置轮播图片 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_activity_slider` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `platform` char(30) NOT NULL DEFAULT 'pc' COMMENT '所属平台（pc PC网站, h5 H5手机网站, ios 苹果APP, android 安卓APP, alipay 支付宝小程序, weixin 微信小程序, baidu 百度小程序, toutiao 头条小程序, qq QQ小程序）',
  `event_type` tinyint(2) NOT NULL DEFAULT '-1' COMMENT '事件类型（0 WEB页面, 1 内部页面(小程序或APP内部地址), 2 外部小程序(同一个主体下的小程序appid), 3 打开地图, 4 拨打电话）',
  `event_value` char(255) NOT NULL DEFAULT '' COMMENT '事件值',
  `images_url` char(255) NOT NULL DEFAULT '' COMMENT '图片地址',
  `name` char(60) NOT NULL DEFAULT '' COMMENT '名称',
  `bg_color` char(30) NOT NULL DEFAULT '' COMMENT 'css背景色值',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用（0否，1是）',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `is_enable` (`is_enable`),
  KEY `sort` (`sort`),
  KEY `platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='活动配置轮播图片 - 应用';

# 活动配置分类 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_activity_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `icon` char(255) NOT NULL DEFAULT '' COMMENT 'icon图标',
  `name` char(60) NOT NULL DEFAULT '' COMMENT '名称',
  `describe` char(255) NOT NULL DEFAULT '' COMMENT '描述',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用（0否，1是）',
  `seo_title` char(100) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` char(130) NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `seo_desc` char(230) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `is_enable` (`is_enable`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='活动配置分类 - 应用';

# 活动配置关联商品 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_activity_goods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `activity_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动id',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `discount_rate` decimal(3,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '折扣系数 0.00~0.99',
  `dec_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '减金额',
  `is_recommend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐（0否，1是）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `activity_id` (`activity_id`),
  KEY `goods_id` (`goods_id`),
  KEY `is_recommend` (`is_recommend`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='活动配置关联商品 - 应用';