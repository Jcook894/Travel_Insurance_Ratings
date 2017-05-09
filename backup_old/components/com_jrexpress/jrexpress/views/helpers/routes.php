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

class RoutesHelper extends MyHelper
{	
	var $helpers = array('html','jreviews');
	
	var $routes = array(
		'content10'=>'index.php?option=com_content&amp;task=view&amp;id=%s%s%s', // Itemid is included second to last
		'content15'=>'index.php?option=com_content&amp;view=article&amp;catid=%s&amp;id=%s%s%s', // Itemid is included second to last
		'listing'=>'index.php?option=com_jrexpress&amp;Itemid=%3$s&amp;url=%1$s_l%2$s%3$s/extension{_PARAM_CHAR}%5$s/',
		'listing_edit'=>'index.php?option=com_jrexpress&amp;Itemid=&amp;url=listings/edit/id{_PARAM_CHAR}%s/',
		'listing_new_category'=>'index.php?option=com_jrexpress&amp;Itemid=&amp;url=new-listing_s%s_c%s/',		
		'listing_new_section'=>'index.php?option=com_jrexpress&amp;Itemid=&amp;url=new-listing_s%s/',		
		'mylistings'=>'index.php?option=com_jrexpress&amp;Itemid=&amp;url=my-listings/user:%s/',
		'myreviews'=>'index.php?option=com_jrexpress&amp;Itemid=&amp;url=my-reviews/user:%s/',
		'review_edit'=>'index2.php?option=com_jrexpress&amp;url=reviews/edit/id{_PARAM_CHAR}%s&amp;width=800&amp;height=580',
        'review_edit15'=>'index.php?option=com_jrexpress&amp;tmpl=component&amp;url=reviews/edit/id{_PARAM_CHAR}%s&amp;width=800&amp;height=580',
		'review_report'=>'index2.php?option=com_jrexpress&amp;url=review_reports/create/id{_PARAM_CHAR}%s&amp;width=300&amp;height=140',
        'review_report15'=>'index.php?option=com_jrexpress&amp;tmpl=component&amp;url=review_reports/create/id{_PARAM_CHAR}%s&amp;width=300&amp;height=140',
		'reviewers'=>'index.php?option=com_jrexpress&amp;url=reviewers%s#%s/',
		'menu' => 'index.php?option=com_jrexpress&amp;Itemid=%s'
	);
    
    function __construct()
    {
        parent::__construct('jrexpress');
    }    
         
	function content($title,$listing,$attributes = array(),$anchor='',$link = true) {
		
		$listing_id = $listing['Listing']['listing_id'];
		$menu_id = Sanitize::getInt($listing['Listing'],'menu_id');
		$cat_id = $listing['Listing']['cat_id'];

		if($menu_id) {
			$menu_id = '&amp;Itemid='.$menu_id;
		} else {
			$menu_id = '';
		}

		switch(getCmsVersion()) {
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				$route = $this->routes['content10'];
				$url = sprintf($route,$listing_id,$menu_id,$anchor!=''?'#'.$anchor:''); 
			break;			
			case CMS_JOOMLA15:
				$listing_slug = Sanitize::getString($listing['Listing'],'slug') != '' ? $listing_id . ':' . S2Router::sefUrlEncode($listing['Listing']['slug']) : $listing_id;
				$cat_slug = Sanitize::getString($listing['Category'],'slug') != '' ? $cat_id . ':' . S2Router::sefUrlEncode($listing['Category']['slug']) : $cat_id;
				$route = $this->routes['content15'];			
				$url = sprintf($route,$cat_slug,$listing_slug,$menu_id,$anchor!=''?'#'.$anchor:''); 				
			break;						
		}

		if($link) {
			return $this->Html->sefLink($title,$url,$attributes);
		} else {
			return $url;
		}
	}
	
	function myListings($title, $user_id, $attributes = array()) {

		if($user_id > 0) {
			$url = sprintf($this->routes['mylistings'],$user_id); 
			return $this->Html->sefLink($title,$url,$attributes);
		}
	}	
		
	function myReviews($title, $user, $attributes = array()) {
		$user_id = $user['user_id'];
		if($user_id > 0) {
			$url = sprintf($this->routes['myreviews'],$user_id); 
			return $this->Html->sefLink($title,$url,$attributes);
		}
	}	
	
	function listing($title,$listing,$attributes = array()) {
		$listing_id = $listing['Listing']['listing_id'];
		$listing_title = S2Router::sefUrlEncode($listing['Listing']['title'],$this->Config->transliterate_urls,__t("and",true));
		$extension = $listing['Listing']['extension'];
		$criteria_id = $listing['Criteria']['criteria_id'];
		if($extension == 'com_content') {
			$menu_id = '_m'.$listing['Listing']['menu_id'];
		} else {
			$menu_id = '';			
		}
		
		$url = sprintf($this->routes['listing'], $listing_title, $listing_id, $menu_id, $criteria_id, $extension);
		return $this->Html->sefLink($title,$url,$attributes);				
	}
		
	function listingEdit($title, $listing, $attributes=array()) {
		$listing_id = $listing['Listing']['listing_id'];
		$url = sprintf($this->routes['listing_edit'],$listing_id); 
		return $this->Html->sefLink($title,$url,$attributes);		
	}
	
	function listingNew($title, $attributes = array()) {

		$section_id = Sanitize::getInt($this->passedArgs,'section',Sanitize::getInt($this->params,'section'));
		$cat_id = Sanitize::getString($this->passedArgs,'cat',Sanitize::getString($this->params,'cat'));
		
		if($this->action == 'section') {
			$url = sprintf($this->routes['listing_new_section'],$section_id);
		} elseif($this->action == 'category') {
			$url = sprintf($this->routes['listing_new_category'],$section_id,$cat_id);
		}
		return $this->Html->sefLink($title,$url,$attributes);
	}		
	
	function reviewEdit($title,$review,$attributes=array()) {
		$review_id = $review['Review']['review_id'];
		
        switch(getCmsVersion()) {
            case CMS_JOOMLA10: 
            case CMS_MAMBO46:
                $url = sprintf($this->routes['review_edit'],$review_id);
            break;            
            case CMS_JOOMLA15:
                $url = sprintf($this->routes['review_edit15'],$review_id);
            break;                        
        }
        
		return $this->Html->sefLink($title,$url,$attributes);
	}
	
	function reviewers($rank,$user_id, $attributes = array()) {

		$paginate = '';
		
		if($rank) 
		{	
			$userRank = $this->Jreviews->userRank($rank);		
	
			$paginate = '';
			
			$limit	= $this->Config->list_limit;
			
			$offset = floor($rank/$limit)*$limit;
	
			if ($offset > 1) {
				$page = $offset/$limit + 1;						
				$paginate = "/page"._PARAM_CHAR."$page/limit"._PARAM_CHAR."$limit";
			}
			
			$url = sprintf($this->routes['reviewers'],$paginate,$user_id);
			 
			return $this->Html->sefLink($userRank,$url,$attributes);
		}
		
	}	
		
	function reviewReport($title,$review,$attributes=array()) {
		$review_id = $review['Review']['review_id'];
		$url = sprintf($this->routes['review_report'],$review_id);
		return $this->Html->sefLink($title,$url,$attributes);		
	}
}