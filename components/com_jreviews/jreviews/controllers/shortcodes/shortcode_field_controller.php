<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Controller','common','jreviews');
S2App::import('Helper', 'custom_fields', 'jreviews');

class ShortcodeFieldController extends MyController
{
    var $uses = array('menu');

    var $helpers = array('time','custom_fields');

    var $components = array('access','everywhere','listings_repository');

    var $autoRender = false;

    var $autoLayout = true;

	function beforeFilter() {}

	function index()
	{
		$attr = Sanitize::getVar($this->params, 'shortcode');

		$fname = Sanitize::getString($attr, 'name');

		$listingId = Sanitize::getInt($attr, 'listing');

		$extension = Sanitize::getString($this->params, 'extension', 'com_content');

		$listing = null;

		if (!$fname)
		{
			return null;
		}

		$this->ListingsRepository
			->without('Media','Favorite','Community')
			->callbacks('afterFind');

		// If a listing alias attribute is included in the shortcode it's automatically converted to the listing id and set to the 'listing' attribute
		if ($listingId)
		{
			$listing = $this->ListingsRepository
				->whereListingId($listingId)
				->one();
		}
		else {

			$ids = CommonController::_discoverIds($this);

			$listingId = Sanitize::getInt($ids, 'listing_id');

			$reviewId = Sanitize::getInt($ids, 'review_id');

			if ($listingId)
			{
				$listing = $this->ListingsRepository
					->whereListingId($listingId)
					->one();
			}
			elseif ($reviewId && $extension == 'com_content')
			{
				$listing = $this->ListingsRepository
					->where('Listing.id IN (SELECT pid FROM #__jreviews_comments WHERE id = ' . $reviewId . ' AND mode = "com_content")')
					->one();
			}
		}

		if ($listing)
		{
			$CustomFields = new CustomFieldsHelper;

			$CustomFields->Access = & $this->Access;

			return $CustomFields->field($fname, $listing);
		}

		return null;
	}
}