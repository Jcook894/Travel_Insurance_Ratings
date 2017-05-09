<?php
defined( 'MVC_FRAMEWORK') or die;

$cache_engine = 'File';

S2Cache::config('default', array(
	'engine'	=>		$cache_engine,
	'path'		=>		S2Paths::get('jreviews','S2_CACHE') . '__data'
));

S2Cache::config('_menu_', array(
	'duration'	=>		86400,
	'engine' 	=> 		$cache_engine,
	'path'		=>		S2Paths::get('jreviews','S2_CACHE') . 'menu'
));

S2Cache::config('_s2framework_core_', array(
	'duration'	=>		86400,
	'engine' 	=> 		$cache_engine,
	'path'		=>		S2Paths::get('jreviews','S2_CACHE') . 'core'
));


/**
 *
 * Cache Engine Configuration
 * Default settings provided below
 *
 * File storage engine.
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'File', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 * 		'path' => CACHE, //[optional] use system tmp directory - remember to use absolute path
 * 		'prefix' => 'cake_', //[optional]  prefix every cache file with this string
 * 		'lock' => false, //[optional]  use file locking
 * 		'serialize' => true, [optional]
 *	));
 *
 *
 * APC (http://pecl.php.net/package/APC)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Apc', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 * 		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *	));
 *
 * Xcache (http://xcache.lighttpd.net/)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Xcache', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 * 		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional] prefix every cache file with this string
 *		'user' => 'user', //user from xcache.admin.user settings
 *      'password' => 'password', //plaintext password (xcache.admin.pass)
 *	));
 *
 *
 * Memcache (http://www.danga.com/memcached/)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Memcache', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 * 		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 * 		'servers' => array(
 * 			'127.0.0.1:11211' // localhost, default port 11211
 * 		), //[optional]
 * 		'compress' => false, // [optional] compress data in Memcache (slower, but uses less memory)
 *	));
 *
 */