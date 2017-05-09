-- 
-- Table structure for table `#__jreviews_captcha`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_captcha` (
  `captcha_id` bigint(13) unsigned NOT NULL auto_increment,
  `captcha_time` int(10) unsigned NOT NULL,
  `ip_address` varchar(16) NOT NULL default '0',
  `word` varchar(20) NOT NULL,
  PRIMARY KEY  (`captcha_id`),
  KEY `word` (`word`),
  KEY `ip_address` (`ip_address`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_categories`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_categories` (
  `id` int(11) NOT NULL default '0',
  `criteriaid` int(11) NOT NULL,
  `dirid` int(11) NOT NULL,
  `groupid` varchar(50) NOT NULL,
  `option` varchar(50) NOT NULL default 'com_content',
  `tmpl` varchar(100) NOT NULL,
  `tmpl_suffix` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`,`option`),
  KEY `criteriaid` (`criteriaid`),
  KEY `groupid` (`groupid`),
  KEY `dirid` (`dirid`),
  KEY `option` (`option`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_comments`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_comments` (
  `id` int(11) NOT NULL auto_increment,
  `pid` int(11) NOT NULL default '0',
  `mode` varchar(50) NOT NULL default 'com_content',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `userid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL,
  `username` varchar(25) NOT NULL,
  `email` varchar(100) NOT NULL,
  `location` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `comments` text NOT NULL,
  `author` tinyint(1) NOT NULL default '0',
  `published` tinyint(1) NOT NULL default '0',
  `ipaddress` varchar(50) NOT NULL,
  `posts` INT( 11 ) NOT NULL,
  `owner_reply_text` TEXT NOT NULL,
  `owner_reply_created` DATETIME NOT NULL,
  `owner_reply_approved` TINYINT( 4 ) NOT NULL DEFAULT '0',
  `owner_reply_note` MEDIUMTEXT NOT NULL,
  `vote_helpful` INT( 10 ) NOT NULL,  
  `vote_total` INT( 10 ) NOT NULL,
  `review_note` MEDIUMTEXT NOT NULL,  
  PRIMARY KEY  (`id`),
  KEY `listing_id` (`pid`),
  KEY `extension` (`mode`),
  KEY `created` (`pid`,`created`),
  KEY `modified` (`pid`,`modified`,`created`),
  KEY `userid` (`userid`, `published`),
  KEY `published` (`published`),
  KEY `votes_helpful` (`pid`,`vote_helpful`),
  KEY `votes` (  `userid` ,  `published` ,  `vote_helpful` ,  `vote_total` ),
  KEY `posts` (`pid`,`posts`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_config`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_config` (
  `id` varchar(30) NOT NULL,
  `value` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_content`
-- 
                                        
CREATE TABLE IF NOT EXISTS `#__jreviews_content` (
  `contentid` int(11) NOT NULL default '0',
  `featured` tinyint(1) NOT NULL default '0',
  `email` varchar(100) NOT NULL,
  `listing_note` MEDIUMTEXT NOT NULL, 
  PRIMARY KEY  (`contentid`),
  KEY `featured` (`featured`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_criteria`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_criteria` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(30) NOT NULL,
  `criteria` text NOT NULL,
  `required` mediumtext,  
  `weights` mediumtext,
  `tooltips` text NOT NULL,
  `qty` int(11) NOT NULL default '0',
  `groupid` text NOT NULL,
  `state` tinyint(1) NOT NULL default '1',
  `config` MEDIUMTEXT NOT NULL,  
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_directories`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_directories` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(50) NOT NULL,
  `desc` text NOT NULL,
  `tmpl_suffix` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  INDEX `title` (`title` ( 35 ) )  
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_favorites`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_favorites` (
  `favorite_id` int(11) NOT NULL auto_increment,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY  (`favorite_id`),
  UNIQUE KEY `user_favorite` (`content_id`,`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_fieldoptions`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_fieldoptions` (
  `optionid` int(11) NOT NULL auto_increment,
  `fieldid` int(11) NOT NULL default '0',
  `text` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `ordering` int(11) NOT NULL,
  `control_field` VARCHAR( 50 ) NOT NULL,
  `control_value` VARCHAR( 255 ) NOT NULL,  
  PRIMARY KEY  (`optionid`),
  KEY `fieldid` (`fieldid`),
  KEY `field_value` (`value`),
  KEY `control_field` (`control_field`),
  KEY `control_value` (`control_value`)  
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_fields`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_fields` (
  `fieldid` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `showtitle` tinyint(1) NOT NULL default '1',
  `description` mediumtext NOT NULL,
  `required` tinyint(1) default '0',
  `groupid` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `location` enum('content','review') NOT NULL default 'content',
  `options` mediumtext,
  `size` int(11) NOT NULL,
  `maxlength` int(11) NOT NULL,
  `cols` int(11) NOT NULL,
  `rows` int(11) NOT NULL,
  `ordering` int(11) NOT NULL,
  `contentview` tinyint(1) NOT NULL default '0',
  `listview` tinyint(1) NOT NULL default '0',
  `compareview` TINYINT( 1 ) NOT NULL default '0',
  `listsort` tinyint(1) NOT NULL default '0',
  `search` tinyint(1) NOT NULL default '1',
  `access` varchar(50) NOT NULL default '0,18,19,20,21,23,24,25',
  `access_view` varchar(50) NOT NULL default '0,18,19,20,21,23,24,25',
  `published` tinyint(1) NOT NULL default '1',
  `metatitle` varchar(255) NOT NULL,
  `metakey` text NOT NULL,
  `metadesc` text NOT NULL,
  `control_field` VARCHAR( 50 ) NOT NULL,
  `control_value` VARCHAR( 255 ) NOT NULL,  
  PRIMARY KEY  (`fieldid`),
  UNIQUE KEY `name` (`name`),
  KEY `groupid` (`groupid`),
  KEY `listsort` (`listsort`),
  KEY `search` (`search`),
  KEY `entry_published` (`published`,`contentview`,`location`,`name`),
  KEY `list_published` (`published`,`listview`,`location`,`name`),
  KEY `control_field` (`control_field`),
  KEY `control_value` (`control_value`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_groups`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_groups` (
  `groupid` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `showtitle` tinyint(1) NOT NULL default '1',
  `type` varchar(50) NOT NULL default 'content',
  `ordering` int(11) NOT NULL,
  `control_field` VARCHAR( 50 ) NOT NULL,
  `control_value` VARCHAR( 255 ) NOT NULL,  
  PRIMARY KEY  (`groupid`),
  KEY `type` (`type`),
  KEY `control_field` (`control_field`),
  KEY `control_value` (`control_value`)  
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_license`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_license` (
  `id` varchar(30) NOT NULL,
  `value` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_ratings`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_ratings` (
  `rating_id` int(11) NOT NULL auto_increment,
  `reviewid` int(11) NOT NULL default '0',
  `ratings` text NOT NULL,
  `ratings_sum` decimal(11,4) unsigned NOT NULL default '0.0000',
  `ratings_qty` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rating_id`,`reviewid`),
  KEY `review_id` (`reviewid`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_report`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `report_note` MEDIUMTEXT NOT NULL,
  PRIMARY KEY  (`report_id`),
  KEY `listing_id` (`listing_id`),
  KEY `review_id` (`review_id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  KEY `approved` (`approved`),
  KEY `extension` (`extension` ( 12 ) )
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_review_fields`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_review_fields` (
  `reviewid` int(11) NOT NULL,
  PRIMARY KEY  (`reviewid`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_sections`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_sections` (
  `sectionid` int(11) NOT NULL,
  `tmpl` varchar(100) NOT NULL,
  `tmpl_suffix` varchar(20) NOT NULL,
  PRIMARY KEY  (`sectionid`),
  KEY `tmpl` (`tmpl`),
  KEY `tmpl_suffix` (`tmpl_suffix`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_votes`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_votes` (
  `vote_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_yes` int(11) NOT NULL DEFAULT '0',
  `vote_no` int(11) NOT NULL DEFAULT '0',
  `ipaddress` TINYTEXT NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY `user_id` (`user_id`),
  KEY `review_id` (`review_id`),
  KEY `ipaddress` (`ipaddress` ( 16 ) )  
) ENGINE=MyISAM;
-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_listing_totals`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_listing_totals` (
  `listing_id` int(11) NOT NULL,
  `extension` varchar(50) NOT NULL,
  `user_rating` DECIMAL( 9, 4 ) NOT NULL,
  `user_rating_count` int(11) NOT NULL,
  `user_criteria_rating` text NOT NULL,
  `user_criteria_rating_count` text NOT NULL,
  `user_comment_count` int(11) NOT NULL,
  `editor_rating` DECIMAL( 9, 4 ) NOT NULL,
  `editor_rating_count` int(11) NOT NULL,
  `editor_criteria_rating` text NOT NULL,
  `editor_criteria_rating_count` text NOT NULL,
  `editor_comment_count` int(11) NOT NULL,
  PRIMARY KEY  (`listing_id`, `extension`),
  INDEX `user_rating` (  `user_rating` ,  `user_rating_count` ),
  INDEX `editor_rating` (  `editor_rating` ,  `editor_rating_count` ),
  INDEX (  `user_comment_count` ),  
  INDEX (  `editor_comment_count` )
) ENGINE=MyISAM;

--
-- Table structure for table `#__jreviews_discussions`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_discussions` (
  `discussion_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `approved` tinyint(4) NOT NULL,
  PRIMARY KEY  (`discussion_id`),
  KEY `parent_post_id` (`parent_post_id`),
  KEY `review_id` (`review_id`),
  KEY `user_id` (`user_id`),
  KEY `approved` (`approved`)  
) ENGINE=MyISAM;

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
    INDEX  `post_activity` (  `post_id` ,  `activity_type` ( 12 ) ,  `activity_new` ),
    INDEX `extension` (`extension` ( 12 ) )
) ENGINE = MyISAM;
                          
--
-- Table structure for table `#__jreviews_predefined_replies`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_predefined_replies` (
`reply_id` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`reply_type` TINYTEXT NOT NULL ,
`reply_subject` MEDIUMTEXT NOT NULL ,
`reply_body` TEXT NOT NULL
) ENGINE = MyISAM; 

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
-- Table structure for table `#__jreviews_claims`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_claims` (
`claim_id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`user_id` INT( 11 ) NOT NULL ,
`listing_id` INT( 11 ) NOT NULL ,
`claim_text` MEDIUMTEXT NOT NULL ,
`created` DATETIME NOT NULL ,
`claim_note` MEDIUMTEXT NOT NULL ,
`approved` TINYINT( 4 ) NOT NULL DEFAULT '0' ,
INDEX ( `listing_id` ),
INDEX ( `user_id` ),
INDEX ( `approved` )
) ENGINE = MyISAM; 

--
-- Table structure for table `#__jreviews_reviewer_ranks`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_reviewer_ranks` (
  `user_id` int(11) NOT NULL,
  `reviews` int(11) NOT NULL,
  `votes_percent_helpful` decimal(5,4) NOT NULL,
  `votes_total` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  PRIMARY KEY  (`user_id`)
) ENGINE=MyISAM;