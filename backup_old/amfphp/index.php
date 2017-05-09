<?php

/**
 * @version		$Id: index.php 111 2008-05-04 15:56:48Z dynedain $
 * @package		J-AMFPHP
 * @copyright	Copyright (C) 2007 Anthony McLin
 * @license		GNU/GPL
 */

// Set flag that this is a parent file
define( '_JEXEC', 1 );
define( 'JPATH_BASE', dirname(__FILE__) );
define( 'DS', DIRECTORY_SEPARATOR );

require_once JPATH_BASE.DS.'includes'.DS.'defines.php';
require_once JPATH_BASE.DS.'includes'.DS.'framework.php';

// We want to echo the errors so that the Flash Remoting client has a chance to capture them in the payload
JError::setErrorHandling( E_ERROR,	 'die' );
JError::setErrorHandling( E_WARNING, 'echo' );
JError::setErrorHandling( E_NOTICE,	 'echo' );

// Instantiate a Joomla Application Object
// This doesn't work, because the 4 application types are hard coded in /libraries/joomla/application/helper.php
//$mainframe =& JFactory::getApplication('amfphp');

//Instead, let's try using our own JApplication extended class
require_once JPATH_BASE.DS.'includes'.DS.'application.php';
//$mainframe =& new JAMFPHP();
$mainframe = new JAMFPHP();



//TODO: Add some enable/disable switching similar to XMLRPC - probably should utilize JRegistry

// Includes the required class files for the AMFPHP Server
jimport( 'amfphp.core.shared.app.Constants' );
jimport( 'amfphp.core.shared.app.Globals' );

// Switch PHP4/PHP5 compatibility
if(AMFPHP_PHP5)
{
	jimport( 'amfphp.core.shared.util.CompatPhp5');
} else {
	jimport( 'amfphp.core.shared.util.CompatPhp4' );
}

jimport( 'amfphp.core.shared.util.CharsetHandler' );
jimport( 'amfphp.core.shared.util.NetDebug' );
jimport( 'amfphp.core.shared.util.Headers' );
jimport( 'amfphp.core.shared.exception.MessageException' );
jimport( 'amfphp.core.shared.app.BasicActions' );
jimport( 'amfphp.core.amf.util.AMFObject' );
jimport( 'amfphp.core.amf.util.WrapperClasses' );
jimport( 'amfphp.core.amf.app.Filters' );
jimport( 'amfphp.core.amf.app.Actions' );
jimport( 'amfphp.core.amf.app.Gateway' );

	//You can set this constant appropriately to disable traces and debugging headers
	//You will also have the constant available in your classes, for changing
	//the mysql server info for example
	define("PRODUCTION_SERVER", false);


	// Create the Application Object
	$gateway = new Gateway();

   $gateway->setLooseMode(true);
	
	//Set where the services classes are loaded from, *with trailing slash*
	//$servicesPath defined in globals.php
	$gateway->setClassPath(JPATH_AMFPHP_SERVICES);

	//Set where class mappings are loaded from (ie: for VOs)
	//$voPath defined in globals.php
	$gateway->setClassMappingsPath(JPATH_AMFPHP_VOPATH);

	//Read above large note for explanation of charset handling
	//The main contributor (Patrick Mineault) is French,
	//so don't be afraid if he forgot to turn off iconv by default!
	$gateway->setCharsetHandler("utf8_decode", "ISO-8859-1", "ISO-8859-1");

	//Error types that will be rooted to the NetConnection debugger
	$gateway->setErrorHandling(E_ALL ^ E_NOTICE);

	if(PRODUCTION_SERVER)
	{
		//Disable profiling, remote tracing, and service browser
		$gateway->disableDebug();
	}

	//If you are running into low-level issues with corrupt messages and
	//the like, you can add $gateway->logIncomingMessages('path/to/incoming/messages/');
	//and $gateway->logOutgoingMessages('path/to/outgoing/messages/'); here
	//$gateway->logIncomingMessages('in/');
	//$gateway->logOutgoingMessages('out/');

	//Explicitly disable the native extension if it is installed
	//$gateway->disableNativeExtension();

	//Enable gzip compression of output if zlib is available,
	//beyond a certain byte size threshold
	$gateway->enableGzipCompression(25*1024);

	//Service now
	$gateway->service();

?>