-- 
-- Table changes build16 
-- 

ALTER TABLE `#__jreviews_ratings` DROP INDEX `average`;

ALTER TABLE `#__jreviews_ratings` ADD INDEX `review_id` ( `reviewid` );

ALTER TABLE `#__jreviews_comments` DROP INDEX `entry`;

ALTER TABLE `#__jreviews_comments` DROP INDEX `myreviews`;

ALTER TABLE `#__jreviews_comments` ADD INDEX `listing_id` ( `pid` );
 
ALTER TABLE `#__jreviews_comments` ADD INDEX `published` ( `published` );