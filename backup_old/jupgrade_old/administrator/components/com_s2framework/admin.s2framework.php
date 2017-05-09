 <?php
/**
 * S2Framework
 * Copyright (C) 2008-2010 ClickFWD LLC
**/

defined( '_JEXEC') or die( 'Direct Access to this location is not allowed.' );

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
 
# Run only on J1.6 installs and called from the install.s2framework.php file 
if(isset($_GET['install']) && $_GET['install'] == 1)  
{
    $db = JFactory::getDbo();
    $query = "UPDATE #__extensions SET enabled = 0 WHERE element = 'com_s2framework'";
    $db->setQuery($query);
    $db->query();    
}  
# Run only by remote updater
if(isset($_GET['update']) && $_GET['update'] == 1)
{
    $path_root = dirname(dirname(dirname(dirname(__FILE__))));
    $path_app_admin = $path_root . DS . 'administrator' . DS . 'components' . DS . 'com_s2framework' . DS;
    $package = $path_app_admin . 's2framework.s2';
    $target = $path_root . DS . 'components' . DS . 'com_s2framework' . DS;    
    
    if(file_exists($package))
    { // Install app
        if (!ini_get('safe_mode')) {
            set_time_limit(2000);
        }                
        
        jimport( 'joomla.filesystem.file' );
        jimport( 'joomla.filesystem.folder' );
        jimport( 'joomla.filesystem.archive' );
        jimport( 'joomla.filesystem.path' );
            
        $adapter = JArchive::getAdapter('zip');
        $result = $adapter->extract ( $package, $target );
 
        if($result)
        {
            @unlink($package);    
            echo json_encode(array('error'=>false,'html'=>'<div style="color:green;">The update completed successfully.</div>'));
        } 
        else 
        {
            echo json_encode(array('error'=>true,'html'=>'<div style="color:red;">There was a problem extracting the files from the downloaded package.</div>'));
        }
    }    
}