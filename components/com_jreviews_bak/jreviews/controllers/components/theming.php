<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ThemingComponent extends S2Component 
{
    var $c;
    var $ignored_controllers = array(
        'categories'=>array(),
        'com_content'=>array(),
        'listings'=>array(
            'edit',
            '_loadForm'
        )
    );
    
	function startup(&$controller) 
    {       
        $this->c = & $controller;
        
        # Set Theme    
        $controller->viewTheme = $controller->Config->template;
        $this->mobileDetect();
        
        $controller->viewImages = S2Paths::get('jreviews', 'S2_THEMES_URL') . Sanitize::getString($controller->Config,'fallback_theme') . _DS . 'theme_images' . _DS;        

        # Dynamic theme setup 
        if(
            (isset($this->ignored_controllers[$controller->name]) && empty($this->ignored_controllers[$controller->name]))
            || 
            (isset($this->ignored_controllers[$controller->name]) && in_array($controller->action,$this->ignored_controllers[$controller->name]))
        ) {
             return;
        }          
        $this->setSuffix();
    }
    
    function mobileDetect() 
    {
        $mobile_theme = Sanitize::getString($this->c->Config,'mobile_theme');
        
        if($mobile_theme == '') return;

        if(!Configure::read('System.mobileDetect'))
        {
            if(App::import('Vendor','mobile_detect' . DS . 'Mobile_Detect'))
            {
                $detect = new Mobile_Detect();
                if ($detect->isMobile() && !$detect->isIpad()) { // It is mobile
                    Configure::write('System.isMobile',true);
                    $this->c->viewTheme = $mobile_theme;
                }
                else { // Not mobile
                    Configure::write('System.isMobile',false);
                }
                Configure::write('System.mobileDetect',true);
            }
        }
        elseif(Configure::read('System.isMobile')) {
            $this->c->viewTheme = $mobile_theme;
        }
    }
    
    /**
    * Sets the correct view suffix
    * 
    * @param mixed $categories
    */
    public function setSuffix($options = array())
    {        
        switch($this->c->action)
        {
            case 'search':
                $this->c->viewSuffix = Sanitize::getString($this->c->params,'tmpl_suffix',$this->c->Config->search_tmpl_suffix);
            break;
        }
        
        # Find cat id
        if($listing_id = Sanitize::getInt($options,'listing_id'))
        {
            $query = "SELECT catid FROM #__content WHERE id = " . $listing_id;
            $this->c->_db->setQuery($query);
            $options['cat_id'] = $this->c->_db->loadResult();
        }   
      
        # Get cat and parent cat info
        if($cat_id = Sanitize::getInt($options,'cat_id'))
        {
            App::import('Model','category','jreviews');
            $CategoryModel = ClassRegistry::getClass('CategoryModel');
            $options['categories'] = $this->c->cmsVersion == CMS_JOOMLA15 ? $CategoryModel->findSectionCat($cat_id) : $CategoryModel->findParents($cat_id);
        }   

        if(Sanitize::getVar($options,'categories'))
        {             
            # Iterate from parent to child and overwrite the suffix if not null
            foreach($options['categories'] AS $category)
            {   
                $category['Category']['tmpl_suffix'] != '' and $this->c->viewSuffix = $category['Category']['tmpl_suffix'];
            }
        }

        # Module params, menu params and posted data override previous values
        if(Sanitize::getVar($this->c->params,'module')) {
            $this->c->viewSuffix = Sanitize::getString($this->c->params['module'],'tmpl_suffix');
        }

        if($suffix = Sanitize::getString($this->c->data,'tmpl_suffix',Sanitize::getString($this->c->params,'tmpl_suffix'))) 
        {     
            $suffix != '' and $this->c->viewSuffix = $suffix;
        }         

        if(isset($this->c->Menu))
        {
            # Nothing yet, so we load the menu params
            $menu_params = $this->c->Menu->getMenuParams(Sanitize::getInt($this->c->params,'Itemid')); 
            Sanitize::getVar($menu_params,'tmpl_suffix') != '' and $this->c->viewSuffix = Sanitize::getVar($menu_params,'tmpl_suffix');
        }
    }
   
    /**
    * Sets the correct view layout
    *  
    * @param mixed $categories
    */
    public function setLayout($options = array())
    {
        if(Sanitize::getVar($options,'categories'))
        {
            # Iterate from parent to child and overwrite the suffix if not null
            foreach($options['categories'] AS $category)
            {
                $category['Category']['tmpl'] != '' and  $this->c->tmpl_list = $category['Category']['tmpl'];
            }
        }
        
        if($this->c->action == 'search')
        {  
            $this->c->tmpl_list = $this->listTypeConversion($this->c->Config->search_display_type);
            return;
        }

        # Add overrides for menus, url params 
        if(null!=Sanitize::getString($this->c->data,'tmpl_list'))
        {
            $this->c->data['tmpl_list'] = Sanitize::getString($this->c->data,'tmpl_list');
        } 
        elseif(null!=Sanitize::getString($this->c->data,'listview'))
        {                
            $this->c->data['tmpl_list'] = Sanitize::getString($this->c->data,'listview');
        }                                                                                         
        elseif(null!=Sanitize::getString($this->c->params,'tmpl_list'))
        {                            
            $this->c->data['tmpl_list'] = Sanitize::getString($this->c->params,'tmpl_list');
        } 
        else 
        {
            $this->c->data['tmpl_list'] = null;
        }                                                                                         

        if(null!=$this->c->data['tmpl_list']) 
        {
            $this->c->tmpl_list = $tmpl_list = $this->listTypeConversion($this->c->data['tmpl_list']);
        }
        
        # Global layout
        empty($this->c->tmpl_list) and $this->c->tmpl_list = $this->listTypeConversion($this->c->Config->list_display_type); 
    
        # Layout can be overriden for certain controller::actions
        if(method_exists($this,$this->c->action)) $this->{$this->c->action}();
    }
    
    /**
    * Uses the listings_favorite theme file if present
    */
    function favorites()
    {
        $Configure = &App::getInstance(); // Get file map
        if(
            isset($Configure->jreviewsPaths['Theme'][$this->c->Config->template]['listings']['listings_favorites.thtml']) 
            ||
            isset($Configure->jreviewsPaths['Theme']['default']['listings']['listings_favorites.thtml'])                    
        ){
            $this->c->tmpl_list = 'favorites';
        }         
    }
    
    /**
    * Uses the listings_favorite theme file if present
    */
    function mylistings()
    {
        $Configure = &App::getInstance(); // Get file map
        if(
            isset($Configure->jreviewsPaths['Theme'][$this->c->Config->template]['listings']['listings_mylistings.thtml']) 
            ||
            isset($Configure->jreviewsPaths['Theme']['default']['listings']['listings_mylistings.thtml'])                    
        ){
            $this->c->tmpl_list = 'mylistings';
        }        
    }    
    
    function listTypeConversion($type) 
    {
        if(!is_null($this->c->tmpl_list))
        {
            return $this->c->tmpl_list;
        }    
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
}
