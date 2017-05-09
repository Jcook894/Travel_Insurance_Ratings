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
		'Listing.title AS `Listing.title`',
		'Listing.introtext AS `Listing.summary`',
		'Listing.fulltext AS `Listing.description`',
		'Listing.images AS `Listing.images`',
		'Listing.hits AS `Listing.hits`',
		'Listing.sectionid AS `Listing.section_id`',
		'Listing.catid AS `Listing.cat_id`',
		'Listing.created_by AS `Listing.user_id`',
		'Listing.created_by_alias AS `Listing.author_alias`',
		'Listing.created AS `Listing.created`',
		'Listing.access AS `Listing.access`',
		'Listing.state AS `Listing.state`',
		'Listing.publish_up AS `Listing.publish_up`',		
		'Listing.state AS `Listing.publish_down`',
		'Listing.metakey AS `Listing.metakey`',
		'Listing.metadesc AS `Listing.metadesc`',
		'\'com_content\' AS `Listing.extension`',
		'Section.id AS `Section.section_id`',
		'Section.title AS `Section.title`',
		'Category.id AS `Category.cat_id`',
		'Category.title AS `Category.title`',
		'Category.image AS `Listing.category_image`',
		'criteria'=>'Criteria.id AS `Criteria.criteria_id`',
		'Criteria.criteria AS `Criteria.criteria`',
		'Criteria.tooltips AS `Criteria.tooltips`',
		'Criteria.weights AS `Criteria.weights`',
		'Criteria.state AS `Criteria.state`',
		'User.id AS `User.user_id`',
		'User.name AS `User.name`',
		'User.username AS `User.username`',
		'User.email AS `User.email`',
/* BLOCK BELOW IS FOR SUMMARY REVIEW INFO IN JREVIEWS LISTINGS PAGE */
		'editor_rating'=>'sum(Review.author*(Rating.ratings_sum/Rating.ratings_qty)) AS `Review.editor_rating`',
		'editor_rating_exists'=>'IF(sum(Review.author*(Rating.ratings_sum/Rating.ratings_qty))>0,1,0) AS `Review.editor_rating_exists`',
		'user_rating'=>'(sum(Rating.ratings_sum-Review.author*Rating.ratings_sum)/sum(Rating.ratings_qty-Review.author*Rating.ratings_qty)) AS `Review.user_rating`',
		'user_rating_exists'=>'IF((sum(Rating.ratings_sum-Review.author*Rating.ratings_sum)/sum(Rating.ratings_qty-Review.author*Rating.ratings_qty))>0,1,0) AS `Review.user_rating_exists`',
		'review_count'=>'(count(Review.id)-sum(Review.author)) AS `Review.review_count`',
		'review_count_exists'=>'IF ((count(Review.id)-sum(Review.author))>0,1,0) AS `Review.review_count_exists`'
/* END BLOCK */ 
	);	
	
	var $joins = array(
/* BLOCK BELOW IS FOR SUMMARY REVIEW INFO IN JREVIEWS LISTINGS PAGE */
		'Review'=>"LEFT JOIN #__jreviews_comments AS Review ON Listing.id = Review.pid AND Review.published = 1 AND Review.mode = 'com_content'",
		'Rating'=>"LEFT JOIN #__jreviews_ratings AS Rating ON Review.id = Rating.reviewid",
/* END BLOCK */ 
		"LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Listing.id",
		"LEFT JOIN #__sections AS Section ON Listing.sectionid = Section.id",
		"LEFT JOIN #__categories AS Category ON Listing.catid = Category.id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_content'",
		"LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id",
		"LEFT JOIN #__users AS User ON User.id = Listing.created_by",
	);
	
	/**
	 * Used to complete the listing information for reviews based on the Review.pid. The list of fields for the listing is not as
	 * extensive as the one above used for the full listing view
	 */
	var $joinsReviews = array(
		'LEFT JOIN #__content AS Listing ON Review.pid = Listing.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_content'",
		"LEFT JOIN #__categories AS Category ON Category.id = Listing.catid",
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);
	
	var $conditions = array();
	
	var $group = array('Listing.id');
	
	function __construct() {
		parent::__construct();
		
		$this->tag = __t("Listing",true);  // Used in MyReviews page to differentiate from other component reviews
		
		// Uncomment line below to show tag in My Reviews page
//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";

		if(defined('MVC_FRAMEWORK_ADMIN')) {
			// Unbind all unnecessary queryData info
			$this->fields = array(
				'Listing.id AS `Listing.listing_id`',
				'Listing.title AS `Listing.title`',
				'Category.title AS `Category.title`',
				'Category.id AS `Category.cat_id`',
			);
//			$this->joins = $this->joinsReviews = array(
//				"LEFT JOIN #__categories AS Category ON Listing.catid = Category.id"
//			);

		} else {
		
			if(getCmsVersion() == CMS_JOOMLA15) {
				// Add listing, category aliases to fields
				$this->fields[] = 'CASE WHEN CHAR_LENGTH(Listing.alias) THEN Listing.alias ELSE "" END AS `Listing.slug`';
				$this->fields[] = 'CASE WHEN CHAR_LENGTH(Category.alias) THEN Category.alias ELSE Category.title END AS `Category.slug`';
				$this->fields[] = 'CASE WHEN CHAR_LENGTH(Section.alias) THEN Section.alias ELSE Section.title END AS `Section.slug`';
			} else {
				$this->fields[] = 'Listing.name AS `Listing.slug`';
				$this->fields[] = 'Category.name AS `Category.slug`';
				$this->fields[] = 'Section.name AS `Section.slug`';
			}
			
		}
		
		// Set default WHERE statement
		$this->conditions = array(
			'Listing.state = 1',
			'( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._CURRENT_SERVER_TIME.'" )',
			'( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._CURRENT_SERVER_TIME.'" )',
			'Listing.access <= ' . $this->_user->gid,
			'Listing.catid > 0'
		);				
		
	}		

	function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_content' . _DS . 'content.php');
	}
		
	function listingUrl($listing) {
		
		App::import('Helper','routes','jrexpress');
		$Routes = RegisterClass::getInstance('RoutesHelper');
		return $Routes->content('',$listing,array(),'',false);
						
	}
	
	function getTemplateSettings($listing_id) {
		
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
		
		$query = "SELECT " . implode(',',$fields)
		. "\n FROM #__content AS Listing"
		. "\n LEFT JOIN #__categories AS Category ON Listing.catid = Category.id"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id"
		. "\n LEFT JOIN #__sections AS Section ON Category.section = Section.id"
		. "\n LEFT JOIN #__jreviews_sections AS JreviewsSection ON Section.id = JreviewsSection.sectionid"
		. "\n WHERE JreviewsCategory.option = 'com_content' AND Listing.id = " . $listing_id
		;
		
		$this->_db->setQuery($query);
		
		$result = end($this->__reformatArray($this->_db->loadAssocList()));
		
		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$result);		
		
		return $result;
	}		

	// Used to check whether reviews can be posted by listing owners
	function getListingOwner($listing_id) {

		$query = "SELECT Listing.created_by FROM #__content AS Listing WHERE Listing.id = " . (int) ($listing_id);
		$this->_db->setQuery($query);
		appLogMessage($this->_db->getErrorMsg(),'owner_listing');
		return $this->_db->loadResult();
		
	}
	
	function afterFind($results) {
		
		if (empty($results) || defined('MVC_FRAMEWORK_ADMIN')) {
			return $results;
		}

		# Check for cached version		
		$cache_prefix = 'listing_model';
		$firstRow = current($results);
		$cache_key = array(array_keys($results),array_keys($firstRow['Listing']));
		if($cache = S2cacheRead($cache_prefix,$cache_key)){
			return $cache;
		}			
		
		# Add Menu ID info for each row (Itemid)
		$Menu = registerClass::getInstance('MenuModel');
		$results = $Menu->addMenuListing($results);
		
		# Reformat image and criteria info
		foreach ($results AS $key=>$listing) {
		
			if(isset($results[$key]['Listing']['summary'])){
				// Remove plugin tags
				$regex = "#{[a-z0-9]*(.*?)}(.*?){/[a-z0-9]*}#s";
				$results[$key]['Listing']['summary'] = preg_replace( $regex, '', $results[$key]['Listing']['summary'] );			
			}
								
			if (is_array($listing['Listing']['images'])) { // Mambo 4.5 compat
				$listing['Listing']['images'] = implode( "\n",$listing['Listing']['images']);
			}
	
			$images = explode("\n",$listing['Listing']['images']);
			unset($results[$key]['Listing']['images']);
			$results[$key]['Listing']['images'] = array();
			
			if(!empty($images))
			{
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
			
			if(isset($listing['Criteria']['criteria']) && $listing['Criteria']['criteria'] != '') {
				$results[$key]['Criteria']['criteria'] = explode("\n",$listing['Criteria']['criteria']);
			}

			if(isset($listing['Criteria']['tooltips']) && $listing['Criteria']['tooltips'] != '') {
				$results[$key]['Criteria']['tooltips'] = explode("\n",$listing['Criteria']['tooltips']);
			}

			if(isset($listing['Criteria']['weights']) && $listing['Criteria']['weights'] != '') {
				$results[$key]['Criteria']['weights'] = explode("\n",$listing['Criteria']['weights']);
			}

			$results[$key][$this->name]['url'] = $this->listingUrl($listing);					
		}		
		
		if(!defined('MVC_FRAMEWORK_ADMIN') || MVC_FRAMEWORK_ADMIN == 0) {
			# Add Community info to results array
			if(isset($listing['User']) && !defined('MVC_FRAMEWORK_ADMIN') && class_exists('CommunityModel')) {
				$Community = registerClass::getInstance('CommunityModel');
				$results = $Community->addProfileInfo($results, 'User', 'user_id');
			}
		}
				
		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$results);

		return $results; 
	}
	
	/**
	 * This can be used to add post save actions, like synching with another table
	 *
	 * @param array $model
	 */
	function afterSave(&$model) {
//		appLogMessage(print_r($model,true),'afterSaveHook');
		switch($model->name)  {
			case 'Review':break;
			case 'Listing':break;			
		}
	}	
	
	// Two functions below were added for the Everywhere Listings Module
	// They are duplicates of the listing.php module's and eventually they all need to move here
	function processSorting($task, $selected) {

		if (!(strpos($selected,'jr_') === false)) {

			$field = $selected;
			$direction = 'ASC';

			if (!(strpos($selected,'rjr_') === false)) {
				$field = substr($selected,1);
				$direction = 'DESC';
			}

			$CustomFields = registerClass::getInstance('FieldModel');

			$queryData = array(
				'fields'=>array('Field.fieldid AS `Field.field_id`'),
				'conditions'=>array(
					'Field.name = "'.$field.'"',
//					'Field.listsort = 1'
					) 
			);

			$field_id = $CustomFields->findOne($queryData);
			
			if ($field_id) {
				$this->fields[] = 'Field.' . $field . ' AS `Field.' . $field . '`';
				$this->fields[] = 'IF (Field.' .$field . ' IS NULL, IF(Field.' .$field . ' = "",1,0), 1) AS `Field.notnull`';
				$this->order[] = '`Field.notnull` DESC';
				$this->order[] = 'Field.' . $field . ' ' .$direction;		
				$this->order[] = 'Listing.created DESC';						
			}		
				
        } else {
            # If special task, then set the correct ordering processed in urlToSqlOrderBy
            switch($task) {
                case 'section':
                case 'category':    
                    if ($selected == '') {
                        $selected = $this->Config->list_order_default;        
                    }
                    break;
                case 'toprated':
                    $selected = 'rating';
                    break;
                case 'topratededitor':
                    $selected = 'editor_rating';
                    break;
                case 'mostreviews':
                    $selected = 'reviews';
                    break;
                case 'latest':
                    $selected = 'rdate';
                    break;
                case 'popular':
                    $selected = 'rhits';
                    break;
                case 'featured':
                    $selected = 'featured';
                    break;
                case 'search':                
                case 'alphaindex':
                case 'mylistings':
                    $selected = $selected;
                    break;    
                case 'random':
                case 'featuredrandom':                
                    $selected = 'random';
                    break;
                default: 
                    $selected = $task;
                break;
            }

            $this->order[] = $this->__urlToSqlOrderBy($selected);            
        }

	}	
	
	function __urlToSqlOrderBy($selected) {

		$order = '';

		switch ( $selected ) {
		  	case 'featured':
		  		$order = '`Listing.featured` DESC, `Review.user_rating` DESC, Listing.created DESC';
		  		break;
		  	case 'editor_rating':  case 'author_rating':
		  		$order = '`Review.editor_rating_exists` DESC, `Review.editor_rating` DESC, `Review.user_rating` DESC, Listing.created DESC';
		  		break;
		  	case 'reditor_rating':
		  		$order = '`Review.editor_rating_exists` DESC, `Review.editor_rating`, `Review.user_rating`, Listing.created DESC';
		  		break;
		  	case 'rating':
		  		$order = '`Review.user_rating_exists` DESC, `Review.user_rating` DESC, `Review.review_count` DESC, Listing.created DESC';
		  		break;
		  	case 'rrating':
		  		$order = '`Review.user_rating_exists` DESC, `Review.user_rating` ASC, `Review.review_count` DESC, Listing.hits DESC';
		  		break;
		  	case 'reviews':
		  		$order = '`Review.review_count_exists` DESC, `Review.review_count` DESC, `Review.user_rating` DESC, Listing.created DESC';
		  		break;
			case 'date':
				$order = 'Listing.created';
				break;
			case 'rdate':
				$order = 'Listing.created DESC';
				break;
//			case 'alias':
//				$order = 'Listing.alias DESC';
//				break;
			case 'alpha':
				$order = 'Listing.title';
				break;
			case 'ralpha':
				$order = 'Listing.title DESC';
				break;
			case 'hits':
				$order = 'Listing.hits ASC';
				break;
			case 'rhits':
				$order = 'Listing.hits DESC';
				break;
			case 'order':
				$order = 'Listing.ordering';
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
			default:
				$order = 'Listing.title';
		 		break;
		}
	
		return $order;
	}	
	
}