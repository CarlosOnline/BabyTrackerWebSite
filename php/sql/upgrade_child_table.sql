#upgrade Initial Table structure to 2.0 version
# parameters: $table, $engine
ALTER TABLE `$table` DROP `attributes`;
ALTER TABLE `$table` DROP `version`;
ALTER TABLE `$table` DROP `sheetrowid`;
ALTER TABLE `$table` DROP `sheetversion`;

ALTER TABLE  `$table` CHANGE  `name`  `name` VARCHAR( 256 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
ALTER TABLE  `$table` CHANGE  `description` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
