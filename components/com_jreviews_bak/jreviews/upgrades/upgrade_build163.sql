ALTER TABLE  `#__jreviews_criteria` ADD  `config` MEDIUMTEXT NOT NULL AFTER  `state`;
ALTER TABLE  `#__jreviews_fields` ADD  `compareview` TINYINT( 1 ) NOT NULL default '0' AFTER  `listview`;