<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ConfigurationController extends MyController {
	
	var $helpers = array('html','form','jreviews');
	
	var $components = array('config');		
	
	var $autoRender = false;

	var $autoLayout = false;
			
	function beforeFilter() 
    {
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}
	
	function index() {

	    $this->name = 'configuration';
        $themes_config = $themes_fallback = array();
	    	
        $Configure =  &Configure::getInstance('jreviews');
        $App = &App::getInstance('jreviews');
        $ThemeArray = $App->jreviewsPaths['Theme'];

        foreach($ThemeArray AS $theme_name=>$files)
        {            
            if(!isset($themes_config[$theme_name]) && isset($files['.info']) && $files['.info']['configuration'] == 1/* && $files['.info']['mobile'] == 0*/)
                $themes_config[$theme_name] = $files['.info']['title'] . ' (' . $files['.info']['location'] . ')';

            if(!isset($themes_mobile[$theme_name]) && isset($files['.info']) && $files['.info']['configuration'] == 1/* && $files['.info']['mobile'] == 1*/) 
                $themes_mobile[$theme_name] = $files['.info']['title'] . ' (' . $files['.info']['location'] . ')';                                       

            if(!isset($themes_fallback[$theme_name]) && isset($files['.info']) && $files['.info']['fallback'] == 1)
                $themes_fallback[$theme_name] = $files['.info']['title'] . ' (' . $files['.info']['location'] . ')';
                
            if($files['.info']['mobile'] == 1) {
                $themes_config[$theme_name] = $themes_config[$theme_name] . ' -mobile';
                $themes_mobile[$theme_name] = $themes_mobile[$theme_name] . ' -mobile';
            }    

            $themes_description[$theme_name] = $files['.info']['description'];
        }

        unset($ThemeArray);
        unset($App);
        
		$this->set(
			array(
				'stats'=>$this->stats,
				'version'=>$this->Config->version,
				'Config'=>$this->Config,
				'themes_config'=>$themes_config,
                'themes_mobile'=>empty($themes_mobile) ? array(''=>'No theme available') : $themes_mobile,
                'themes_fallback'=>$themes_fallback,
                'themes_description'=>$themes_description
			)
		);
        
        return $this->render();
		
	}	

	function _save()
	{	
		$formValues = $this->params['form'];

		// Fix single quote sql insert error
		if (isset($formValues['location_places'])) 
        {
			$formValues['location_places'] = str_replace("'","&apos;",@$formValues['location_places']);
		}
		
		if (isset($formValues['task']) && $formValues['task'] != "access") 
		{
			$formValues['rss_title'] = str_replace("'",' ',$formValues['rss_title']);
			$formValues['rss_description'] = str_replace("'",' ',$formValues['rss_description']);;
		}
		
		if(isset($formValues['ranks_rebuild_interval'])) {
            $formValues['ranks_rebuild_interval'] = (int) $formValues['ranks_rebuild_interval'];
        }
		
		// bind it to the table
		$this->Config->bindRequest($formValues);

        //Convert array settings to comma separated list
        $keys = array_keys($formValues);
		if (isset($formValues['task']) && $formValues['task'] == "access") 
        {
			$this->Config->moderation_item = in_array('moderation_item',$keys) ? implode(',',$formValues['moderation_item']) : 'none';
			$this->Config->editaccess = in_array('editaccess',$keys) ? implode(',',$formValues['editaccess']) : 'none';
            $this->Config->listing_publish_access = in_array('listing_publish_access',$keys) ? implode(',',$formValues['listing_publish_access']) : 'none';
            $this->Config->listing_delete_access = in_array('listing_delete_access',$keys) ? implode(',',$formValues['listing_delete_access']) : 'none';            
			$this->Config->addnewaccess = in_array('addnewaccess',$keys) ? implode(',',$formValues['addnewaccess']) : 'none';
			$this->Config->addnewmeta = in_array('addnewmeta',$keys) ? implode(',',$formValues['addnewmeta']) : 'none';	
			$this->Config->editaccess_reviews = in_array('editaccess_reviews',$keys) ? implode(',',$formValues['editaccess_reviews']) : 'none';
			$this->Config->addnewaccess_reviews = in_array('addnewaccess_reviews',$keys) ? implode(',',$formValues['addnewaccess_reviews']) : 'none';
			$this->Config->moderation_reviews = in_array('moderation_reviews',$keys) ? implode(',',$formValues['moderation_reviews']) : 'none';
			$this->Config->user_vote_public = in_array('user_vote_public',$keys) ? implode(',',$formValues['user_vote_public']) : 'none';	
            $this->Config->addnewaccess_posts = in_array('addnewaccess_posts',$keys) ? implode(',',$formValues['addnewaccess_posts']) : 'none';
            $this->Config->moderation_posts = in_array('moderation_posts',$keys) ? implode(',',$formValues['moderation_posts']) : 'none';
			$this->Config->addnewwysiwyg = in_array('addnewwysiwyg',$keys) ? implode(',',$formValues['addnewwysiwyg']) : 'none';				
// Discussion
            $this->Config->post_edit_access = in_array('post_edit_access',$keys) ? implode(',',$formValues['post_edit_access']) : 'none';
            $this->Config->post_delete_access = in_array('post_delete_access',$keys) ? implode(',',$formValues['post_delete_access']) : 'none';            
// Owner replies
            $this->Config->moderation_owner_replies = in_array('moderation_owner_replies',$keys) ? implode(',',$formValues['moderation_owner_replies']) : 'none';
        } 
        else 
        {
            $this->Config->security_image = in_array('security_image',$keys) ? implode(',',$formValues['security_image']) : 'none';            
        }	

		$this->Config->store();
	
		return $this->ajaxResponse(array(),false);
	}
}