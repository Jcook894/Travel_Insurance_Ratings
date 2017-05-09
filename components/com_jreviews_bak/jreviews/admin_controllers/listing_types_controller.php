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

class ListingTypesController extends MyController {
	
	var $uses = array('acl','criteria', 'review');
	
	var $helpers = array('html','form','jreviews','admin/admin_criterias');
    
    var $components = array('config');
	
	var $autoRender = false;
	
	var $autoLayout = false;
    
    var $__listings = array();

	function beforeFilter() 
    {
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

	function index() 
    {
		$rows = $this->Criteria->getList();
	 					
	 	$table = $this->listViewTable($rows);
		
		$this->set(array('table'=>$table));

		return $this->render();
	}	
	
	function listViewTable($rows) 
    {
		foreach($rows AS $key=>$row) {
			
			$groupList = '';
			
			if ($row->groupid != '') 
			{
				$groups = explode (",", $row->groupid);

				foreach ($groups as $group) {
					$this->_db->setQuery("SELECT CONCAT(name,' (',IF(type=\"content\",\"listing\",type),')') AS `group` FROM #__jreviews_groups WHERE groupid = $group");
					$result = $this->_db->loadResult();

					if($result != '') {
						$groupList .= "<li>$result</li>";
					}
				}
				
				$rows[$key]->field_groups = "<ul>$groupList</ul>";
			}

		}
						
		$this->set(array(
			'rows'=>$rows
		));
		
		return $this->render('listing_types','table');
	
	}
		
	function edit() 
    {
		$this->name = 'listing_types';
		$this->action = 'edit';
	
		$this->autoRender = false;
				
		$criteriaid =  (int) $this->data['criteria_id'];

		$reviews = '';
		
		if ($criteriaid) 
        {
			$criteria = $this->Criteria->findRow(array('conditions'=>array('id = ' . $criteriaid)));
			
			// check if reviews exist, also used in _save
			$query = "
				SELECT
					COUNT(*)
				FROM
					#__jreviews_comments AS Reviews
				INNER JOIN
					#__content AS Content ON Content.id = Reviews.pid
				INNER JOIN
					#__categories AS Cat ON Cat.id = Content.catid	
				INNER JOIN
					#__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Cat.id
				WHERE
					JreviewsCategory.criteriaid = $criteriaid
			";
			$this->_db->setQuery($query);
			$reviews = $this->_db->loadResult();
			
		} else {
			$criteria = $this->Criteria->emptyModel();
			$criteria['Criteria']['state'] = 1;
			$criteria['Criteria']['group_id'] = '';
		}
		
		// create custom field groups select list
		$this->_db->setQuery("
            SELECT 
                groupid AS value, 
                CONCAT(name,' - ',UPPER(IF(type=\"content\",\"listing\",type))) AS text 
            FROM 
                `#__jreviews_groups` 
            ORDER BY 
                type, name"
            );
	
		$groups = $this->_db->loadObjectList();
		
		foreach ( array('criteria', 'weights', 'tooltips') as $v )
		{
			$criteriaDisplay[$v] = explode("\n", $criteria['Criteria'][$v]);
		}
        
        $criteriaDisplay['required'] = explode("\n", $criteria['Criteria']['required']);

		$this->set(		
			array(
				'criteria'=>$criteria,
				'groups'=>$groups,
				'criteriaDisplay' => $criteriaDisplay,
				'reviewsExist' => $reviews,
                'accessGroups' => $this->Acl->getAccessGroupList(),
                'rowId' => count($criteriaDisplay['criteria']),
                'listingTypes'=>$this->Criteria->getSelectList()
			)		
		);	
		
		$page = $this->render();
        return $page;        
	}
	
	function _save() 
    {
		$this->action = 'index';
		$criteriaid = $this->data['Criteria']['id'];
		$reviews = array();
        $apply = Sanitize::getBool($this->data,'apply',false);
        
		// revert all input arrays to strings
		foreach ( array('criteria', 'required', 'weights', 'tooltips') as $v )
		{
            if($v == 'tooltips') {
                $this->data['Criteria'][$v] = implode("\n", $this->data['__raw']['Criteria'][$v]);
            }
            else {
                $this->data['Criteria'][$v] = implode("\n", $this->data['Criteria'][$v]);
            }
        } 

        # Configuration overrides - save as json object
        // Pre-process access overrides first
        $keys = array_keys($this->data['Criteria']['config']);
        $access_keys = array('addnewaccess','addnewaccess_reviews');
        while($access_key = array_shift($access_keys))
        {
            $this->data['Criteria']['config'][$access_key] = in_array($access_key,$keys) ? implode(',',$this->data['Criteria']['config'][$access_key]) : 'none';
        }
        
        $this->data['Criteria']['config'] = json_encode(Sanitize::getVar($this->data['Criteria'],'config'));

		// Lets remove any blank lines from the new criteria
		$newCriteria = cleanString2Array($this->data['Criteria']['criteria'],"\n");
		
		// clean Required field
		$newRequired = cleanString2Array($this->data['Criteria']['required'],"\n"); 
	      
		// Lets remove any blank lines from the new criteria
		$newTooltips = cleanString2Array($this->data['Criteria']['tooltips'],"\n");
	
		// New weights
		$newWeights = cleanString2Array($this->data['Criteria']['weights'],"\n");
	
		// Begin basic validation
		$msg = array();
	
		if ($this->data['Criteria']['title']=='') {
			$msg[] = "Fill in the criteria set name.";
		}

		if ($this->data['Criteria']['state'] == 1 ) {
	
			if ($this->data['Criteria']['criteria']=='') {
				$msg[] = "Add at least one criteria to rate your items.";
			}
			if ($this->data['Criteria']['weights']!='') {
				if (round(array_sum(explode("\n",$this->data['Criteria']['weights']))) != 100 && trim($this->data['Criteria']['weights']) != '' )
					$msg[] = "The criteria weights have to add up to 100.";
			}
			if (count($newCriteria) != count($newWeights) && count($newWeights) > 0 ) {
				$msg[] = "The number of criteria does not match the number of weights. Check your entries.";
			}
			if (count($newTooltips) > count($newCriteria)) {
				$msg[] = "There are more tooltips than criteria, please remove the extra tooltips. You may leave blank lines for tooltips if there's a criteria that will not have a tooltip, but the number of lines must match the number of criteria";
			}
			
			if ( count($newRequired) != count($newCriteria) )
			{
				$msg[] = "The number of criteria does not match the number of the 'Required' fields.";
			}
			
	
		} else {
			// if input invalid default to 0
			if ( !in_array( $this->data['Criteria']['state'], array(0,2) ) )
			{
				$this->data['Criteria']['state'] = 0;
			}
		}
	
		if (count($msg) > 0) 
        {
            $action = 'error';
            $text = implode("<br />",$msg);
            return $this->ajaxResponse(compact('action','text'),false);            
		}

		// If this is a new criteria, proceed to save
		if ($criteriaid) 
        {
            // We are in edit mode so let's check if the number of criteria has changed
            $criteria = $this->Criteria->findRow(array('conditions'=>array('id = ' . $criteriaid)));
        
            if (count($newCriteria) != count(cleanString2Array($criteria['Criteria']['criteria']))) 
            {
                $query = "
                    SELECT
                        COUNT(*)

                    FROM
                        #__jreviews_comments AS Reviews
                    INNER JOIN
                        #__content AS Content ON Content.id = Reviews.pid
                    INNER JOIN
                        #__categories AS Cat ON Cat.id = Content.catid    
                    INNER JOIN
                        #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Cat.id
                    WHERE
						Review.mode = 'com_content' 
						AND 
                        JreviewsCategory.criteriaid = $criteriaid
                ";
                $this->_db->setQuery($query);
                $reviews = $this->_db->loadResult();
                
                // Todo: there are no 'everywhere' checks. will have to go component by component..
            
                if ($reviews) 
                {  // There are reviews so saving is denied.                    
                    $action = 'error';
                    $text = "There are $reviews reviews in the system for listings using this listing type which prevent you from changing the number of criteria. You can only edit the criteria labels, but not add or remove criteria unless you first delete the existing $reviews reviews.";
                    return $this->ajaxResponse(compact('action','text'),false);
                }            
            }
		}

        // Lets remove any blank lines from the new criteria
        $newCriteriaArray = cleanString2Array($this->data['Criteria']['criteria'],"\n");
        $this->data['Criteria']['criteria'] = implode("\n",$newCriteriaArray); //Reconstruct the string using the cleaned-up array
        $this->data['Criteria']['qty'] = count($newCriteriaArray);
    
        // Remove blank lines from weights
        $newWeightsArray = cleanString2Array($this->data['Criteria']['weights'],"\n");        
        $this->data['Criteria']['weights'] = implode("\n",$newWeightsArray);
        
        // for Required
        $newRequiredArray = cleanString2Array($this->data['Criteria']['required'],"\n");        
        $this->data['Criteria']['required'] = implode("\n",$newRequiredArray);
        
        // Convert groupid array to list
        if(isset($this->data['Criteria']['groupid'][0]) && is_array($this->data['Criteria']['groupid'][0])) {
            $this->data['Criteria']['groupid'] = implode(',',$this->data['Criteria']['groupid'][0]);
        } elseif(isset($this->data['Criteria']['groupid']) && is_array($this->data['Criteria']['groupid'])) {
            $this->data['Criteria']['groupid'] = implode(',',$this->data['Criteria']['groupid']);
        } else {
            $this->data['Criteria']['groupid'] = '';            
        }
        
        $this->Criteria->store($this->data);

        if($apply)
        {
            $action = 'apply';
            return $this->ajaxResponse(compact('action'),false);
        }   
         
        $action = 'success';
        
        $page = $this->index();
        
        $row_id = "criteria".$this->data['Criteria']['id']; 
        
        return $this->ajaxResponse(compact('action','page','row_id'),false);
	}
	
	function _delete() 
    {			
        $response = array();
		$id = Sanitize::getInt($this->data,'entry_id');
        if(!$id) return $this->ajaxResponse($response);
		// Check if the criteria is being used by a category
		$this->_db->setQuery("
            SELECT 
                count(*) 
            FROM 
                #__jreviews_categories 
            WHERE 
                criteriaid = " . $id
        );
		
		if ($count = $this->_db->loadResult()) 
        {
			$response[] = "s2Alert('You have {$count} categories using this listing type, first you need to delete them. Keep in mind that deleting the listing type will result in the removal of all reviews in listings using this listing type.<br /><br />However, your content and category structure will remain intact, even after you remove the category from the JReviews category manager.');";
			return $this->ajaxResponse($response);
		}

		$this->_db->setQuery("DELETE FROM #__jreviews_criteria WHERE id = ". $id);
		$this->_db->query();

		$this->_db->setQuery("SELECT id FROM #__jreviews_categories WHERE criteriaid = ". $id);
		$catids = $this->_db->loadResultArray();
		$catids = implode(',',$catids);

		// If the criteria is assigned to a category, delete all existing reviews
		if ($catids) 
        {
			$this->_db->setQuery("
                SELECT 
                    jc.id as reviewid 
                FROM 
                    #__content c
				INNER JOIN 
                    #__jreviews_comments jc on jc.pid = c.id
                WHERE c.catid IN ( ". $catids . ")"
            );
			
			$cid = $this->_db->loadResultArray();

			$del_id = 'id';
			$del_id_rel = 'reviewid';
			$tables_rel = array();
			$table = "#__jreviews_comments";
			$tables_rel[] = "#__jreviews_ratings";
//			$tables_rel[] = "#__jreviews_votes"; // Alejandro - need to add delete procedure for votes rows
			$tables_rel[] = "#__jreviews_report";
		} 
        else 
        {
			$response[] = "jreviews_admin.dialog.close();jreviews_admin.tools.removeRow('criteria{$id}');";
			return $this->ajaxResponse($response);
		}
	
		if (count($cid))
		{
			$ids = implode(',', $cid);
			$this->_db->setQuery("DELETE FROM $table WHERE $del_id IN ($ids)");
			if (!$this->_db->query()) 
            {
                $response[] = "s2Alert('".$this->_db->getErrorMsg()."');";
                return $this->ajaxResponse($response);
			}
	
			if (count($tables_rel)) 
            {
				foreach ($tables_rel as $table_rel) 
                {
					$this->_db->setQuery("DELETE FROM $table_rel WHERE $del_id_rel IN ($ids)");
					if (!$this->_db->query()) 
                    {
                        $response[] = "s2Alert('".$this->_db->getErrorMsg()."');";
                        return $this->ajaxResponse($response);
					}
				}
			}
	
		}
		
        $response[] = "jreviews_admin.dialog.close();jreviews_admin.tools.removeRow('criteria{$id}');";
		// Clear cache
		clearCache('', 'views');
		clearCache('', '__data');

		return $this->ajaxResponse($response);
	}	

	function _copy() 
    {
        $copies = Sanitize::getInt($this->data,'copies',0);
		$formValues = $this->params['form'];
        $response = array();
		if ( !empty($formValues['criteriaid']) )
		{
			$criteriaid = $formValues['criteriaid'];
		}
		else
		{
			$response[] = "s2Alert('You didn't select a listing type to copy, try again.');";
            return $this->ajaxResponse($response,true);
		}
	
		$criteria = $this->Criteria->findRow(array('conditions'=>array('id = ' . $criteriaid)));
		$newCriteria = array();
		$newCriteria['Criteria']['title'] = 'Copy of ' . $criteria['Criteria']['title'];
		$newCriteria['Criteria']['criteria'] = $criteria['Criteria']['criteria'];
		$newCriteria['Criteria']['required'] = $criteria['Criteria']['required'];
		$newCriteria['Criteria']['weights'] = $criteria['Criteria']['weights'];
		$newCriteria['Criteria']['tooltips'] = $criteria['Criteria']['tooltips'];
		$newCriteria['Criteria']['qty'] = $criteria['Criteria']['quantity'];
		$newCriteria['Criteria']['groupid'] = $criteria['Criteria']['group_id'];
		$newCriteria['Criteria']['state'] = $criteria['Criteria']['state'];
	
		// store it in the db
		for ($i=1; $i<=$copies; $i++) 
        {
			$this->Criteria->store($newCriteria);
			$ids[] = $newCriteria['Criteria']['id'];
			unset($newCriteria['Criteria']['id']);
		}
	
		// Reloads the whole list to display the new/updated record 	
		$fieldrows = $this->Criteria->getList();
	 	
        $page = $this->listViewTable($fieldrows);

		foreach ($ids as $id) 
        {
			$response[]= "jreviews_admin.tools.flashRow('criteria{$id}');";
		}

        return $this->ajaxResponse($response,true,compact('page'));
	}
		
    /**
     * Recalculates the rating sum based on the weights assigned to each rating
     *
     */
    function refreshReviewRatings() 
    {
        error_reporting(E_ALL); ini_set('display_errors','On'); 
        ini_set('max_execution_time', 600); # 10 minutes
        
        // Get the list of category ids and weights
        $sql = "
            SELECT 
                criteria.id, criteria.weights, cat.id AS catid, cat.option 
            FROM 
                #__jreviews_criteria AS criteria
            INNER JOIN             
                #__jreviews_categories AS cat on cat.criteriaid = criteria.id
        ";
        
        $this->_db->setQuery($sql);
        $rows = $this->_db->loadObjectList();

        foreach ($rows as $row) {
            $weights_check = trim($row->weights);
            if ($weights_check != '') {
                if($row->catid>0){
                    // Using $row->option, otherwise may overwrite values if there are dup catid's across components
                    $weights[$row->catid.$row->option] = explode("\n",$row->weights); 
                }
            }
        }
     
        # working in chunks to avoid memory overload
        $this->_db->setQuery("SELECT COUNT(*) FROM #__jreviews_comments");
        $reviewCount = $this->_db->loadResult();
        $leap = 1000; # configurable
        
        for ( $offset = 0; $offset < $reviewCount; $offset += $leap ) # encompassing with for loop
        {
            // Get list of reviewids, category ids and ratings        
            $sql = "
                SELECT 
                    Review.id, Review.pid AS lid, Review.mode, 
                    Rating.ratings, Rating.ratings_sum, Rating.ratings_qty
                FROM 
                    #__jreviews_comments AS Review
                LEFT JOIN 
                    #__jreviews_ratings AS Rating ON Rating.reviewid = Review.id
                ORDER BY
                    Review.id
                LIMIT $offset, $leap
            "; # using left join, need comments table in any case for comment count
            $this->_db->setQuery($sql);
            $rows = $this->_db->loadObjectList();

            // Recalculate the total rating sum
            foreach ($rows as $key=>$row) {

                if ( empty($row->ratings) )
                {
                    continue;
                }
                
                // Load listings' Everywhere model
                $file_name = 'everywhere' . '_' . $row->mode;
                $class_name = inflector::camelize($file_name).'Model';
                App::import('Model',$file_name,'jreviews');
                $this->Listing = new $class_name();
  
                if(!isset($__listings[$row->mode.$row->lid]))
                    {
                        $listing = $this->Listing->findRow(array(
                            'conditions' => "Listing.{$this->Listing->realKey} = ".$row->lid
                        ),array());
                        
                        $__listings[$row->mode.$row->lid] = $listing;
                        
                    } 
                else 
                    {
                        $listing = $__listings[$row->mode.$row->lid];
                    }
                
                if(!is_array($listing) || empty($listing) || !$listing){
                    continue;
                }
                                                      
                $row->catid = array_key_exists('catid',$listing['Category']) ? $listing['Category']['cat_id'] : $listing['Listing']['cat_id']; 
                $__listings[$row->mode.$row->lid]['cat_id'] = $row->catid;
                
                $ratings_sum = 0;
                $ratings = explode (",",$row->ratings);
                $quantity = $row->ratings_qty;
        
                if (@is_array($weights[$row->catid.$row->mode])) {
                
                    $sumWeights = 
                        array_sum(
                            array_intersect_key(
                                $weights[$row->catid.$row->mode], 
                                array_filter(
                                    $ratings,
                                    create_function(
                                        '$el', 'return is_numeric($el);'
                                    )
                                )
                            )
                        )
                    ;
                    
                    if ( $sumWeights > 0 )
                    {
                        foreach ($ratings as $key2=>$rating) {
                            $ratings_sum += $rating * $weights[$row->catid.$row->mode][$key2] / $sumWeights;
                        }
                        
                        $ratings_sum = $ratings_sum*$quantity;
                    }
                    
                    $rows[$key]->ratings_sum = $ratings_sum;
                } else {
                    $rows[$key]->ratings_sum = array_sum($ratings);
                }
            }
            
            // Update database records
            foreach ($rows as $row) 
            {
                if ( empty($row->ratings) )
                {
                    continue;
                }
                
                $sql = "UPDATE #__jreviews_ratings SET ratings_sum = '$row->ratings_sum' WHERE reviewid = '$row->id'";              
                $this->_db->setQuery($sql);                
                if (!$this->_db->query()) 
                {
                    # halting script on first error so to avoid possible further damage to the database data
                    // Clear cache
                    clearCache('', 'views');
                    clearCache('', '__data');
                    return "There was a problem updating the database for review ID {$row->id}.<br />Operation halted.";
                }
            }
            
            # unset before more db data is loaded into memory 
            unset($rows);
        }
    
        $msg = 'Ratings averages update: Complete.<br /><br />';
        
        // Update listing totals        
        
        # reloading listing data for all listings. diving into chunks again
        $this->_db->setQuery("SELECT COUNT(DISTINCT pid) FROM #__jreviews_comments");
        $listingCount = $this->_db->loadResult();
        
        for ( $offset = 0; $offset < $listingCount; $offset += $leap )
        {
            $sql = "
                SELECT DISTINCTROW
                    Review.pid as lid, Review.mode
                FROM 
                    #__jreviews_comments AS Review
                ORDER BY
                    Review.pid
                LIMIT $offset, $leap
            ";
            $this->_db->setQuery($sql);
            $rows = $this->_db->loadObjectList();
           
            foreach ( $rows as $row )
            {
                if(isset($__listings[$row->mode.$row->lid]))
                {
                    $catid = $__listings[$row->mode.$row->lid]['cat_id'];
                    if ( !$this->Review->saveListingTotals($row->lid, $row->mode, !empty($weights[$catid.$row->mode]) ? $weights[$catid.$row->mode] : '') )
                    {
                        # halting script on first error so to avoid possible further damage to the database data        
                        // Clear cache
                        clearCache('', 'views');
                        clearCache('', '__data');

                        return $msg . "There was an error recalculating totals for listing ID {}$row->lid}.<br />Operation halted.";
                    }
                }
            }

            unset($rows);
        }

        // Clear cache
        clearCache('', 'views');
        clearCache('', '__data');
        
        return $msg . "Listings totals update: Complete.";
    } 
}
