<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );
App::import('Helper','routes','jreviews');

class EverywhereComContentModel extends MyModel  {
		
	var $UI_name = 'Content';
	
	var $name = 'Listing';
	
	var $useTable = '#__content AS Listing';
	
	var $primaryKey = 'Listing.listing_id';
	
	var $realKey = 'id';
	
	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'created';
   	
	var $extension = 'com_content';
	
	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
        'Listing.alias AS `Listing.slug`',
        'Category.alias AS `Category.slug`',
		'Listing.title AS `Listing.title`',
		'Listing.introtext AS `Listing.summary`',
		'Listing.fulltext AS `Listing.description`', 
		'Listing.images AS `Listing.images`',
		'Listing.hits AS `Listing.hits`',
		'Listing.catid AS `Listing.cat_id`',
		'Listing.created_by AS `Listing.user_id`',
		'Listing.created_by_alias AS `Listing.author_alias`',
		'Listing.created AS `Listing.created`',
        'Listing.modified AS `Listing.modified`',       
		'Listing.access AS `Listing.access`',
		'Listing.state AS `Listing.state`',
		'Listing.publish_up AS `Listing.publish_up`',		
		'Listing.publish_down AS `Listing.publish_down`',
		'Listing.metakey AS `Listing.metakey`',
		'Listing.metadesc AS `Listing.metadesc`',
		'\'com_content\' AS `Listing.extension`',
		'Category.id AS `Category.cat_id`',
		'Category.title AS `Category.title`',
        'cat_params'=>'Category.params AS `Category.params`', /* J16 */        
		'Directory.id AS `Directory.dir_id`',
		'Directory.desc AS `Directory.title`',
		'Directory.title AS `Directory.slug`',
		'criteria'=>'Criteria.id AS `Criteria.criteria_id`',
		'Criteria.title AS `Criteria.title`',
		'Criteria.criteria AS `Criteria.criteria`',
		'Criteria.tooltips AS `Criteria.tooltips`',
		'Criteria.weights AS `Criteria.weights`',
        'Criteria.required AS `Criteria.required`',       
		'Criteria.state AS `Criteria.state`',
        'Criteria.config AS `ListingType.config`',
		'`Field`.featured AS `Listing.featured`',
        'Frontpage.content_id AS `Listing.frontpage`',        
		'User.id AS `User.user_id`',
		'User.name AS `User.name`',
		'User.username AS `User.username`',
		'email'=>'User.email AS `User.email`',
        // User reviews
        'user_rating'=>'Totals.user_rating AS `Review.user_rating`',
        'Totals.user_rating_count AS `Review.user_rating_count`',
        'Totals.user_criteria_rating AS `Review.user_criteria_rating`',
        'Totals.user_criteria_rating_count AS `Review.user_criteria_rating_count`',
        'Totals.user_comment_count AS `Review.review_count`',
		// Editor reviews
        'editor_rating'=>'Totals.editor_rating AS `Review.editor_rating`', 
        'Totals.editor_rating_count AS `Review.editor_rating_count`',
        'Totals.editor_criteria_rating AS `Review.editor_criteria_rating`',
        'Totals.editor_criteria_rating_count AS `Review.editor_criteria_rating_count`',
        'Totals.editor_comment_count AS `Review.editor_review_count`',
        'Claim.approved AS `Claim.approved`'        
	);	
	
	var $joins = array(
        'JreviewsCategory'=>"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_content'",
        'Category'=>        "LEFT JOIN #__categories AS Category ON JreviewsCategory.id = Category.id",
        'ParentCategory'=>  "LEFT JOIN #__categories AS ParentCategory ON Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt",              
        'Section'=>         "LEFT JOIN #__sections AS Section ON Category.section = Section.id",
		'Total'=>           "LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_content'",
        'Field'=>           "LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Listing.id",
		                    "LEFT JOIN #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id",
		                    "LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id",
		'User'=>            "LEFT JOIN #__users AS User ON User.id = Listing.created_by",
        'Claim'=>           "LEFT JOIN #__jreviews_claims AS Claim ON Claim.listing_id = Listing.id AND Claim.approved = 1",        
        'Frontpage'=>       "LEFT JOIN #__content_frontpage AS Frontpage ON Frontpage.content_id = Listing.id"        
	);        
	
	/**
	 * Used to complete the listing information for reviews based on the Review.pid. The list of fields for the listing is not as
	 * extensive as the one above used for the full listing view
	 */
	var $joinsReviews = array(
		'LEFT JOIN #__content AS Listing ON Review.pid = Listing.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_content'",
		"LEFT JOIN #__categories AS Category ON Category.id = JreviewsCategory.id",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);
	
	var $conditions = array();

	var $limit;
	var $offset;
    var $order = array();

	function __construct() 
    {
		if(getCmsVersion() == CMS_JOOMLA15)
        {
            $this->fields[] = 'Listing.sectionid AS `Listing.section_id`';
            $this->fields[] = 'Section.id AS `Section.section_id`';
            $this->fields[] = 'Section.title AS `Section.title`';
            $this->fields[] = 'Section.alias AS `Section.slug`';
            $this->fields[] = 'Category.image AS `Listing.category_image`';
            unset($this->fields['cat_params'],$this->joins['ParentCategory']);
        }
        else
        {
            unset($this->joins['Section']);
        }
        
        parent::__construct();
		
		$this->tag = __t("Listing",true);  // Used in MyReviews page to differentiate from other component reviews
		
		// Uncomment line below to show tag in My Reviews page
//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
        
        // PaidListings integration - when completing review info needs to be triggered here
        if(class_exists('PaidListingsComponent'))
        {
            PaidListingsComponent::applyBeforeFindListingChanges($this);
        } 
        
        $this->Routes =  ClassRegistry::getClass('RoutesHelper');       			
	}		
    
	function exists() {
		return (bool) file_exists(PATH_ROOT . 'components' . _DS . 'com_content' . _DS . 'content.php');
	}                         
		
	function listingUrl($listing) 
    {
		return $this->Routes->content('',$listing,array('return_url'=>true,'sef'=>false));
	} 
	
	function getTemplateSettings($listing_id) 
    {
		# Check for cached version
		$cache_prefix = 'everywhere_content_themesettings';
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
		
		$query = "
            SELECT 
                " . implode(',',$fields) . "
		    FROM 
                #__content AS Listing
		    INNER JOIN 
                #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.option = 'com_content'
		    LEFT JOIN 
                #__categories AS Category ON JreviewsCategory.id = Category.id 
		    LEFT JOIN 
                #__sections AS Section ON Category.section = Section.id
		    LEFT JOIN 
                #__jreviews_sections AS JreviewsSection ON Section.id = JreviewsSection.sectionid
		    WHERE 
                Listing.id = " . $listing_id
		;
		
		$this->_db->setQuery($query);
		
		$result = end($this->__reformatArray($this->_db->loadAssocList()));
		
		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$result);		
		
		return $result;
	}		

    // Used to check whether reviews can be posted by listing owners
    function getListingOwner($result_id) 
    {
        $query = "
            SELECT 
                Listing.created_by AS user_id, User.name, User.email 
            FROM 
                #__content AS Listing 
            LEFT JOIN
                #__users AS User ON Listing.created_by = User.id                
            WHERE 
                Listing.id = " . (int) ($result_id);
        $this->_db->setQuery($query);
        appLogMessage($this->_db->getErrorMsg(),'owner_listing');
        return current($this->_db->loadAssocList());        
    }
	
    function afterFind($results) 
    {  
        if (empty($results)) 
        {
            return $results;
        }

        App::import('Model',array('favorite','field','criteria'),'jreviews');        
        
        # Add Menu ID info for each row (Itemid)
        $Menu = ClassRegistry::getClass('MenuModel');
        $results = $Menu->addMenuListing($results);
                                    
        # Reformat image and criteria info
        foreach ($results AS $key=>$listing) 
        {         
            // Check for guest user submissions
            if(isset($listing['User']) 
                && ($listing['User']['user_id'] == 0 
                || ($listing['User']['user_id'] == 62 && $listing['Listing']['author_alias']!=''))) 
            {
                $results[$key]['User']['name'] = $listing['Listing']['author_alias'];
                $results[$key]['User']['username'] = $listing['Listing']['author_alias'];
                $results[$key]['User']['user_id'] = 0;
            }            
        
            // Remove plugin tags
            if(isset($results[$key]['Listing']['summary']) && Sanitize::getString($this,'controller')=='categories') { // Not in edit mode
                $regex = "#{[a-z0-9]*(.*?)}(.*?){/[a-z0-9]*}#s";
                $results[$key]['Listing']['summary'] = preg_replace( $regex, '', $results[$key]['Listing']['summary'] );            
            }
            
             // Escape quotes in meta tags
            isset($listing['Listing']['metakey']) and $listing['Listing']['metakey'] = htmlspecialchars($listing['Listing']['metakey'],ENT_QUOTES,'UTF-8');
            isset($listing['Listing']['metadesc']) and $listing['Listing']['metadesc'] = htmlspecialchars($listing['Listing']['metadesc'],ENT_QUOTES,'UTF-8');

            # Config overrides
            if(isset($listing['ListingType'])) {
                $results[$key]['ListingType']['config'] = json_decode($listing['ListingType']['config'],true);
                if(isset($results[$key]['ListingType']['config']['relatedlistings'])) {
                    foreach($results[$key]['ListingType']['config']['relatedlistings'] AS $rel_key=>$rel_row) {
                        isset($rel_row['criteria']) and $results[$key]['ListingType']['config']['relatedlistings'][$rel_key]['criteria'] = implode(',',$rel_row['criteria']);
                    } 
                }
            } 
             
            $results[$key][$this->name]['url'] = $this->listingUrl($listing);                    

            if(isset($listing['Listing']['images']))
            {             
                if (is_array($listing['Listing']['images'])) { // Mambo 4.5 compat
                    $listing['Listing']['images'] = implode( "\n",$listing['Listing']['images']);
                }
        
				// Line added for J2.5 compatibility because it uses the content.images column to store some json object
				$listing['Listing']['images']  = preg_replace('/{(.*)}/','',$listing['Listing']['images'] );

                // Removes empty elements and resets the array keys
				$images = array_merge(array_filter(explode("\n",$listing['Listing']['images'])));
				
                unset($results[$key]['Listing']['images']);

				$results[$key]['Listing']['images'] = array();
                
                if(!empty($images[0]))
                {
//					if(strpos($images[0],'{"') !== false) continue;
					
                    foreach($images as $image) 
                    {                        
                        $image_parts = explode("|", $image);
                        if($image_parts[0]!='') {
                            $results[$key]['Listing']['images'][] = array(
                                'path'=>trim($image_parts[0]),
                                'caption'=>isset($image_parts[4]) ? $image_parts[4] : ''
                            );
                        }
                    }
                }
            }
      
            if(isset($listing['Criteria']['criteria']) && $listing['Criteria']['criteria'] != '') 
            {
                $results[$key]['Criteria']['criteria'] = explode("\n",$listing['Criteria']['criteria']);
                
                $results[$key]['Criteria']['required'] = explode("\n",$listing['Criteria']['required']);
                // every criteria must have 'Required' set (0 or 1). if not, either it's data error or data from older version of jr, so default to all 'Required'
                if ( count($results[$key]['Criteria']['required']) != count($results[$key]['Criteria']['criteria']) )
                {
                    $results[$key]['Criteria']['required'] = array_fill(0, count($results[$key]['Criteria']['criteria']), 1);
                }
            }

            if(isset($listing['Criteria']['tooltips']) && $listing['Criteria']['tooltips'] != '') {
                $results[$key]['Criteria']['tooltips'] = explode("\n",$listing['Criteria']['tooltips']);
            }

            if(isset($listing['Criteria']['weights']) && $listing['Criteria']['weights'] != '') {
                $results[$key]['Criteria']['weights'] = explode("\n",$listing['Criteria']['weights']);
            }
            
            // Add detailed rating info
            // If $listing['Rating'] is already set we don't want to overwrite it because it's for individual reviews
            if(isset($listing['Review']) 
                && !isset($listing['Rating'])
                && ($listing['Review']['user_rating_count'] > 0 || $listing['Review']['editor_rating_count'] >0)
                ) 
            {
                $results[$key]['Rating'] = array(
                        'average_rating' => $listing['Review']['user_rating_count'] > 0 ? $listing['Review']['user_rating'] : $listing['Review']['editor_rating'],
                        'ratings' => explode(',', $listing['Review']['user_rating_count'] > 0 ? $listing['Review']['user_criteria_rating'] : $listing['Review']['editor_criteria_rating']),
                        'criteria_rating_count' => explode(',', $listing['Review']['user_rating_count'] > 0 ? $listing['Review']['user_criteria_rating_count'] : $listing['Review']['editor_criteria_rating_count'])
                    );
            }

        }        
        
        if(!defined('MVC_FRAMEWORK_ADMIN') || MVC_FRAMEWORK_ADMIN == 0) {
            # Add Community info to results array
            if(isset($listing['User']) && !defined('MVC_FRAMEWORK_ADMIN') && class_exists('CommunityModel')) {
                $Community = ClassRegistry::getClass('CommunityModel');
                $results = $Community->addProfileInfo($results, 'User', 'user_id');
            }

            # Add Favorite info to results array
            $Favorite = ClassRegistry::getClass('FavoriteModel');
            $Favorite->Config = &$this->Config;        
            $results = $Favorite->addFavorite($results);
        }
        
        # Add custom field info to results array
        $CustomFields = ClassRegistry::getClass('FieldModel');        
        $results = $CustomFields->addFields($results,'listing');

        /* Call to model initiated via review module controller
         * This was added to process paid listing info (i.e. images) for reviews 
         * because the paid listing plugin cannot be triggered in the reviews module controller
         */
        if(!defined('MVC_FRAMEWORK_ADMIN') && Configure::read('EverywhereReviewModel') && class_exists('PaidListingsComponent'))
        {                 
            Configure::write('EverywhereReviewModel',false);
            App::import('Model',array('paid_order','paid_plan'),'jreviews');
            $PaidListings = ClassRegistry::getClass('PaidListingsComponent');
            $PaidListings->processPaidData($results);
        }
        return $results; 
    }
	/**
	 * This can be used to add post save actions, like synching with another table
	 *
	 * @param array $model
	 */
    function afterSave(&$model) 
    {
        if(isset($model->name))
        {
            switch($model->name)  
            {
                case 'Review':break;
                case 'Listing':break;            
            }
        }
    }
	
    
    function processSorting($controller_action, $order) 
    {
        $addCondition = false; 

        # Order by custom field
        if (false !== (strpos($order,'jr_'))) 
        {
            $this->__orderByField($order);				
        } 
	else {                 
            # If special task, then set the correct ordering processed in urlToSqlOrderBy
            switch($controller_action) 
            {
                case 'section':
                case 'category':   
                case 'custom': 
                    if ($order == '') {
                        $order = $this->Config->list_order_default;        
                    }
                    break;
                case 'toprated':
                    $order = 'rating';
                    break;
                case 'topratededitor':
                    $order = 'editor_rating';
                    break;
                case 'mostreviews':
                    $order = 'reviews';   
                    break;
                case 'latest':
                    $order = 'rdate';
                    break;
                case 'popular':
                    $order = 'rhits';
                    break;
                case 'featured':
                    $order = 'featured';
                    break;
                case 'updated':
                    $order = 'updated';
                    break;
                case 'search':                
                case 'alphaindex':
                case 'mylistings':
                    // Nothing
                    break;    
                case 'random':
                case 'featuredrandom':                
                    $order = 'random';
                    break;
                case 'module':
                    $addCondition = true;
                break;
                default: 
                    $order = $controller_action;
                break;
            }
            $this->order[] = $this->__urlToSqlOrderBy($order,$addCondition);                        
        }
	}	
	
    function __orderByField($field)
    {
        $direction = 'ASC';

        if (false !== (strpos($field,'rjr_'))) {
            $field = substr($field,1);
            $direction = 'DESC';
        }

        $CustomFields = ClassRegistry::getClass('FieldModel');

        $queryData = array(
            'fields'=>array('Field.fieldid AS `Field.field_id`'),
            'conditions'=>array(
                'Field.name = "'.$field.'"',
//                    'Field.listsort = 1'
                ) 
        );

        $field_id = $CustomFields->findOne($queryData);
        
        if ($field_id) 
        {
            $this->fields[] = 'Field.' . $field . ' AS `Field.' . $field . '`';
//            $this->fields[] = 'IF (Field.' .$field . ' IS NULL, IF(Field.' .$field . ' = "",1,0), 1) AS `Field.notnull`';
//            $this->order[] = '`Field.notnull` DESC';
//            $this->conditions[] = 'Field.' . $field . ' IS NOT NULL';
//            $this->conditions[] = 'Field.' . $field . '<> ""';
            $this->order[] = 'Field.' . $field . ' ' .$direction;        
            $this->order[] = 'Listing.created DESC';                        
        }        
    }
    
    function __urlToSqlOrderBy($sort, $addCondition = false) 
    {
        $order = '';
        switch ( $sort ) 
        {
            case 'featured':
                $order = '`Listing.featured` DESC, Listing.created DESC';
            break;
            case 'editor_rating':  
            case 'author_rating':
                $order = 'Totals.editor_rating DESC, Totals.editor_rating_count DESC';
                $addCondition and $this->conditions[] = 'Totals.editor_rating > 0';
            break;
            case 'reditor_rating':
                $order = 'Totals.editor_rating ASC, Totals.editor_rating_count DESC';
//                $this->useKey = array('Totals'=>'editor_rating,editor_rating_count'); // KEY HINT por improved performance
                $addCondition and $this->conditions[] = 'Totals.editor_rating > 0';
            break;
            case 'rating':
                $order = 'Totals.user_rating DESC, Totals.user_rating_count DESC';
                $addCondition and $this->conditions[] = 'Totals.user_rating > 0';
            break;
            case 'rrating':
                $order = 'Totals.user_rating ASC, Totals.user_rating_count DESC';
//                $this->useKey = array('Total'=>'user_rating,user_rating_count'); // KEY HINT por improved performance
                $addCondition and $this->conditions[] = 'Totals.user_rating > 0';
            break;
            case 'reviews':
                $order = 'Totals.user_comment_count DESC'; 
                $addCondition and $this->conditions[] = 'Totals.user_comment_count > 0';
            break;
            case 'date':
                $order = 'Listing.created';
                $this->useKey = array('Listing'=>'jr_created'); // KEY HINT por improved performance
            break;
            case 'rdate':
                $order = 'Listing.created DESC';  
                $this->useKey = array('Listing'=>'jr_created'); // KEY HINT por improved performance
            break;
//			case 'alias':
//				$order = 'Listing.alias DESC';
//				break;
            case 'alpha':
                $order = 'Listing.title';
                $this->useKey = array('Listing'=>'jr_title'); // KEY HINT por improved performance
            break;
            case 'ralpha':
                $order = 'Listing.title DESC';
                $this->useKey = array('Listing'=>'jr_title'); // KEY HINT por improved performance
            break;
            case 'hits':
                $order = 'Listing.hits ASC';
                $this->useKey = array('Listing'=>'jr_hits'); // KEY HINT por improved performance
            break;
            case 'rhits':
                $order = 'Listing.hits DESC';
                $this->useKey = array('Listing'=>'jr_hits'); // KEY HINT por improved performance
            break;
            case 'order':
                $order = 'Listing.ordering';
                $this->useKey = array('Listing'=>'jr_ordering'); // KEY HINT por improved performance
            break;
            case 'author':
                if ($this->Config->name_choice == 'realname') {
                    $order = 'User.name, Listing.created';
                } else {
                    $order = 'User.username, Listing.created';
                }
            break;
            case 'rauthor':
                if ($this->Config->name_choice == 'realname') {
                $order = 'User.name DESC, Listing.created';
                } else {
                $order = 'User.username DESC, Listing.created';
                }
            break;
            case 'random':
                $order = 'RAND()';
            break;  
            case 'updated':
                $order = 'Listing.modified DESC, Listing.created DESC';
                $this->useKey = array('Listing'=>'jr_modified,jr_created'); // KEY HINT por improved performance
            break;  
            default:
                $order = 'Listing.title';
                $this->useKey = array('Listing'=>'jr_title'); // KEY HINT por improved performance
                break;
        }
        return $order;
    }	
	    
    function delete(&$data) 
    {
        $listing_id = $this->data['listing_id'] = (int) $data['Listing']['id'];
        
        $this->plgBeforeDelete('Listing.id',$listing_id); // Only works for single listing deletion
                    
        $query = "DELETE FROM #__content WHERE id = '$listing_id'";
        $this->_db->setQuery( $query );
        $this->_db->query();
        
        $query = "DELETE FROM #__content_frontpage WHERE content_id = '$listing_id'";
        $this->_db->setQuery( $query );
        $this->_db->query();
    
        $query = "DELETE FROM #__jreviews_content WHERE contentid = '$listing_id'";
        $this->_db->setQuery( $query );
        $this->_db->query();
        
        $query = "DELETE FROM #__jreviews_votes"
        . "\n WHERE review_id IN (SELECT id FROM #__jreviews_comments WHERE pid = $listing_id)";
        $this->_db->setQuery( $query );
        $this->_db->query();        
        
        $query = "DELETE FROM #__jreviews_votes"
        . "\n WHERE review_id IN (SELECT id FROM #__jreviews_comments WHERE pid = $listing_id)";
        $this->_db->setQuery( $query );
        $this->_db->query();  
        
        // delete ratings
        $query = "
            DELETE Rating FROM 
                #__jreviews_ratings AS Rating
            INNER JOIN
                #__jreviews_comments AS Review ON Review.id = Rating.reviewid
            WHERE
                Review.pid = $listing_id
        ";
        $this->_db->setQuery($query);
        $this->_db->query();
        
        $query = "DELETE FROM #__jreviews_comments WHERE pid = '$listing_id' AND `mode` = 'com_content'";
        $this->_db->setQuery( $query );
        $this->_db->query();
        
        // delete listing totals
        $query = "DELETE FROM #__jreviews_listing_totals WHERE listing_id = '$listing_id' AND extension = 'com_content'";
        $this->_db->setQuery( $query );
        $this->_db->query();
        
        // delete claims
        $query = "DELETE FROM #__jreviews_claims WHERE listing_id = '$listing_id'";
        $this->_db->setQuery( $query );
        $this->_db->query();   
        
        // delete reports
        $query = "DELETE FROM #__jreviews_reports WHERE listing_id = '$listing_id' AND extension = 'com_content'";
        $this->_db->setQuery( $query );
        $this->_db->query();                   
                
        # delete thumbnails
        App::import('Model','thumbnail','jreviews');
        $Thumbnail = new ThumbnailModel();
        
        $error = $Thumbnail->delete($data);
        
        $query = "SELECT id FROM #__content WHERE id = $listing_id";
        $this->_db->setQuery($query);
        $result = $this->_db->loadResult();
    
        if (!$result) 
            {
                // Clear cache
                clearCache('', 'views');
                clearCache('', '__data');

                // Trigger plugin callback
                $this->data = &$data;
                $this->plgAfterDelete($data);            
                return true;
            } 
        else 
            {
                return false;
            }
    }
        
    function frontpage($listing_id)
    {
        $listing_id = (int) $listing_id;
        $result = array('success'=>false,'state'=>null,'access'=>true);
        if(!$listing_id) return $result;
        
        # Check access
        $Access = Configure::read('JreviewsSystem.Access');
        if(!$Access->isManager()) 
        {            
            $result['access'] = false; 
            return $result;
        }
                
        App::import('Model','frontpage','jreviews');
        $Frontpage = ClassRegistry::getClass('FrontpageModel');
        
        $row = $Frontpage->findRow(array('conditions'=>array('content_id = ' . $listing_id)));

        $data = array('Frontpage'=>array('content_id'=>$listing_id)); 
        
        if($row)                                                            
            {            
                // Already in frontpage so we delete it
                if($update = $Frontpage->delete('content_id',$listing_id))
                {
                    $result = array('success'=>true,'state'=>0,'access'=>true);
                }
            } 
        else 
            {            
                // Put in frontpage
                $this->data['Frontpage']['ordering'] = 0;
                if($update = $Frontpage->insert('#__content_frontpage','Frontpage',$data))
                {
                    $result = array('success'=>true,'state'=>1,'access'=>true);
                }
            }
         
        $Frontpage->reorder();
                
        if($update)
            {
                $Frontpage->reorder();
                // Clear cache
                clearCache('', 'views');
                clearCache('', '__data');        
            }
            
        return $result;                
    }  
    
    function feature($listing_id)
    {
        $listing_id = (int) $listing_id;
        $result = array('success'=>false,'state'=>null,'access'=>true);
        if(!$listing_id) return $result;

        # Check access
        $Access = Configure::read('JreviewsSystem.Access');
        if(!$Access->isManager()) 
        {            
            $result['access'] = false; 
            return $result;
        }
        
        # Load current listing featured state
        $query = "
            SELECT 
                Listing.id, Field.featured AS state
            FROM 
                #__content AS Listing
            LEFT JOIN
                #__jreviews_content AS Field ON Field.contentid = Listing.id 
            WHERE 
                Listing.id = " . $listing_id
        ;
        $this->_db->setQuery($query);
        
        if($row = end($this->_db->loadAssocList()))        
        {
            $new_state = $result['state'] = (int) !$row['state'];

            $query = "
                INSERT INTO 
                    #__jreviews_content (contentid,featured) 
                VALUES 
                    ($listing_id,$new_state)
                ON DUPLICATE KEY UPDATE 
                    featured = $new_state;
            ";
            
            $this->_db->setQuery($query);
            
            if($this->_db->query())
            {
                // Clear cache
                clearCache('', 'views');
                clearCache('', '__data');        
                $result['success'] = true;           
            }
        }
        
        return $result;
    } 
    
    function publish($listing_id)
    {
        $result = array('success'=>false,'state'=>null,'access'=>true);
        $listing_id = (int) $listing_id;
        if(!$listing_id) return $result;
         
        # Load current listing publish state and author id
        $query = "
            SELECT 
                Listing.created_by, 
                Listing.state 
            FROM 
                #__content AS Listing 
            WHERE 
                Listing.id = " . $listing_id
        ;
        $this->_db->setQuery($query);
        
        if($row = end($this->_db->loadAssocList()))
        {
            # Check access
            $Access = Configure::read('JreviewsSystem.Access');
            if(!$Access->canPublishListing($row['created_by'])) 
            {            
                $result['access'] = false;                         
                return $result;
            }
            
            $data['Listing']['id'] = $listing_id;
            $data['Listing']['state'] = $result['state'] = (int)!$row['state'];

            # Update listing state
            if($this->store($data,false,array()))
            {
                // clear cache
                clearCache('', 'views');
                clearCache('', '__data');
                        
                $result['success'] = true;
            }
        }
        return $result;
    } 
    
    /**
    * Gets the most basic listing info to construct the urls for them
    * 
    * @param mixed $id
    */
    function getListingById($id) 
    {
        # Add Menu ID info for each row (Itemid)
        $Menu = ClassRegistry::getClass('MenuModel');
        
        $fields = array(
            'Listing.id AS `Listing.listing_id`',
            'Listing.alias AS `Listing.slug`',
            'Listing.title AS `Listing.title`',
            'Listing.catid AS `Listing.cat_id`',
            'Category.alias AS `Category.slug`',
            'Category.id AS `Category.cat_id`',
            'Category.title AS `Category.title`',
        );
                
        if(getCmsVersion() == CMS_JOOMLA15)
        {
           $fields[] = 'Listing.sectionid AS `Listing.section_id`';
        }
        
        $query = "
            SELECT 
                " . implode (",", $fields) . "
            FROM 
                #__content AS Listing
            LEFT JOIN 
                #__categories AS Category ON Category.id = Listing.catid
            WHERE 
                Listing.id IN (" . $this->Quote($id) . ")        
        ";          
        
        $this->_db->setQuery($query);
        
        $listings = $this->__reformatArray($this->_db->loadObjectList());
        
        $listings = $this->changeKeys($listings,'Listing','listing_id');
        
        $listings = $Menu->addMenuListing($listings);
                
        foreach($listings AS $key=>$listing) {
            $listings[$key]['Listing']['url'] = $this->listingUrl($listing);
        }
        
        return $listings;
    }      
}
