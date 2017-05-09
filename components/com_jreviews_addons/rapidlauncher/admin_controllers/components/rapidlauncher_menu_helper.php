<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

S2App::Import('Model', ['rapidlauncher_menu', 'rapidlauncher_directory'], 'jreviews');

class RapidlauncherMenuHelperComponent
{
	public function startup(& $controller)
	{
		$this->menu = ClassRegistry::getClass('RapidlauncherMenuModel');

		$this->directory = ClassRegistry::getClass('RapidlauncherDirectoryModel');
	}

	public function import($rows)
	{
		// Remove header row

		$headers = array_shift($rows);

		foreach($rows AS $row)
		{
			$this->create($headers, $row);
		}
	}

	public function create($columns, $row)
	{
		$data = array_combine($columns, $row);

		$type = Sanitize::getString($data, 'type');

		switch($type)
		{
			case 'directories/index':

				if($directoryTitle = Sanitize::getString($data, 'directory'))
				{
					$dirId = $this->directory->getDirectoryId($directoryTitle);

					if($dirId > 0)
					{
						$data['dirid'] = $dirId; // WordPress
					}
				}

			break;
		}

		$this->menu->createJReviewsMenu($data);
	}
}