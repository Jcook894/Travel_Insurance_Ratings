<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

S2App::Import('Model', array('rapidlauncher_directory'), 'jreviews');

class RapidlauncherDirectoryHelperComponent
{
	protected $directory;

	public function startup(& $controller)
	{
		$this->directory = ClassRegistry::getClass('RapidlauncherDirectoryModel');
	}

	public function import($rows)
	{
		// Remove header row

		array_shift($rows);

		foreach($rows AS $row)
		{
			$this->create(array('title'), $row);
		}
	}

	public function create($columns, $row)
	{
		$data = array_combine($columns, $row);

		$directoryData = array('desc' => $data['title']);

		$this->directory->create($directoryData);
	}

	public function export($dirId)
	{
		return $this->directory->read($dirId);
	}
}