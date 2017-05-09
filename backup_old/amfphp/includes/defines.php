<?php

/**
 * @version		$Id: defines.php 111 2008-05-04 15:56:48Z dynedain $
 * @package		J-AMFPHP
 * @copyright	Copyright (C) 2007 Anthony McLin
 * @license		GNU/GPL
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

//Joomla framework path definitions
$parts = explode( DS, JPATH_BASE );
array_pop( $parts );

define( 'JPATH_ROOT',			implode( DS, $parts ) );
define( 'JPATH_SITE',			JPATH_ROOT );
define( 'JPATH_CONFIGURATION',	JPATH_ROOT );
define( 'JPATH_INSTALLATION',	JPATH_ROOT . DS . 'installation' );
define( 'JPATH_ADMINISTRATOR',	JPATH_ROOT . DS . 'administrator' );
define( 'JPATH_AMFPHP',			JPATH_ROOT . DS . 'amfphp' );
define( 'JPATH_LIBRARIES',		JPATH_ROOT . DS . 'libraries' );
define( 'JPATH_PLUGINS',		JPATH_ROOT . DS . 'plugins'   );
define( 'JPATH_COMPONENTS',		JPATH_ROOT . DS . 'components'   );
define( 'JPATH_CACHE',			JPATH_BASE . DS . 'cache');



//define( 'JPATH_AMFPHP_SERVICES',JPATH_PLUGINS . DS . 'j-amfphp' . DS );
define( 'JPATH_AMFPHP_SERVICES',JPATH_COMPONENTS . DS . 'com_jepumextension' . DS . 'services');
define( 'JPATH_AMFPHP_VOPATH',	JPATH_AMFPHP_SERVICES . 'vo' . DS );


	//This file is intentionally left blank so that you can add your own global settings
	//and includes which you may need inside your services. This is generally considered bad
	//practice, but it may be the only reasonable choice if you want to integrate with
	//frameworks that expect to be included as globals, for example TextPattern or WordPress

	//Set start time before loading framework
	list($usec, $sec) = explode(" ", microtime());
	$amfphp['startTime'] = ((float)$usec + (float)$sec);

//	$servicesPath = "services/";
//	$servicesPath = "services" . DS;
//	$servicesPath = '..' . DS . 'plugins' . DS . 'j-amfphp' . DS;
//	$servicesPath = JPATH_PLUGINS . DS . 'j-amfphp' . DS;


//	$voPath = "services/vo/";
//	$voPath = $servicesPath . "vo" . DS;

	//As an example of what you might want to do here, consider:

	/*
	if(!PRODUCTION_SERVER)
	{
		define("DB_HOST", "localhost");
		define("DB_USER", "root");
		define("DB_PASS", "");
		define("DB_NAME", "amfphp");
	}
	*/
define( 'AMFPHP_BASE', 			JPATH_LIBRARIES . DS . 'amfphp' . DS . 'core' . DS );
?>