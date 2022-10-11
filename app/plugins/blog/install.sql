# 博客轮播图片 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_blog_slide` (
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
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='博客轮播图片 - 应用';

# 博客博文 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_blog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `title` char(60) NOT NULL DEFAULT '' COMMENT '标题',
  `blog_category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文章分类',
  `title_color` char(7) NOT NULL DEFAULT '' COMMENT '标题颜色',
  `jump_url` char(255) NOT NULL DEFAULT '' COMMENT '跳转url地址',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用（0否，1是）',
  `describe` char(255) NOT NULL DEFAULT '' COMMENT '描述',
  `content` longtext COMMENT '内容',
  `cover` char(255) NOT NULL DEFAULT '' COMMENT '封面图片',
  `goods_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '推荐商品数量',
  `images` text COMMENT '图片数据（一维数组json）',
  `images_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图片数量',
  `access_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '访问次数',
  `is_recommended` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐（0否, 1是）',
  `is_live_play` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '是否直播（0否, 1是）',
  `video_url` char(255) NOT NULL DEFAULT '' COMMENT '视频地址',
  `seo_title` char(100) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` char(130) NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `seo_desc` char(230) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `is_enable` (`is_enable`),
  KEY `access_count` (`access_count`),
  KEY `images_count` (`images_count`),
  KEY `blog_category_id` (`blog_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='博客博文 - 应用';

# 博客分类 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_blog_category` (
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
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='博客分类 - 应用';

# 博客推荐关联商品 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_blog_goods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `blog_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '博文id',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `blog_id` (`blog_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='博客推荐关联商品 - 应用';

# 博客推荐 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_blog_recommend` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `title` char(30) NOT NULL DEFAULT '' COMMENT '标题',
  `vice_title` char(60) NOT NULL DEFAULT '' COMMENT '副标题',
  `color` char(30) NOT NULL DEFAULT '' COMMENT 'css颜色值',
  `describe` char(255) NOT NULL DEFAULT '' COMMENT '描述',
  `cover` char(255) NOT NULL DEFAULT '' COMMENT '封面图片',
  `more_category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更多分类指向地址',
  `keywords` text COMMENT '推荐关键字（英文逗号分割）',
  `blog_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联博客数量',
  `access_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '访问次数',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用（0否，1是）',
  `is_goods_detail` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否商品详情页展示（0否，1是）',
  `is_home` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否首页展示（0否，1是）',
  `home_data_location` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '首页数据位置（0楼层数据上面，1楼层数据下面）',
  `style_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '样式类型（0图文，1九方格，2一行滚动）',
  `data_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '数据类型（0自动模式，1手动模式）',
  `order_by_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '自动数据-排序类型（0最新，1热度，2更新）',
  `order_by_rule` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '自动数据-排序规则（0降序(desc)，1升序(asc)）',
  `data_auto_category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '自动数据-指定分类条件',
  `data_auto_number` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '自动数据-展示数量',
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
  KEY `is_goods_detail` (`is_goods_detail`),
  KEY `blog_count` (`blog_count`),
  KEY `access_count` (`access_count`),
  KEY `style_type` (`style_type`),
  KEY `data_type` (`data_type`),
  KEY `order_by_type` (`order_by_type`),
  KEY `order_by_rule` (`order_by_rule`),
  KEY `upd_time` (`upd_time`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='博客推荐 - 应用';

# 博客推荐关联 - 应用
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_blog_recommend_join` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `recommend_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '推荐id',
  `blog_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '博客id',
  `is_recommend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐（0否，1是）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `recommend_id` (`recommend_id`),
  KEY `blog_id` (`blog_id`),
  KEY `is_recommend` (`is_recommend`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='博客推荐关联 - 应用';