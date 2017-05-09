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

class ComContentController extends MyController {
	
	var $uses = array('user','menu','captcha','criteria','listing','review','category','vote');
	
	var $helpers = array('routes','libraries','html','form','text','time','jreviews','thumbnail','rating','community');	
	
	var $components = array('config','access');
	
	var $autoRender = false; //Output is returned
	
	var $autoLayout = true;
			
	function beforeFilter() {					
		# Call beforeFilter of MyController parent class
		parent::beforeFilter();	
		
		# Make configuration available in models
		$this->Listing->Config = &$this->Config;
	}
	
	function com_content_view($passedArgs) {

		$this->layout = 'detail';
		
		$content_row = $passedArgs['row'];
		$content_params = $passedArgs['params'];

		$editor_review = array();
		$reviews  = array();
		$ratings_summary = array();
		$review_count = null;	
								
		// Check if item category is configured for jreviews		
		if(!$this->Category->isJreviewsCategory($content_row->catid)) {			
			return array('row'=>$content_row,'params'=>$content_params);
		}
				
		# Override content page parameter settings
//		prx($content_params);
//		$content_params->set('show_pdf_icon',0);
//		$content_params->set('show_print_icon',0);
//		$content_params->set('show_email_icon',0);

		$content_params->set('show_title',1);
		$content_params->set('item_title',1); // J1.0.x & Mambo 4.6.x
		
		$content_params->set('show_category',1);
		$content_params->set('category',1); // J1.0.x & Mambo 4.6.x
		
		$content_params->set('show_section',1);
		$content_params->set('section',1); // J1.0.x & Mambo 4.6.x
		
		$content_params->set('show_author',0);
		$content_params->set('author',0); // J1.0.x & Mambo 4.6.x
		
		$content_params->set('show_create_date',0);
		$content_params->set('createdate',0); // J1.0.x & Mambo 4.6.x
		
		$content_params->set('show_vote',0);
		$content_params->set('rating',0); // J1.0.x & Mambo 4.6.x

		$content_params->set('show_modify_date',0);
		$content_params->set('modifydate',0); // J1.0.x & Mambo 4.6.x
		
		$content_params->set('page_title','');
		$content_params->set('show_page_title',0); // J1.5.4+		
				
		$content_params->set('show_hits',0);
		
		$content_params->set('show_item_navigation',0);
		$content_params->set('item_navigation',0); // J1.0.x & Mambo 4.6.x		

		# Get cached vesion
		if($this->_user->id === 0) {	
					
			$page = $this->cached($this->here . 'plugin');
	
			if($page) {
				$content_row->text = $page;			
				return array('row'=>$content_row,'params'=>$content_params);				
			}
		}

		# Summarize votes
		$this->Vote->summarizeVotes($this->Config->vote_summarize_period);
		
		# Get listing and review summary data
		$fields = array(
			'Criteria.criteria AS `Criteria.criteria`',
			'Criteria.tooltips AS `Criteria.tooltips`',
			'Criteria.weights AS `Criteria.weights`'
		);

		$listing = $this->Listing->findRow(array('fields'=>$fields,'conditions'=>array('Listing.id = '. $content_row->id)));

		// Check if the listing has any html tags, and if it does, then strip the double /r/r added by J1.5, otherwise it is
		// required for proper spacing of summary and description fields
		if(preg_match('/(<\w+)(\s*[^>]*)(>)/',$content_row->text)) {
			$listing['Listing']['text'] = str_replace("\r",'',$content_row->text); // Elimites double break between summary and description 
		} else {
			$listing['Listing']['text'] = $content_row->text; 
		}

		$regex = '/{mosimage\s*.*?}/i';
		$listing['Listing']['text'] = preg_replace( $regex, '', $listing['Listing']['text'] );
					
		# Get editor review data
		if ($this->Config->author_review) 
		{
			$fields = array(
				'Criteria.id AS `Criteria.criteria_id`',
				'Criteria.criteria AS `Criteria.criteria`',
				'Criteria.tooltips AS `Criteria.tooltips`',
				'Criteria.weights AS `Criteria.weights`'			
			);
			
			$joins = $this->Listing->joinsReviews;
						
			$conditions = array(
				'Review.pid = '. $listing['Listing']['listing_id'],
				'Review.author = 1',
				'Review.published = 1'
			);

			$editor_review = $this->Review->findRow(array(
				'fields'=>$fields,
				'conditions'=>$conditions,
				'joins'=>$joins
			));
		}

		# Ger user review data
		if ($this->Config->user_reviews) 
		{
			$fields = array(
				'Criteria.id AS `Criteria.criteria_id`',
				'Criteria.criteria AS `Criteria.criteria`',
				'Criteria.tooltips AS `Criteria.tooltips`',
				'Criteria.weights AS `Criteria.weights`'			
			);
			
			$joins = $this->Listing->joinsReviews;
									
			$conditions = array(
				'Review.pid= '. $listing['Listing']['listing_id'],
				'Review.author = 0',
				'Review.published = 1',
				'Review.mode = \'com_content\'',
				'JreviewsCategory.`option` = \'com_content\''
			);

			$queryData = array
			(	
				'fields'=>$fields,
				'conditions'=>$conditions,
				'joins'=>$joins,
				'offset'=>0,
				'limit'=>$this->Config->user_limit,
				'order'=>array($this->Review->processSorting())				
			);

			$reviews = $this->Review->findAll($queryData);

			$review_count = $this->Review->findCount($queryData);

			$ratings_summary = array();
			$ratings_summary['Rating'] = $this->Review->getAverageRating($listing['Listing']['listing_id'],$passedArgs['component'],$listing['Criteria']['weights']);			
			$ratings_summary['Criteria'] = $listing['Criteria'];
		}

		$security_code = '';

		if($this->Access->showCaptcha) {

			$captcha = $this->Captcha->displayCode();

			$security_code = $captcha['image'];
		}

		# Initialize review array and set Criteria and extension keys
		$review = $this->Review->init();
		$review['Criteria'] = $listing['Criteria'];
		$review['Review']['extension'] = $listing['Listing']['extension'];

		$this->set(array(
				'extension'=>'com_content',
				'Access'=>$this->Access,
				'User'=>$this->_user,
				'listing'=>$listing,
				'editor_review'=>$editor_review,
				'reviews'=>$reviews,
				'ratings_summary'=>$ratings_summary,
				'review_count'=>$review_count,				
				'review'=>$review,
				'captcha'=>$security_code
			)
		);
		
		$content_row->text = $this->render('listings','detail');
			
		# Save cached version		
		if($this->_user->id ===0) {	
			$this->cacheView('listings','detail',$this->here . 'plugin', $content_row->text);
		}
			
		return array('row'=>$content_row,'params'=>$content_params);
	}

	function com_content_blog($passedArgs)
	{                      
		$this->autoLayout = true;
		$this->layout = 'cmsblog';

		$content_row = $passedArgs['row'];
		$content_params = $passedArgs['params'];	
		
//		return array('row'=>$content_row,'params'=>$content_params);
							
		// Check if item category is configured for jreviews
		if(!$this->Category->isJreviewsCategory($content_row->catid)) 
        {
			return array('row'=>$content_row,'params'=>$content_params);			
		} 
		
		# Override content page parameter settings
//		prx($content_params);
//		$content_params->set('show_title',0);
//		$content_params->set('show_category',0);
//		$content_params->set('show_section',0);
//		$content_params->set('page_title','');
//		$content_params->set('show_hits',0);			

		$content_params->set('show_author',0);
		$content_params->set('author',0); // J1.0.x & Mambo 4.6.x
		
		$content_params->set('show_create_date',0);
		$content_params->set('createdate',0); // J1.0.x & Mambo 4.6.x
		
		$content_params->set('show_vote',0);
		$content_params->set('rating',0); // J1.0.x & Mambo 4.6.x

		$content_params->set('show_modify_date',0);
		$content_params->set('modifydate',0); // J1.0.x & Mambo 4.6.x
		
		
		# Get listing and review summary data
		$fields = array(
			'Criteria.criteria AS `Criteria.criteria`',
			'Criteria.tooltips AS `Criteria.tooltips`',
			'Criteria.weights AS `Criteria.weights`'
		);
		$listing = $this->Listing->findRow(array('fields'=>$fields,'conditions'=>array('Listing.id = '. $content_row->id)));

		$listing['Listing']['text'] = $content_row->text;

		$regex = '/{mosimage\s*.*?}/i';
		$listing['Listing']['text'] = preg_replace( $regex, '', $listing['Listing']['text'] );
		
		$security_code = '';

		$this->set(array(
				'Access'=>$this->Access,
				'User'=>$this->_user,
				'listing'=>$listing
		));
		
		$content_row->text = $this->render('listings','cmsblog');
		
		return array('row'=>$content_row,'params'=>$content_params);
		
	}
		
	function __getContentTmplSuffix($setup) {
		if ($setup->cat_suffix) {
			$tmpl_suffix = $setup->cat_suffix;
		} elseif ($setup->sec_suffix) {
			$tmpl_suffix = $setup->sec_suffix;
		} else {
			$tmpl_suffix = '';
		}
		return $tmpl_suffix;
	}	
}