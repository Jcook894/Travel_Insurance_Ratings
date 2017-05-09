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

class ListingsController extends MyController {
		
	var $uses = array('user','menu','listing','section','category','review','criteria','captcha','vote');
	
	var $helpers = array('cache','routes','libraries','html','form','time','jreviews','community','editor','rating','thumbnail','paginator');
	
	var $components = array('config','access','uploads','everywhere');
			
	function beforeFilter() {
		
		# Call beforeFilter of MyController parent class
		parent::beforeFilter();
		
		# Make configuration available in models
		$this->Listing->Config = &$this->Config;

	}	
	
	// Need to return object by reference for PHP4
	function &getNotifyModel() {
		return $this->Listing;
	}
	
	function detail() {

		if($this->_user->id === 0) {
			$this->cacheAction = Configure::read('Cache.expires');
		}
				
		$this->autoRender = true;
		$this->autoLayout = true;
		$this->layout = 'detail';	
		
		# Initialize vars
		$editor_review = array();
		$review_fields = array();
		$ratings_summary = array();		
		
		$listing_id = Sanitize::getInt($this->params,'id');
		$extension = Sanitize::getString($this->params,'extension','com_content');
		$sort = Sanitize::getString($this->params,'order');

		# Summarize votes
		$this->Vote->summarizeVotes($this->Config->vote_summarize_period);		
		
		$listing = $this->Listing->findRow(array('conditions'=>array("Listing.{$this->Listing->realKey} = ". $listing_id)));

		if(!$listing || empty($listing)) {
			
			echo cmsFramework::noAccess();
			$this->autoRender = false;
			return;			
		
		} 
		
		// Make sure variables are set
		$listing['Listing']['summary'] = Sanitize::getString($listing['Listing'],'summary');
		$listing['Listing']['description'] = Sanitize::getString($listing['Listing'],'description');
		$listing['Listing']['metakey'] = Sanitize::getString($listing['Listing'],'metakey');
		$listing['Listing']['metadesc'] = Sanitize::getString($listing['Listing'],'metadesc');

		$listing['Listing']['text'] = $listing['Listing']['summary'] . $listing['Listing']['description'];

		$regex = '/{.*}/';
		$listing['Listing']['text'] = preg_replace( $regex, '', $listing['Listing']['text'] );
					
		# Get editor review data
		if ($extension == 'com_content' && $this->Config->author_review) 
		{
			$fields = array(
				'Criteria.id AS `Criteria.criteria_id`',
				'Criteria.criteria AS `Criteria.criteria`',
				'Criteria.tooltips AS `Criteria.tooltips`',
				'Criteria.weights AS `Criteria.weights`'			
			);
						
			$conditions = array(
				'Review.pid = '. $listing['Listing']['listing_id'],
				'Review.author = 1',
				'Review.published = 1'
			);

			$editor_review = $this->Review->findRow(array(
				'fields'=>$fields,
				'conditions'=>$conditions,
			));
		}

		# Ger user review data
		if ($this->Config->user_reviews || $extension != 'com_content')
		{
			$fields = array(
				'Criteria.id AS `Criteria.criteria_id`',
				'Criteria.criteria AS `Criteria.criteria`',
				'Criteria.tooltips AS `Criteria.tooltips`',
				'Criteria.weights AS `Criteria.weights`'			
			);
												
			$conditions = array(
				'Review.pid= '. $listing['Listing']['listing_id'],
				'Review.author = 0',
				'Review.published = 1',
				'Review.mode = "'.$extension.'"' 
			);
			
			$order[] = $this->Review->processSorting($sort);

			$queryData = array
			(
				'fields'=>$fields,			
				'conditions'=>$conditions,
				'offset'=>$this->offset,
				'limit'=>$this->limit,
				'order'=>$order				
			);

			$reviews = $this->Review->findAll($queryData);
			
			//Remove unnecessary query parameters for findCount
			$this->Review->joins = array(); // Only need to query comments table			

			$review_count = $this->Review->findCount($queryData);
			
			$listing['Review']['review_count'] = $review_count;

		}
		
		// Two lines below allow showing the ratings summary in jReviews listings page
		// It requires removing the if statement in the detail.thtml which prevents the summary from showing
		$ratings_summary['Rating'] = $this->Review->getAverageRating($listing['Listing']['listing_id'],$extension,$listing['Criteria']['weights']);			
		$ratings_summary['Criteria'] = $listing['Criteria'];		

		$security_code = '';

		if($this->Access->showCaptcha) {

			$captcha = $this->Captcha->displayCode();

			$security_code = $captcha['image'];
		}
		
		# Initialize review array and set Criteria and extension keys
		$review = $this->Review->init();
		$review['Criteria'] = $listing['Criteria'];
		$review['Review']['extension'] = $extension;		

		$this->set(array(
				'extension'=>$extension,
				'Access'=>$this->Access,
				'User'=>$this->_user,
				'listing'=>$listing,
				'reviews'=>$reviews,
				'ratings_summary'=>$ratings_summary,
				'review'=>$review,
				'review_count'=>$review_count,				
				'captcha'=>$security_code,
				'pagination'=>array(
					'total'=>$review_count
				)
				
			)
		);								
	}
}