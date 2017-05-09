<?php
/*
    JReviews Express - user reviews for Joomla
    Copyright (C) 2009  Alejandro Schmeichler

    JReviews Express is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JReviews Express is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

(defined( '_VALID_MOS') || defined( '_JEXEC')) or die( 'Direct Access to this location is not allowed.' );

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

$path_root = dirname(dirname(dirname(dirname(__FILE__))));
$path_app_admin = $path_root . DS . 'administrator' . DS . 'components' . DS . 'com_jrexpress' . DS;

$package = $path_app_admin . 'jrexpress.s2';
$target = $path_root . DS . 'components' . DS . 'com_jrexpress' . DS;
	
define('MVC_FRAMEWORK_ADMIN',1);
	
// If framework and app installed, then run app
if(file_exists($path_root . DS . 'components' . DS . 'com_jrexpress' . DS . 'jrexpress' . DS . 'framework.php') && 
	file_exists($path_root . DS . 'components' . DS . 'com_s2framework' . DS . 's2framework' . DS . 'basics.php')) 
{
    // Run some checks on the tmp folders first
    $msg = array();
    $tmp_path = $path_root . DS . 'components' . DS . 'com_s2framework' . DS . 'tmp' . DS . 'cache' . DS;
    $folders = array('__data','assets','core','views');
    foreach($folders AS $folder){
        if(!file_exists( $tmp_path . $folder)) {
            if(@!mkdir($tmp_path . $folder,777)){
                $msg[] = 'You need to create the '.  $tmp_path . $folder. ' folder and make sure it is writable (777)';
            }
        } 
        if(!is_writable( $tmp_path . $folder . DS)){
            if(@!chmod($tmp_path . $folder . DS,777)){
                $msg[] = 'You need to make the '.  $tmp_path . $folder. ' folder writable (777)';                
            }
        }        
    }    
    
    if(empty($msg)){
	    // MVC initalization script
	    $S2_ROOT = dirname(dirname(dirname(dirname(__FILE__))));
	    require( $S2_ROOT . DS . 'components' . DS . 'com_jrexpress' . DS . 'jrexpress' . DS . 'index.php' );
    } else {
        echo implode('<br />',$msg);
    }
	
} elseif (file_exists($path_root . DS . 'components' . DS . 'com_jrexpress' . DS . 'jrexpress'. DS . 'framework.php') && 
	!file_exists($path_root . DS . 'components' . DS . 'com_s2framework' . DS . 's2framework' . DS . 'basics.php')) {
	?>
	<div style="font-size:12px;border:1px solid #000;background-color:#FBFBFB;padding:10px;">
	The S2 Framework required to run JReviews Express is not installed. Please install the com_s2framework component included in the JReviews Express package.
	</div>
	<?php

} elseif(file_exists($path_root . DS . 'administrator' . DS . 'components' . DS . 'com_jrexpress' .DS . 'jrexpress.s2'))
{ // Install app
	if (!ini_get('safe_mode')) {
	    set_time_limit(2000);
	}				
	
	if($install_bypass === false) {
	
		jimport( 'joomla.filesystem.file' );
		jimport( 'joomla.filesystem.folder' );
		jimport( 'joomla.filesystem.archive' );
		jimport( 'joomla.filesystem.path' );

		$adapter = & JArchive::getAdapter('zip');
		$result = @$adapter->extract($package, $target);
	}

	if(!file_exists($target . 'jrexpress' . DS . 'index.php')) 
	{ // Extract 2nd attempt
		
		require_once ($path_root . DS . 'administrator' . DS . 'includes' . DS . 'pcl' . DS . 'pclzip.lib.php');
		require_once ($path_root . DS . 'administrator' . DS . 'includes' . DS . 'pcl' . DS . 'pclerror.lib.php');

		$extract = new PclZip ( $package );
		
		if ((substr ( PHP_OS, 0, 3 ) == 'WIN')) {
			if(!defined('OS_WINDOWS')) define('OS_WINDOWS',1);
		} else {
			if(!defined('OS_WINDOWS')) define('OS_WINDOWS',0);
		}
				
		$result = @$extract->extract( PCLZIP_OPT_PATH, $target );		
	}	
			
	if(file_exists($target . 'jrexpress' . DS . 'index.php')) 
	{ // If extracted, run installer
		@unlink($path_app_admin . 'jrexpress.s2');	
		
		$S2_ROOT = dirname(dirname(dirname(dirname(__FILE__))));
		require( $S2_ROOT . DS . 'components' . DS . 'com_jrexpress' . DS . 'jrexpress' . DS . 'framework.php' );
					
		$Dispatcher = new S2Dispatcher('jrexpress');	
		
		$Dispatcher->dispatch('install/index',array());	
	}
	
} else 
{ // Can't install app
	?>
	<div style="font-size:12px;border:1px solid #000;background-color:#FBFBFB;padding:10px;">
	There was a problem extracting the JReviews Express. <br />
	1) Locate the jrexpress.s2 file in the component installation package you just tried to install.<br />
	2) Rename it to jrexpress.zip and extract it to your hard drive<br />
	3) Upload it to the frontend /components/com_jrexpress/ directory.
	</div>
	<?php
}