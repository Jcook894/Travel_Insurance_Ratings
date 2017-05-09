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


# MVC initalization script
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);	
if(defined('JPATH_SITE')){    
    $root = JPATH_SITE . DS;
} else {
    global $mainframe;
    $root = $mainframe->getCfg('absolute_path') . DS;
}
require($root . 'components' . DS . 'com_jrexpress' . DS . 'jrexpress' . DS . 'framework.php');

# Populate $params array with module settings
$moduleParams['module'] = stringToArray($params->_raw);
$moduleParams['module_id'] = $module->id;
$moduleParams['page'] = 1;
$moduleParams['data']['module'] = true;
$moduleParams['data']['controller'] = 'module_totals';
$moduleParams['data']['action'] = 'index';

$Dispatcher = new S2Dispatcher('jrexpress',false);
echo $Dispatcher->dispatch($moduleParams);