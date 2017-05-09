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

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class LibrariesHelper extends MyHelper
{						
	function js()
	{
		$javascriptLibs = array();				
		$javascriptLibs['jrexpress'] 			=	'jrexpress';
		$javascriptLibs['module'] 				=	'jrexpress.module';
		$javascriptLibs['jquery']				= 	'jquery/jquery-1.2.6.pack';//'jquery/jquery-1.3.1.min';
		$javascriptLibs['ui.core']				= 	'jquery/ui.core';
		$javascriptLibs['ui.rating']			= 	'jquery/rating/ui.stars.pack';
//		$javascriptLibs['jq.dimensions'] 		= 	'jquery/jquery.dimensions'; // required for tooltip
		$javascriptLibs['jq.bigframe']	 		= 	'jquery/jquery.bgiframe'; // required tooltip
		$javascriptLibs['jq.thickbox'] 			= 	'jquery/thickbox/thickbox.min';
		$javascriptLibs['jq.tooltip'] 			= 	'jquery/tooltip/jquery.tooltip.min';
		$javascriptLibs['jq.datepicker'] 		= 	'jquery/datepicker/ui.datepicker.min';
		$javascriptLibs['jq.selectboxes'] 		= 	'jquery/jquery.selectboxes.pack';
		$javascriptLibs['jq.tabs']		 		= 	'jquery/tabs/ui.tabs.pack';
		$javascriptLibs['jq.treeview']			= 	'jquery/treeview/jquery.treeview.pack';		
		$javascriptLibs['jq.jrexpress.plugins'] = 	'jrexpress.jquery.plugins';
		$javascriptLibs['jq.onload'] 			= 	'jquery.onload';

		$exclude = Configure::read('Libraries.disableJS');

		if(is_array($exclude)){
			foreach($exclude AS $lib){
				if(isset($javascriptLibs[$lib])) unset($javascriptLibs[$lib]);
			}
		}
		
		return $javascriptLibs;
	}	
	
	function css()
	{
		$styleSheets = array();
		$styleSheets['theme']				 	= 	'theme';
		$styleSheets['theme.list']		 		= 	'list';
		$styleSheets['theme.detail']		 	= 	'detail';	
		$styleSheets['theme.form']		 		= 	'form';
		$styleSheets['paginator']				= 	'paginator';
		$styleSheets['jq.tabs']					= 	'tabs/ui.tabs';
		$styleSheets['jq.tooltip'] 				= 	'tooltip/jquery.tooltip';
		$styleSheets['ui.rating'] 				= 	'rating/ui.stars';
		$styleSheets['jq.thickbox'] 			= 	'thickbox/thickbox';
		$styleSheets['jq.datepicker'] 			= 	'datepicker/ui.datepicker';
		$styleSheets['jq.treeview'] 			= 	'treeview/jquery.treeview';		
		return $styleSheets;
	}
}