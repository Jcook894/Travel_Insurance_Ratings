<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ListingTotalModel extends MyModel {

	var $name = 'ListingTotal';

	var $useTable = '#__jreviews_listing_totals AS ListingTotal';

	var $primaryKey = 'ListingTotal.listing_id';

	var $realKey = 'listing_id';

	function completeRows($extension = 'com_content', $listing_id = null)
	{
		S2App::import('Model','everywhere_com_content','jreviews');

        $where = '';

		switch(_CMS_NAME)
		{
			case 'joomla':

		        if($listing_id)
		        {
		            $where = " AND Listing.id = " . (int) $listing_id;
		        }

				$query = "
					INSERT INTO `#__jreviews_listing_totals`
						(listing_id, extension)
					SELECT
						Listing." . EverywhereComContentModel::_LISTING_ID . " AS listing_id, '". $extension ."' AS extension
					FROM
						" . EverywhereComContentModel::_LISTING_TABLE . " AS Listing
		            LEFT JOIN
		                #__jreviews_listing_totals AS Total ON Listing." . EverywhereComContentModel::_LISTING_ID . " = Total.listing_id AND Total.extension = " . $this->Quote($extension) . "
					WHERE
						Listing.catid IN (
							SELECT id FROM `#__jreviews_categories` WHERE `option` = " . $this->Quote($extension) . "
						)
                    	AND Total.listing_id IS NULL
                    	" . $where . "
		            ON DUPLICATE KEY UPDATE
		                user_rating = 0,
		                user_rating_rank = 0,
		                user_criteria_rating = '',
		                user_criteria_rating_count = '',
		                user_rating_count = 0,
		                user_comment_count = 0,
		                editor_rating = 0,
		                editor_rating_rank = 0,
		                editor_criteria_rating = '',
		                editor_criteria_rating_count = '',
		                editor_rating_count = 0,
		                editor_comment_count = 0";

				break;

			case 'wordpress':

		        if($listing_id)
		        {
		            $where = " AND Listing.ID = " . (int) $listing_id;
		        }

		        $query = "
					INSERT INTO `#__jreviews_listing_totals`
						(listing_id, extension)
					SELECT
						Listing." . EverywhereComContentModel::_LISTING_ID . " AS listing_id, '". $extension ."' AS extension
					FROM
						" . EverywhereComContentModel::_LISTING_TABLE . " AS Listing
		            LEFT JOIN
		                #__jreviews_listing_totals AS Total ON Listing." . EverywhereComContentModel::_LISTING_ID . " = Total.listing_id AND Total.extension = " . $this->Quote($extension) . "
		        	LEFT JOIN
		        		#__term_relationships AS CategoryRelationships ON CategoryRelationships.object_id = Listing.ID
		            LEFT JOIN
		                #__term_taxonomy AS Taxonomy ON Taxonomy.term_taxonomy_id = CategoryRelationships.term_taxonomy_id
					WHERE
						Taxonomy.term_id IN (
							SELECT id FROM #__jreviews_categories WHERE `option` = " . $this->Quote($extension) . "
						)
                    	AND Total.listing_id IS NULL
                    	" . $where . "
		            ON DUPLICATE KEY UPDATE
		                user_rating = 0,
		                user_rating_rank = 0,
		                user_criteria_rating = '',
		                user_criteria_rating_count = '',
		                user_rating_count = 0,
		                user_comment_count = 0,
		                editor_rating = 0,
		                editor_rating_rank = 0,
		                editor_criteria_rating = '',
		                editor_criteria_rating_count = '',
		                editor_rating_count = 0,
		                editor_comment_count = 0";

				break;
		}

		$this->query($query);
	}
}
