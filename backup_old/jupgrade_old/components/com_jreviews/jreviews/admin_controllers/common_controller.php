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

class CommonController extends MyController {
	
	var $uses = array('review');
    var $helpers = array('text');
    var $components = array('config');
    var $autoLayout = false;
    var $autoRender = false;    
    	
    function feed()
    {
        if(function_exists('curl_init'))
        {          
            if(!class_exists('SimplePie')) {
                App::import('Vendor','simplepie/simplepie.inc');
            }   
            $feedUrl = "http://www.reviewsforjoomla.com/smf/index.php?board=7.0&type=rss2&action=.xml";    
            $feed = new SimplePie();
            $feed->set_feed_url($feedUrl);
            $feed->enable_cache(true);
            $feed->set_cache_location(PATH_ROOT.'cache');
            $feed->set_cache_duration(3600);
            $feed->init();
            $feed->handle_content_type();
            $items = $feed->get_items();        
            $this->set('items',$items);
            $page = $this->render('about','feed');
        } else {
            $page = 'News feed requires curl';
        }
        echo $page;      
    }
    
    function getVersion()
    {
        $page = '';
        $new_version = 'none';

        $session_var = cmsFramework::getSessionVar('new_version','jreviews');
              
        if(empty($session_var))
        {
            if(function_exists('curl_init'))
            {     
                // Version checker
                $curl_handle = curl_init('http://www.reviewsforjoomla.com/updates_server/files.php');
                curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1); // return instead of echo
                @curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);       
                curl_setopt($curl_handle, CURLOPT_HEADER, 0);
                $data = curl_exec($curl_handle);
                curl_close($curl_handle);
                $current_versions = json_decode($data,true);
                
                $this->Config->updater_betas and isset($current_versions['jreviews']['beta']) and $current_versions['jreviews'] = array_merge($current_versions['jreviews'],$current_versions['jreviews']['beta']);
                
                $remoteVersion = $current_versions['components']['jreviews']['version'];
                $remoteVersion=(int)str_replace('.','',$remoteVersion);
                $localVersion=(int)str_replace('.','',strip_tags($this->Config->version));
                if($remoteVersion > $localVersion)
                {
                    $new_version = 'new';
                }   
            } 
            else 
            {
                $new_version = 'curl';
            } 
            cmsFramework::setSessionVar('new_version',$new_version,'jreviews');
        } else {
            $new_version = $session_var;
        }

        switch($new_version)
        {
            case 'new':
                $page = '<a style="font-weight:normal;font-size:13px;color:red;" href="#updater_version_check" id="updater_notification">'.__a("New version available",true).'</a>'; 
            break;
            case 'curl':
                $page = '<span style="font-weight:normal;font-size:13px;color:red;">Version checker requires curl</span>';
            break;
            default:
                $page = '';
            break;
        } 
        
        return $this->ajaxResponse(array(),false,compact('page'));        
    }
    
	function toggleIcon() 
    {
		$id = Sanitize::getInt($this->data,'id');
		if(!$id) return '{}';
        
        $field = Sanitize::getString($this->data,'column');
		$table = Sanitize::getString($this->data,'table');
		$key = Sanitize::getString($this->data,'key');
		
		$this->_db->setQuery( "SELECT $field FROM `$table` WHERE $key = '$id'"	);

		$state = $this->_db->loadResult();
		
		$state = $state ? 0 : 1;
	
		$this->_db->setQuery( "UPDATE `$table` SET `$field` = '$state' WHERE $key = '$id'" );
	
		if (!$this->_db->query()) 
        {
		    return '{}';
        }
	
        // Clear cache
        clearCache('', 'views');
        clearCache('', '__data');

        return json_encode(array('state'=>$state));
	}
	
	function _rebuildReviewerRanks() 
    {
        return __a($this->Review->rebuildRanksTable() ?
			'The reviewer ranks table was successfully rebuilt.'
			:
			'There was an error rebuilding the reviewer ranks table.'
		, true);
    }
	
	function clearCache() 
    {
		clearCache('', 'views');
		clearCache('', '__data');		
		clearCache('', 'assets');		
        return __a("The cache has been cleared.",true);
	}
    
    function clearFileRegistry() 
    {
        clearCache('', 'core');
        return __a("The file registry been cleared.",true);
    }  	
}