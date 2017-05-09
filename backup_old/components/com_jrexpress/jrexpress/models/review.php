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

class ReviewModel extends MyModel {
		
	var $name = 'Review';
	
	var $useTable = '#__jreviews_comments AS Review';

	var $primaryKey = 'Review.review_id';
	
	var $realKey = 'id';
	
	var $fields = array(
		'Review.id AS `Review.review_id`',
		'Review.pid AS `Review.listing_id`', 
		'Review.mode AS `Review.extension`',
		'Review.created AS `Review.created`',
		'Review.modified AS `Review.modified`',
		'Review.userid AS `User.user_id`',
		'CASE WHEN CHAR_LENGTH(User.name) THEN User.name ELSE Review.name END AS `User.name`',		
		'CASE WHEN CHAR_LENGTH(User.username) THEN User.username ELSE Review.username END AS `User.username`',		
		'Review.email AS `User.email`',
		'Review.location AS `User.location`', // Old, should use custom field instead
		'Review.ipaddress AS `User.ipaddress`',
		'Review.title AS `Review.title`',
		'Review.comments AS `Review.comments`',
		'Review.author AS `Review.editor`',
		'Review.published AS `Review.published`',
		'Rating.ratings AS `Rating.ratings`',
		'(Rating.ratings_sum/Rating.ratings_qty) AS `Rating.average_rating`',
		'Vote.yes AS `Vote.yes`',
		'Vote.no AS `Vote.no`',
		'(Vote.yes/(Vote.yes+Vote.no))*100 AS `Vote.helpful`'
//		'Criteria.id AS `Criteria.criteria_id`',
//		'Criteria.criteria AS `Criteria.criteria`',
//		'Criteria.tooltips AS `Criteria.tooltips`',
//		'Criteria.weights AS `Criteria.weights`'
	);
	
	var $joins = array(
		'ratings'=>'LEFT JOIN #__jreviews_ratings AS Rating ON Review.id = Rating.reviewid',
		'votes'=>'LEFT JOIN #__jreviews_votes AS Vote ON Review.id = Vote.reviewid',
//		'listings'=>'LEFT JOIN #__content AS Listing ON Review.pid = Listing.id', // Overriden in controller for jReviewsEverywhere
//		'jreviews_categories'=>'LEFT JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id', // AND JreviewsCategory.`option`=\'com_content\'
//		'criteria'=>'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
		'user'=>'LEFT JOIN #__users AS User ON Review.userid = User.id'
	);
	
	var $conditions = array();
	
	var $group = array('Review.id');
	
	var $runProcessRatings = true;
		
    /*
    * Centralized review delete function
    * *
    * @param array $review_ids
    */
    
    function delete($review_ids){
            
        $tables_rel = array();

        $del_id = 'id';
        $del_id_rel = 'reviewid';
        $table_rel = array();
        $table = "#__jreviews_comments";
        $tables_rel[] = "#__jreviews_ratings";
        $tables_rel[] = "#__jreviews_votes";
        $tables_rel[] = "#__jreviews_votes_tmp";
        $tables_rel[] = "#__jreviews_report";
    
        if (!empty($review_ids))
        {
            $review_ids = implode(',', $review_ids);
            $this->_db->setQuery("DELETE FROM $table WHERE $del_id IN ($review_ids)");
            if (!$this->_db->query()) {
                return $this->_db->getErrorMsg();
            }
    
            if (count($tables_rel)) {
                foreach ($tables_rel as $table_rel) {
                    $this->_db->setQuery("DELETE FROM $table_rel WHERE $del_id_rel IN ($review_ids)");
                    if (!$this->_db->query()) {
                        return $this->_db->getErrorMsg();
                    }
                }
            }
    
        }
        
        return true;                
        
    }    
        
	function getRankList() {
        
		# Check for cached version		
		$cache_prefix = 'review_model_ranklist';
		$cache_key = func_get_args();
		if($cache = S2cacheRead($cache_prefix,$cache_key)){
			return $cache;
		}
						
        $excludeEditorReviews = Configure::read('Jreviews.editor_rank_exclude');
                        
		$query = "SELECT Review.userid, count(Review.userid) as review_count,"
		. "\n sum(Vote.yes)/(sum(Vote.yes)+sum(Vote.no)) as helpful,"
		. "\n (count(Review.id)*(sum(Vote.yes)/(sum(Vote.yes)+sum(Vote.no)))) is null AS vote_null"
		. "\n FROM #__jreviews_comments AS Review"
		. "\n LEFT JOIN #__jreviews_votes AS Vote ON Review.id = Vote.reviewid"
		. "\n WHERE Review.published = 1 AND Review.userid > 0"
        . ($excludeEditorReviews ? "\n  AND Review.author = 0" : "")
		. "\n GROUP BY Review.userid"
		. "\n ORDER BY review_count DESC, helpful DESC, vote_null ASC"
		;

		$this->_db->setQuery($query);

		$rows = $this->_db->loadAssocList();

		$userids = array();

		if ($rows) {
			$i = 0;
			while(isset($rows[$i])) {
			   $userids[$rows[$i]['userid']] = $i+1;
			   $i++;
			}
		}

		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$userids);
				
		return $userids;
	}
	
	function getReviewExtension($review_id) {

		$query = "SELECT Review.`mode` FROM #__jreviews_comments AS Review WHERE Review.id = " . (int) $review_id;
		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}
	
	function getReviewerTotal() {
		
		$this->_db->setQuery(
			"SELECT COUNT(DISTINCT(userid))"
			."\n FROM #__jreviews_comments AS Review"
			."\n WHERE Review.published = 1 AND Review.userid > 0 AND Review.author = 0" 
		);
		
		return $this->_db->loadResult();
	}
	
	function getRankPage($page,$limit) {
		     
		# Check for cached version		
		$cache_prefix = 'review_model_rankpage';
		$cache_key = func_get_args();
		if($cache = S2cacheRead($cache_prefix,$cache_key)){
			return $cache;
		}	

		$offset = (int)($page-1)*$limit;
		
		$fields = array(
			'Review.userid AS `User.user_id`',
			'User.name AS `User.name`',
			'User.username AS `User.username`',
			'count(Review.userid) AS `Review.count`',
			'sum(yes)/(sum(yes)+sum(no)) AS `Vote.helpful`',
			'sum(yes)+sum(no) AS `Vote.count`',
			'(count(Review.id)*(sum(yes)/(sum(yes)+sum(no)))) is null AS `Vote.is_null`' 
		);
		
        $excludeEditorReviews = Configure::read('Jreviews.editor_rank_exclude');        
		
		$query = "SELECT " . implode(',',$fields)
		 ."\n FROM #__jreviews_comments AS Review"
		 ."\n RIGHT JOIN #__users AS User ON Review.userid = User.id"
		 ."\n LEFT JOIN #__jreviews_votes AS Vote ON Review.id = Vote.reviewid"
		 ."\n WHERE Review.published = 1 AND Review.userid > 0"
        . ($excludeEditorReviews ? "\n  AND Review.author = 0" : "")
		 ."\n GROUP BY Review.userid"
		 ."\n ORDER BY `Review.count` DESC, `Vote.helpful` DESC, `Vote.is_null` ASC"
		 ."\n LIMIT $offset, $limit"
		 ;
		 
		$this->_db->setQuery($query);

		$temp = $this->_db->loadObjectList();

		$temp = $this->__reformatArray($temp);

		# Add Community Builder info to results array
		if(!defined('MVC_FRAMEWORK_ADMIN') && class_exists('CommunityModel')) {
			$Community = registerClass::getInstance('CommunityModel');
			$results = $Community->addProfileInfo($temp, 'User', 'user_id');
		}

		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$results);		
		
		return $results;
		 
	}
		
	function getAverageRating($listing_id,$component,$weights) 
	{	
		# Check for cached version		
		$cache_prefix = 'review_model_avgrating';
		$cache_key = func_get_args();
		if($cache = S2cacheRead($cache_prefix,$cache_key)){
			return $cache;
		}		
					
		$output = array();
		
		$query = "SELECT Rating.ratings"
		."\n FROM #__jreviews_comments AS Review"
		."\n INNER JOIN #__jreviews_ratings AS Rating ON Review.id = Rating.reviewid"
		."\n WHERE Review.pid = '$listing_id' AND Review.published = 1"
		."\n AND Review.author = 0"
		."\n AND Review.mode = '$component'"
//		."\n GROUP BY reviews.id"
		;
		
		$this->_db->setQuery($query);
		
		$rows = $this->_db->loadAssocList();
							
		if ($rows) {

			$weighted = (is_array($weights) && array_sum($weights) == 100 ? 1 : 0);
			
			$reviewCount = count($rows);
			
			foreach ($rows as $rating) {

				$ratings_array = explode(',',$rating['ratings']);
				
				// Calculates the totals for each criteria
				for ($j = 0;$j<count($ratings_array);$j++) 
				{
					if (isset($sumRatings[$j])) 
					{
						$sumRatings[$j] += $ratings_array[$j];
					} else 
					{
						$sumRatings[$j] = $ratings_array[$j];
					}
				}

			}

			// Outputs the criteria averages
			for ($i=0;$i<count($sumRatings);$i++) {

				$row = array();
				
				$output['ratings'][] = $sumRatings[$i]/$reviewCount;

				if ($weighted) 
				{
					$sumRatingsWeighted[] = $sumRatings[$i]*$weights[$i]/100;
				}

			}

			if ($weighted) 
			{
				$output['average_rating'] = array_sum($sumRatingsWeighted)/$reviewCount;
			} else {
				$output['average_rating'] = array_sum($sumRatings)/$reviewCount/count($ratings_array);
			}

		}
		
		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$output);	
							
		return $output;

	}
	
	function processSorting($selected = null) {

		$order = '';
			
		switch ( $selected ) {
		  	case 'rdate':
		  		$order = '`Review.created` DESC';
		  		break;
		  	case 'date':
		  		$order = '`Review.created` ASC';
		  		break;
		  	case 'rating':
		  		$order = '`Rating.average_rating` DESC, `Review.created` DESC';
		  		break;
		  	case 'rrating':
		  		$order = '`Rating.average_rating` ASC, `Review.created` DESC';
		  		break;
		  	case 'helpful':
		  		$order = '`Vote.helpful` DESC, `Vote.yes` DESC, `Rating.average_rating` DESC';
		  		break;
		  	case 'rhelpful':
		  		$order = '`Vote.helpful` ASC, `Vote.no` DESC, `Rating.average_rating` DESC';
		  		break;		  		
			default:
				$order = '`Review.created` DESC';
		 		break;
		}
	
		return $order;
	}	
	
	function save(&$data,$Access) {
				
		$userid = $this->_user->id;
				
		# Initialize author key
		$data['Review']['author'] = 0;
		
		# Check if this is a new review or an updated review
		$isNew = (int) $data['Review']['id'] > 0 ? false : true;
		$review_id = (int) $data['Review']['id'];
		
		$output = array("err" => '', "reviewid" => '', "author" => 0 );

		# If new then assign the logged in user info. Zero if it's a guest
		if ($isNew) {
			# Validation passed, so proceed with saving review to DB
			$data['Review']['ipaddress'] = $_SERVER['REMOTE_ADDR'];
			$data['Review']['userid'] = $this->_user->id;
			$data['Review']['created'] = gmdate('Y-m-d H:i:s');
		}

		# Edited review
		if (!$isNew) 
		{
			appLogMessage('*********Load current info because we are editing the review','database');
			
			$this->stopAfterFind = true;
								
			// Load the review info
			$row = $this->findRow(array('conditions'=>array('Review.id = ' . $review_id)));
			
			unset($this->stopAfterFind);
			
			// If user editing is the same as reviewer then update the modified date
			if ( $this->_user->id == $row['User']['user_id']) {
				$data['Review']['ipaddress'] = $_SERVER['REMOTE_ADDR'];
				$data['Review']['modified'] = gmdate('Y-m-d H:i:s');		
			}
			
			$data['Review']['author'] = $row['Review']['editor'];			
		} 
		
		# Complete user info for new reviews
		if ($isNew && $this->_user->id > 0) {
			$data['Review']['name'] = $this->_user->name;
			$data['Review']['username'] = $this->_user->username;			
			$data['Review']['email'] = $this->_user->email;			
		} elseif(!$isNew) {
			unset($data['Review']['name']);
			unset($data['Review']['username']);
			unset($data['Review']['email']);			
		}
		
		# Complete publish settings based on moderation
		if ($Access->moderateReview && $isNew) 
		{
			$data['Review']['published'] = 0;
		
		} else {
			
			$data['Review']['published'] = 1;
		}		

		# Marks review as editor review if conditions are met and this is core content entry
		if ($isNew && $data['Review']['mode'] == 'com_content')
		{
			if ($Access->isJreviewsEditor($this->_user->id)) 
			{
				$editor_count = $this->findCount(
					array(
						'conditions'=>array(
							'Review.pid = ' . $data['Review']['pid'],
							'Review.author = 1',
							"Review.mode = '" . $data['Review']['mode'] . "'"
						)	
					)						
				);
														
				if ($editor_count > 0) 
				{
					$data['Review']['author'] = 0;

				} else {
					
					$data['Review']['author'] = 1;
					
				}
			}
		}

		# Get criteria info	to process ratings
		appLogMessage('*******Get criteria info to process ratings','database');

		$CriteriaModel = RegisterClass::getInstance('CriteriaModel');
		$criteria = $CriteriaModel->findRow(
			array(
				'conditions'=>array('Criteria.id = '. $data['Criteria']['id'])
			)
		);
		
		// Complete review info with $criteria info
		$data = array_insert($data,$criteria);
		
		// Process rating data
		$ratings_qty = count($data['Rating']['ratings']);
		
		$ratings_sum = 0;		
		
		if (trim($criteria['Criteria']['weights'])!='') 
		{
			$weights = explode ("\n", $criteria['Criteria']['weights']);
			
			foreach ($data['Rating']['ratings'] as $key=>$rating)
			{
				$ratings_sum += $rating*$weights[$key]/100;
			}
			
			$ratings_sum = $ratings_sum*$ratings_qty; // This is not the real sum, but it is divided again in the queries.

		} else {
			$ratings_sum = array_sum($data['Rating']['ratings']);
		}		
		
		$data['average_rating'] = $ratings_sum/$ratings_qty;
		$data['new'] = $isNew ? 1 : 0;

		# Save standard review fields
		appLogMessage('*******Save standard review fields','database');
		$save = $this->store($data);

		if(!$save) {
			appLogMessage('*******There was a problem saving the review fields','database');	
			return false;	
		}
		
		$data['Rating']['reviewid'] = $data['Review']['id'];
		$data['Rating']['ratings'] = implode(',',$data['Rating']['ratings']);
		$data['Rating']['ratings_sum'] = $ratings_sum;
		$data['Rating']['ratings_qty'] = $ratings_qty;

		# Save rating fields
		appLogMessage('*******Save standard rating fields','database');
		if($isNew || (!$isNew && Sanitize::getString($row['Rating'],'ratings','') == '')) {
			$save = $this->insert( '#__jreviews_ratings', 'Rating', $data, 'reviewid');
		} else {
			$save = $this->update( '#__jreviews_ratings', 'Rating', $data, 'reviewid');
		}
		
		if(!$save) {
			appLogMessage('*******There was a problem saving the ratings','database');	
			return false;	
		}
		
		return $output;		
	}		
	
	function afterFind($results) 
	{
		if (empty($results) || isset($this->stopAfterFind) && $this->stopAfterFind === true) {
			return $results;
		}
		
		# Check for cached version		
		$cache_prefix = 'review_model';
		$firstRow = current($results);
		$cache_key = array(array_keys($results),array_keys($firstRow['Review']));
		if($cache = S2cacheRead($cache_prefix,$cache_key)){
			return $cache;
		}
					
		$sumRatings = array();
                
		# Add Community Builder info to results array
		if(!defined('MVC_FRAMEWORK_ADMIN') && class_exists('CommunityModel')) {
			$Community = registerClass::getInstance('CommunityModel');
			$results = $Community->addProfileInfo($results, 'User', 'user_id');
		}

		# User rank
		if(!defined('MVC_FRAMEWORK_ADMIN') && !isset($this->rankList)) {         			
			$this->rankList = $this->getRankList();
		}
		
		# Preprocess criteria and rating information
		if($this->runProcessRatings) {
   			$results = $this->processRatings($results);
		}
	
		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$results);
		
		return $results;
	}
	
	/**
	 * Pre-process criteria and rating information
	 */	
	function processRatings($results) {

		$single_row = false;
		

		foreach($results AS $key=>$result) 
		{
			if(is_string($results[$key]['Rating']['ratings'])) {
			$results[$key]['Rating']['ratings'] = explode(',',$results[$key]['Rating']['ratings']);
			}
			
			if(isset($result['Criteria']['criteria']) && $result['Criteria']['criteria'] != '' && is_string($result['Criteria']['criteria'])) {
				$results[$key]['Criteria']['criteria'] = explode("\n",$results[$key]['Criteria']['criteria']);
			}

			if(isset($result['Criteria']['tooltips']) && $result['Criteria']['tooltips'] != '' && is_string($result['Criteria']['tooltips'])) {
				$results[$key]['Criteria']['tooltips'] = explode("\n",$results[$key]['Criteria']['tooltips']);
			}

			# Calculate weighted average rating for each review
			if(isset($result['Criteria']['weights']) && $result['Criteria']['weights'] != '' && is_string($result['Criteria']['weights'])) {
				
				$results[$key]['Criteria']['weights'] = explode("\n",$results[$key]['Criteria']['weights']);
			
				$weighted_average = 0;
				
				if (array_sum($results[$key]['Criteria']['weights']) == 100) 
				{
					$i = 0;
					while(isset($results[$key]['Rating']['ratings'][$i])) {
						$weighted_average = $weighted_average + $results[$key]['Rating']['ratings'][$i]*$results[$key]['Criteria']['weights'][$i]/100;
						$i++;
					}

					$results[$key]['Rating']['average_rating'] = $weighted_average;
				}			
			}

			if($results[$key]['User']['user_id']>0 && isset($this->rankList[$results[$key]['User']['user_id']])) {

				$results[$key]['User']['review_rank'] = $this->rankList[$results[$key]['User']['user_id']];
			} else {
				$results[$key]['User']['review_rank'] = null;
			}

		}
		
		return $results;		
	}
	
	function getTemplateSettings($review_id) {
		
		# Check for cached version		
		$cache_prefix = 'review_model_themesettings';
		$cache_key = func_get_args();
		if($cache = S2cacheRead($cache_prefix,$cache_key)){
			return $cache;
		}			
						
		$fields = array(
			'JreviewsSection.tmpl AS `Section.tmpl_list`',
			'JreviewsSection.tmpl_suffix AS	`Section.tmpl_suffix`',
			'JreviewsCategory.tmpl AS `Category.tmpl_list`',
			'JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`'		
		);
		
		$query = "SELECT " . implode(',',$fields)
		. "\n FROM #__jreviews_comments AS Review"
		. "\n LEFT JOIN #__content AS Listing ON Review.pid = Listing.id"		
		. "\n LEFT JOIN #__categories AS Category ON Listing.catid = Category.id"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id"
		. "\n LEFT JOIN #__sections AS Section ON Category.section = Section.id"
		. "\n LEFT JOIN #__jreviews_sections AS JreviewsSection ON Section.id = JreviewsSection.sectionid"
		. "\n WHERE JreviewsCategory.option = 'com_content' AND Review.id = " . $review_id
		;
		
		$this->_db->setQuery($query);
		
		$result = end($this->__reformatArray($this->_db->loadAssocList()));		

		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$result);
		
		return $result;
	}	
}
