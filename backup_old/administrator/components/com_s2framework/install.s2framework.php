<?php
/**
 * jReviews - Reviews Extension
 * Copyright (C) 2008 Alejandro Schmeichler
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

(defined( '_VALID_MOS') || defined( '_JEXEC')) or die( 'Direct Access to this location is not allowed.' );

function com_install() {

	if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
	
	$path_root = dirname(dirname(dirname(dirname(__FILE__))));
	$path_app_admin = $path_root . DS . 'administrator' . DS . 'components' . DS . 'com_s2framework' . DS;

	$package = $path_app_admin . 's2framework.s2';
	$target = $path_root . DS . 'components' . DS . 'com_s2framework' . DS;
			
	# Get paths
	global $mainframe, $database;
		
	# Initialize framework
	if(!defined('MVC_FRAMEWORK_ADMIN')) {
		define('MVC_FRAMEWORK_ADMIN',1);
	}
		
	# Multi CSM constants
	if(!defined('CMS_JOOMLA15')) define('CMS_JOOMLA15','CMS_JOOMLA15');
	if(!defined('CMS_JOOMLA10')) define('CMS_JOOMLA10','CMS_JOOMLA10');
	if(!defined('CMS_MAMBO46'))	 define('CMS_MAMBO46','CMS_MAMBO46');			
				
	if(getCmsVersionInstall() == CMS_JOOMLA15) {
	
		jimport( 'joomla.filesystem.file' );
		jimport( 'joomla.filesystem.folder' );
		jimport( 'joomla.filesystem.archive' );
		jimport( 'joomla.filesystem.path' );
			
		$adapter = & JArchive::getAdapter('zip');
		$result = $adapter->extract ( $package, $target );
			
	}
	
	if(!file_exists($target . 's2framework' . DS . 'basics.php')) {
	
		require_once ($path_root . DS . 'administrator' . DS . 'includes' . DS . 'pcl' . DS . 'pclzip.lib.php');
		require_once ($path_root . DS . 'administrator' . DS . 'includes' . DS . 'pcl' . DS . 'pclerror.lib.php');
	
		$extract = new PclZip ( $package );
		
		if ((substr ( PHP_OS, 0, 3 ) == 'WIN')) {
			if(!defined('OS_WINDOWS')) define('OS_WINDOWS',1);
		} else {
			if(!defined('OS_WINDOWS')) define('OS_WINDOWS',0);
		}
				
		$result = $extract->extract ( PCLZIP_OPT_PATH, $target );		
	}
	
	if(!file_exists($target . 's2framework' . DS . 'basics.php')) {
		echo "There was a problem installing the framework. You need to extract and rename the s2framework.s2 file inside the component zip you just tried to install to s2framework.zip. Then extract it locally and upload via ftp to the /components/com_s2framework/ directory.";
	} else {
		echo "The S2 Framework has been successfully installed.";
	}
	
}


/**
 * Returns CMS version
**/
function getCmsVersionInstall()
{	
	if(defined('_JEXEC') && class_exists('JFactory')){
		return CMS_JOOMLA15;
	} else if(defined('_VALID_MOS') && class_exists('joomlaVersion')){
	    return CMS_JOOMLA10;
	}elseif(defined('_VALID_MOS') && class_exists('mamboCore')){
	    return CMS_MAMBO46;
	}
	
}