ALTER TABLE `#__jreviews_criteria` ADD `required` mediumtext AFTER `criteria`;

-- --------------------------------------------------------

--
-- Table structure for table `#__jreviews_listing_totals`
--

CREATE TABLE `#__jreviews_listing_totals` (
  `listing_id` int(11) NOT NULL,
  `extension` varchar(50) NOT NULL,
  `user_rating` text NOT NULL,
  `user_rating_count` int(11) NOT NULL,
  `user_criteria_rating` text NOT NULL,
  `user_criteria_rating_count` text NOT NULL,
  `user_comment_count` int(11) NOT NULL,
  `editor_rating` text NOT NULL,
  `editor_rating_count` int(11) NOT NULL,
  `editor_criteria_rating` text NOT NULL,
  `editor_criteria_rating_count` text NOT NULL,
  `editor_comment_count` int(11) NOT NULL,
  PRIMARY KEY  (`listing_id`, `extension`)
) ENGINE=MyISAM;