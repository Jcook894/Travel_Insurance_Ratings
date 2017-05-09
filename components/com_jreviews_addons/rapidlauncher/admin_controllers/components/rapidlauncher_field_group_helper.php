<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

S2App::Import('Model', array('rapidlauncher_field_group'), 'jreviews');

class RapidlauncherFieldGroupHelperComponent
{
	protected $fieldGroup;

	public function startup(& $controller)
	{
		$this->fieldGroup = ClassRegistry::getClass('RapidlauncherFieldGroupModel');
	}

	public function import($rows)
	{
		// Remove header row

		array_shift($rows);

		foreach($rows AS $row)
		{
			$row = array_pad($row, 3, '');

			$this->create(array('title', 'type', 'showtitle'), $row);
		}
	}

	public function create($columns, $row)
	{
		$data = array_combine($columns, $row);

		if($data['type'] == 'listing') $data['type'] = 'content';

		$this->fieldGroup->create($data);
	}

	public function export($dirId)
	{
		$groupIds = $this->fieldGroup->getGroupFromDirectory($dirId);

		$rows = $this->fieldGroup->read($groupIds);

		return $rows;
	}
}