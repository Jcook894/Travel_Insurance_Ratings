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

class MyController extends S2Controller {
		
	function beforeFilter() {
		
		# Init Access
		if(isset($this->Access))
			$this->Access->init($this->Config);

		// Dynamic Community integration loading
		$community_extension = Configure::read('Community.extension');
		$community_extension = $community_extension != '' ? $community_extension : 'community_builder';
		
        App::import('Model',$community_extension,'jrexpress');
		$this->Community = new CommunityModel();
		
		# Set Theme	
		$this->viewTheme = $this->Config->template;
		$this->viewImages = S2Paths::get('jrexpress', 'S2_THEMES_URL') . 'default' . _DS . 'theme_images' . _DS;		
			
		# Set template type for lists and template suffix
		$this->__initTemplating();

		# Set pagination vars
		// First check url, then menu parameter. Otherwise the limit list in pagination doesn't respond b/c menu params always wins
		$this->limit = Sanitize::getInt($this->params,'limit',Sanitize::getInt($this->data,'limit_special'));
//		$this->passedArgs['limit'] = $this->limit;

		$this->page = Sanitize::getInt($this->data,'page',Sanitize::getInt($this->params,'page',1));

		if(!$this->limit) {
	 		if(Sanitize::getVar($this->params,'action')=='myreviews') {
				$this->limit = Sanitize::getInt($this->params,'limit',$this->Config->user_limit);						
			} else {
				$this->limit = Sanitize::getInt($this->params,'limit',$this->Config->list_limit);			
			}
		} 
        
        // Set a hard code limit to prevent abuse
        $this->limit = min($this->limit, 50);

		// Need to normalize the limit var for modules
		if(isset($this->params['module'])) {
			$module_limit = Sanitize::getInt($this->params['module'],'module_limit',5);
		} else {
			$module_limit = 5;
		}

		$this->module_limit = Sanitize::getInt($this->data,'module_limit',$module_limit);
		$this->module_page = Sanitize::getInt($this->data,'module_page',1);
		$this->module_page = $this->module_page === 0 ? 1 : $this->module_page;
		$this->module_offset = (int)($this->module_page-1) * $this->module_limit;	
		if($this->module_offset < 0) $this->module_offset = 0;
		
		$this->page = $this->page === 0 ? 1 : $this->page;
		
		$this->offset = (int)($this->page-1) * $this->limit;
		
		if($this->offset < 0) $this->offset = 0;
		
		# Add global javascript variables
		if(!defined('MVC_GLOBAL_JS_VARS') && !$this->xajaxRequest) 
		{
			cmsFramework::addScript('<script type="text/javascript">var xajaxUri = "'.getXajaxUri('jrexpress').'"</script>');	

			$javascriptcode = '<script type="text/javascript">%s</script>';
			
			# Set thicbox loading image
			cmsFramework::addScript(sprintf($javascriptcode,'var tb_pathToImage = "'.$this->viewImages.'loadingAnimation.gif";'));
		
			# Set calendar image
			cmsFramework::addScript(sprintf($javascriptcode,'var datePickerImage = "'.$this->viewImages.'calendar.gif";'));
			
			define('MVC_GLOBAL_JS_VARS',1);			
		}

	}

	function __initTemplating() {
				
		$tmpl_list = null;
		$tmpl_suffix = '';

		// If tmpl_list is set we use that, otherwise we check the menu parameters
		$this->data['tmpl_list'] = Sanitize::getString($this->data,'tmpl_list') ? Sanitize::getString($this->data,'tmpl_list') : Sanitize::getString($this->data,'listview');
		
		if($list = Sanitize::getString($this->data,'tmpl_list',Sanitize::getString($this->params,'tmpl_list'))) {

			$tmpl_list = $this->__listTypeConversion($list);
		
		}
		
		if(Sanitize::getVar($this->params,'module')) {
			$this->params['tmpl_suffix'] = Sanitize::getString($this->params['module'],'tmpl_suffix');
		}

		if($suffix = Sanitize::getString($this->data,'tmpl_suffix',Sanitize::getString($this->params,'tmpl_suffix'))) {

			$tmpl_suffix = $suffix;
		
		} 

        if(isset($this->params['module'])){
            
            $task = 'module';
            
        } elseif($this->name == 'categories') {

			$task = $this->action;
		
		} else {

			$task = $this->name;
		
		}

		switch($task) {
			case 'com_content':		
			case 'category':		
						
					$dbSettings = $this->Category->getTemplateSettings(Sanitize::getInt($this->params,'cat'));

					$this->__setTemplateVars($dbSettings, $tmpl_list, $tmpl_suffix);
				
				break;
				
			case 'section':
					$dbSettings = $this->Section->getTemplateSettings(Sanitize::getInt($this->params,'section'));

					$this->__setTemplateVars($dbSettings, $tmpl_list, $tmpl_suffix);				
				
				break;
				
			case 'mylistings':
				
				$this->viewSuffix = '';
				
				if(file_exists(S2_THEMES . $this->Config->template . DS . 'listings' . DS . 'listings_mylistings.ctp')
					|| file_exists(S2_THEMES . 'default' . DS . 'listings' . DS . 'listings_mylistings.ctp'))
				{

					$this->tmpl_list = 'mylistings';
				
				} else {

					$this->tmpl_list = $this->__listTypeConversion($this->Config->list_display_type);				
				
				}
				
				break;
				
			case 'favorites':

				$this->viewSuffix = '';
												
				if(file_exists(S2_THEMES . $this->Config->template . DS . 'listings' . DS . 'listings_favorites.ctp')
					|| file_exists(S2_THEMES . 'default' . DS . 'listings' . DS . 'listings_favorites.ctp'))
				{

					$this->tmpl_list = 'favorites';
				
				} else {

					$this->tmpl_list = $this->__listTypeConversion($this->Config->list_display_type);				
				
				}
				
				break;
							
			case 'alphaindex':				

				$menu_params = $this->Menu->get('menuParams'.Sanitize::getInt($this->params,'Itemid'));
				
				$this->tmpl_suffix = Sanitize::getVar($menu_params,'tmpl_suffix');
				
				$tmpl_list = $this->__listTypeConversion(Sanitize::getVar($menu_params,'listview'));
			
				if($tmpl_list != '' ) {
					$this->tmpl_list = $tmpl_list;
				} else {
					$this->tmpl_list = 	$this->tmpl_list = $this->__listTypeConversion($this->Config->list_display_type);
				}
				
			break;		
				
			case 'directories':
			case 'featured':
			case 'toprated':
			case 'topratedauthor':
			case 'latest':
			case 'popular':
			case 'mostreviews':					

					$this->viewSuffix = $tmpl_suffix;

					if($tmpl_list) {
						
						$this->tmpl_list = $tmpl_list;
					
					} else {

						$this->tmpl_list = $this->__listTypeConversion($this->Config->list_display_type);
					}	

				break;	
				
            case 'list':
			case 'search':
				
				$this->tmpl_list = $this->__listTypeConversion($this->Config->search_display_type);

                $this->viewSuffix = Sanitize::getString($this->params,'tmpl_suffix',$this->Config->search_tmpl_suffix);
				
				break;
				
			case 'listings':

				switch($this->action) {
					
					case '_loadForm':
						
						$dbSettings = $this->Category->getTemplateSettings(Sanitize::getInt($this->data['Listing'],'catid'));
	
						$this->__setTemplateVars($dbSettings, $tmpl_list, $tmpl_suffix);						
						
						break;
						
					case 'edit':		

						$dbSettings = $this->Listing->getTemplateSettings((int)$this->params['id']);

						$this->__setTemplateVars($dbSettings, $tmpl_list, $tmpl_suffix);
						
						break;	
						
					case 'create':

						$this->viewSuffix = $tmpl_suffix;
						
						break;	
					
				}
				
				break;
			
			case 'reviews':
				
				switch($this->action) {
					
					case '_save':

						$dbSettings = $this->Listing->getTemplateSettings((int)$this->data['Review']['pid']);

						$this->__setTemplateVars($dbSettings, $tmpl_list, $tmpl_suffix);				

						break;
						
					case 'edit':

						$review_id = Sanitize::getInt($this->params,'id');

						$dbSettings = $this->Review->getTemplateSettings($review_id);

						$this->__setTemplateVars($dbSettings, $tmpl_list, $tmpl_suffix);				
					
						break;	
				}

				break;
				
			default:
					$this->tmpl_list = $this->__listTypeConversion($this->Config->list_display_type);
					$this->viewSuffix = $tmpl_suffix;
				break;		
					
		}

	}
	
	function __listTypeConversion($type) {
		
		switch($type) {
			case null:
				return null;
				break;
			case 0:
				return 'tableview';
				break;
			case 1:
				return 'blogview';
				break;
			case 2:
				return 'thumbview';
				break;
			default:
				return null;
				break;	
		}		
		
	}
	
	function __setTemplateVars($dbSettings, $tmpl_list, $tmpl_suffix) {
				
		# Set template type
		if(!is_null($tmpl_list) && $tmpl_list != '') {

			$this->tmpl_list = $tmpl_list;
		
		} else {
			
			if(isset($dbSettings['Category']['tmpl_list']) && $dbSettings['Category']['tmpl_list'] != '') {
				
				$this->tmpl_list = $dbSettings['Category']['tmpl_list'];
			
			} elseif(isset($dbSettings['Section']['tmpl_list']) && $dbSettings['Section']['tmpl_list'] != '') {
				
				$this->tmpl_list = $dbSettings['Section']['tmpl_list'];				
				
			} else {
				
				$this->tmpl_list = $this->__listTypeConversion($this->Config->list_display_type);
			}
				
		}
		
		# Set template suffix
		if(!is_null($tmpl_suffix) && $tmpl_suffix != '') {

			$this->viewSuffix = $tmpl_suffix;
		
		} else {
			
			if(isset($dbSettings['Category']['tmpl_suffix']) && $dbSettings['Category']['tmpl_suffix'] != '') {
				
				$this->viewSuffix = $dbSettings['Category']['tmpl_suffix'];
			
			} elseif(isset($dbSettings['Section']['tmpl_suffix']) && $dbSettings['Section']['tmpl_suffix'] != '') {
				
				$this->viewSuffix = $dbSettings['Section']['tmpl_suffix'];				
				
			}

		}		
	}

}