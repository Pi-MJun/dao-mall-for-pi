# 签到表
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_yx_qiandao` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '签到时间',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '奖励类型：0积分，1抽奖机会',
  `num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所得具体值',
  `xu` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '周期内第几天，如果断了当天就是1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='插件积分签到表';

# 用户特殊奖励次数
ALTER TABLE `{PREFIX}user` add `cannum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '抽奖机会' after `upd_time`;

CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_yx_qiandao_send` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `towho` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '积分接收者',
  `fromwho` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '积分送出者',
  `num` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '积分量',
  `add_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '赠送时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} COMMENT='积分互赠记录表' ROW_FORMAT=DYNAMIC;