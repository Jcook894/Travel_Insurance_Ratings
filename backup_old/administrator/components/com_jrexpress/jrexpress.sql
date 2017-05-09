-- 
-- Table structure for table `#__jreviews_captcha`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_captcha` (
  `captcha_id` bigint(13) unsigned NOT NULL auto_increment,
  `captcha_time` int(10) unsigned NOT NULL,
  `ip_address` varchar(16) NOT NULL default '0',
  `word` varchar(20) NOT NULL,
  PRIMARY KEY  (`captcha_id`),
  KEY `word` (`word`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_categories`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_categories` (
  `id` int(11) NOT NULL default '0',
  `criteriaid` int(11) default NULL,
  `dirid` int(11) default NULL,
  `groupid` varchar(50) default NULL,
  `option` varchar(50) NOT NULL default 'com_content',
  `tmpl` varchar(100) default NULL,
  `tmpl_suffix` varchar(20) default NULL,
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
  `name` varchar(50) NOT NULL default '',
  `username` varchar(25) NOT NULL default '',
  `email` varchar(100) NOT NULL default '',
  `location` varchar(50) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `comments` text NOT NULL,
  `author` tinyint(1) NOT NULL default '0',
  `published` tinyint(1) NOT NULL default '0',
  `ipaddress` varchar(50) NOT NULL default '',
  `checked_out` int(11) unsigned default '0',
  `checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `listing_id` (`pid`),
  KEY `extension` (`mode`),
  KEY `created` (`created`),
  KEY `modified` (`modified`),
  KEY `userid` (`userid`),
  KEY `published` (`published`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_config`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_config` (
  `id` varchar(30) NOT NULL default '',
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
  PRIMARY KEY  (`contentid`),
  KEY `featured` (`featured`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_criteria`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_criteria` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(30) NOT NULL default '',
  `criteria` text NOT NULL,
  `weights` mediumtext,
  `tooltips` text NOT NULL,
  `qty` int(11) NOT NULL default '0',
  `groupid` text,
  `state` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_directories`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_directories` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(50) NOT NULL default '',
  `desc` text NOT NULL,
  `tmpl_suffix` varchar(20) default NULL,
  PRIMARY KEY  (`id`)
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
  `text` varchar(255) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  `image` varchar(255) default NULL,
  `ordering` int(11) default NULL,
  PRIMARY KEY  (`optionid`),
  KEY `fieldid` (`fieldid`),
  KEY `field_value` (`value`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_fields`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_fields` (
  `fieldid` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `showtitle` tinyint(1) NOT NULL default '1',
  `description` mediumtext NOT NULL,
  `required` tinyint(1) default '0',
  `groupid` int(11) default NULL,
  `type` varchar(50) NOT NULL default '',
  `location` enum('content','review') NOT NULL default 'content',
  `options` mediumtext,
  `size` int(11) default NULL,
  `maxlength` int(11) default NULL,
  `cols` int(11) default NULL,
  `rows` int(11) default NULL,
  `ordering` int(11) default NULL,
  `contentview` tinyint(1) NOT NULL default '0',
  `listview` tinyint(1) NOT NULL default '0',
  `listsort` tinyint(1) NOT NULL default '0',
  `search` tinyint(1) NOT NULL default '1',
  `access` varchar(50) NOT NULL default '0,18,19,20,21,23,24,25',
  `access_view` varchar(50) NOT NULL default '0,18,19,20,21,23,24,25',
  `published` tinyint(1) NOT NULL default '1',
  `metatitle` varchar(255) NOT NULL,
  `metakey` text NOT NULL,
  `metadesc` text NOT NULL,
  PRIMARY KEY  (`fieldid`),
  UNIQUE KEY `name` (`name`),
  KEY `listsort` (`listsort`),
  KEY `search` (`search`),
  KEY `entry_published` (`published`,`contentview`,`location`,`name`),
  KEY `list_published` (`published`,`listview`,`location`,`name`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_groups`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_groups` (
  `groupid` int(11) NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  `title` varchar(200) default NULL,
  `showtitle` tinyint(1) NOT NULL default '1',
  `type` varchar(50) NOT NULL default 'content',
  `ordering` int(11) default NULL,
  PRIMARY KEY  (`groupid`),
  KEY `type` (`type`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_license`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_license` (
  `id` varchar(30) NOT NULL default '',
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
  PRIMARY KEY  (`rating_id`),
  KEY `review_id` (`reviewid`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_report`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_report` (
  `id` int(11) NOT NULL auto_increment,
  `reviewid` int(11) NOT NULL default '0',
  `message` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `reviewid` (`reviewid`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_review_fields`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_review_fields` (
  `reviewid` int(11) NOT NULL default '0',
  `jr_reviewone` mediumtext,
  `jr_selectlist` mediumtext,
  `jr_reviewboxes` mediumtext,
  `jr_reviewradios` mediumtext,
  `jr_reviewmulti` mediumtext,
  `jr_date` datetime default NULL,
  PRIMARY KEY  (`reviewid`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_sections`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_sections` (
  `sectionid` int(11) NOT NULL,
  `tmpl` varchar(100) default NULL,
  `tmpl_suffix` varchar(20) default NULL,
  PRIMARY KEY  (`sectionid`),
  KEY `tmpl` (`tmpl`),
  KEY `tmpl_suffix` (`tmpl_suffix`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_votes`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_votes` (
  `reviewid` int(11) NOT NULL default '0',
  `yes` int(11) NOT NULL default '0',
  `no` int(11) NOT NULL default '0',
  PRIMARY KEY  (`reviewid`),
  KEY `vote_yes` (`yes`),
  KEY `vote_no` (`no`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `#__jreviews_votes_tmp`
-- 

CREATE TABLE IF NOT EXISTS `#__jreviews_votes_tmp` (
  `id` int(11) NOT NULL auto_increment,
  `reviewid` int(11) NOT NULL default '0',
  `yes` int(11) NOT NULL default '0',
  `no` int(11) NOT NULL default '0',
  `ipaddress` varchar(50) NOT NULL default '',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `reviewid` (`reviewid`,`ipaddress`),
  KEY `vote_yes` (`yes`),
  KEY `vote_no` (`no`)
) ENGINE=MyISAM;