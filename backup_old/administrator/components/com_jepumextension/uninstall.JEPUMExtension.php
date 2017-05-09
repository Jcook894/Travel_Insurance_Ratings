<?php

/**
 * @version		$Id: uninstall.WRPictureManager.php 94 2007-06-13 21:07:02Z dynedain $
 * @package		WRPictureManager
 * @subpackage  Uninnstaller
 * @copyright	Copyright (C) 2010 Web Responsive
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * com_uninstall
 * Removes files that were copied to other folders by the WRPictureManager installer
 * @access public
 * @return boolean	Whether the installer succeeded or not
 */
function com_uninstall()
{
	$sources = array();

	$sources[] = JPath::clean(JPATH_SITE.DS.'amfphp');

	$sources[] = JPath::clean(JPATH_SITE.DS.'libraries'.DS.'amfphp');

	$sources[] = JPath::clean(JPATH_SITE.DS.'components'.DS.'com_jepumextension');

	foreach ($sources as $source)
	{
		if (!JFolder::delete($source))
		{
			JError::raiseWarning(1, 'Component UnInstall: '.JText::_("Failed to remove JEPUM Extension files from $source"));
			return false;
		}
	}

	return true;
}