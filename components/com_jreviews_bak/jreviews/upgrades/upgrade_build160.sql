ALTER TABLE  `#__jreviews_fields` ADD INDEX  `groupid` ( `groupid` );

ALTER TABLE  `#__jreviews_captcha` ADD INDEX  `ip_address` ( `ip_address` );

ALTER TABLE  `#__jreviews_reports` ADD INDEX  `listing_id` ( `listing_id` );
ALTER TABLE  `#__jreviews_reports` ADD INDEX  `review_id` ( `review_id` );
ALTER TABLE  `#__jreviews_reports` ADD INDEX  `post_id` ( `post_id` );
ALTER TABLE  `#__jreviews_reports` ADD INDEX  `user_id` ( `user_id` );
ALTER TABLE  `#__jreviews_reports` ADD INDEX  `approved` ( `approved` );
ALTER TABLE  `#__jreviews_reports` ADD INDEX  `extension` ( `extension` (20) );

ALTER TABLE  `#__jreviews_votes` ADD INDEX  `ipaddress` ( `ipaddress` (16) );

ALTER TABLE  `#__jreviews_discussions` ADD INDEX  `parent_post_id` ( `parent_post_id` );
ALTER TABLE  `#__jreviews_discussions` ADD INDEX  `review_id` ( `review_id` );
ALTER TABLE  `#__jreviews_discussions` ADD INDEX  `user_id` ( `user_id` );
ALTER TABLE  `#__jreviews_discussions` ADD INDEX  `approved` ( `approved` );

ALTER TABLE  `#__jreviews_claims` ADD INDEX  `user_id` ( `user_id` );
ALTER TABLE  `#__jreviews_claims` ADD INDEX  `approved` ( `approved` );

ALTER TABLE  `#__jreviews_activities` ADD INDEX  `extension` ( `extension` (20));


