# 会员等级介绍
DROP TABLE IF EXISTS `{PREFIX}plugins_membershiplevelvip_introduce`;

# 会员等级
DROP TABLE IF EXISTS `{PREFIX}plugins_membershiplevelvip_level`;

# 会员等级付费用户
DROP TABLE IF EXISTS `{PREFIX}plugins_membershiplevelvip_payment_user`;

# 会员等级付费订单纪录
DROP TABLE IF EXISTS `{PREFIX}plugins_membershiplevelvip_payment_user_order`;

# 会员等级会员佣金明细
DROP TABLE IF EXISTS `{PREFIX}plugins_membershiplevelvip_user_profit`;


# 用户会员等级
ALTER TABLE `{PREFIX}user` DROP `plugins_user_level`;

# 商品会员价格扩展数据
ALTER TABLE `{PREFIX}goods` DROP `plugins_membershiplevelvip_price_extends`;