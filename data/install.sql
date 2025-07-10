CREATE TABLE IF NOT EXISTS `__PREFIX__wok_sse_app` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '应用App_id',
  `enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '启用',
  `create_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '添加时间',
  `update_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '更新时间',
  `name` varchar(55) NOT NULL DEFAULT '' COMMENT '名称',
  `secret` varchar(55) NOT NULL DEFAULT '' COMMENT '应用Secret_key',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10001 DEFAULT CHARSET=utf8 COMMENT='推送应用';

CREATE TABLE IF NOT EXISTS `__PREFIX__wok_sse_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `app_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '应用App_id',
  `nickname` varchar(55) NOT NULL DEFAULT '' COMMENT '昵称',
  `remark` varchar(55) NOT NULL DEFAULT '' COMMENT '备注',
  `token` varchar(100) NOT NULL DEFAULT '' COMMENT 'token',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户外部id',
  `login_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '登录时间',
  `create_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '添加时间',
  `update_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_app_id` (`app_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10001 DEFAULT CHARSET=utf8 COMMENT='推送用户';