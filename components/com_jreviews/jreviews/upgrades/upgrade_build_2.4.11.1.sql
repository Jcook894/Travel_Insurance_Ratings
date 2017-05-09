ALTER TABLE `#__jreviews_media` CHANGE COLUMN `file_extension` `file_extension` VARCHAR(15);

ALTER TABLE `#__jreviews_listing_totals` CHANGE COLUMN `media_count` `media_count` INT(10) NOT NULL DEFAULT 0;

ALTER TABLE `#__jreviews_listing_totals` CHANGE COLUMN `video_count` `video_count` INT(10) NOT NULL DEFAULT 0;

ALTER TABLE `#__jreviews_listing_totals` CHANGE COLUMN `photo_count` `photo_count` INT(10) NOT NULL DEFAULT 0;

ALTER TABLE `#__jreviews_listing_totals` CHANGE COLUMN `audio_count` `audio_count` INT(10) NOT NULL DEFAULT 0;

ALTER TABLE `#__jreviews_listing_totals` CHANGE COLUMN `attachment_count` `attachment_count` INT(10) NOT NULL DEFAULT 0;

ALTER TABLE `#__jreviews_listing_totals` CHANGE COLUMN `media_count_user` `media_count_user` INT(10) NOT NULL DEFAULT 0;

ALTER TABLE `#__jreviews_listing_totals` CHANGE COLUMN `video_count_user` `video_count_user` INT(10) NOT NULL DEFAULT 0;

ALTER TABLE `#__jreviews_listing_totals` CHANGE COLUMN `photo_count_user` `photo_count_user` INT(10) NOT NULL DEFAULT 0;

ALTER TABLE `#__jreviews_listing_totals` CHANGE COLUMN `audio_count_user` `audio_count_user` INT(10) NOT NULL DEFAULT 0;

ALTER TABLE `#__jreviews_listing_totals` CHANGE COLUMN `attachment_count_user` `attachment_count_user` INT(10) NOT NULL DEFAULT 0;