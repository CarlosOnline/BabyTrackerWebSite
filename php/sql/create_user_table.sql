# parameters: $table, $engine

CREATE TABLE IF NOT EXISTS `$table` (
`id` INT NOT NULL AUTO_INCREMENT,
`datetime` DATETIME NOT NULL,
`type` ENUM('Breast','Bottle','Pump','Wet Diaper','Poopy Diaper','Food','Nap') NOT NULL,
`amount` DECIMAL(6,2) NULL,
`description` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
`amount_oz` DECIMAL(6,2) NULL,
`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)) ENGINE = $engine;

ALTER TABLE  `$table` CHANGE  `type`  `type`
ENUM('Breast',  'Bottle',  'Pump',  'Wet Diaper',  'Poopy Diaper',  'Food',  'Nap' )
CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
