DROP TABLE IF EXISTS `el_och_discount`;
CREATE TABLE `el_och_discount` (
	`card_num` INT( 8 ) UNSIGNED NOT NULL ,
	`discount` TINYINT( 100 ) UNSIGNED NOT NULL ,
	`spent_amount` INT UNSIGNED NOT NULL ,
	`update_time` INT NOT NULL ,
	`query_ip` VARCHAR( 15 ) NOT NULL ,
	`query_time` INT NOT NULL ,
	`query_count` INT NOT NULL ,
	PRIMARY KEY (  `card_num` )
) ENGINE = MYISAM;

