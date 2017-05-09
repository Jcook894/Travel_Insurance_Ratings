--
-- Table structure for table `#__jreviews_activities`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_activities` (
    `activity_id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `activity_type` mediumtext NOT NULL,
    `user_id` INT( 11 ) NOT NULL,
    `email` TINYTEXT NOT NULL,
    `created` DATETIME NOT NULL,
    `ipaddress` TINYTEXT NOT NULL,
    `activity_new` INT( 4 ) NOT NULL,
    `listing_id` INT( 11 ) NOT NULL,
    `review_id` INT( 11 ) NOT NULL,
    `helpful_vote_id` INT( 11 ) NOT NULL,
    `post_id` INT( 11 ) NOT NULL,
    `extension` TINYTEXT NOT NULL,
    `value` INT( 4 ) NOT NULL,
    `permalink` MEDIUMTEXT NOT NULL,
    INDEX `listing_activity` (  `listing_id` ,  `activity_type` ( 12 ) ,  `activity_new` ),
    INDEX  `review_activity` (  `review_id` ,  `activity_type` ( 12 ) ,  `activity_new` ),
    INDEX  `post_activity` (  `post_id` ,  `activity_type` ( 12 ) ,  `activity_new` )
) ENGINE = MYISAM;