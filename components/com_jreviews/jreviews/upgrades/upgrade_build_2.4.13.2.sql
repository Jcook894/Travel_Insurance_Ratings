INSERT IGNORE INTO `#__jreviews_listing_totals`
	(listing_id, extension)
	SELECT
		Listing.id AS listing_id,
		'com_content' AS extension
	FROM
		`#__content` AS Listing
	WHERE
		Listing.catid IN (
			SELECT id FROM `#__jreviews_categories` WHERE `option` = 'com_content'
		)
		AND
		Listing.id NOT IN (
			SELECT listing_id FROM `#__jreviews_listing_totals` WHERE `extension` = 'com_content'
		)
	;

INSERT IGNORE INTO `#__jreviews_content`
	(contentid)
	SELECT
		Listing.id AS contentid
	FROM
		`#__content` AS Listing
	WHERE
		Listing.catid IN (
			SELECT id FROM `#__jreviews_categories` WHERE `option` = 'com_content'
		)
	;