<?php
/**
 * S2Framework
 * Copyright (C) 2010-2016 ClickFWD LLC
**/

defined( '_JEXEC') or die;

$_PATH_S2_COMPONENT = JPATH_ROOT . '/components/com_s2framework';

$package = JPATH_ADMINISTRATOR . '/components/com_s2framework/s2framework.s2';

$db = JFactory::getDbo();

$query = "UPDATE #__extensions SET enabled = 0 WHERE element = 'com_s2framework'";

$db->setQuery($query)->query();

if(isset($_GET['update']) && $_GET['update'] == 1)
{
    if(!file_exists($package))
    {
        echo json_encode(array('success'=>true));
    }
    elseif(S2FrameworkInstallPackage($package, $_PATH_S2_COMPONENT))
    {
        echo json_encode(array('success'=>true));
    }
    else {

        echo json_encode(array('success'=>false));
    }
}

function S2FrameworkInstallPackage($package, $target)
{
    if(!file_exists($package))
    {
        return false;
    }

    if (@!ini_get('safe_mode'))
    {
        set_time_limit(2000);
    }

    jimport( 'joomla.filesystem.file' );
    jimport( 'joomla.filesystem.folder' );
    jimport( 'joomla.filesystem.archive' );
    jimport( 'joomla.filesystem.path' );

    $adapter = JArchive::getAdapter('zip');

    $result = $adapter->extract($package, $target);

    if($result)
    {
        JFile::delete($package);
    }

    return $result;
}