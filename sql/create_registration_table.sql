# parameters: $table, $engine

CREATE TABLE IF NOT EXISTS `$table` ( .
`id` INT NOT NULL AUTO_INCREMENT,
`name` VARCHAR(256) NOT NULL,
`dob` DATE NOT NULL,
`userid` VARCHAR(256) NOT NULL,
`token` VARCHAR(256) NOT NULL,
`tablename` VARCHAR(256) NULL,
`title` VARCHAR(128) NULL,
`key` VARCHAR(128) NULL,
`spreadsheetid` VARCHAR(64) NULL,
`worksheetid` VARCHAR(16) NULL,
`last_row` BIGINT NULL,
`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)) ENGINE = $engine;

ALTER TABLE `$table` add unique index(`userid`, `name`);

ALTER TABLE  `$table` ADD UNIQUE (`tablename`);
ALTER TABLE  `$table` ADD UNIQUE (`token`);
ALTER TABLE  `$table` ADD UNIQUE (`key`);
ALTER TABLE  `$table` ADD UNIQUE (`spreadsheetid`);
