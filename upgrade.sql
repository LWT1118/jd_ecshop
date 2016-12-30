/* 2016-12-27  */
ALTER TABLE `jd_ecshop`.`ecs_users` 
ADD COLUMN `id_card_no` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '身份证号' AFTER `headimg`,
ADD COLUMN `bank_card_no` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '银行卡号' AFTER `id_card_no`,
ADD COLUMN `img_bank_card` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '银行卡正面照' AFTER `bank_card_no`,
ADD COLUMN `img_id_card_1` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '身份证正面照' AFTER `img_bank_card`,
ADD COLUMN `img_id_card_2` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '身份证反面照' AFTER `img_id_card_1`;
