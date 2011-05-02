# parameters: $reg_table, $children_table, $sessions_table, $engine

CREATE TABLE IF NOT EXISTS `$reg_table` ( .
`id` INT NOT NULL AUTO_INCREMENT,
`name` VARCHAR(256) NOT NULL,
`userid` VARCHAR(256) NOT NULL,
`password` VARCHAR(256) NOT NULL,
`token` VARCHAR(256) NOT NULL,
`version` DECIMAL(5,2) NOT NULL DEFAULT 2.0,
`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)) ENGINE = $engine;
ALTER TABLE `$reg_table` add unique index(`userid`);

CREATE TABLE IF NOT EXISTS `$children_table` ( .
`id` INT NOT NULL AUTO_INCREMENT,
`user_token` VARCHAR(256) NOT NULL,
`name` VARCHAR(256) NOT NULL,
`dob` DATE NOT NULL,
`token` VARCHAR(256) NOT NULL,
`tablename` VARCHAR(256) NULL,
`title` VARCHAR(128) NULL,
`version` DECIMAL(5,2) NOT NULL DEFAULT 2.0,
`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)) ENGINE = $engine;

CREATE TABLE IF NOT EXISTS `$sessions_table` ( .
`id` INT NOT NULL AUTO_INCREMENT,
`user_token` VARCHAR(256) NOT NULL,
`child_token` VARCHAR(256) NOT NULL,
`token` VARCHAR(256) NOT NULL,
`registered_address` VARCHAR(256) NOT NULL,
`version` DECIMAL(5,2) NOT NULL DEFAULT 2.0,
`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)) ENGINE = $engine;
