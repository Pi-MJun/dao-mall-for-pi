# 分销等级 - 应用
DROP TABLE IF EXISTS `{PREFIX}plugins_distribution_level`;

# 佣金明细 - 应用
DROP TABLE IF EXISTS `{PREFIX}plugins_distribution_profit_log`;

# 分销商取货点 - 应用
DROP TABLE IF EXISTS `{PREFIX}plugins_distribution_user_self_extraction`;

# 分销商取货点关联订单 - 应用
DROP TABLE IF EXISTS `{PREFIX}plugins_distribution_user_self_extraction_order`;

# 积分发放明细 - 应用
DROP TABLE IF EXISTS `{PREFIX}plugins_distribution_integral_log`;

# 自定义取货地址 - 应用
DROP TABLE IF EXISTS `{PREFIX}plugins_distribution_custom_extraction_address`;

# 分销阶梯返佣记录 - 应用
DROP TABLE IF EXISTS `{PREFIX}plugins_distribution_appoint_ladder_log`;

# 分销阶梯返佣记录商品 - 应用
DROP TABLE IF EXISTS `{PREFIX}plugins_distribution_appoint_ladder_log_goods`;

# 分销等级多商户 - 应用
DROP TABLE IF EXISTS `{PREFIX}plugins_distribution_level_shop`;


# 用户分销等级
ALTER TABLE `{PREFIX}user` DROP `plugins_distribution_level`;