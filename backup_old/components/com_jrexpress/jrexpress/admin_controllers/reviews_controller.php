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

class ReviewsController extends MyController {
	
	var $uses = array('review','criteria','menu');
	
	var $components = array('config','access');
	
	var $helpers = array('html','libraries','form','paginator','time','rating');
	
	var $autoRender = true;
	
	var $autoLayout = true;
		
	function beforeFilter() {

		$this->Access->init($this->Config);
	
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
		
	}
	
	// Need to return object by reference for PHP4
	function &getNotifyModel() {
		return $this->Review;
	}
		
	function index() {
		
		$extension = 'com_content';
        $this->components = array('everywhere');
        $this->__initComponents();
        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model        

		$filter_entry_title =  Sanitize::getString($this->data, 'entry_title');
		$filter_order =  Sanitize::paranoid(Sanitize::getString($this->data, 'filter_order', 0 ));

		$filter_entry_id = Sanitize::getInt($this->passedArgs,'entry_id');
	
		// Begin query setup
		unset($this->Review->fields);
		unset($this->Review->joins);
		$this->Review->fields = array();
		$conditions = array();
		$fields = array();
		$order = array();
				
	    $where = array();
	    
		// Process search & filtering options	
		if ($filter_entry_title != '') {
	
			// Find all entry ids matching the title search
			$query = "SELECT id FROM #__content WHERE title like " . $this->quoteLike($filter_entry_title);
			$this->_db->setQuery($query);
			$filter_entry_id = $this->_db->loadResultArray();
			$filter_entry_id = implode(",",$filter_entry_id);
		}
	
		if (isset($filter_entry_id) && $filter_entry_id) {
			$where[] = "review.pid IN ($filter_entry_id)";
		}
	
	    $where[] = "review.mode = 'com_content'";
	
		switch($filter_order) {
			case 1:
	   			$conditions[] = "Review.published ='0'";
	   			$order[] = "Review.id DESC";
			break;
			case 2:
	   			$conditions[] = "Review.author ='0'";
	   			$order[] = "Review.id DESC";
			break;
			case 3:
	   			$conditions[] = "Review.author ='1'";
	   			$order[] = "Review.id DESC";
			break;
			default:
	   			$order[] = "Review.id DESC";
			break;
		}
	
		$fields = array(
			'Review.id AS `Review.review_id`',
			'Review.pid AS `Review.listing_id`', 
			'Review.mode AS `Review.extension`',
			'Review.created AS `Review.created`',
			'Review.modified AS `Review.modified`',
			'Review.userid AS `User.user_id`',
			'Review.name AS `User.name`',
			'Review.username AS `User.username`',
			'Review.email AS `User.email`',
			'Review.ipaddress AS `User.ipaddress`',
			'Review.title AS `Review.title`',
			'Review.author AS `Review.editor`',
			'Review.published AS `Review.published`'		
		);
		
		$conditions[] = 'Review.mode = ' . $this->Quote($extension);
		
		$queryData = array(
			'fields'=>$fields,
			'conditions'=>$conditions,
			'offset'=>$this->offset,
			'limit'=>$this->limit,
			'order'=>$order					
		);

		# Don't run it here because it's run in the Everywhere Observer Component
		$this->Review->runProcessRatings = false;

		$reviews = $this->Review->findAll($queryData,false);

		$count = $this->Review->findCount($queryData);
				
		$this->set(array(
				'stats'=>$this->stats,			
				'reviews'=>$reviews,
				'filter_order'=>$filter_order,
				'extension'=>$extension,
				'entry_title'=>$filter_entry_title,
				'pagination'=>array('total'=>$count
				)
			)
		);	
	
	}

	function edit() {
			
		$this->autoRender = false;		
		$this->autoLayout = false;		

		$this->components = array('everywhere');
		$this->__initComponents();
		
		$this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model	
				
		$reviewid = Sanitize::getInt( $this->passedArgs, 'reviewid', '' );
		$catid = Sanitize::getInt( $this->passedArgs, 'catid', '' );
		$option = Sanitize::getString( $this->passedArgs, 'extension', '' );

		# Get criteriaid from catid
		$query = "SELECT criteriaid FROM #__jreviews_categories WHERE id = $catid AND `option` = " . $this->Quote($option);

		$this->_db->setQuery($query);
		
		$criteriaid = $this->_db->loadResult();

		$fields = array(
			'Criteria.id AS `Criteria.criteria_id`',
			'Criteria.criteria AS `Criteria.criteria`',
			'Criteria.tooltips AS `Criteria.tooltips`',
			'Criteria.weights AS `Criteria.weights`'		
		);
		
		$review = $this->Review->findRow(
			array(
				'fields'=>$fields,
				'conditions'=>array('Review.id = ' . $reviewid ),
			)
		);		
		
		$this->passedArgs['component'] = $option;
		
		$this->set(
			array(
				'inAdmin'=>true,
				'passedArgs'=>$this->passedArgs,
				'User'=>$this->_user,
				'Access'=>$this->Access,
//				'criteria'=>$criteria,
				'review'=>$review,
			)
		);
		
		return $this->render('reviews','create');
	}
	
	function _save($params) {

		$xajax = new xajaxResponse();

		# Clean formValues
		$this->data['Review']['pid'] = $pid = Sanitize::getInt($this->data['Review'],'pid',0);
			
		$this->data['Criteria']['id'] = Sanitize::getInt($this->data['Criteria'],'id',0);
		$this->data['Review']['name'] = Sanitize::getString($this->data['Review'],'name','');
		$this->data['Review']['email'] = Sanitize::getString($this->data['Review'],'email','');
		$this->data['Review']['title'] = Sanitize::getString($this->data['Review'],'title','');
		$this->data['Review']['comments'] = Sanitize::getString($this->data['Review'],'comments','');
		$this->data['Review']['mode'] = Sanitize::getString($this->data['Review'], 'mode', 'com_content');
				
		# Validate standard fields
		# Validate standard fields
		$this->Review->validateInput($this->data['Review']['name'], "name", "text", __t("You must fill in your name.",true), !$this->_user->id);

		$this->Review->validateInput($this->data['Review']['email'], "email", "email", __t("You must fill in a valid email address.",true), $this->Config->reviewform_email && !$this->_user->id && $isNew);
		
		$this->Review->validateInput($this->data['Review']['title'], "title", "text", __t("You must fill in a title for the review.",true), $this->Config->reviewform_title);

		# Validate rating fields
		$criteria_qty = count($this->data['Rating']['ratings']);
		
		$ratingErr = 0;
		
		for ( $i = 0;  $i < $criteria_qty; $i++ ) 
		{
			if (!$this->data['Rating']['ratings'][$i] || $this->data['Rating']['ratings'][$i]=='' ) {
				$ratingErr++;
			}
		}
		$this->Review->validateInput('', "rating", "text", sprintf(__t("You are missing a rating in %s criteria.",true),$ratingErr), $ratingErr);	
	
		$this->Review->validateInput($this->data['Review']['comments'], "comments", "text", __t("You must fill in your comment.",true), $this->Config->reviewform_comment);		
	 
		# Process validation errors
		$msg = $this->Review->validateGetErrorAlert();
	
		if ($msg != '') 
		{
			$xajax->alert($msg);
	
			$xajax->assign("submitButton","disabled",false);
			
			$xajax->assign("cancel","disabled",false);
	
			return $xajax;
		}	

		$savedReview = $this->Review->save($this->data, $this->Access);
		
		# Update table
		$title = $this->data['Review']['title'] != '' ? $this->data['Review']['title'] : "[No title, click to edit]";

        $xajax->assign('title_'.$this->data['Review']['id'],"innerHTML",$title);

		$xajax->script('tb_remove();');
		        
        $xajax->call('flashRow','reviews'.$this->data['Review']['id']);		
			
		return $xajax;
	}	
	
	function _delete($params) {
	
		$xajax = new xajaxResponse();

		$data = array_shift($params);
	
		$cid = array($data['row_id']);

        // Calls delete method in the review model
        $msg = $this->Review->delete($cid);
        
        if($msg!==true)  {
            $xajax->alert($msg);            
            return $xajax;
        }
        
		$xajax->call('removeRow',"reviews{$cid[0]}");
		
		// Clear cache
		clearCache('', 'views');
		clearCache('', '__data');
		
		return $xajax;
	}	
}
