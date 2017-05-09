ALTER TABLE  `#__jreviews_fields` ADD  `control_field` VARCHAR( 50 ) NOT NULL , 
ADD  `control_value` VARCHAR( 255 ) NOT NULL ,
ADD INDEX ( control_field, control_value );

ALTER TABLE  `#__jreviews_groups` ADD  `control_field` VARCHAR( 50 ) NOT NULL ,
ADD  `control_value` VARCHAR( 255 ) NOT NULL ,
ADD INDEX ( control_field, control_value );

ALTER TABLE  `#__jreviews_fieldoptions` ADD  `control_field` VARCHAR( 50 ) NOT NULL ,
ADD  `control_value` VARCHAR( 255 ) NOT NULL ,
ADD INDEX ( control_field, control_value );

CREATE TABLE IF NOT EXISTS `#__jreviews_reviewer_ranks` (
  `user_id` int(11) NOT NULL,
  `reviews` int(11) NOT NULL,
  `votes_percent_helpful` decimal(5,4) NOT NULL,
  `votes_total` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  PRIMARY KEY  (`user_id`)
) ENGINE=MyISAM;