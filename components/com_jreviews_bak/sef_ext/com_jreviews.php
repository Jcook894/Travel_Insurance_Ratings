<?php
/**
 * sh404SEF support for com_jreviews component.
 * Author : ClickFWD LLC
 * contact : support@reviewsforjoomla.com    
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
$url = isset($url) ? urldecode($url) : '';

// It's a JReviews link
if(isset($url) && $url!='' && strpos($url,'menu')===false)
{	
	// You can change the value below to anything you want, but it is important to distinguish jReviews urls
	if(!isset($Itemid)) {
		$title[] =  'reviews';
	} else {
		$title[] = $menu;
	}
	
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

	if($newUrl != '') {
		// Trick to force the new url to be saved to the database	
		shAddToGETVarsList('url', stripslashes(rtrim($newUrl, '/')));  // this will establish the new value of $url
		shRemoveFromGETVarsList('url');  // remove from var list as url is processed, so that the new value of $url is stored in the
	}
	
	$title[] = ''; // Prevents sh404sef from adding the .html at the end of the url for non-menu links
	
} 
else {
// It's a menu link		
	$url = urldecode($url); // 2nd pass urldecode is required

	if($url == '') {

		$title[] = $menu;			

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
		
		shAddToGETVarsList('url','menu');
		shRemoveFromGETVarsList('url');
	}
}

if(isset($page)) {
	shAddToGETVarsList('page',$page);
}

// Home page - there's no query string
if((isset($Itemid) && $Itemid != '')) {
	shRemoveFromGETVarsList('Itemid');
}
else {
	unset($shGETVars['Itemid']);
}

shRemoveFromGETVarsList('option');

shRemoveFromGETVarsList('lang');

unset($shGETVars['view']); // This param is not required for JReviews

//shRemoveFromGETVarsList('section');
//shRemoveFromGETVarsList('dir');

// ------------------  standard plugin finalize function - don't change ---------------------------  
	
if ($dosef){
   $string = shFinalizePlugin( $string, $title, $shAppendString, $shItemidString, 
      (isset($limit) ? @$limit : null), (isset($limitstart) ? @$limitstart : null), 
      (isset($shLangName) ? @$shLangName : null));
}      
// ------------------  standard plugin finalize function - don't change ---------------------------