/* 2016-12-27  */
ALTER TABLE `jd_ecshop`.`ecs_users` 
ADD COLUMN `id_card_no` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '身份证号' AFTER `headimg`,
ADD COLUMN `bank_card_no` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '银行卡号' AFTER `id_card_no`,
ADD COLUMN `img_bank_card` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '银行卡正面照' AFTER `bank_card_no`,
ADD COLUMN `img_id_card_1` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '身份证正面照' AFTER `img_bank_card`,
ADD COLUMN `img_id_card_2` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '身份证反面照' AFTER `img_id_card_1`;
/* 2017-01-04*/
ALTER TABLE `jd_ecshop`.`ecs_users` 
DROP COLUMN `img_id_card_2`,
DROP COLUMN `img_id_card_1`,
DROP COLUMN `id_card_no`;
ALTER TABLE `jd_ecshop`.`ecs_users` 
CHANGE COLUMN `user_money` `user_money` INT NOT NULL DEFAULT 0 COMMENT '可用资金字段作为提现额度使用' ;
ALTER TABLE `jd_ecshop`.`ecs_users` 
CHANGE COLUMN `pay_points` `pay_points` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '消费积分作为消费额度字段使用' ;
ALTER TABLE `jd_ecshop`.`ecs_users` 
CHANGE COLUMN `user_money` `user_money` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '可用资金字段作为提现额度使用' ,
CHANGE COLUMN `pay_points` `pay_points` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '消费积分作为消费额度字段使用';
/*2017-01-05*/
CREATE TABLE `jd_ecshop`.`ecs_deposit_record` (
  `record_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL DEFAULT 0,
  `card_no` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '卡号',
  `pos_no` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '终端编号',
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '充值金额',
  `create_time` INT NOT NULL DEFAULT 0 COMMENT '充值时间',
  PRIMARY KEY (`record_id`));
ALTER TABLE `jd_ecshop`.`ecs_deposit_record`
ENGINE = MyISAM ;
CREATE TABLE `jd_ecshop`.`ecs_cash_record` (
  `record_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL DEFAULT 0,
  `card_no` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '卡号',
  `pos_no` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '终端编号',
  `cash` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '提现金额',
  `create_time` INT NOT NULL DEFAULT 0 COMMENT '提现时间',
  PRIMARY KEY (`record_id`));
ALTER TABLE `jd_ecshop`.`ecs_cash_record`
ENGINE = MyISAM ;
/*2017-01-06*/
ALTER TABLE `jd_ecshop`.`ecs_users`
CHANGE COLUMN `credit_line` `credit_line` DECIMAL(10,2) UNSIGNED NOT NULL COMMENT '信用额度作为提现额度使用' ;

ALTER TABLE `jd_ecshop`.`ecs_cash_record`
  CHANGE COLUMN `cash` `user_money` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额' ,
  ADD COLUMN `credit_line` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `user_money`;

ALTER TABLE `jd_ecshop`.`ecs_cash_record`
  ADD COLUMN `status` TINYINT NULL DEFAULT 0 COMMENT '0：交易中，-1：交易回滚，1：交易成功' AFTER `create_time`;
/*02017-01-07*/
insert into ecs_admin_action (parent_id, action_code , relevance) values (5, 'pos_manage', '');
insert into ecs_admin_action (parent_id, action_code , relevance) values (5, 'pos_drop', 'pos_manage');

CREATE TABLE `jd_ecshop`.`ecs_pos` (
  `pos_id` INT NOT NULL AUTO_INCREMENT,
  `pos_no` VARCHAR(45) NOT NULL DEFAULT '',
  `contact` VARCHAR(20) NOT NULL DEFAULT '',
  `mobile` VARCHAR(20) NOT NULL DEFAULT '',
  `total` INT NOT NULL DEFAULT 0 COMMENT '刷卡次数',
  `create_time` INT NOT NULL DEFAULT 0 COMMENT '创建日期',
  PRIMARY KEY (`pos_id`))
ENGINE = MyISAM;
ALTER TABLE `jd_ecshop`.`ecs_pos`
CHANGE COLUMN `total` `address` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '刷卡次数' ;
ALTER TABLE `jd_ecshop`.`ecs_pos`
ADD COLUMN `categary` VARCHAR(50) NOT NULL DEFAULT '' AFTER `address`;
/*2017-01-08*/
ALTER TABLE `jd_ecshop`.`ecs_order_info`
CHANGE COLUMN `surplus` `surplus` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额支付金额' ,
CHANGE COLUMN `integral_money` `integral_money` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '积分支付金额作为消费额度字段使用' ;
/*2017-01-11*/
ALTER TABLE `jd_ecshop`.`ecs_deposit_record`
  ADD COLUMN `client_key` VARCHAR(45) NOT NULL DEFAULT '' AFTER `amount`;
/*2017-01-16*/
ALTER TABLE `jd_ecshop`.`ecs_order_info`
  CHANGE COLUMN `pay_name` `pay_name` VARCHAR(120) NOT NULL DEFAULT '' COMMENT '如果是终端消费，则用来存储pos机编号' ;
/*2017-01-20*/
ALTER TABLE `jd_ecshop`.`ecs_pos`
  CHANGE COLUMN `create_time` `bank_no` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '创建日期' AFTER `pos_no`;
ALTER TABLE `jd_ecshop`.`ecs_pos`
  ADD COLUMN `create_time` INT NOT NULL DEFAULT 0 AFTER `category`;
insert into ecs_shop_config (parent_id,code,type,store_range,store_dir,value, sort_order) values (8, 'sms_audit_success','textarea', '', '', '恭喜您通过商联一卡通的审核成为普通会员，您可以使用%s，默认密码为：666666登录平台www.shanglianykt.com，进入会员中心的安全设置设置支付密码，支付密码设置成功后才可以使用您的卡进行消费和提现。', 25);
insert into ecs_shop_config (parent_id,code,type,store_range,store_dir,value, sort_order) values (8, 'sms_audit_failed','textarea', '', '', '审核失败，请联系商联一卡通管理员。', 26);
/*2017-01-21*/
update ecs_shop_config set value='尊敬的%s用户:您于%s消费了%s元；您的卡消费额度为：%s元；提现额度为:%s元。' where id=1038;
insert into ecs_shop_config (parent_id,code,type,store_range,store_dir,value, sort_order) values (8, 'sms_repay_success','textarea', '', '', '您已成功还款%s元。', 27);
insert into ecs_shop_config (parent_id,code,type,store_range,store_dir,value, sort_order) values (8, 'sms_invite_success','textarea', '', '', '尊敬的%s用户:您推荐的%s已经成功审核通过。', 28);
/*2017-01-22*/
insert into ecs_shop_config (parent_id,code,type,store_range,store_dir,value, sort_order) values (8, 'sms_credit_improved','textarea', '', '', '您的额度已提升。', 29);