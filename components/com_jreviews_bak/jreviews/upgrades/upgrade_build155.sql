ALTER TABLE  `#__jreviews_activities` CHANGE `activity_type` `activity_type` MEDIUMTEXT NOT NULL;
ALTER TABLE  `#__jreviews_activities` ADD INDEX  `listing_activity` (  `listing_id` ,  `activity_type` ( 20 ) ,  `activity_new` );
ALTER TABLE  `#__jreviews_activities` ADD INDEX  `review_activity` (  `review_id` ,  `activity_type` ( 20 ) ,  `activity_new` );
ALTER TABLE  `#__jreviews_activities` ADD INDEX  `post_activity` (  `post_id` ,  `activity_type` ( 20 ) ,  `activity_new` );

