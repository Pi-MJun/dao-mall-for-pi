# v1.2.6
# 充值记录
ALTER TABLE `{PREFIX}plugins_wallet_recharge` add `operate_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人id' after `payment_name`;
ALTER TABLE `{PREFIX}plugins_wallet_recharge` add `operate_name` char(30) NOT NULL DEFAULT '' COMMENT '操作人名称' after `operate_id`;
# 日志
ALTER TABLE `{PREFIX}plugins_wallet_log` add `operate_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人id' after `msg`;
ALTER TABLE `{PREFIX}plugins_wallet_log` add `operate_name` char(30) NOT NULL DEFAULT '' COMMENT '操作人名称' after `operate_id`;