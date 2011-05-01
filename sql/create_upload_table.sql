# parameters: $table, $engine

CREATE TABLE IF NOT EXISTS `$table` ( .
`id` INT NOT NULL AUTO_INCREMENT,
`date` DATE NOT NULL,
`time` TIME NOT NULL,
`type` ENUM('Breast','Bottle','Pump','Wet Diaper','Poopy Diaper','Deleted') NOT NULL,
`amount` DECIMAL(6,2) NULL,
`description` VARCHAR(256) NULL,
`action` ENUM(  'insert',  'update',  'delete' ) NOT NULL DEFAULT  'insert',
`action_state` VARCHAR(256) NULL,
`sqlrowid` BIGINT NOT NULL,
`sheetrowid` BIGINT NULL,
`title` VARCHAR(128) NULL,
`key` VARCHAR(128) NOT NULL,
`spreadsheetid` VARCHAR(64) NULL,
`worksheetid` VARCHAR(16) NULL,
`state` ENUM('new','processing','failed','completed') NOT NULL DEFAULT 'new',
`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
`changedby` VARCHAR(128) NULL,
`failure_message` VARCHAR(2048) NULL,
`errno` INT NULL,
`attempt` INT NOT NULL DEFAULT 0,
PRIMARY KEY (`id`)) ENGINE = $engine;
