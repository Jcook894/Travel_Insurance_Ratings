<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

S2App::Import('Model', array('rapidlauncher_category', 'rapidlauncher_jreviews_category', 'rapidlauncher_listing_type', 'rapidlauncher_directory', 'rapidlauncher_menu'), 'jreviews');

class RapidlauncherCategoryHelperComponent
{
	protected $category;

	protected $jreviewsCategory;

	protected $listingType;

	protected $directory;

	public function startup(& $controller)
	{
		$this->category = ClassRegistry::getClass('RapidlauncherCategoryModel');

		$this->jreviewsCategory = ClassRegistry::getClass('RapidlauncherJreviewsCategoryModel');

		$this->listingType = ClassRegistry::getClass('RapidlauncherListingTypeModel');

		$this->directory = ClassRegistry::getClass('RapidlauncherDirectoryModel');

		$this->menu = ClassRegistry::getClass('RapidlauncherMenuModel');
	}

	public function import($rows)
	{
		// Remove header row

		array_shift($rows);

		foreach($rows AS $row)
		{
			$row['extension'] = 'com_content';
			$row['published'] = 1;
			$row['language'] = '*';
			$row['params'] = array('category_layout' => '','image' => '');
			$row['metadata'] = array('author' => '','robots' => '');

			$this->create(array('title', 'parent', 'directory', 'listing_type', 'extension', 'published', 'language', 'params', 'metadata'), $row);
		}
	}

	public function create($columns, $row)
	{
		$data = array_combine($columns, $row);

		$categoryData = array(
			'id' => 0,
			'parent_id' => 0,
			'title' => $data['title'],
			'extension' => $data['extension'],
			'published' => $data['published'],
			'language' => $data['language'],
			'params' => $data['params']
		);

		$parentCategoryTitle = Sanitize::getString($data, 'parent');

		if($parentCategoryTitle != '')
		{
			if($parentCategoryId = $this->category->getCategoryId($parentCategoryTitle))
			{
				$categoryData['parent_id'] = $parentCategoryId;
			}
		}

		$category = $this->category->create($categoryData);

		// If category created, then automatically set it up in JReviews with directory and listing type

		$directoryTitle = Sanitize::getString($data, 'directory');

		$listingTypeTitle = Sanitize::getString($data, 'listing_type');

		$extension = Sanitize::getString($data, 'extension');

		$directoryId = $this->directory->getDirectoryId($directoryTitle);

		$listingTypeId = $this->listingType->getListingTypeId($listingTypeTitle);

		$jreviewsCategoryData = array(
			'id' => $category['id'],
			'criteriaid' => $listingTypeId,
			'dirid' => $directoryId,
			'option' => $extension
		);

		$this->jreviewsCategory->create($jreviewsCategoryData);

		if(_CMS_NAME == 'joomla')
		{
			// Create the category menus

			$parentMenuId = $this->menu->getMenuIdByCatId($categoryData['parent_id']);

			$categoryMenudata = array(
				'id' => $category['id'],
				'title' => $categoryData['title'],
				'alias' => $category['alias'],
				'menutype' => 'required-joomla-menus',
				'parent_id' => $parentMenuId
			);

			$this->menu->createCategoryMenu($categoryMenudata);
		}
	}

	public function export($dirId)
	{
		return $this->category->read($dirId);
	}
}