<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( '_JEXEC') or die;

define('MVC_FRAMEWORK_ADMIN',1);

/**
 * Check to display link for admin side Joomla core access settings
 */

if (JFactory::getUser()->authorise('core.admin', 'com_jreviews'))
{
    JToolBarHelper::preferences('com_jreviews');
}

$JInput = JFactory::getApplication()->input;

$urlParamVal = $JInput->get('url');

/**
 * Perform pre-install requirements checks
 */

if(
    (!$urlParamVal || $urlParamVal == 'about')
    &&
    !JReviewsInstallChecks()
){
    // Check failed
    return;
}

$_PATH_JR_COMPONENT = JPATH_ROOT . '/components/com_jreviews';

$_PATH_S2_COMPONENT = JPATH_ROOT . '/components/com_s2framework';

$_PATH_APP = $_PATH_JR_COMPONENT . '/jreviews';

$_PATH_S2 = $_PATH_S2_COMPONENT . '/s2framework';

$_PATH_S2_TMP = $_PATH_S2_COMPONENT . '/tmp/cache';

$package = JPATH_ADMINISTRATOR . '/components/com_jreviews/jreviews.s2';

JToolbarHelper::title('JReviews');

/*************************************************
*                   MAINTENANCE                  *
*************************************************/

if($JInput->get('task') != '') {

    JReviewsMaintenanceTasks($JInput->get('task'));
}

/*************************************************
*                   UPDATES                      *
*************************************************/

if($JInput->get('update') == 1)
{
    if(!file_exists($package))
    {
        echo json_encode(array('success'=>true));
    }
    elseif(JReviewsInstallPackage($package, $_PATH_JR_COMPONENT))
    {
        echo json_encode(array('success'=>true));
    }
    else {

        echo json_encode(array('success'=>false));
    }
}
else {

    // If framework and app installed, then run app
    if(file_exists($_PATH_APP . '/framework.php') && file_exists($_PATH_S2 . '/basics.php'))
    {
        // Run some checks on the tmp folders first
        $msg = array();

        $folders = array('__data','core','menu','views');

        foreach($folders AS $folder){

            if(!file_exists($_PATH_S2_TMP . '/' . $folder)) {

                if(@!mkdir($_PATH_S2_TMP . '/' . $folder, 755)){

                    $msg[] = 'You need to create the ' .  $_PATH_S2_TMP . '/' . $folder . ' folder and make sure it is writable (755) and has correct ownership';
                }
            }

            if(!is_writable($_PATH_S2_TMP . '/' . $folder)){

                if(@!chmod($_PATH_S2_TMP . '/' . $folder, 755)){

                    $msg[] = 'You need to make the '.  $_PATH_S2_TMP . '/' . $folder . ' folder writable (755) and or change its ownership';
                }
            }
        }

        if(empty($msg))
        {
            // Start the APP

            require( $_PATH_APP . '/cms_compat/joomla/bootloader.php' );
        }
        else {

            echo implode('<br />',$msg);
        }

    }
    elseif(file_exists($_PATH_APP . '/framework.php') && !file_exists($_PATH_S2 . '/basics.php')) {
        ?>
        <div class="alert alert-error">
        The S2 Framework required to run jReviews is not installed. Please install the S2Framework component included in the JReviews package.
        </div>
        <?php

    }
    elseif(JReviewsInstallPackage($package, $_PATH_JR_COMPONENT)) {

        header('Location: index.php?option=com_jreviews&url=install');
        die;
    }
    else {
        // Can't install app
        ?>
        <div class="alert alert-error">
        There was a problem extracting the jReviews. <br />
        1) Locate the jreviews.s2 file in the component installation package you just tried to install.<br />
        2) Rename it to jreviews.zip and extract it to your hard drive<br />
        3) Upload it to the frontend /components/com_jreviews/ directory.
        </div>
        <?php
    }
}

function JReviewsMaintenanceTasks($task)
{
    jimport('joomla.filesystem.file');
    jimport('joomla.filesystem.folder');

    $_PATH_S2_COMPONENT = JPATH_ROOT . '/components/com_s2framework';

    $_PATH_S2 = $_PATH_S2_COMPONENT . '/s2framework';

    $_PATH_S2_TMP = $_PATH_S2_COMPONENT . '/tmp/cache';

    $_CORE = $_PATH_S2_TMP . '/core';

    $cacheFolders = array('__data','core','menu','views');

    switch($task)
    {
        case 'cache':

            foreach($cacheFolders AS $folder)
            {
                $exists = JFolder::exists($_PATH_S2_TMP . '/' . $folder);

                $permissions = $exists ? substr(sprintf("%o",fileperms($_PATH_S2_TMP . '/' . $folder)),-4) : null;

                if($exists && $permissions != '0755')
                {
                    if(!@JFolder::delete($_PATH_S2_TMP . '/' . $folder))
                    {
                        chmod($_PATH_S2_TMP . '/' . $folder, 0755);
                    }

                    $exists = false;
                }

                if(!$exists)
                {
                    $empty = '';

                    JFolder::create($_PATH_S2_TMP . '/' . $folder);

                    JFile::write($_PATH_S2_TMP . '/' . $folder . '/index.html', $empty);
                }
            }

            echo '<div style="margin-top: 20px;" class="alert alert-info">The cache folders permissions were fixed.</div>';

        break;

        case 'registry':

            $files = JFolder::files($_CORE, $filter = '_paths_', $recurse = false, $full = true);

            if(!empty($files))
            {
                JFile::delete($files);
            }

            $files = JFolder::files($_CORE, $filter = '_paths_', $recurse = false, $full = true);

            if(empty($files))
            {
                echo '<div style="margin-top: 20px;" class="alert alert-info">The file registry was cleared.</div>';
            }
            else {
                echo '<div style="margin-top: 20px;" class="alert alert-danger">The file registry could not be cleared</div>';
            }

        break;
    }
}

function JReviewsInstallPackage($package, $target)
{
    if(!file_exists($package))
    {
        return false;
    }

    if (!ini_get('safe_mode')) {
        set_time_limit(2000);
    }

    jimport( 'joomla.filesystem.file' );
    jimport( 'joomla.filesystem.folder' );
    jimport( 'joomla.filesystem.archive' );
    jimport( 'joomla.filesystem.path' );

    $adapter = JArchive::getAdapter('zip');

    $result = @$adapter->extract($package, $target);

    if($result)
    {
        JFile::delete($package);
    }

    return $result;
}

function JReviewsInstallChecks()
{
    $ioncube_check = extension_loaded('ionCube Loader');

    $phpversion_check = version_compare( PHP_VERSION, 5.3, '>=') >= 0;
    $json_check = extension_loaded("json");
    $mbstring_check = extension_loaded("mbstring");
    $curl_check = function_exists('curl_init');
    $gd_check = function_exists("gd_info");
    $bcpow_check = function_exists('bcpow');

    if(
        $ioncube_check
        && $phpversion_check
        && $json_check
        && $mbstring_check
        && $curl_check
        && $gd_check
        && $bcpow_check
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
            'curl'=>'CURL PHP Extension',
            'bcpow'=>'BCMath PHP Extension'
        );
?>
        <style type="text/css">
        .jrRoundedPanelLt    {
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
        <div class="jrRoundedPanelLt">
              <h1>Pre-installation Server Requirements</h1>
              <p>Your server did not pass the checks for minimum installation requirements. Below you will find a list of the checks performed and the results.</p>
              <ul class="installCheckList">
                <?php foreach($checks AS $check=>$text):?>
                <li><span class="checkLabel"><?php echo $text;?></span>: <?php echo checkPassFail(${$check.'_check'});?></li>
                <?php endforeach;?>
              </ul>
              <p class="checkHighlight">For more information please read the <a target="_blank" href="https://docs.jreviews.com/?title=JReviews_Pre-install_requirements">JReviews Pre-install Requirements</a> document on our website</p>
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
