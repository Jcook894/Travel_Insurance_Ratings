ALTER TABLE `#__jreviews_content` ADD `listing_note` MEDIUMTEXT NOT NULL AFTER `email`;

ALTER TABLE `#__jreviews_comments` 
ADD `posts` INT( 11 ) NOT NULL AFTER `ipaddress`,
ADD `owner_reply_text` TEXT NOT NULL AFTER `posts`,
ADD `owner_reply_created` DATETIME NOT NULL AFTER `owner_reply_text`,
ADD `owner_reply_approved` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `owner_reply_created`,
ADD `owner_reply_note` MEDIUMTEXT NOT NULL AFTER `owner_reply_approved`,
ADD `vote_helpful` INT( 10 ) NOT NULL AFTER `posts`,  
ADD `vote_total` INT( 10 ) NOT NULL AFTER `posts`,
ADD `review_note` MEDIUMTEXT NOT NULL,
DROP `checked_out`,
DROP `checked_out_time`;

--
-- Table structure for table `#__jreviews_discussions`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_discussions` (
  `discussion_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type` varchar(32) NOT NULL,
  `parent_post_id` int(11) NOT NULL DEFAULT '0',
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` TINYTEXT NOT NULL,
  `username` TINYTEXT NOT NULL,
  `email` TINYTEXT NOT NULL,
  `ipaddress` TINYTEXT NOT NULL,  
  `text` text NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `approved` tinyint(4) NOT NULL
) ENGINE=MyISAM;

--
-- Table structure for table `#__jreviews_activities`
--

CREATE TABLE `#__jreviews_activities` (
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
`value` INT( 4 ) NOT NULL
) ENGINE = MYISAM; 


--
-- Table structure for table `#__jreviews_activities`
--

DROP TABLE IF EXISTS `#__jreviews_report`;

CREATE TABLE IF NOT EXISTS `#__jreviews_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `listing_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` TINYTEXT NOT NULL,
  `username` TINYTEXT NOT NULL,
  `email` TINYTEXT NOT NULL,
  `ipaddress` TINYTEXT NOT NULL,  
  `report_text` mediumtext NOT NULL,
  `created` datetime NOT NULL,
  `extension` TINYTEXT NOT NULL,
  `approved` TINYINT( 4 ) NOT NULL DEFAULT '0',
  `report_note` MEDIUMTEXT NOT NULL     
) ENGINE=MyISAM;

--
-- Table structure for table `#__jreviews_predefined_replies`
--

CREATE TABLE `#__jreviews_predefined_replies` (
`reply_id` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`reply_type` TINYTEXT NOT NULL ,
`reply_subject` MEDIUMTEXT NOT NULL ,
`reply_body` TEXT NOT NULL
) ENGINE = MYISAM; 

INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(1, 'listing', 'Your listing has been approved', '{name},\n\nThank you for submitting your listing. It has been approved and you can see it by visiting the link below:\n\n{url}');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(2, 'listing', 'Your listing has been rejected', '{name},\n\nThank you for your recent listing submission. Unfortunately, it has been rejected.');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(3, 'listing', '', '');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(4, 'listing', '', '');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(5, 'listing', '', '');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(6, 'review', 'Your review has been approved', '{name},\n\nThank you for submitting your review. It has been approved and you can see it by visiting the link below:\n\n{url}');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(7, 'review', 'Your review has been rejected', '{name},\n\nThank you for your recent review submission. Unfortunately, it has been rejected.');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(8, 'review', '', '');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(9, 'review', '', '');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(10, 'review', '', '');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(11, 'owner_reply', 'Your owner reply has been approved', '{name},\n\nThank you for submitting your owner reply. It has been approved and you can see it by visiting the link below:\n\n{url}');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(12, 'owner_reply', 'Your owner reply has been rejected', '{name},\n\nThank you for your recent review reply for one of your listings. Unfortunately, it has been rejected.');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(13, 'owner_reply', '', '');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(14, 'owner_reply', '', '');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(15, 'owner_reply', '', '');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(16, 'discussion_post', 'Your review comment has been approved', '{name},\n\nThank you for submitting your comment. It has been approved and you can see it by visiting the link below:\n\n{url}');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(17, 'discussion_post', 'Your review comment has been rejected', '{name},\n\nThank you for your recent review comment. Unfortunately, it has been rejected.');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(18, 'discussion_post', '', '');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(19, 'discussion_post', '', '');
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(20, 'discussion_post', '', '');

--
-- Table structure for table `#__jreviews_predefined_replies`
--

CREATE TABLE `#__jreviews_claims` (
`claim_id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`user_id` INT( 11 ) NOT NULL ,
`listing_id` INT( 11 ) NOT NULL ,
`claim_text` MEDIUMTEXT NOT NULL ,
`created` DATETIME NOT NULL ,
`claim_note` MEDIUMTEXT NOT NULL ,
`approved` TINYINT( 4 ) NOT NULL DEFAULT '0' ,
INDEX ( `listing_id` )
) ENGINE = MYISAM 