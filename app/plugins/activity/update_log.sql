# v2.2.5
# 活动配置
ALTER TABLE `{PREFIX}plugins_activity` add `style_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '样式类型（0图文，1九方格，2一行滚动）' after `home_data_location`;
ALTER TABLE `{PREFIX}plugins_activity` add `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序' after `time_end`;

# 索引
ALTER TABLE `{PREFIX}plugins_activity` ADD INDEX home_data_location(`home_data_location`);
ALTER TABLE `{PREFIX}plugins_activity` ADD INDEX style_type(`style_type`);
ALTER TABLE `{PREFIX}plugins_activity` ADD INDEX sort(`sort`);







# 表重命名
RENAME TABLE `{PREFIX}plugins_activity_slide` TO `{PREFIX}plugins_activity_slider`;

# 商品是否推荐
ALTER TABLE `{PREFIX}plugins_activity_goods` add `discount_rate` decimal(3,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '折扣系数 0.00~0.99' after `goods_id`;
ALTER TABLE `{PREFIX}plugins_activity_goods` add `dec_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '减金额' after `discount_rate`;
ALTER TABLE `{PREFIX}plugins_activity_goods` add `is_recommend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐（0否，1是）' after `dec_price`;
ALTER TABLE `{PREFIX}plugins_activity_goods` ADD INDEX is_recommend(`is_recommend`);

# 活动时间
ALTER TABLE `{PREFIX}plugins_activity` add `time_start` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间' after `is_goods_detail`;
ALTER TABLE `{PREFIX}plugins_activity` add `time_end` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间' after `time_start`;