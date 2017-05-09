<?php
/**
 * jReviews - Reviews Extension
 * Copyright (C) 2006-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

(defined( '_VALID_MOS') || defined( '_JEXEC')) or die( 'Direct Access to this location is not allowed.' );

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

# Perform pre-install requirements checks
if(
    (!isset($_GET['url']) || $_GET['url'] == 'about') 
    && 
    !JReviewsInstallChecks()
){ 
    // Check failed
    return; 
}


$path_root = explode(DS,realpath(dirname($_SERVER["SCRIPT_FILENAME"])));
array_pop($path_root);
$path_root = implode(DS,$path_root);
define('S2_ROOT',$path_root . DS . 'components' . DS . 'com_s2framework');

if(isset($_GET['update']) && $_GET['update'] == 1)
{
    jimport( 'joomla.filesystem.file' );
    jimport( 'joomla.filesystem.folder' );
    jimport( 'joomla.filesystem.archive' );
    jimport( 'joomla.filesystem.path' );
    
    $path_app_admin = $path_root . DS . 'administrator' . DS . 'components' . DS . 'com_jreviews' . DS;
    $package = $path_app_admin . DS . 'jreviews.s2';
    $target = $path_root . DS . 'components' . DS . 'com_jreviews' . DS;    

    if(is_dir($path_app_admin . DS . 'admin'))
    {
        # Transfer files from admin folder to component admin root
        $admin_files = JFolder::files($path_app_admin . 'admin','.');
        if(!empty($admin_files))
        {
            foreach($admin_files AS $file)
            {     
                JFile::copy($path_app_admin . 'admin' . DS . $file, $path_app_admin . $file);
            }
        }   
        
        # Transfer language folder
        if(adminGetCmsVersion() != 'CMS_JOOMLA15')
        {
            JFolder::delete($path_app_admin . 'language');    
            JFolder::copy($path_app_admin . 'admin' . DS . 'language', $path_app_admin . 'language');    
        } 

        # Contents transfered, delete...
        JFolder::delete($path_app_admin . 'admin'); 
        
        # Transfer files from site folder to component site root
        $site_files = JFolder::files($path_app_admin . 'site','.'); 
        if(!empty($site_files))
        {
            foreach($site_files AS $file)
            {
                JFile::copy($path_app_admin . 'site' . DS . $file, $target . $file);
            }
        }        

        # Transfer views folder
        if(adminGetCmsVersion() != 'CMS_JOOMLA15')
        {
            JFolder::delete($target . 'views');
            JFolder::copy($path_app_admin . 'site' . DS . 'views', $target . 'views');
        } 

        # Contents transfered, delete...
        JFolder::delete($path_app_admin . 'site'); 
    }

    if(file_exists($package))
    { // Install app
        if (!ini_get('safe_mode')) {
            set_time_limit(2000);
        }                
        
        $adapter = JArchive::getAdapter('zip');
        $result = @$adapter->extract($package, $target);

        if($result)
        {
            JFile::delete($package);    
            echo json_encode(array('error'=>false,'html'=>'<div style="color:green;">New package extracted successfully.</div>'));
            return;
        } 
    }  

    echo json_encode(array('error'=>true,'html'=>'<div style="color:red;">There was a problem extracting the files from the downloaded package.</div>'));
} 
else
{
    $path_app_admin = $path_root . DS . 'administrator' . DS . 'components' . DS . 'com_jreviews' . DS;

    $package = $path_app_admin . 'jreviews.s2';
    $target = $path_root . DS . 'components' . DS . 'com_jreviews' . DS;
        
    define('MVC_FRAMEWORK_ADMIN',1);
        
    // If framework and app installed, then run app
    if(file_exists($path_root . DS . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'framework.php') && 
        file_exists($path_root . DS . 'components' . DS . 'com_s2framework' . DS . 's2framework' . DS . 'basics.php')) 
    {
        // Run some checks on the tmp folders first
        $msg = array();
        $tmp_path = $path_root . DS . 'components' . DS . 'com_s2framework' . DS . 'tmp' . DS . 'cache' . DS;
        $folders = array('__data','assets','core','views');
        foreach($folders AS $folder){
            if(!file_exists( $tmp_path . $folder)) {
                if(@!mkdir($tmp_path . $folder,755)){
                    $msg[] = 'You need to create the '.  $tmp_path . $folder. ' folder and make sure it is writable (755) and has correct ownership';
                }
            } 
            if(!is_writable( $tmp_path . $folder . DS)){
                if(@!chmod($tmp_path . $folder . DS,755)){
                    $msg[] = 'You need to make the '.  $tmp_path . $folder. ' folder writable (755) and or change its ownership';                
                }
            }        
        }    
        
        if(empty($msg)){
            // MVC initalization script
            require( $path_root . DS . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'index.php' );
        } else {
            echo implode('<br />',$msg);
        }
        
    } elseif(file_exists($path_root . DS . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'framework.php') && 
        !file_exists($path_root . DS . 'components' . DS . 'com_s2framework' . DS . 's2framework' . DS . 'basics.php')) {
        ?>
        <div style="font-size:12px;border:1px solid #000;background-color:#FBFBFB;padding:10px;">
        The S2 Framework required to run jReviews is not installed. Please install the com_s2framework component included in the jReviews package.
        </div>
        <?php

    } elseif(file_exists($path_root . DS . 'administrator' . DS . 'components' . DS . 'com_jreviews' .DS . 'jreviews.s2'))
    { // Install app
        if (!ini_get('safe_mode')) {
            set_time_limit(2000);
        }                
        
        $install_bypass = isset($_GET['bypass']) ? true : false;    
        
        if($install_bypass === false) 
        {
            jimport( 'joomla.filesystem.file' );
            jimport( 'joomla.filesystem.folder' );
            jimport( 'joomla.filesystem.archive' );
            jimport( 'joomla.filesystem.path' );

            $adapter = JArchive::getAdapter('zip');
            $result = @$adapter->extract($package, $target);
        }
                
        if(file_exists($target . 'jreviews' . DS . 'index.php')) 
        { // If extracted, run installer
            
            @unlink($path_app_admin . 'jreviews.s2');    
            
            require( $path_root . DS . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'framework.php' );
                        
            $Dispatcher = new S2Dispatcher('jreviews');    
            
            $Dispatcher->dispatch('install/index',array());           
        }
        
    } else 
    { // Can't install app
        ?>
        <div style="font-size:12px;border:1px solid #000;background-color:#FBFBFB;padding:10px;">
        There was a problem extracting the jReviews. <br />
        1) Locate the jreviews.s2 file in the component installation package you just tried to install.<br />
        2) Rename it to jreviews.zip and extract it to your hard drive<br />
        3) Upload it to the frontend /components/com_jreviews/ directory.
        </div>
        <?php
    }    
}

function adminGetCmsVersion()
{    
        $version = new JVersion();
        switch($version->RELEASE)
        {
            case 1.5:
                return 'CMS_JOOMLA15';
            break;                
			case 1.6:
			case 1.7:
			case 2.5:
				return 'CMS_JOOMLA16';
			break;         
		}
}

function JReviewsInstallChecks()
{
    $ioncube_check = extension_loaded('ionCube Loader');
    $phpversion = phpversion(); 
    $phpversion_check = (substr($phpversion,0,3)=='5.2' || substr($phpversion,0,3)=='5.3');
    $json_check = extension_loaded("json");
    $mbstring_check = extension_loaded("mbstring");
    $curl_check = function_exists('curl_init');
    $gd_check = function_exists("gd_info");
    if(
        $ioncube_check
        && $phpversion_check
        && $json_check
        && $mbstring_check
        && $curl_check
        && $gd_check
    ) { 
        return true;
    }
    else {
        $checks = array(
            'ioncube'=>'ionCube Loaders',
            'phpversion'=>'PHP Version',
            'json'=>'JSON PHP Extension',
            'mbstring'=>'MBSTRING PHP Extension',
            'gd'=>'GD PHP Image Library',
            'curl'=>'CURL PHP Extension'
        );
?>
        <style type="text/css">
        .roundedPanelLt    {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            background-color: #fefefe;
            background-image: -moz-linear-gradient(top, #fff, #fbfbfb);
            background-image: -webkit-gradient(linear, center top, center bottom, color-stop(0, #fff), color-stop(1, #fbfbfb));
            background-image: -webkit-linear-gradient(#fff, #fbfbfb);
            background-image: linear-gradient(top, #fff, #fbfbfb);
            -moz-border-radius: 10px;
            -webkit-border-radius: 10px;    
            border-radius: 10px; 
        } 
        ul.installCheckList, ul.installCheckList li{
            margin-left:30px;
            padding:0;
        }
        ul.installCheckList li {
            line-height: 2.0em;
        }
        p.checkHighlight {
            font-size: 1.2em;
            font-weight: bold;
        }
        .checkPassed {
            font-weight: bold;
            color: #1dc315;
        }
        .checkFailed {
            font-weight: bold;
            color: #FF0000;
        }
        </style>
        <div class="roundedPanelLt">
              <h1>Pre-installation Server Requirements</h1>
              <p>Your server did not pass the checks for minimum installation requirements. Below you will find a list of the checks performed and the results.</p>
              <ul class="installCheckList">
                <?php foreach($checks AS $check=>$text):?>
                <li><span class="checkLabel"><?php echo $text;?></span>: <?php echo checkPassFail(${$check.'_check'});?></li>
                <?php endforeach;?>
              </ul>
              <p class="checkHighlight">For more information please read the <a target="_blank" href="http://docs.reviewsforjoomla.com/JReviews_Pre-install_requirements">JReviews Pre-install Requirements</a> document on our website</p>
        </div>
<?php 
    return false;
    }
}

function checkPassFail($result)
{
    return $result 
            ?  
            '<span class="checkPassed">Passed</span>'
            : 
            '<span class="checkFailed">Failed</span>';
}
