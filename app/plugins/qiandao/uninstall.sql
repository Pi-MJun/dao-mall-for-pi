# 签到表 - 应用
DROP TABLE IF EXISTS `{PREFIX}plugins_yx_qiandao`;
DROP TABLE IF EXISTS `{PREFIX}plugins_yx_qiandao_send`;

# 用户特殊奖励次数
ALTER TABLE `{PREFIX}user` DROP `cannum`;