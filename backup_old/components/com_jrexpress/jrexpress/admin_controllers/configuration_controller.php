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

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ConfigurationController extends MyController {
	
	var $helpers = array('html','form','jreviews');
	
	var $components = array('config');		
	
	var $autoRender = true;

	var $autoLayout = true;
			
	function beforeFilter() {

		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
		
	}
	
	function index() {

	    $this->name = 'configuration';
	    				
		$themes = array();
		$themes_paths[] = array();
	    $theme_paths[] = S2Paths::get($this->app,'S2_THEMES');
	    $theme_paths[] = S2Paths::get($this->app,'S2_THEMES_OVERRIDES');

	    foreach($theme_paths AS $theme_path)
	    {
			$templatefolder = @dir( $theme_path );
		
			if ($templatefolder) 
			{
				while ($templatefile = $templatefolder->read()) {
					
					if ($templatefile != "." && $templatefile != ".." && is_dir( "$theme_path/$templatefile" )  ) {
						
						$templatename = $templatefile;
	
						$themes[$templatename] = array('value'=>$templatefile, 'text'=>$templatename);
					}
				}
				
				$templatefolder->close();
			}
	    }

		sort($themes);

		$this->set(
			array(
				'stats'=>$this->stats,
				'version'=>$this->Config->version,
				'Config'=>$this->Config,
				'themes'=>$themes
			)
		);
		
	}	

	function _save($params)
	{	
		$formValues = array_shift($params);
				
		$xajax = new xajaxResponse();

		// Fix single quote sql insert error
		if (isset($formValues['location_places'])) {
			$formValues['location_places'] = str_replace("'","&apos;",@$formValues['location_places']);
		}
		
		
		if (isset($formValues['task']) && $formValues['task'] != "access" && isset($formValues['rss_title'])) 
		{
			$formValues['rss_title'] = str_replace("'",' ',$formValues['rss_title']);
			$formValues['rss_description'] = str_replace("'",' ',$formValues['rss_description']);;
		}	
		
		// bind it to the table
		$this->Config->bindRequest($formValues);

		if (isset($formValues['task']) && $formValues['task'] == "access") {
			//Convert array settings to comma separated list
			$keys = array_keys($formValues);
			
			if(is_array($formValues['security_image'][0])) 
			{ // Required for xajax Beta and earlier - compat with xajax plugin	
				$this->Config->security_image = in_array('security_image',$keys) ? implode(',',$formValues['security_image'][0]) : 'none';
				$this->Config->moderation_item = in_array('moderation_item',$keys) ? implode(',',$formValues['moderation_item'][0]) : 'none';
				$this->Config->editaccess = in_array('editaccess',$keys) ? implode(',',$formValues['editaccess'][0]) : 'none';
				$this->Config->addnewaccess = in_array('addnewaccess',$keys) ? implode(',',$formValues['addnewaccess'][0]) : 'none';
				$this->Config->addnewmeta = in_array('addnewmeta',$keys) ? implode(',',$formValues['addnewmeta'][0]) : 'none';
		
				$this->Config->editaccess_reviews = in_array('editaccess_reviews',$keys) ? implode(',',$formValues['editaccess_reviews'][0]) : 'none';
				$this->Config->addnewaccess_reviews = in_array('addnewaccess_reviews',$keys) ? implode(',',$formValues['addnewaccess_reviews'][0]) : 'none';
				$this->Config->moderation_reviews = in_array('moderation_reviews',$keys) ? implode(',',$formValues['moderation_reviews'][0]) : 'none';
				$this->Config->user_vote_public = in_array('user_vote_public',$keys) ? implode(',',$formValues['user_vote_public'][0]) : 'none';
		
				$this->Config->addnewwysiwyg = in_array('addnewwysiwyg',$keys) ? implode(',',$formValues['addnewwysiwyg'][0]) : 'none';
				
			} else {
				$this->Config->security_image = in_array('security_image',$keys) ? implode(',',$formValues['security_image']) : 'none';
				$this->Config->moderation_item = in_array('moderation_item',$keys) ? implode(',',$formValues['moderation_item']) : 'none';
				$this->Config->editaccess = in_array('editaccess',$keys) ? implode(',',$formValues['editaccess']) : 'none';
				$this->Config->addnewaccess = in_array('addnewaccess',$keys) ? implode(',',$formValues['addnewaccess']) : 'none';
				$this->Config->addnewmeta = in_array('addnewmeta',$keys) ? implode(',',$formValues['addnewmeta']) : 'none';
		
				$this->Config->editaccess_reviews = in_array('editaccess_reviews',$keys) ? implode(',',$formValues['editaccess_reviews']) : 'none';
				$this->Config->addnewaccess_reviews = in_array('addnewaccess_reviews',$keys) ? implode(',',$formValues['addnewaccess_reviews']) : 'none';
				$this->Config->moderation_reviews = in_array('moderation_reviews',$keys) ? implode(',',$formValues['moderation_reviews']) : 'none';
				$this->Config->user_vote_public = in_array('user_vote_public',$keys) ? implode(',',$formValues['user_vote_public']) : 'none';
		
				$this->Config->addnewwysiwyg = in_array('addnewwysiwyg',$keys) ? implode(',',$formValues['addnewwysiwyg']) : 'none';				
			}
		}	

		$this->Config->store();
	
		$xajax->assign('status',"innerHTML", "The new settings have been saved." );

		$xajax->script("jQuery('#status').fadeIn();");
		$xajax->script("setTimeout(function() {jQuery('#status').fadeOut();}, 1500);");
				
		return $xajax;
		
	}
}

?>