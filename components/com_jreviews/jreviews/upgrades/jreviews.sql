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
  `page_title` varchar(255) NOT NULL,
  `title_override` tinyint(1) unsigned NOT NULL default 0,
  `metadesc` text NOT NULL,
  `desc_override` tinyint(1) unsigned NOT NULL default 0,
  PRIMARY KEY  (`id`,`option`),
  KEY `criteriaid` (`criteriaid`),
  KEY `groupid` (`groupid`),
  KEY `dirid` (`dirid`),
  KEY `option` (`option`)
) DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_comments`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_comments` (
  `id` int(11) NOT NULL auto_increment,
  `pid` int(11) NOT NULL default '0',
  `listing_type_id` INT(11) NOT NULL,
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
  `rating` DECIMAL(11, 4) NOT NULL,
  `published` tinyint(1) NOT NULL default '0',
  `ipaddress` varchar(50) NOT NULL,
  `posts` INT( 11 ) unsigned NOT NULL,
  `owner_reply_text` TEXT NOT NULL,
  `owner_reply_created` DATETIME NOT NULL,
  `owner_reply_approved` TINYINT( 4 ) NOT NULL DEFAULT '0',
  `owner_reply_note` MEDIUMTEXT NOT NULL,
  `vote_helpful` INT( 10 ) unsigned NOT NULL,
  `vote_total` INT( 10 ) unsigned NOT NULL,
  `review_note` MEDIUMTEXT NOT NULL,
  `media_count` int(10) unsigned NOT NULL DEFAULT '0',
  `video_count` int(10) unsigned NOT NULL DEFAULT '0',
  `photo_count` int(10) unsigned NOT NULL DEFAULT '0',
  `audio_count` int(10) unsigned NOT NULL DEFAULT '0',
  `attachment_count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`),
  KEY `listing_id` (`pid`),
  KEY `extension` (`mode`),
  KEY `listing` (`pid`,`mode`,`published`,`author`,`created`),
  KEY `created` (`created`, `published`, `mode`, `author`),
  KEY `modified` (`modified`,`created`, `published`, `mode`, `author`),
  KEY `userid` (`userid`, `published`, `created`, `mode`, `author`),
  KEY `published` (`published`, `listing_type_id`, `created`, `mode`, `author`),
  KEY `votes_helpful` (`vote_helpful`, `published`, `created`, `mode`, `author`),
  KEY `votes` ( `vote_total`, `vote_helpful`, `created`,`mode`, `published`, `author`),
  KEY `posts` (`posts`, `published`, `created`, `mode`, `author`),
  KEY `media_count` ( `media_count`, `published`, `created`, `mode`, `author` ),
  KEY `video_count` ( `video_count`, `published`, `created`, `mode`, `author` ),
  KEY `photo_count` ( `photo_count`, `published`, `created`, `mode`, `author` ),
  KEY `audio_count` ( `audio_count`, `published`, `created`, `mode`, `author` ),
  KEY `attachment_count` ( `attachment_count`, `published`, `created`, `mode`, `author` ),
  KEY `published_user_author` ( `published`, `userid`, `author`)
) DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_config`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_config` (
  `id` varchar(255) NOT NULL,
  `value` text,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_content`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_content` (
  `contentid` int(11) NOT NULL default '0',
  `featured` tinyint(1) NOT NULL default '0',
  `email` varchar(100) NOT NULL,
  `ipaddress` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `metakey` varchar(255) NOT NULL DEFAULT '',
  `metadesc` text NOT NULL,
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `listing_note` MEDIUMTEXT NOT NULL,
  PRIMARY KEY  (`contentid`),
  KEY `featured` (`featured`),
  KEY `metakey` (`metakey`),
  KEY `publish_down` (`publish_down`)
) DEFAULT CHARSET=utf8;

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
  `search` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8;

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
) DEFAULT CHARSET=utf8;

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
) DEFAULT CHARSET=utf8;

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
  `default` TINYINT(1) NOT NULL default '0',
  `description` TEXT NOT NULL,
  PRIMARY KEY  (`optionid`),
  KEY `fieldid` (`fieldid`),
  KEY `field_value` (`value`),
  KEY `control_field` (`control_field`),
  KEY `control_value` (`control_value`),
  KEY `default` (`default`)
) DEFAULT CHARSET=utf8;

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
  `access` varchar(50) NOT NULL,
  `access_view` varchar(50) NOT NULL,
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
) DEFAULT CHARSET=utf8;

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
  KEY `type` (`type`,`name`),
  KEY `control_field` (`control_field`),
  KEY `control_value` (`control_value`)
) DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_license`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_license` (
  `id` varchar(30) NOT NULL,
  `value` text,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_report`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `media_id`int(11) unsigned NOT NULL,
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
) DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_review_fields`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_review_fields` (
  `reviewid` int(11) NOT NULL,
  PRIMARY KEY  (`reviewid`)
) DEFAULT CHARSET=utf8;

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
) DEFAULT CHARSET=utf8;
-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_listing_totals`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_listing_totals` (
  `listing_id` int(11) NOT NULL,
  `extension` varchar(50) NOT NULL,
  `user_rating` DECIMAL( 9, 4 ) NOT NULL,
  `user_rating_count` int(11) NOT NULL,
  `user_rating_rank` DECIMAL(9,4) NOT NULL,
  `user_criteria_rating` text NOT NULL,
  `user_criteria_rating_count` text NOT NULL,
  `user_comment_count` int(11) NOT NULL,
  `editor_rating` DECIMAL( 9, 4 ) NOT NULL,
  `editor_rating_count` int(11) NOT NULL,
  `editor_rating_rank` DECIMAL(9,4) NOT NULL,
  `editor_criteria_rating` text NOT NULL,
  `editor_criteria_rating_count` text NOT NULL,
  `editor_comment_count` int(11) NOT NULL,
  `media_count` int(10) NOT NULL DEFAULT '0',
  `video_count` int(10) NOT NULL DEFAULT '0',
  `photo_count` int(10) NOT NULL DEFAULT '0',
  `audio_count` int(10) NOT NULL DEFAULT '0',
  `attachment_count` int(10) NOT NULL DEFAULT '0',
  `media_count_user` int(10) NOT NULL DEFAULT '0',
  `video_count_user` int(10) NOT NULL DEFAULT '0',
  `photo_count_user` int(10) NOT NULL DEFAULT '0',
  `audio_count_user` int(10) NOT NULL DEFAULT '0',
  `attachment_count_user` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`listing_id`, `extension`),
  INDEX `user_rating` (  `user_rating` ,  `user_rating_count` ),
  INDEX `editor_rating` (  `editor_rating` ,  `editor_rating_count` ),
  INDEX (  `user_rating_rank`),
  INDEX (  `editor_rating_rank`),
  INDEX (  `user_comment_count` ),
  INDEX (  `media_count` ),
  INDEX (  `video_count` ),
  INDEX (  `photo_count` ),
  INDEX (  `audio_count` ),
  INDEX (  `attachment_count` ),
  INDEX (  `media_count_user` ),
  INDEX (  `video_count_user` ),
  INDEX (  `photo_count_user` ),
  INDEX (  `audio_count_user` ),
  INDEX (  `attachment_count_user` ),
  INDEX `user_editor_photo_counts` (`user_comment_count`,`editor_comment_count`,`photo_count`)
  ) DEFAULT CHARSET=utf8;

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
) DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__jreviews_predefined_replies`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_predefined_replies` (
`reply_id` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`reply_type` TINYTEXT NOT NULL ,
`reply_subject` MEDIUMTEXT NOT NULL ,
`reply_body` TEXT NOT NULL
) DEFAULT CHARSET=utf8;

--

INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(1, 'listing', 'Your listing has been approved', '{name},\n\nThank you for submitting your listing. It has been approved and you can see it by visiting the link below:\n\n{url}');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(2, 'listing', 'Your listing has been rejected', '{name},\n\nThank you for your recent listing submission. Unfortunately, it has been rejected.');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(3, 'listing', '', '');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(4, 'listing', '', '');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(5, 'listing', '', '');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(6, 'review', 'Your review has been approved', '{name},\n\nThank you for submitting your review. It has been approved and you can see it by visiting the link below:\n\n{url}');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(7, 'review', 'Your review has been rejected', '{name},\n\nThank you for your recent review submission. Unfortunately, it has been rejected.');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(8, 'review', '', '');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(9, 'review', '', '');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(10, 'review', '', '');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(11, 'owner_reply', 'Your owner reply has been approved', '{name},\n\nThank you for submitting your owner reply. It has been approved and you can see it by visiting the link below:\n\n{url}');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(12, 'owner_reply', 'Your owner reply has been rejected', '{name},\n\nThank you for your recent review reply for one of your listings. Unfortunately, it has been rejected.');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(13, 'owner_reply', '', '');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(14, 'owner_reply', '', '');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(15, 'owner_reply', '', '');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(16, 'discussion_post', 'Your review comment has been approved', '{name},\n\nThank you for submitting your comment. It has been approved and you can see it by visiting the link below:\n\n{url}');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(17, 'discussion_post', 'Your review comment has been rejected', '{name},\n\nThank you for your recent review comment. Unfortunately, it has been rejected.');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(18, 'discussion_post', '', '');
--
INSERT INTO `#__jreviews_predefined_replies` (`reply_id`, `reply_type`, `reply_subject`, `reply_body`) VALUES(19, 'discussion_post', '', '');
--
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
) DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__jreviews_reviewer_ranks`
--

CREATE TABLE IF NOT EXISTS `#__jreviews_reviewer_ranks` (
  `user_id` int(11) NOT NULL,
  `reviews` int(11) NOT NULL,
  `votes_percent_helpful` decimal(5,4) NOT NULL,
  `votes_total` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  PRIMARY KEY  (`user_id`),
  KEY `jr_rank` (`rank`),
  KEY `jr_reviews_votes` (`reviews`,`votes_percent_helpful`,`user_id`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_media` (
  `media_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `media_type` enum('video','photo','audio','attachment') DEFAULT NULL,
  `media_function` enum('cover','logo') DEFAULT NULL,
  `listing_id` int(10) unsigned NOT NULL DEFAULT '0',
  `review_id` int(10) unsigned NOT NULL DEFAULT '0',
  `extension` varchar(100) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `file_extension` varchar(15) NOT NULL DEFAULT '',
  `rel_path` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `duration` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Media duration',
  `filesize` int(11) unsigned NOT NULL DEFAULT '0',
  `media_info` text NOT NULL,
  `embed` varchar(50) NOT NULL DEFAULT '',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `ipaddress` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Use INET_ATON() and INET_NTOA() to convert back and forth',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `main_media` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `metadata` blob NOT NULL,
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `likes_up` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `likes_total` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `likes_rank` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `ordering` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`media_id`),
  KEY `count` (`media_type`,`approved`,`published`),
  KEY `listing` (`listing_id`,`extension`,`media_id`,`media_type`,`published`,`approved`,`main_media`),
  KEY `review` (`review_id`,`extension`,`media_id`,`media_type`,`published`,`approved`,`main_media`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_media_encoding` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(255) NOT NULL DEFAULT '',
  `media_id` int(11) unsigned NOT NULL DEFAULT '0',
  `status` enum('processing','cancelled','finished','waiting','failed') NOT NULL DEFAULT 'waiting',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `response` text NOT NULL,
  `notifications` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `job` (`job_id`,`media_id`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_media_likes` (
  `like_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `media_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ipaddress` int(10) unsigned NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `vote` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`like_id`),
  KEY `vote` (`vote`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_inquiry` (
  `inquiry_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) unsigned DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `from_name` varchar(128) NOT NULL DEFAULT '',
  `from_email` varchar(128) NOT NULL DEFAULT '',
  `to_email` varchar(128) NOT NULL DEFAULT '',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Sender user id',
  `message` mediumtext NOT NULL,
  `extra_fields` mediumtext NOT NULL,
  `ipaddress` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Use INET_ATON() and INET_NTOA() to convert back and forth',
  PRIMARY KEY (`inquiry_id`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_registration` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(200) NOT NULL DEFAULT '',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `listing_id` int(11) unsigned NOT NULL DEFAULT '0',
  `review_id` int(11) unsigned NOT NULL DEFAULT '0',
  `discussion_id` int(11) unsigned NOT NULL DEFAULT '0',
  `media_id` int(11) unsigned NOT NULL DEFAULT '0',
  `session_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_addons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `manifest` text NOT NULL,
  `state` tinyint(4) NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_criteria_ratings` (
  `criteria_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `weight` int(2) NOT NULL DEFAULT '0',
  `description` mediumtext NOT NULL,
  `listing_type_id` int(11) NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`criteria_id`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_review_ratings` (
  `listing_id` int(11) NOT NULL DEFAULT '0',
  `review_id` int(11) NOT NULL DEFAULT '0',
  `extension` varchar(50) NOT NULL,
  `criteria_id` int(11) NOT NULL DEFAULT '0',
  `rating` decimal(11,4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`review_id`,`criteria_id`,`listing_id`,`extension`),
  UNIQUE KEY `listing` (`listing_id`,`criteria_id`,`review_id`,`extension`),
  KEY `rating` (`review_id`,`criteria_id`,`rating`)
) DEFAULT CHARSET=utf8;

--

CREATE TABLE IF NOT EXISTS `#__jreviews_listing_ratings` (
  `listing_id` int(11) NOT NULL DEFAULT '0',
  `extension` varchar(128) NOT NULL DEFAULT '',
  `criteria_id` int(11) NOT NULL DEFAULT '0',
  `user_rating` decimal(11,4) NOT NULL DEFAULT '0',
  `user_rating_count` int(11) NOT NULL DEFAULT '0',
  `user_rating_rank` decimal(11,4) NOT NULL DEFAULT '0',
  `editor_rating` decimal(11,4) NOT NULL DEFAULT '0',
  `editor_rating_count` int(11) NOT NULL DEFAULT '0',
  `editor_rating_rank` decimal(11,4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`listing_id`,`extension`,`criteria_id`),
  KEY `user_rating` (`criteria_id`,`user_rating`,`user_rating_count`),
  KEY `editor_rating` (`criteria_id`,`editor_rating`,`editor_rating_count`)
) DEFAULT CHARSET=utf8;