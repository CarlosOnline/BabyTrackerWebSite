# parameters: $table, $engine

CREATE TABLE IF NOT EXISTS `$table` (
`id` INT NOT NULL AUTO_INCREMENT,
`datetime` DATETIME NOT NULL,
`type` ENUM('Breast','Bottle','Pump','Wet Diaper','Poopy Diaper','Deleted') NOT NULL,
`amount` DECIMAL(6,2) NULL,
`description` VARCHAR(256) NULL,
`amount_oz` DECIMAL(6,2) NULL,
`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)) ENGINE = $engine;
