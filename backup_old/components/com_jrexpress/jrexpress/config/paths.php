<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

$basePaths = array(
    PATH_ROOT . 'components' . DS . 'com_s2framework' . DS . 's2framework' . DS . 'libs' . DS,
    PATH_ROOT . 'components' . DS . 'com_'.$app . DS. $app . DS,
    PATH_ROOT . 'templates' . DS . 'jrexpress_overrides' . DS
);

$relativePaths = array(
    'Lib' => '',
    'Controller' => array(
        'controller', // s2framework
        'controllers',
        'controllers' . DS . 'cb_plugins',
        'controllers' . DS . 'community_plugins',
        'controllers' . DS . 'modules',
    ),
    'AdminController' => 'admin_controllers',
    'Component' => array(
        'controller' . DS . 'components', // s2framework
        'controllers' . DS . 'components'    
    ),
    'Model' => array(
        'model', // s2framework
        'models',
        'models' . DS . 'community',
        'controllers' . DS . 'components' . DS . 'everywhere'        
    ),
    'Helper' => array(
        'view' . DS . 'helpers', // s2framework
        'views' . DS . 'helpers'
    ),
    'AdminHelper' => 'views' . DS . 'admin' . DS . 'helpers',
);

$themePath = 'views' . DS. 'themes';
