#v1.1.4
# 博客新增视频地址信息
ALTER TABLE `{PREFIX}plugins_blog` add `is_live_play` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '是否直播（0否, 1是）' after `is_recommended`;
ALTER TABLE `{PREFIX}plugins_blog` add `video_url` char(255) NOT NULL DEFAULT '' COMMENT '视频地址' after `is_live_play`;

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
  `blog_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联商品数量',
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