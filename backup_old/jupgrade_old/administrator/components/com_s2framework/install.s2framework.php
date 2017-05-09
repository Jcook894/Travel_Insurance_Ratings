<?php
/**
 * S2Framework
 * Copyright (C) 2008-2010 ClickFWD LLC
**/

defined( '_JEXEC') or die( 'Direct Access to this location is not allowed.' );

function com_install() 
{
    if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
    
    $path_root = dirname(dirname(dirname(dirname(__FILE__))));
    $path_app_admin = $path_root . DS . 'administrator' . DS . 'components' . DS . 'com_s2framework' . DS;
    $package = $path_app_admin . 's2framework.s2';
    $target = $path_root . DS . 'components' . DS . 'com_s2framework' . DS;

    jimport( 'joomla.filesystem.file' );
    jimport( 'joomla.filesystem.folder' );
    jimport( 'joomla.filesystem.archive' );
    jimport( 'joomla.filesystem.path' );
        
    $adapter = JArchive::getAdapter('zip');
    $result = $adapter->extract ( $package, $target );
    
    if($result) 
    {
        $version = new JVersion();
        if($version->RELEASE >= 1.6)
        {
            ?>
            <script type="text/javascript">                        
            window.addEvent('domready', function() { 
                var req = new Request({ 
                  method: 'get', 
                  url: '<?php echo JURI::base();?>index.php?option=com_s2framework&tmpl=component&format=raw&install=1', 
                }).send();
            });
            </script>
            <?php
        }        
        echo "The S2 Framework has been successfully installed.";
    } 
    else 
    {
        echo "There was a problem installing the framework. You need to extract and rename the s2framework.s2 file inside the component zip you just tried to install to s2framework.zip. Then extract it locally and upload via ftp to the /components/com_s2framework/ directory.";
    }             
}