<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Component', 'base_repository', 'jreviews');

class CategoriesRepositoryComponent extends BaseRepository {

    protected $c;

    protected $config;

    protected $access;

    protected $category;

	function startup (& $controller)
	{
		$this->c = & $controller;

		$this->config = & $this->c->Config;

        $this->access = & $this->c->Access;

        $this->category = & $this->c->Category;
	}

	function getParents($catId)
	{
        $parent_categories = $this->category->findParents($catId);

        if(!$parent_categories)
        {
        	$this->setError('not_found');

        	return false;
        }

        if($parent_categories)
        {
            $category = array_pop($parent_categories); // This is the current category

            if(!$category['Category']['published'] || !$this->access->isAuthorized($category['Category']['access']))
            {
	        	$this->setError('no_access');

	        	return false;
            }

            $dirId = $category['Directory']['dir_id'];

            $categories = $this->category->findChildren($catId, $category['Category']['level']);

            // Check the listing type of all subcategories and if it's the same one apply the overrides to the parent category as well
            // Also get set the listing type id and default ordering based on a common listing type if it's the same for all sub-categories

            $overrides = array();

            $listingTypeId = null;

            if(count($categories) > 1 && $category['Category']['criteria_id'] == 0 && !empty($categories))
            {
                $subcategories = $this->category->findChildren($catId);

                foreach($subcategories AS $subcat) {

                    if($subcat['Category']['criteria_id'] > 0 && !empty($subcat['ListingType']['config'])) {

                        $overrides[$subcat['Category']['criteria_id']] = $subcat['ListingType']['config'];
                    }
                }

                if(count($overrides) == 1) {

                	$override = array_shift($overrides);

					if(!is_array($override)) {

	                    $override = json_decode($override, true);
	                }

                    $category['ListingType']['config'] = $override;

                    $listingTypeId = $subcat['Category']['criteria_id'];
                }
            }
            else {

                $listingTypeId = $category['Category']['criteria_id'];

                $category['ListingType']['config'] = !is_array($category['ListingType']['config']) ? json_decode($category['ListingType']['config'], true) : $category['ListingType']['config'];
            }

            if(isset($category['ListingType']) && $listingTypeId)
            {
            	$this->config->override($category['ListingType']['config']);

                $category['ListingType']['type_id'] = $listingTypeId;
            }

            $parent_categories[$category['Category']['cat_id']] = $category;
        }

        return $parent_categories;
	}

    public function parentHasListingSubmitAccess(& $results, $catId, $children)
    {
        foreach ($children AS $row)
        {
            $overrides = !is_array($row['config']) ? json_decode($row['config'],true) : $row['config'];

            if($this->access->canAddListing(Sanitize::getVar($overrides,'addnewaccess'))) {
                $results[$catId][] = $row['value'];
            }

            if (isset($row['children']))
            {
                $this->parentHasListingSubmitAccess($results, $row['value'], $row['children']);
            }
        }
    }
}