<?php
/**
 * jReviews - Reviews Extension
 * Copyright (C) 2006-2009 Alejandro Schmeichler
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 **/


defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/**
 * Returns CMS version
**/
if(!function_exists('getCmsVersion')) 
{
	function getCmsVersion(){
			
		if(defined('_JEXEC') && class_exists('JFactory')){
			return CMS_JOOMLA15;
		} else if(defined('_VALID_MOS') && class_exists('joomlaVersion')){
		    return CMS_JOOMLA10;
		}elseif(defined('_VALID_MOS') && class_exists('mamboCore')){
		    return CMS_MAMBO46;
		}
			
	}
}

class cmsFramework {
	
	var $scripts;
	
	function &getInstance() {
		static $instance = array();

		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] =& new S2Router();
		}
		return $instance[0];
	}	
		
	function init(&$object) {
		
		switch(getCmsVersion())
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				global $database, $mainframe, $acl, $my;
				$object->_db = &$database;
				$object->_mainframe = &$mainframe;
				if(!isset($my) || empty($my)) {
					$object->_user = &$mainframe->getUser();
				} else {
					$object->_user = &$my;
				}
				$object->_acl = &$acl;
			break;
			
			case CMS_JOOMLA15:
				global $mainframe;
				$object->_db = &JFactory::getDBO();
				$object->_mainframe = $mainframe;
				$object->_user = &JFactory::getUser();
				$object->_acl = &JFactory::getACL();
			break;						
		}
		
	}
	
	function isAdmin() {
		
		global $mainframe;
		
		if(defined('MVC_FRAMEWORK_ADMIN') || $mainframe->isAdmin()) {
			return true;
		} else {
			return false;
		}
	}
	
    function getTemplate(){      
        global $mainframe;
        return $mainframe->getTemplate();
    }
    
	function getUser() {
		switch(getCmsVersion())
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				global $mainframe, $my;
				if(!isset($my) || empty($my)) {
					return $mainframe->getUser();
				} else {
					return $my;
				}
			break;
			
			case CMS_JOOMLA15:
				return JFactory::getUser();
			break;						
		}		
	}
	
	function addScript($text, $inline=false, $duress = false)
	{
		$_this = & cmsFramework::getInstance();

		if($text != '' && ($duress || !isset($_this->scripts[md5($text)]))) {
		
			if($inline) {
				
				echo $text;
			
			} else {
				switch(getCmsVersion()) {
					case CMS_JOOMLA15:
						global $mainframe;
						$mainframe->addCustomHeadTag($text);				
					break;
					case CMS_JOOMLA10:					
					case CMS_MAMBO46:
						if(defined('MVC_FRAMEWORK_ADMIN')) {
							echo $text;
						} else {
							global $mainframe;
							$mainframe->addCustomHeadTag($text);				
						}
					break;						
				}
			}
			
			$_this->scripts[md5($text)] = true;
		}
	}
	
	function getCharset() {
		
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA15:
			case CMS_MAMBO46:				
											   
				return 'UTF-8';
			break;
			
			case CMS_JOOMLA10:

				return substr_replace(_ISO, '', 0, 8);			
			break;		
		}						
		
	}
	
	function &getCache($group='')
	{	
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10:
			case CMS_MAMBO46:				
											   
				return mosCache::getCache($group);
			break;
			
			case CMS_JOOMLA15:

				return JFactory::getCache($group);

			break;		
		}		
	}
		
	function cleanCache($group=false)
	{
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10:
			case CMS_MAMBO46:				
											   
				mosCache::cleanCache($group);
			break;
			
			case CMS_JOOMLA15:

				$cache =& JFactory::getCache($group);
				$cache->clean($group);

			break;		
		}				
	}	
	
	function getConfig($var, $default = null) {
		
		# Will need to add a conversion table for configuration variable names once they start differing between CMSs

		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10:
			case CMS_MAMBO46:				
				
				global $mainframe;
				
				$value = $mainframe->getCfg($var);
			
				 if(!isset($value)){
				      $value = $default;
				 }
				   
				return $value;				
			break;
			
			case CMS_JOOMLA15:
				
			    $cmsConfig = RegisterClass::getInstance('JConfig');

			    if(isset($cmsConfig->{$var})){
			      return $cmsConfig->{$var};
			    } else {
			      return $default;
			    }			 				
			break;		
		}						
		
	}
	
	function getToken($new = true, $app = 'jreviews') {
				
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				if(!isset($_SESSION)) {
					session_start();
				}
								
				if($new) { 
					$token = md5(uniqid(rand(), TRUE));
					$_SESSION[S2Paths::get($app, 'S2_CMSCOMP')]['__Token']['Keys'][] = $token;
					return $token;
				} else {
					$tokenKeys = $_SESSION[S2Paths::get($app, 'S2_CMSCOMP')]['__Token'];
					return $tokenKeys;
				}
				
			break;
			
			case CMS_JOOMLA15:
				
				if($new) {
					
					$token = md5(uniqid(rand(), TRUE));

					$session =& JFactory::getSession();
		
					$tokenKeys = $session->get('__Token', array(),S2Paths::get($app, 'S2_CMSCOMP'));

					$tokenKeys['Keys'][] = $token;
					
					$session->set('__Token',$tokenKeys,S2Paths::get($app, 'S2_CMSCOMP'));
										
					return $token;
					
				} else {
					
					$session =& JFactory::getSession();
					$tokenKeys = $session->get('__Token', array(), S2Paths::get($app, 'S2_CMSCOMP'));
					return $tokenKeys;
				}
			    
			break;						
		}			
		
	}
	
	function removeToken($token, $app = 'jreviews') {
				
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				unset($_SESSION[S2Paths::get($app, 'S2_CMSCOMP')]['__Token']['Keys'][array_search($token,$_SESSION[S2Paths::get($app, 'S2_CMSCOMP')]['__Token']['Keys'])]);
			break;
			
			case CMS_JOOMLA15:
				
				$tokenKeys = cmsFramework::getToken(false);
				$session =& JFactory::getSession();
				unset($tokenKeys['Keys'][array_search($token,$tokenKeys['Keys'])]);
				$session->set('__Token',$tokenKeys,S2Paths::get($app, 'S2_CMSCOMP'));		
			    
			break;						
		}					
	}
	
	function localDate($date = 'now', $offset = null, $format = 'M d Y H:i:s') {
		
		if(is_null($offset)) {
			$offset = cmsFramework::getConfig('offset')*3600;
		} else {
			$offset = 0;
		}
		
		if($date == 'now') {
			
			switch(getCmsVersion()) 
			{
				case CMS_JOOMLA10: 
				case CMS_MAMBO46:
					$date = strtotime(date($format));
				break;
				
				case CMS_JOOMLA15:
					$date = strtotime(gmdate($format, time()));
				break;
			}
			
		} else {
			$date = strtotime($date);
		}
		
		$date = $date + $offset;
		
		$date = date($format, $date);
		
		return $date;		
	}
	
	function language() {
		    		
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				global $mosConfig_lang;
				return $mosConfig_lang;
			break;
			
			case CMS_JOOMLA15:
			    $lang	= & JFactory::getLanguage();
			    return $lang->getBackwardLang();
			break;						
		}	    
	}
	
	function locale() 
	{
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				global $mosConfig_lang,$mosConfig_locale;
				$locale = substr($mosConfig_lang,0,3); 
			break;
			
			case CMS_JOOMLA15:
			    $lang	= & JFactory::getLanguage();
			    $locale = $lang->getLocale();
			    $locale = low(str_replace('_','-',current(explode('.',$locale[0]))));
			break;						
		}
		$parts = explode('-',$locale);
		if(count($parts)>1 && $parts[0]==$parts[1]) {
			return $parts[0];
		}
		else return $locale;
	}
	
	function listImages( $name, &$active, $javascript=NULL, $directory=NULL ) {
		    		
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				return mosAdminMenus::Images( $name, $active, $javascript, $directory);
			break;
			
			case CMS_JOOMLA15:
				return JHTML::_('list.images', $name, $active, $javascript, $directory);
			break;						
		}	    
	}
	
	function listPositions( $name, $active=NULL, $javascript=NULL, $none=1, $center=1, $left=1, $right=1, $id=false ) {
		    		
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				return mosAdminMenus::Positions( $name, $active, $javascript, $none, $center, $left, $right, $id);
			break;
			
			case CMS_JOOMLA15:
				return JHTML::_('list.positions', $name, $active, $javascript, $none, $center, $left, $right, $id);
			break;						
		}	    
	}
	
	/**
	 * Check for Joomla/Mambo sef status
	 *
	 * @return unknown
	 */
	function mosCmsSef() {
		if(!function_exists('sefencode') && (getCmsVersion() == CMS_JOOMLA10 || getCmsVersion() == CMS_MAMBO46) && cmsFramework::getConfig('sef')) {
			return true;
		} else {
			return false;
		}
	}		
	
	function meta($type,$text) {
			
		if($text == '') {
			return;
		}
		
		global $mainframe;

		switch($type) {
			case 'title':
				switch(getCmsVersion()) 
				{		
					case CMS_JOOMLA10: 
					case CMS_MAMBO46:
						$mainframe->setPageTitle($text);				
					break;
					case CMS_JOOMLA15:
						$document =& JFactory::getDocument();
						$document->setTitle($text);
					break;
				}				
				break;
			
			case 'keywords':
			case 'description':	
				switch(getCmsVersion()) 
				{		
					case CMS_JOOMLA10: 
					case CMS_MAMBO46:
						$mainframe->prependMetaTag($type,strip_tags($text));				
					break;
					case CMS_JOOMLA15:
						$document = & JFactory::getDocument();
						if($type == 'description') {
							$document->description = strip_tags($text);
						} else {
							$document->setMetaData($type,strip_tags($text));
						}
					break;
				}			
				break;			
		}
		
	}
			
	
	function noAccess() {
		
		switch(getCmsVersion()) {

			case CMS_JOOMLA10:
			case CMS_MAMBO46:
				mosNotAuth();
			break;
			
			case CMS_JOOMLA15:
				echo JText::_('ALERTNOTAUTH');
			break;						
		}
	}
	
	function formatDate($date) {
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				return mosFormatDate($date);
			break;
			
			case CMS_JOOMLA15:
				JHTML::_('date', $date );
			break;						
		}		
	}
	
	/**
	 * Different function names used in different CMSs
	 *
	 * @return unknown
	 */
	function reorderList() {
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				return 'updateOrder';
			break;
			
			case CMS_JOOMLA15:
				return 'reorder';
			break;						
		}			
	}
	
	function redirect($url,$msg = '') {

		$url = str_replace('&amp;','&',$url);
		
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				mosRedirect($url,$msg);
			break;
			
			case CMS_JOOMLA15:
				global $mainframe;
				$mainframe->redirect($url,$msg);
			break;						
		}		
	}
			
	function route($link) {

		if(false===strpos($link,'index.php') && false===strpos($link,'index2.php')) {

			if(defined('MVC_FRAMEWORK_ADMIN')) {
				$link = 'index2.php?'.$link;
			} else {
				$link = 'index.php?'.$link;
			}
		}

		// Check core sef
		$sef = cmsFramework::getConfig('sef');
		$sef_rewrite = cmsFramework::getConfig('sef_rewrite');
		
		// Check sh404sef
/*		$shsef = false;
		if(class_exists(('shRouter'))) {
			$sefConfig = shRouter::shGetConfig();
			if ($sefConfig->Enabled) { 
				$shsef = true;
			}	
		}	*/	
						
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				
				if(false===strpos($link,'option=com_jreviews')) {
					return sefRelToAbs($link);
				}
				
				if(defined('MVC_FRAMEWORK_ADMIN')) {
					if(false!==strpos($link,'http')) {
						return $link;
					} else {
						// Remove any double slashes
						$link = str_replace('//','/',$link);						
						$link = str_replace('index.php','index2.php',$link);
						return WWW_ROOT . 'administrator/' . $link;
					}
				}
				
				if(!$sef) {
					if(false!==strpos($link,'http')) {
						return $link;
					} else {
						return WWW_ROOT . $link;
					}					
				}

/*				if(cmsFramework::mosCmsSef() && strstr($link,'url=')) 
				{
					// Remove last forward slash
					if($link{strlen($link)-1} == '/') {
						$link = substr($link,0,strlen($link)-1);
					}

					// Fix url encoding issues
					$url_pos = strpos($link,'url=');
					$url_part_before = substr($link,0,$url_pos+4);
					$url_part_after = urlencode(urldecode(substr($link,$url_pos+4)));
					$link = $url_part_before.$url_part_after;						
				}
*/				
				
				// Remove any double slashes
				$link = str_replace('//','/',$link);				
				
				if($sef) {
					// Remove last forward slash
					if($link{strlen($link)-1} == '/') {
						$link = substr($link,0,strlen($link)-1);
					}									
					
					if(substr($link,0,10)==='index.php'){
						$link = str_replace('/','%2F',$link);
					}
				} else {
					if(false!==strpos($link,'http')) {
						return $link;
					} else {
						return WWW_ROOT . $link;
					}					
				}

				// Core sef doesn't know how to deal with colons, so we convert them to something else and then replace them again.
				$link = str_replace(_PARAM_CHAR,'*@*',$link);
				$sefUrl = sefRelToAbs($link);
				$sefUrl = str_replace('%2A%40%2A',_PARAM_CHAR,$sefUrl); 
				$sefUrl = str_replace('*@*',_PARAM_CHAR,$sefUrl); // For non sef links
				return $sefUrl;
			break;
			
			case CMS_JOOMLA15:
				if(defined('MVC_FRAMEWORK_ADMIN')) {
					if(false!==strpos($link,'http')) {
						return $link;
					} else {
						// Remove any double slashes
						$link = str_replace('//','/',$link);	
						$link = str_replace('index.php','index2.php',$link);
						return WWW_ROOT . 'administrator/' . $link;
					}
				}

				if(false===strpos($link,'option=com_jreviews') && !$sef) {					
					$url = JRoute::_($link);
					if(false === strpos($url,'http')) {
						$url = WWW_ROOT . ltrim(substr($url,strpos($url,'/index')),'/');
					}
					return $url;
				} elseif (false===strpos($link,'option=com_jreviews')) {					
					$url = JRoute::_($link);    
					if(false === strpos($url,'http')) {
						$parsedUrl = parse_url(WWW_ROOT);
						$url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $url;
					}
					return $url;
				} 
				
				// Fixes component menu urls with pagination and ordering parameters when core sef is enabled.
				$link = str_replace('//','/',$link);

				if($sef) {
					// Remove last forward slash
/*					if($link{strlen($link)-1} == '/') {
						$link = substr($link,0,strlen($link)-1);
					}
*/        
					
					if(substr($link,0,10)==='index.php'){
						$link = str_replace('/','%2F',$link);
					}
				} else {
					if(false!==strpos($link,'http')) {
						return $link;
					} else {
						return WWW_ROOT . $link;
					}					
				}

				// Core sef doesn't know how to deal with colons, so we convert them to something else and then replace them again.
				$link = str_replace(_PARAM_CHAR,'*@*',$link);
				$sefUrl = JRoute::_($link);
				$sefUrl = str_replace('%2A%40%2A',_PARAM_CHAR,$sefUrl); 
				$sefUrl = str_replace('*@*',_PARAM_CHAR,$sefUrl); // For non sef links
				return $sefUrl;
			break;						
		}		
	}
		
	function constructRoute($passedArgs,$excludeParams = null, $app = 'jreviews') {

		$segments = '';
		$url_param = array();

		if(defined('MVC_FRAMEWORK_ADMIN')) {
			$base_url = 'index2.php?option='.S2Paths::get($app, 'S2_CMSCOMP');					
		} else {
			$Itemid = Sanitize::getInt($this->params,'Itemid') > 0 ? Sanitize::getInt($this->params,'Itemid') : '';
			$base_url = 'index.php?option='.S2Paths::get($app, 'S2_CMSCOMP').'&amp;Itemid=' . $Itemid;
		}
		
		// Get segments without named params
		if(isset($passedArgs['url'])) {
			$parts = explode('/',$passedArgs['url']);
			foreach($parts AS $bit) {
				if(false===strpos($bit,_PARAM_CHAR)) {
					$segments[] = $bit;
				}
			}
		} else {
			$segments[] = 'menu';
		}
		
		unset($passedArgs['option'], $passedArgs['Itemid'], $passedArgs['url']);
		if(is_array($excludeParams)) {
			foreach($excludeParams AS $exclude) {
				unset($passedArgs[$exclude]);		
			}
		}
		
		foreach($passedArgs AS $paramName=>$paramValue) {
			if(is_string($paramValue)){
				$url_param[] = $paramName . _PARAM_CHAR . urlencodeParam($paramValue);
			}
		}		
		
		$new_route = $base_url . '&amp;url=' . implode('/',$segments) . '/' . implode('/',$url_param);
		
		return $new_route;	
		
	}		
}