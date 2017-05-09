<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

S2App::Import('Model', ['rapidlauncher_field_group', 'rapidlauncher_field', 'rapidlauncher_user'], 'jreviews');

class RapidlauncherFieldHelperComponent
{
	protected $fieldGroup;

	public function startup(& $controller)
	{
		$this->fieldGroup = ClassRegistry::getClass('RapidlauncherFieldGroupModel');

		$this->field = ClassRegistry::getClass('RapidlauncherFieldModel');
	}

	public function import($rows)
	{
		// Remove header row

		array_shift($rows);

		foreach($rows AS $row)
		{
			// Ensure the number of columns is correct before we do the column association

			$row = array_pad($row, 10, '');

			$this->create(array('title', 'name', 'group', 'type', 'required', 'contentview', 'listview', 'compareview', 'showtitle', 'options'), $row);
		}
	}

	public function create($columns, $row)
	{
		$data = array_combine($columns, $row);

		// Need to set the default access settings and other default attributes

		$data['access'] = RapidlauncherUserModel::$defaultUserGroups;

		$data['access_view'] = RapidlauncherUserModel::$defaultUserGroups;

		// Need to convert the group title to group id and continue only if the group exists

		if($groupTitle = Sanitize::getString($data, 'group'))
		{
			unset($data['group']);

			if($groupId = $this->fieldGroup->getGroupId($groupTitle))
			{
				$data['groupid'] = $groupId;

				$this->field->create($data);
			}
		}
	}

	public function export($dirId)
	{
		$groupIds = $this->fieldGroup->getGroupFromDirectory($dirId);

		$rows = $this->field->readBygroupId($groupIds);

		return $rows;
	}
}