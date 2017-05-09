<?php
/**
 * sh404SEF plugin
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

// ------------------  standard plugin initialize function - don't change ---------------------------
global $sh_LANG, $sefConfig, $shGETVars; 
$shLangName = '';
$shLangIso = '';
$title = array();
$shItemidString = '';
$dosef = shInitializePlugin( $lang, $shLangName, $shLangIso, $option);
if ($dosef == false) return;
// ------------------  standard plugin initialize function - don't change ---------------------------

// ------------------  load language file - adjust as needed ----------------------------------------
//$shLangIso = shLoadPluginLanguage( 'com_XXXXX', $shLangIso, '_SEF_SAMPLE_TEXT_STRING');
// ------------------  load language file - adjust as needed ----------------------------------------
    
// start by inserting the menu element title (just an idea, this is not required at all)
$menu = getMenuTitle($option, null, @$Itemid, null, $shLangName );
$_PARAM_CHAR = '*@*';
$newUrl = '';
$url = isset($url) ? $url : '';
$url = urldecode($url);  // required!

// It's a JReviews Express link
if(isset($url) && $url!='' && strpos($url,'menu')===false)
	{	
		// You can change the value below to anything you want, but it is important to distinguish jrexpress urls
		$title[] =  'reviews';
		 
		$urlParams = explode('/', $url);

		foreach($urlParams as $urlParam) 
		{    
			// Segments
			if (false === strpos($urlParam,$_PARAM_CHAR)) { 
		    	$title[] = rtrim( $urlParam, '/');
		        $newUrl .= $urlParam . '/'; 
		    // Internal to external parameter conversion
		    } else {       
		    	$bits = explode($_PARAM_CHAR,$urlParam);
			    shAddToGETVarsList($bits[0], stripslashes(urldecode($bits[1])));
		    }
		}
	}
else 
	{
// It's a menu link		
		$url = urldecode($url); // 2nd pass urldecode is required

		if($url == '') {
			
			$title[] = $menu;			
		    $newUrl .= $menu . '/';
		    
		} else {
		
			$urlParams = explode('/', $url);
	
			foreach( $urlParams as $urlParam) 
			{
				if($urlParam != '') {
					// Segments		    
					if (false === strpos($urlParam,$_PARAM_CHAR)) {
						$tmpParam = str_replace('menu',$menu,$urlParam);
				    	$title[] =  rtrim($tmpParam , '/');
				        $newUrl .= $tmpParam . '/';
				    // Internal to external parameter conversion			    
				    } else {         
				    	$bits = explode($_PARAM_CHAR,$urlParam); 
					    shAddToGETVarsList($bits[0], stripslashes(urldecode($bits[1])));
				    }
				}
			}
		}
	}

// Trick to force the new url to be saved to the database	
shAddToGETVarsList('url', stripslashes(rtrim($newUrl, '/')));  // this will establish the new value of $url
shRemoveFromGETVarsList('url');  // remove from var list as url is processed, so that the new value of $url is stored in the DB
	
// Home page - there's no query string
if (!isset($url) || (isset($url) && !strpos($url,'menu')))
	{
  		shRemoveFromGETVarsList('Itemid');
	}

shRemoveFromGETVarsList('option');
shRemoveFromGETVarsList('lang');
//shRemoveFromGETVarsList('section');
//shRemoveFromGETVarsList('dir');
  
// ------------------  standard plugin finalize function - don't change ---------------------------  
if ($dosef){
   $string = shFinalizePlugin( $string, $title, $shAppendString, $shItemidString, 
      (isset($limit) ? @$limit : null), (isset($limitstart) ? @$limitstart : null), 
      (isset($shLangName) ? @$shLangName : null));
}      
// ------------------  standard plugin finalize function - don't change ---------------------------