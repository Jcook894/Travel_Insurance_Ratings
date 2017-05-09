<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

S2App::Import('Model', array('rapidlauncher_rating_criteria', 'rapidlauncher_listing_type'), 'jreviews');

class RapidlauncherRatingCriteriaHelperComponent
{
	protected $listingType;

	protected $ratingCriteria;

	protected $ordering = 1;

	public function startup(& $controller)
	{
		$this->listingType = ClassRegistry::getClass('RapidlauncherListingTypeModel');

		$this->ratingCriteria = ClassRegistry::getClass('RapidlauncherRatingCriteriaModel');
	}

	public function import($rows)
	{
		// Remove header row

		array_shift($rows);

		foreach($rows AS $row)
		{
			$row['ordering'] = $this->ordering++;

			$this->create(array('listing_type', 'title', 'required', 'weight', 'description', 'ordering'), $row);
		}
	}

	public function create($columns, $row)
	{
		$data = array_combine($columns, $row);

		$ratingCriteriaData = array(
			'title' => $data['title'],
			'required' => $data['required'],
			'weight' => $data['weight'],
			'description' => $data['description'],
			'ordering' => $data['ordering']
		);

		// First get the Listing Type ID from the Listing Type title

		$listingTypeTitle = Sanitize::getString($data, 'listing_type');

		if($listingTypeTitle)
		{
			if($listingTypeId = $this->listingType->getListingTypeId($listingTypeTitle))
			{
				$ratingCriteriaData['listing_type_id'] = $listingTypeId;

				// Create the new listing type

				$this->ratingCriteria->create($ratingCriteriaData);
			}
		}
	}

	public function export($dirId)
	{
		$listingTypeIds = $this->listingType->getListingTypeFromDirectory($dirId);

		return $this->ratingCriteria->read($listingTypeIds);
	}
}