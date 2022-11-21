# 店铺
DROP TABLE IF EXISTS `{PREFIX}plugins_shop`;

# 店铺分类
DROP TABLE IF EXISTS `{PREFIX}plugins_shop_category`;

# 店铺收藏
DROP TABLE IF EXISTS `{PREFIX}plugins_shop_favor`;

# 商品分类
DROP TABLE IF EXISTS `{PREFIX}plugins_shop_goods_category`;

# 收益明细
DROP TABLE IF EXISTS `{PREFIX}plugins_shop_order_profit`;

# 页面设计
DROP TABLE IF EXISTS `{PREFIX}plugins_shop_design`;

# 导航管理
DROP TABLE IF EXISTS `{PREFIX}plugins_shop_navigation`;

# 店铺轮播图片
DROP TABLE IF EXISTS `{PREFIX}plugins_shop_slider`;

# 首页推荐
DROP TABLE IF EXISTS `{PREFIX}plugins_shop_recommend`;

# 平台商品添加关联日志
DROP TABLE IF EXISTS `{PREFIX}plugins_shop_goods_copy_log`;

# 店铺订单确认
DROP TABLE IF EXISTS `{PREFIX}plugins_shop_order_confirm`;

# 商品
ALTER TABLE `{PREFIX}goods` DROP `shop_id`;
ALTER TABLE `{PREFIX}goods` DROP `shop_user_id`;
ALTER TABLE `{PREFIX}goods` DROP `shop_category_id`;
ALTER TABLE `{PREFIX}goods` DROP `shop_settle_price`;
ALTER TABLE `{PREFIX}goods` DROP `shop_settle_rate`;

# 订单
ALTER TABLE `{PREFIX}order` DROP `shop_id`;
ALTER TABLE `{PREFIX}order` DROP `shop_user_id`;
ALTER TABLE `{PREFIX}order` DROP `shop_is_delete_time`;

# 仓库
ALTER TABLE `{PREFIX}warehouse` DROP `shop_id`;
ALTER TABLE `{PREFIX}warehouse` DROP `shop_user_id`;