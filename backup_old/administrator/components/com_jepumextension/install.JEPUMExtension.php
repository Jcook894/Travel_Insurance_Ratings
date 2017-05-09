<?php

/**
 * @version		$Id: install.JEPUMExtension.php 94 2007-06-13 21:07:02Z dynedain $
 * @package		JEPUM Extension
 * @subpackage  Installer
 * @copyright	Copyright (C) 2010 Web Responsive
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * com_install
 * Copies necessary JEPUM Extension files to the appropriate folders
 * @access public
 * @return boolean	Whether the installer succeeded or not
 */
function com_install()
{
	$sources = array();
	$sources[] = JPath::clean(JPATH_SITE.DS.'components'.DS.'com_jepumextension'.DS.'amfphp');
	$sources[] = JPath::clean(JPATH_SITE.DS.'components'.DS.'com_jepumextension'.DS.'libraries'.DS.'amfphp');

	$destinations = array();
	$destinations[] = JPath::clean(JPATH_SITE.DS.'amfphp');
	$destinations[] = JPath::clean(JPATH_SITE.DS.'libraries'.DS.'amfphp');

	foreach ($sources as $key=>$source)
	{
		if (!JFolder::move($source, $destinations[$key]))
		{
			JError::raiseWarning(1, 'Component Install: '.JText::_('Failed to copy JEPUM Extension files to appropriate folders'));
			return false;
		}
	}

	if (!JFolder::delete(JPATH_SITE.DS.'components'.DS.'com_jepumextension'.DS.'libraries'))
	{
		JError::raiseWarning(1, 'Component Install: '.JText::_('Failed to clean up JEPUM Extension files'));
		return false;
	}

	return true;
}