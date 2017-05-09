<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RoutesHelper extends MyHelper
{    
    var $helpers = array('html');
    
    var $routes = array(
        'alphaindex_alldir'=>'index.php?option=com_jreviews&amp;Itemid=%1$s&amp;url=alphaindex/index{_PARAM_CHAR}%2$s/',    
        'alphaindex'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=%1$s_alphaindex_%2$s_d%3$s/',
        'alphaindex_menu'=>'index.php?option=com_jreviews&amp;Itemid=%4$s&amp;url=%1$s_alphaindex_%2$s_d%3$s/', /* _m%4$s */
        'article'=>'index.php?option=com_content&amp;view=article&amp;id=%s&Itemid=%s',
        'content15'=>'index.php?option=com_content&amp;view=article&amp;id=%s&amp;catid=%s%s%s', // Itemid is included second to last
        'category'=>'index.php?option=com_jreviews&amp;Itemid=%7$s&amp;url=%1$s/%2$s/%3$s_c%6$s/', /* _m%7$s */
        'category16'=>'index.php?option=com_jreviews&amp;Itemid=%1$s&amp;url=%2$s/%3$s_c%4$s/', /* _m%1$s */
        'category_blog'=>'index.php?option=com_content&amp;view=category&amp;layout=blog&amp;id=%s%s',
        'directory'=>'index.php?option=com_jreviews&amp;Itemid=%4$s&amp;url=%1$s_d%2$s%3$s/',
        'review_discuss'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=discussions/review/id:%s/',                
        'favorites'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=favorites/user:%s/',
        'listing'=>'index.php?option=com_jreviews&amp;Itemid=%7$s&amp;url=%1$s_l%2$s%3$s/extension{_PARAM_CHAR}%5$s/reviewtype{_PARAM_CHAR}%6$s/',
        'listing_default'=>'index.php?option=com_jreviews&amp;Itemid=%7$s&amp;url=%1$s_l%2$s%3$s/',
        'listing_edit'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=listings/edit/id{_PARAM_CHAR}%s/',
        'listing_new_category'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=new-listing_c%s/',        
        'listing_new_category15'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=new-listing_s%s_c%s/',        
        'listing_new_section'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=new-listing_s%s/',        
        'my_menu'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=menu/user:%s/',
		'mylistings'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=my-listings/user:%s/',
        'myreviews'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=my-reviews/user:%s/',
        'section'=>'index.php?option=com_jreviews&amp;Itemid=%5$s&amp;url=%1$s/%2$s_s%4$s/', /* _m%5$s */
        'search'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=advanced-search/',
        'search_menu'=>'index.php?option=com_jreviews&amp;Itemid=%s',
        'search_results'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=search-results%s/',
        'search_results_menu'=>'index.php?option=com_jreviews&amp;Itemid=%1$s&amp;url=search-results%2$s',
        'review_edit'=>'index.php?option=com_jreviews&amp;url=reviews/edit/id{_PARAM_CHAR}%s&amp;width=800&amp;height=580',
        'review_edit15'=>'index.php?option=com_jreviews&amp;tmpl=component&amp;url=reviews/edit/id{_PARAM_CHAR}%s&amp;width=800&amp;height=580',
        'reviewers'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=reviewers%s#user-%s',
        'reviewers_menu'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=menu%s#user-%s',
        'rss_listings_directory'=>'index.php?option=com_jreviews&amp;url=categories/latest/dir:%s/action:xml/',
        'rss_reviews'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=reviews_%s.rss/',
        'rss_reviews_directory'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=%s_d%s.rss/',
        'rss_reviews_section'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=%1$s_s%2$s.rss/',
        'rss_reviews_category'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=%s_c%s.rss/',
        'rss_reviews_listing'=>'index.php?option=com_jreviews&amp;Itemid=%4$s&amp;url=%1$s_l%2$s_%3$s.rss/',
        'tag'=>'index.php?option=com_jreviews&amp;Itemid=%4$s&amp;url=%2$s_%1$s%4$s/criteria{_PARAM_CHAR}%3$s/',
        'menu' => 'index.php?option=com_jreviews&amp;Itemid=%s',
        'whois'=>'http://whois.domaintools.com/%s',
    );
    
    # Click2search
    function tag($tag,$field,$value,$criteria_id,$menu_id,$attributes=array()) {

/*		if($menu_id>0) {
            $menu_id = '_m'.$menu_id;
        } else {
            $menu_id = '';
        }
*/
        $url = sprintf($this->routes['tag'],$field,$value,$criteria_id,$menu_id);
		
        return $this->Html->sefLink($tag,$url,$attributes);                    
    }        
    
    function alphaindex($alpha_title,$alpha_value,$directory,$attributes=array()) 
    {	
        if(($this->cmsVersion == CMS_JOOMLA15 && count($directory) > 1) || empty($directory)) 
		{
            $menu_id = Sanitize::getInt($this->params,'Itemid');
            return $this->Html->sefLink($alpha_title,sprintf($this->routes['alphaindex_alldir'],$menu_id,$alpha_value),$attributes);
        } 
		else {
            $first = $this->cmsVersion == CMS_JOOMLA15 || isset($directory['Directory']) ? array_shift($directory) : array_shift(array_shift($directory)); 
			
            if(isset($first['Directory'])) {
                $first = $first['Directory'];
            }

			
			$dir_title = S2Router::sefUrlEncode($first['slug'],__t("and",true));        
            $dir_id = $first['dir_id'];            
            $menu_id = $first['menu_id'];
        }
		
        if(is_numeric($menu_id)) {
            $url = sprintf($this->routes['alphaindex_menu'],$dir_title,$alpha_value,$dir_id,$menu_id);
        } 
		else {
            $url = sprintf($this->routes['alphaindex'],$dir_title,$alpha_value,$dir_id);            
        }
        return $this->Html->sefLink($alpha_title,$url,$attributes);
    }
            
    function article($article, $attributes = array())
    {
        $article_id = Sanitize::getInt($article['Article'],'article_id');
        if(!$menu_id = Sanitize::getInt($article['Article'],'menu_id'))
        {
            $menuModel = ClassRegistry::getClass('MenuModel');
            $menu_id = $menuModel->get('core_content_menu_id_'.$article_id);            
        }
        
        $url = sprintf($this->routes['article'], $article_id, $menu_id);
        return $this->Html->sefLink($article['Article']['title'],$url,$attributes);        
    } 
               
    function category() 
    {
        # Process parameters whether passed individually or as an array
        $params = func_get_args();            
        $node = array();
        $section = array();
          
        if($this->cmsVersion == CMS_JOOMLA15)
        {
            $directory = array_shift($params);
            $section = array_shift($params);
            $category = array_shift($params);
            $attributes = !empty($params) ? array_shift($params) : array();
            $node = $directory;
            $node['Section'] = $section;
            $node['Category'] = $category;
        }
        else
        {
            $node = array_shift($params);
            $attributes = isset($params) ? array_shift($params) : array();
        }
        
        count(array_keys($node)) == 1 and $node = array_shift($node);
        $Config = Configure::read('JreviewsSystem.Config');
        $dir_slug = S2Router::sefUrlEncode($node['Directory']['slug'],__t("and",true));
        $dir_id = $node['Directory']['dir_id'];        
        $cat_slug = S2Router::sefUrlEncode($node['Category']['slug'],__t("and",true));
        $cat_id = $node['Category']['cat_id'];
        $section_id = Sanitize::getInt($section,'section_id');
        
        if(Sanitize::getVar($attributes,'image')) 
        {
            $params = json_decode($node['Category']['params'],true);
            $image_path = WWW_ROOT . ($this->cmsVersion == CMS_JOOMLA15 ? 'images' . _DS . 'stories' : '') . _DS . $params['image'];
            isset($params['image']) and $params['image']!='' and $node['Category']['title'] = $this->Html->image($image_path,array('border'=>0,'alt'=>$node['Category']['title']));
            unset($attributes['image']);
        }
        
        $menuModel = ClassRegistry::getClass('MenuModel');

       # Check if there's a menu for this category to prevent duplicate urls
        $menu_id = $menuModel->get('jr_category_menu_id_'.$cat_id);

        if($menu_id > 0) 
        {  
            if(!$menuModel->get('jr_manyIds_'.$menu_id)){
                $url = sprintf($this->routes['menu'], $menu_id);
                return $this->Html->sefLink($node['Category']['title'],$url,$attributes);
            }
        }
        else
        {
                $menu_id = $menuModel->getCategory(array('cat_id'=>$cat_id,'dir_id'=>$dir_id,'section_id'=>$section_id));   
        }      

        if($this->cmsVersion == CMS_JOOMLA15)
        {
			$section_slug = S2Router::sefUrlEncode($node['Section']['slug'],__t("and",true));
			
            empty($menu_id) and $this->routes['category'] = str_replace(array('_m%7$s','&amp;Itemid=%7$s'),'',$this->routes['category']);
            $url = sprintf($this->routes['category'],$dir_slug,$section_slug,$cat_slug,$dir_id,$section_id,$cat_id,$menu_id);
            return $this->Html->sefLink($category['title'],$url,$attributes);            
        }

        empty($menu_id) and $this->routes['category16'] = str_replace(array('_m%1$s','&amp;Itemid=%1$s'),'',$this->routes['category16']);
        $url = sprintf($this->routes['category16'],$menu_id,$dir_slug,$cat_slug,$cat_id);
        return $this->Html->sefLink($node['Category']['title'],$url,$attributes);
    }

    function categoryBlog($category,$attributes = array()) 
    {
        $cat_title = S2Router::sefUrlEncode($category['slug'],__t("and",true));
        $cat_id = $category['cat_id'];

        if(Sanitize::getVar($attributes,'image')) {
            $category['title'] = $this->Html->image(WWW_ROOT . 'images' . _DS . 'stories' . _DS . $category['image'],array('border'=>0,'alt'=>$category['title']));
            unset($attributes['image']);
        }
        
        // Check if there's a menu for this category to prevent duplicate urls
        $menuModel = ClassRegistry::getClass('MenuModel');
        $menu_id = $menuModel->get('core_category_menu_id_'.$cat_id);
        $url = sprintf($this->routes['category_blog'],$cat_id,$menu_id ? '&amp;Itemid='.$menu_id : '');
        return $this->Html->sefLink($category['title'],$url,$attributes);
    }    
    
    function content($title,$listing,$attributes = array(),$anchor='') 
    {        
        $listing_id = $listing['Listing']['listing_id'];
        $menu_id = Sanitize::getInt($listing['Listing'],'menu_id');
        $cat_id = $listing['Listing']['cat_id'];

        if($menu_id) {
            $menu_id = '&amp;Itemid='.$menu_id;
        } else {
            $menu_id = '';
        }

        $listing_slug = Sanitize::getString($listing['Listing'],'slug') != '' ? $listing_id . ':' . S2Router::sefUrlEncode($listing['Listing']['slug']) : $listing_id;
		
        $cat_slug = Sanitize::getString($listing['Category'],'slug') != '' ? $cat_id . ':' . S2Router::sefUrlEncode($listing['Category']['slug']) : $cat_id;
        
		$route = $this->routes['content15'];            

        // For Joomfish compat
        if(isset($this->params['lang']) && $this->params['lang']!=''){
            $menu_id .= '&amp;lang='.Sanitize::getString($this->params,'lang');    
        }

        $url = sprintf($route,$listing_slug,$cat_slug,$menu_id,$anchor!=''?'#'.$anchor:'');                 
        
        return $this->Html->link($title,$url,$attributes);
    }
    
    function directory($directory,$attributes = array()) 
    {             
        $dir_title = S2Router::sefUrlEncode($directory['Directory']['slug'],__t("and",true));
        $dir_id = (int) $directory['Directory']['dir_id'];
          
        if(!($menu_id = Sanitize::getInt($directory['Directory'],'menu_id')))
        {
            // Check if there's a menu for this directory to prevent duplicate urls
            $menuModel = ClassRegistry::getClass('MenuModel');
            $menu_id = $menuModel->getDir($dir_id);
        }
        
        if($menu_id) {
            $url = sprintf($this->routes['menu'], $menu_id);
            return $this->Html->sefLink($directory['Directory']['title'],$url,$attributes);
        }
        else {
            $menu_id = Sanitize::getInt($_REQUEST,'Itemid');
            $menu_id_param = /*$menu_id ? '_m'.$menu_id:*/ '';
            $url = sprintf($this->routes['directory'], $dir_title, $dir_id, $menu_id_param, $menu_id);
            return $this->Html->sefLink($directory['Directory']['title'],$url,$attributes);
        }
    }
    
    function favorites($title, $user_id, $attributes = array()) 
    {
        if($user_id > 0) {
            $Menu = ClassRegistry::getClass('MenuModel');
            $menu_id = $Menu->getMenuIdByAction(13); 
            $url = sprintf($this->routes[($menu_id > 0 ? 'my_menu' : 'favorites')],$menu_id,$user_id); 
            return $this->Html->sefLink($title,$url,$attributes);
        }
    }        
    
    function myListings($title, $user_id, $attributes = array()) 
    {

        if($user_id > 0) {
            $Menu = ClassRegistry::getClass('MenuModel');
            $menu_id = $Menu->get('jr_mylistings');            
            $url = sprintf($this->routes[($menu_id > 0 ? 'my_menu' : 'mylistings')],$menu_id,$user_id); 
            return $this->Html->sefLink($title,$url,$attributes);
        }
    }    
        
    function myReviews($title, $user, $attributes = array()) 
    {
        $user_id = $user['user_id'];
        
        if($user_id > 0) 
        {
            $Menu = ClassRegistry::getClass('MenuModel');
            $menu_id = $Menu->get('jr_myreviews');
            $url = sprintf($this->routes[($menu_id > 0 ? 'my_menu' : 'myreviews')],$menu_id,$user_id); 
            return $this->Html->sefLink($title,$url,$attributes);
        }
    }    
    
    function listing($title, &$listing, $reviewType='user', $attributes = array()) 
    {
        // backwards theme compat
        if(is_array($reviewType)){
            $attributes = $reviewType;
            $reviewType = 'user';
        }
        $Itemid = $menu_id = '';
        $listing_id = $listing['Listing']['listing_id'];
        $listing_title = S2Router::sefUrlEncode($listing['Listing']['title'],__t("and",true));
        $extension = $listing['Listing']['extension'];
        $criteria_id = $listing['Criteria']['criteria_id'];
        if($extension == 'com_content')
        {
            $Menu = ClassRegistry::getClass('MenuModel');
            $Itemid = $Menu->getCategory($listing['Category']['cat_id'],$listing['Section']['section_id'],$listing['Directory']['dir_id']);
//            $menu_id = '_m'.$Itemid;
        }

		if($extension == 'com_content' && $reviewType == 'user') {
			$url = sprintf($this->routes['listing_default'], $listing_title, $listing_id, $menu_id, $criteria_id, '', '', $Itemid);
		}
		elseif($extension == 'com_content') {
			$url = sprintf($this->routes['listing_default'], $listing_title, $listing_id, $menu_id, $criteria_id, '', '', $Itemid).'reviewType{_PARAM_CHAR}'.$reviewType.'/';
		}
		elseif($reviewType == 'user') {
			$url = sprintf($this->routes['listing_default'], $listing_title, $listing_id, $menu_id, $criteria_id, '', '', $Itemid).'extension{_PARAM_CHAR}'.$extension.'/';
		}
		else {
			$url = sprintf($this->routes['listing'], $listing_title, $listing_id, $menu_id, $criteria_id, $extension, $reviewType, $Itemid);
		}
		
        unset($listing['Review']['reviewType']);
        return $this->Html->sefLink($title,$url,$attributes);                
    }
        
    function listingEdit($title, $listing, $attributes=array()) 
    {
        $listing_id = $listing['Listing']['listing_id'];
        $url = sprintf($this->routes['listing_edit'],$listing_id);
//        $this->cmsVersion != CMS_JOOMLA15 and $url = str_replace('&amp;Itemid=','',$url);
        return $this->Html->sefLink($title,$url,$attributes);        
    }
    
    function listingNew($title, $attributes = array()) 
    {     
        $section_id = Sanitize::getInt($this->passedArgs,'section',Sanitize::getInt($this->params,'section'));
        $cat_id = Sanitize::getString($this->passedArgs,'cat',Sanitize::getString($this->params,'cat'));

        if($this->action == 'section') 
        {
            $url = sprintf($this->routes['listing_new_section'],($this->Config->list_addnew_menuid ? Sanitize::getInt($this->params,'Itemid') : ''),$section_id);
        } 
        elseif($this->action == 'category') 
        {
            if($this->cmsVersion == CMS_JOOMLA15)
            {
                $url = sprintf($this->routes['listing_new_category15'],($this->Config->list_addnew_menuid ? Sanitize::getInt($this->params,'Itemid') : ''),$section_id,$cat_id);
            }
            else
            {
                $url = sprintf($this->routes['listing_new_category'],($this->Config->list_addnew_menuid ? Sanitize::getInt($this->params,'Itemid') : ''),$cat_id);
            }
        }
        return $this->Html->sefLink($title,$url,$attributes);
    }        
        
    function listingsFeed($title = '',$attributes=array())
    {                    
        $base_url = rtrim(cmsFramework::constructRoute($this->passedArgs),'/').'/action:xml/';        
        $title = sprintf(__t("%s listing feeds",true),$title);
        if(isset($attributes['return_url'])){
            $base_url = cmsFramework::route($base_url);
            return $base_url;
        } else {
            $attributes = array_merge(array('title'=>$title,'class'=>'jr_feedListings'),$attributes);
            return $this->Html->link('',$base_url,$attributes);
        }
    } 
    
    function listingsFeedDirectory($directory,$title='',$attributes=array())
    {            
        $dir_id = $directory['Directory']['dir_id'];
        $directory_title = $directory['Directory']['title'];
        $title = $title != '' ? $title : sprintf(__t("%s listing feeds",true),$directory_title);
        $attributes = array_merge(array('title'=>$title,'class'=>'jr_feedListings'),$attributes);
        $url = sprintf($this->routes['rss_listings_directory'],$dir_id);
        return $this->Html->link('', $url ,$attributes);
    }        
             
    function search($title,$attributes = array()) 
    {
        $Menu = ClassRegistry::getClass('MenuModel');
        $menu_id = $Menu->get('jr_advsearch');
        if($menu_id)
        {
            return $this->Html->sefLink($title,sprintf($this->routes['search_menu'],$menu_id));    
        }
        return $this->Html->sefLink($title,sprintf($this->routes['search']));    
    }
    
    function search_results($menu_id, $params) {
        if($menu_id) {
            return sprintf($this->routes['search_results_menu'],$menu_id,$params);
        } else {
            return sprintf($this->routes['search_results'],$params);
        }
    }
    
    function section($directory,$section,$attributes = array()) 
    {
        $dir_title = S2Router::sefUrlEncode($directory['Directory']['slug'],__t("and",true));
        $section_title = S2Router::sefUrlEncode($section['slug'],__t("and",true));
        $dir_id = $directory['Directory']['dir_id'];
        $section_id = $section['section_id'];

        if(Sanitize::getVar($attributes,'image')) {
            $section['title'] = $this->Html->image(WWW_ROOT . 'images' . _DS . 'stories' . _DS . $section['image'],array('border'=>0,'alt'=>$section['title']));
            unset($attributes['image']);
        }
        
        // Check if there's a jReviews menu for this section to prevent duplicate urls
        $menuModel = ClassRegistry::getClass('MenuModel');
        $menu_id = $menuModel->get('jr_section_menu_id_'.$section_id);
        if($menu_id) {
            if(!$menuModel->get('jr_manyIds_'.$menu_id)){
                $url = sprintf($this->routes['menu'], $menu_id);
                return $this->Html->sefLink($section['title'],$url,$attributes);
            }
        }    
        
        $menu_id = $section['menu_id'];
        if($menu_id=='')
        {
            $this->routes['section'] = str_replace(array('_m%5$s','&amp;Itemid=%5$s'),'',$this->routes['section']);
        }        
        $url = sprintf($this->routes['section'],$dir_title,$section_title,$dir_id,$section_id,$menu_id);
        return $this->Html->sefLink($section['title'],$url,$attributes);
    }
    
    function reviewEdit($title,$review,$attributes=array()) 
    {         
        $review_id = $review['Review']['review_id'];                                                 
        $dialogTitle = __t("Edit review",true);
        $attributes['sef'] = false;                                                                  
        if(isset($attributes['class']) && $attributes['class']=='thickbox'){
            unset($attributes['class']);                   
        }

        $attributes['onclick']= "jreviews.review.edit(this,{title:'".addslashes($dialogTitle)."',review_id:{$review_id}});return false;";
        return $this->Html->link($title,'#edit-review',$attributes);     
    }
    
    function reviewers($rank,$user_id, $attributes = array()) 
    {
		$limit = $this->limit;
        $paginate = '';
        $menu_id = '';
        $paginate = '';
        if($rank) 
        {   
			App::import('Helper','jreviews','jreviews');
            $Jreviews = ClassRegistry::getClass('JreviewsHelper');
            $Menu = ClassRegistry::getClass('MenuModel');
            $menu_id = $Menu->getReviewers();
            $userRank = $Jreviews->userRank($rank);        
            $offset = floor($rank/$limit)*$limit;
            if ($offset > 1) {
                $page = $offset/$limit + 1;                        
                $paginate = "/page"._PARAM_CHAR.$page;
            }
            $url = sprintf(($menu_id ? $this->routes['reviewers_menu'] : $this->routes['reviewers']),$menu_id,$paginate,$user_id);
            return $this->Html->sefLink($userRank,$url,$attributes);
        }
        
    }    
    
    function reviewDiscuss($title, $review, $attributes = array()) 
    {
        $Itemid = '';
        $section_id = null;
        $dir_id = null;
        if(isset($review['Review'])){
            $review = $review['Review'];
        }
        
        $review_id = $review['review_id'];
        
        if(isset($attributes['listing']) && !empty($attributes['listing'])) 
        {                               
            $Menu = ClassRegistry::getClass('MenuModel');
            $listing = &$attributes['listing']; 
            if(!isset($listing['Listing']['extension']) || $listing['Listing']['extension'] == 'com_content')
            {        
                isset($listing['Section']) and $section_id = Sanitize::getInt($listing['Section'],'section_id');
                isset($listing['Directory']) and $dir_id = Sanitize::getInt($listing['Directory'],'dir_id');
                $Itemid = $Menu->getCategory($listing['Category']['cat_id'],$section_id,$dir_id);
            }
            !$Itemid and $Itemid = $Menu->getMenuIdByAction(17); // Latest comments menu
        } 
        unset($attributes['listing']);
                                                              
        if($review_id > 0) {
            $url = sprintf($this->routes['review_discuss'],$Itemid,$review_id);
			return $this->Html->link($title,$url,$attributes);
        }
    }     
         
    function reportThis($title,$params,$attributes = array())
    {
        $listing_id = Sanitize::getInt($params,'listing_id');
        $review_id = Sanitize::getInt($params,'review_id');
        $post_id = Sanitize::getInt($params,'post_id');
        $extension = Sanitize::getString($params,'extension','com_content');

        $attributes['sef'] = false; 

        if(!isset($attributes['class'])){
            $attributes['class'] = 'jr_report';                   
        }
        $attributes['onclick']= "
            jreviews.report.showForm(this,
                {title:'$title',
                listing_id:$listing_id,
                review_id:$review_id,
                post_id:$post_id,
                extension:'$extension'}
            );
            return false;";
        return $this->Html->link($title,'#report',$attributes);                
    }   
                                              
    function ownerReply($title,$review,$attributes=array()) 
    {     
        $review_id = $review['Review']['review_id'];
        $attributes['sef'] = false; 
        if(!isset($attributes['class'])){
            $attributes['class'] = 'jr_ownerReply';                   
        }
        $attributes['onclick']= "jreviews.review.reply(this,{title:'{$title}',review_id:{$review_id}});return false;";
        return $this->Html->link($title,'#owner-reply',$attributes);        
    }
    
    function rss($extension = 'com_content', $title = '',$attributes = array()) 
    {                           
        $url = sprintf($this->routes['rss_reviews'],$extension);
        return $this->Html->sefLink($title,$url,$attributes);
    }
    
    function rssDirectory($directory,$title = '',$attributes = array()) 
    {                     
        $dir_slug = S2Router::sefUrlEncode($directory['Directory']['slug']);
        $dir_title = $directory['Directory']['title'];
        $id = $directory['Directory']['dir_id'];
        $Menu = ClassRegistry::getClass('MenuModel');
        $menu_id = $Menu->getDir($id);
        $title = $title != '' ? $title : sprintf(__t("%s review feeds",true),$dir_title);
        $attributes = array_merge(array('class'=>'jr_feedReviews','title'=>$title),$attributes);      
        $url = sprintf($this->routes['rss_reviews_directory'],$menu_id,$dir_slug,$id);
        return $this->Html->sefLink('',$url,$attributes);        
    }

    function rssSection($section,$title = '',$attributes = array()) 
    {
        $section_slug = S2Router::sefUrlEncode($section['Section']['slug'],__t("and",true));        
        $section_title = $section['Section']['title'];
        $id = $section['Section']['section_id'];
        $title = $title != '' ? $title : sprintf(__t("%s review feeds",true),$section_title);
        $attributes = array_merge(array('class'=>'jr_feedReviews','title'=>$title),$attributes);        
        $url = sprintf($this->routes['rss_reviews_section'],$section_slug,$id);
        return $this->Html->sefLink('',$url,$attributes);        
    }

    function rssCategory($category,$title='',$attributes = array()) 
    {    
        $cat_slug = S2Router::sefUrlEncode($category['Category']['slug'],__t("and",true));
        $cat_title = $category['Category']['title'];
        $cat_id = $category['Category']['cat_id'];
        $title = $title != '' ? $title : sprintf(__t("%s review feeds",true),$cat_title);  
        
        $Menu = ClassRegistry::getClass('MenuModel');
        $menu_id = $Menu->getCategory(array('cat_id'=>$category['Category']['cat_id'],'dir_id'=>$category['Directory']['dir_id']));
        $attributes = array_merge(array('class'=>'jr_feedReviews','title'=>$title),$attributes);        
        $url = sprintf($this->routes['rss_reviews_category'],$menu_id,$cat_slug,$cat_id);
        return $this->Html->sefLink('',$url,$attributes);        
    }
    
    function rssListing($listing,$title='',$attributes = array()) 
    {                
        $menu_id = '';
        $section_id = '';
        if(isset($listing['Listing']['slug'])){
            $listing_slug = S2Router::sefUrlEncode($listing['Listing']['slug'],__t("and",true));            
        } else {
            $listing_slug = S2Router::sefUrlEncode($listing['Listing']['title'],__t("and",true));                        
        }
        $listing_title = $listing['Listing']['title'];
        $listing_id = $listing['Listing']['listing_id'];         
        $extension = $listing['Listing']['extension'];
        if($extension == 'com_content') {
            if($this->cmsVersion == CMS_JOOMLA15) {
                $section_id = $listing['Section']['section_id'];
            }
            $Menu = ClassRegistry::getClass('MenuModel');
            $menu_id = $Menu->getCategory(array('cat_id'=>$listing['Category']['cat_id'],'dir_id'=>$listing['Directory']['dir_id'],'section_id'=>$section_id));   
        }
        $title = $title != '' ? $title : sprintf(__t("%s review feeds",true),$listing_title);
        $attributes = array_merge(array('class'=>'jr_feedReviews','title'=>$title),$attributes);        
        $url = sprintf($this->routes['rss_reviews_listing'],$listing_slug,$listing_id,$extension,$menu_id);
        return $this->Html->sefLink('',$url,$attributes);
    }
    
    function whois($ip_address)
    {
        $url = sprintf($this->routes['whois'],$ip_address);        
        return $this->Html->link($ip_address,$url,array('sef'=>false,'target'=>'_blank'));
    }    
}
