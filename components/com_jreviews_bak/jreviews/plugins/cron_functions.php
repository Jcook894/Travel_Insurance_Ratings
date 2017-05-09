<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CronFunctionsComponent extends S2Component {
    
    var $plugin_order = 1;
    
    var $name = 'cron_functions';
    
    var $published = true;
    
    var $c;
    
    function startup(&$controller)
    {     
        if(!isset($controller->Config) || $controller->ajaxRequest || Sanitize::getString($controller->params,'action') == 'xml')  return;

        $this->c = &$controller;
        
        $this->cacheCleaner();
        
        $this->rebuildRankTable();
    }     
    
    /**
    * Cleans the JReviews cache
    * 
    */
    function cacheCleaner() 
    {               
        if(isset($this->c->Config->cache_cleanup) && $this->c->name != 'about') {
            $last_clean = Sanitize::getInt($this->c->Config,'last_cache_clean');
            $now = time();
            if($last_clean == 0 || ($now - $last_clean) > Sanitize::getInt($this->c->Config,'cache_cleanup'))
            {        
                $this->c->Config->store(array('last_cache_clean'=>$now));  
                clearCache('', 'views');
                clearCache('', '__data');        
            }
        }
    }
    
    /**
    * Rebuilds the reviewer rank table
    * 
    */
    function rebuildRankTable()
    {                 
        if(in_array($this->c->name,array('about','categories','com_content')) && $this->c->action != 'com_content_blog' && isset($this->c->Config->ranks_rebuild_last))
        {
            if( $this->c->Config->ranks_rebuild_last + $this->c->Config->ranks_rebuild_interval * 3600 <= time() ): // Update ranks table periodically 
            ?>
            <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready(function() { jreviews.review.rebuildRanksTable(); });       
            //]]>        
            </script> 
            <?php  endif;
        }
    }         
}
