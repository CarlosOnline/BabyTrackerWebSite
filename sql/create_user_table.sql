# parameters: $table, $engine

CREATE TABLE IF NOT EXISTS `$table` (
`id` INT NOT NULL AUTO_INCREMENT,
`datetime` DATETIME NOT NULL,
`type` ENUM('Breast','Bottle','Pump','Wet Diaper','Poopy Diaper','Deleted') NOT NULL,
`attributes` int NULL,
`amount` DECIMAL(6,2) NULL,
`description` VARCHAR(256) NULL,
`version` int NOT NULL DEFAULT 0,
`amount_oz` DECIMAL(6,2) NULL,
`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`sheetrowid` BIGINT NULL,
`sheetversion` VARCHAR(256) NULL,
PRIMARY KEY (`id`)) ENGINE = $engine;
