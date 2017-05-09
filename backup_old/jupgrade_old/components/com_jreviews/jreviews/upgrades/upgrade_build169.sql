ALTER TABLE  `#__jreviews_listing_totals` CHANGE  `user_rating`  `user_rating` DECIMAL( 9, 4 ) NOT NULL;
ALTER TABLE  `#__jreviews_listing_totals` CHANGE  `editor_rating`  `editor_rating` DECIMAL( 9, 4 ) NOT NULL;
ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX  `user_rating` (  `user_rating` ,  `user_rating_count` );
ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX  `editor_rating` (  `editor_rating` ,  `editor_rating_count` );
ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX `user_comment_count` (  `user_comment_count` );
ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX `editor_comment_count` (  `editor_comment_count` );
ALTER TABLE  `#__jreviews_directories` ADD INDEX `title` ( `title` ( 35 ) );