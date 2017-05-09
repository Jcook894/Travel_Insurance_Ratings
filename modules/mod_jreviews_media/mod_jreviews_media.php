<?php
/**
 * jReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined('_JEXEC') or die;

require_once JPATH_ROOT . '/components/com_jreviews/jreviews/includes/modules/helper.php';

// Need to pass the module id to the controller and this is the only way

$params->set('module_id', $module->id);

$params->set('controller', 'module_media');

if(!$params->get('owncache',0))
{
	echo JreviewsModuleHelper::renderModule($params);

	return;
}

$cacheid = JreviewsModuleHelper::getCacheID($module, $params, array('url','cat','criteria','dir'));

$cacheparams = new stdClass;

$cacheparams->cachemode = 'id';

$cacheparams->class = 'JreviewsModuleHelper';

$cacheparams->method = 'renderModule';

$cacheparams->methodparams = $params;

$cacheparams->modeparams = $cacheid;

$output = JModuleHelper::moduleCache($module, $params, $cacheparams);

if($output)
{
	echo $output;
}
