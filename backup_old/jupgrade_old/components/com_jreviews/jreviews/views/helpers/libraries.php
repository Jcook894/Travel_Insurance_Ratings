<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class LibrariesHelper extends MyHelper
{						
	function js()
	{
		$javascriptLibs = array();				
		$javascriptLibs['jreviews'] 			=	'jreviews';
        $javascriptLibs['jquery']               =   'jquery/jquery-1.7.1.min';
        $javascriptLibs['jq.ui.core']           =   'jquery/jquery-ui-1.8.17.custom.min';
//        $javascriptLibs['jq.ui.accordion']      =   'jquery/ui.accordion.min';
		$javascriptLibs['jq.json']			    = 	'jquery/json.min'; 
		$javascriptLibs['jq.jsoncookie']		= 	'jquery/jquery.extjasoncookie-0.2';
		$javascriptLibs['jq.ui.rating']			= 	'jquery/ui.stars';
        $javascriptLibs['jq.scrollable']        =   'jquery/jquery.scrollable.min';
        $javascriptLibs['jq.fancybox']          =   'jquery/jquery.fancybox-1.3.4.pack';
		$javascriptLibs['jq.tooltip'] 			= 	'jquery/jquery.tooltip.min';
		$javascriptLibs['jq.treeview']			= 	'jquery/jquery.treeview.min';		
		$javascriptLibs['jq.jreviews.plugins'] 	= 	'jreviews.jquery.plugins';
        
        if(!isset($this->Config) || empty($this->Config))
        {
            $this->Config = Configure::read('JreviewsSystem.Config');
        }
                
        if($this->Config->libraries_jquery && !defined('MVC_FRAMEWORK_ADMIN'))
        {
            unset($javascriptLibs['jquery']);
        }
         
        if($this->Config->libraries_jqueryui && !defined('MVC_FRAMEWORK_ADMIN'))
        {
            unset($javascriptLibs['jq.ui.core']);
        }   
        
		/*
		$exclude = Configure::read('Libraries.disableJS');

		if(is_array($exclude)){
			foreach($exclude AS $lib){
				if(isset($javascriptLibs[$lib])) unset($javascriptLibs[$lib]);
			}
		}
		 */
		
		return $javascriptLibs;
	}	
	
	function css()
	{
		$styleSheets = array();
        $styleSheets['modules']                 =   'modules';
        $styleSheets['plugins']                 =   'plugins';
		$styleSheets['theme']				 	= 	'theme';
		$styleSheets['theme.directory']	 		= 	'directory';
		$styleSheets['theme.list']		 		= 	'list';
		$styleSheets['theme.detail']		 	= 	'detail';	
        $styleSheets['theme.discussion']        =   'discussion';    
		$styleSheets['theme.form']		 		= 	'form';
		$styleSheets['paginator']				= 	'paginator';         
        $styleSheets['jq.ui.core']              =   'jquery_ui_theme/jquery-ui-1.8.17.custom';
        $styleSheets['jq.fancybox']             =   'fancybox/jquery.fancybox';        
		$styleSheets['jq.treeview'] 			= 	'treeview/jquery.treeview';		

        if(!isset($this->Config) || empty($this->Config))
        {
            $this->Config = Configure::read('JreviewsSystem.Config');
        }
        
        if($this->Config->libraries_jqueryui && !defined('MVC_FRAMEWORK_ADMIN'))
        {
            unset($styleSheets['jq.ui.core']);
        }  

		return $styleSheets;
	}
}