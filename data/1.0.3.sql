ALTER TABLE `__PREFIX__wok_sse_user`
	ADD `group` varchar(50) NOT NULL DEFAULT 'default' COMMENT '分组' COLLATE 'utf8_general_ci' AFTER `uid`;