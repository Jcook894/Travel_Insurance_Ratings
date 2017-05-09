<?php  
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ListingsController extends MyController {
        
    var $uses = array('user','menu','section'/*J15*/,'category','jreviews_category'/*J15*/,'review','favorite','field','criteria','captcha','vote');
    
    var $helpers = array('cache','routes','libraries','html','text','assets','form','time','jreviews','community','editor','custom_fields','rating','thumbnail','paginator','widgets');
    
    var $components = array('config','access','uploads','everywhere','activities');     
          
    var $formTokenKeys = array('id'=>'listing_id');
                
    function beforeFilter() 
    {
        $this->Access->init($this->Config);    

        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
        
        # Make configuration available in models
        $this->Listing->Config = &$this->Config;
    }    
    
    // Need to return object by reference for PHP4
    function &getPluginModel() {
        return $this->Listing;
    }
        
    // Need to return object by reference for PHP4
    function &getNotifyModel() {
        return $this->Listing;
    }
    
    // Need to return object by reference for PHP4
    function &getEverywhereModel() {
        // Completes the review with listing info for each Everywhere component
        return $this->Review;
    } 
    
    // Need to return object by reference for PHP4
    function &getActivityModel() {
        return $this->Listing;
    }     
        
    // Need to return object by reference for PHP4
/*    function &getObserverModel() 
    {
        return $this->Listing;
    }    */
        
    function detail() 
    {
        if($this->_user->id === 0) {
            $this->cacheAction = Configure::read('Cache.expires');
        }
                
        $this->autoRender = true;
        $this->autoLayout = true;
        $this->layout = 'detail';    
        
        # Initialize vars
        $editor_review = array();
        $review_fields = array();
        
        $listing_id = Sanitize::getInt($this->params,'id');
        $extension = Sanitize::getString($this->params,'extension','com_content');

        $listing = $this->Listing->findRow(array('conditions'=>array("Listing.{$this->Listing->realKey} = ". $listing_id)));

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);
               
        $sort = Sanitize::getString($this->params,'order',$this->Config->user_review_order);
        $this->params['order'] = $sort;
        
        if(!$listing || empty($listing)) 
        {            
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
                'Criteria.state AS `Criteria.state`', 
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
                'conditions'=>$conditions
/*                ,'joins'=>array(  # They are added in the Everywhere component when the listing is queried
                    'listings'=>'LEFT JOIN #__content AS Listing ON Review.pid = Listing.id', // Overriden in controller for jReviewsEverywhere
                    'jreviews_categories'=>'LEFT JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id', // AND JreviewsCategory.`option`=\'com_content\'
                    'criteria'=>'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
                )
*/            ));
        }

        # Get user review data or editor reviews data in multiple editor review mode
        $reviewType = (int) ( $this->Config->author_review && $extension == 'com_content' && Sanitize::getString($this->params,'reviewType','user') == 'editor' );
        
        if ( $extension != 'com_content' || $this->Config->user_reviews || $reviewType )
        {
            $fields = array(
                'Criteria.id AS `Criteria.criteria_id`',
                'Criteria.criteria AS `Criteria.criteria`',
                'Criteria.state AS `Criteria.state`',
                'Criteria.tooltips AS `Criteria.tooltips`',
                'Criteria.weights AS `Criteria.weights`'            
            );
                                                
            $conditions = array(
                'Review.pid= '. $listing['Listing']['listing_id'],
                'Review.author = '.$reviewType,
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
/*                ,'joins'=>array(  # They are added in the Everywhere component when the listing is queried
                    'listings'=>'LEFT JOIN #__content AS Listing ON Review.pid = Listing.id', // Overriden in controller for jReviewsEverywhere
                    'jreviews_categories'=>'LEFT JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id', // AND JreviewsCategory.`option`=\'com_content\'
                    'criteria'=>'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
                )*/
            );

            $reviews = $this->Review->findAll($queryData);
            
            //Remove unnecessary query parameters for findCount
            $this->Review->joins = array(); // Only need to query comments table            
            $review_count = $this->Review->findCount($queryData);
            
            $listing['Review']['review_count'] = $review_count;
        }
        
        // Two lines below allow showing the ratings summary in jReviews listings page
        // It requires removing the if statement in the detail.thtml which prevents the summary from showing
        $ratings_summary = array(
            'Rating' => array(
                'average_rating' => $listing['Review']['user_rating']
                #,'ratings' => explode(',', @$listing['Review']['user_criteria_rating'])
            ),
            'Criteria' => $listing['Criteria']
        );

        # Get custom fields for review form if form is shown on page
        $review_fields = $this->Field->getFieldsArrayNew($listing['Criteria']['criteria_id'], 'review');
        
        $security_code = '';

        if($this->Access->showCaptcha()) {

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
                'editor_review'=>$editor_review,
                'reviews'=>$reviews,
                'ratings_summary'=>$ratings_summary,
                'review_fields'=>$review_fields,
                'review'=>$review,
                'review_count'=>$review_count,
                'reviewType' => empty($reviewType) ? 0 : 1, # used to set title for 'View all reviews' page                
                'captcha'=>$security_code,
                'pagination'=>array(
                    'total'=>$review_count
                )
                
            )
        );
                                
    }
        
    function create() 
    {                 
        $this->autoRender = true;
        $dir_id = Sanitize::getInt($this->params,'dir');
        $section_id = Sanitize::getString($this->params,'section');
        $cat_id = Sanitize::getString($this->params,'cat');
        $content_id = null;
        $option = 'com_content';
        $sections = array();
        $categories = array();

        if($cat_id > 0)
        {  
            $category = $this->Category->findRow(
                array(
                    'conditions'=>array('Category.id = ' . $cat_id)
                )
            );
            # Override global configuration
            isset($category['ListingType']) and $this->Config->override($category['ListingType']['config']);
        }    

        if (!$this->Access->canAddListing()) {
            if($this->_user->id > 0) {
                cmsFramework::noAccess();
                $this->autoRender = false;
                return;                 
            }
            $this->autoRender = false; 
            return $this->render('elements','login');                    
        }
        
        
	    if($this->cmsVersion == CMS_JOOMLA15)
	    {
		    // Gets list of jReviews Sections
	            $sections = $this->Section->getList('',$section_id > 0 ? $section_id : '',$dir_id);
	            // Get list of jReviews Categories
	            !empty($section_id) and $categories = $this->Category->getList($section_id);
	    }
	    else
	    {
            if($cat_id)
            {
                // Find parent categories of pre-selected cat to show the correct category select lists in the form
                $parent_categories = $this->Category->findParents($cat_id);
                foreach($parent_categories AS $key=>$row)
                {
                    $categories[$key] = $this->Category->getCategoryList(array(
                        'disabled'=>false,
                        'indent'=>false,
                        'level'=>$row['Category']['level'],
                        'parent_id'=>$row['Category']['parent_id'],
                        'dir_id'=>$dir_id,
						'listing_type'=>true
                    ));
                } 
            }
            else
            {
                $categories = $this->Category->getCategoryList(array(
                    'level'=>1,
                    'disabled'=>false,
                    'dir_id'=>$dir_id,
					'listing_type'=>true
                ));                 
            }

			if(!empty($categories))
            { 
				// Remove categories without submit access
				foreach($categories AS $key => $subcategories) 
				{
					if(is_array($subcategories)) {
						foreach($subcategories AS $subkey=>$row) {
							$overrides = json_decode($row->config,true);
							if(!$this->Access->canAddListing($overrides['addnewaccess'])) {
								unset($categories[$key][$subkey]);
							}
						}
					}
					else {
						$overrides = json_decode($subcategories->config,true);
						if(!$this->Access->canAddListing($overrides['addnewaccess'])) {
							unset($categories[$key]);
						}
					}
				}
			}
	    }

        $this->set(
            array(
                'menu_id'=>$this->Menu->get($this->app.'_public'), // Public JReviews menu to be used in submit form action                        
                'submit_step'=>array(1),
                'Access'=>$this->Access,
                'User'=>$this->_user,
                'sections'=>$sections,
                'categories'=>$categories,
                'listing'=>array('Listing'=>array(
                        'listing_id'=>null,
                        'section_id'=>$section_id ? $section_id : null,
                        'cat_id'=>$cat_id ? $cat_id : null,
                        'title'=>'',
                        'summary'=>'',
                        'description'=>'',
                        'metakey'=>'',
                        'metadesc'=>''
                    ))                
            )
        );

    }
    
    function edit() 
    {        
        $this->autoRender = false;
        
        $listing_id = Sanitize::getInt($this->params,'id');
        $sections = array();
        $categories = array();
        
        Configure::write('ListingEdit',true); // Read in Fields model for PaidListings integration
                   
        $listing = $this->Listing->findRow(
            array(
                'fields'=>array('Listing.metakey AS `Listing.metakey`','Listing.metadesc AS `Listing.metadesc`'),
                'conditions'=>'Listing.id = ' . $listing_id
            )                
        );

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);
                        
        # Set the theme suffix
        $this->Theming->setSuffix(array('cat_id'=>$listing['Category']['cat_id']));

        if (!$this->Access->canEditListing($listing['Listing']['user_id'])) {
            cmsFramework::noAccess();
            $this->autoRender = false;
            return;
        }

        # Get listing custom fields
        $listing_fields = $this->Field->getFieldsArrayNew($listing['Criteria']['criteria_id'], 'listing', $listing);

        // Show section/category lists if user is editor or above.
        if ($this->cmsVersion == CMS_JOOMLA15 && $this->Access->isEditor()) 
	    {   
            // Limit sections/categories based on criteriaid of item being edited
            $query = "SELECT id FROM #__jreviews_categories"
            ."\n WHERE criteriaid = '{$listing['Criteria']['criteria_id']}' AND `option` = 'com_content'"
            ;
            $this->_db->setQuery($query);

            $catids = implode(",",$this->_db->loadResultArray());
        
            $sections = $this->Section->getList($catids);
    
            $categories = $this->Category->getList($listing['Listing']['section_id'],$catids);
        }
        elseif ($this->Access->isEditor() && Sanitize::getInt($listing['Criteria'],'criteria_id'))  
        {                 
            $categories = $this->Category->getCategoryList(array(
				'disabled'=>true,
				'type_id'=>array(0,$listing['Criteria']['criteria_id']),
				'listing_type'=>true
			));
			
        	if(!empty($categories))
        	{
				// Remove categories without submit access
				foreach($categories AS $key => $row) 
				{
					$overrides = json_decode($row->config,true);
					if(!$this->Access->canAddListing($overrides['addnewaccess'])) {
						unset($categories[$key]);
					}
				}
			}
        }	

        // Needed to preserve line breaks when not using wysiwyg editor
        if(!$this->Access->loadWysiwygEditor()) {
            $listing['Listing']['summary'] = $listing['Listing']['summary'];
            $listing['Listing']['description '] = $listing['Listing']['description'];        
        }
                    
        $image_manager = '';
        
        $image_count = count($listing['Listing']['images']);
        
        // Check if image limit is enforced and modify the number of image fields shown based on current image account.
        if ($this->Config->content_images_total_limit) 
        {
            $this->Config->content_images = $this->Config->content_images - $image_count;
        }            
    
        $this->set(
            array(
                'submit_step'=>array(1,2),            
                'User'=>$this->_user,
                'Access'=>$this->Access,
                'listing'=>$listing,
                'sections'=>$sections,
                'categories'=>$categories,
                'listing_fields'=>$listing_fields,
                'formTokenKeys'=>$this->formTokenKeys                
            )
        );
        
        return $this->render('listings','create');
                    
    } // edit
    
    function _favoritesAdd() 
    {            
        $response = array();
                
        if(!$this->_user->id) {            
            return $this->ajaxError(s2Messages::accessDenied());   
        }
        
        $listing_id = Sanitize::getInt($this->data,'listing_id');

        $user_id = (int) $this->_user->id;

        // Force plugin loading on Review model
        $this->_initPlugins('Favorite');
        $this->Favorite->data = $this->data;
                
        // Get favored count
        $favored = $this->Favorite->getCount($listing_id);
        
        // Insert new and update display
        if ($this->Favorite->add($listing_id,$user_id) > 0) 
        {
            $favored++;
            $response[] = "jQuery('#jr_favoriteCount{$listing_id}').html('{$favored}');";
            $response[] = "jQuery('#jr_favoriteImg{$listing_id}').fadeOut('slow');";            
            return $this->ajaxResponse($response);
        }
                        
        return $this->ajaxError(s2Messages::submitErrorDb());
    }
    
    function _favoritesDelete() 
    {                
        $response = array();
        
        if(!$this->_user->id) {
            return $this->ajaxError(s2Messages::accessDenied());   
        }        
        
        $listing_id = Sanitize::getInt($this->data,'listing_id');

        $user_id = $this->_user->id;
            
        // Get favored count
        $favored = $this->Favorite->getCount($listing_id);
        
        if ($favored > 0) 
        {
            // Force plugin loading on Review model
            $this->_initPlugins('Favorite');
            $this->Favorite->data = $this->data;
        
            // Delete favorite
            $deleted = $this->Favorite->delete($listing_id, $user_id);

            if($deleted) 
            {
                $favored--;
                $response[] = "jQuery('#jr_favoriteCount{$listing_id}').html('{$favored}');";
                $response[] = "jQuery('#jr_favoriteImg{$listing_id}').fadeOut('slow');";            
                return $this->ajaxResponse($response);
            }
        
        }
                
        return $this->ajaxError(s2Messages::submitErrorDb());
    }
    
    function _feature()
    {
        $listing_id = isset($this->data['Listing']) ? Sanitize::getInt($this->data['Listing'],'id') : 0;
        
        # Stop form data tampering 
        $formToken = cmsFramework::getCustomToken($listing_id);
        if(!$listing_id || !$this->__validateToken($formToken)) 
        {    
            return json_encode(array('error'=>true,'msg'=>stripslashes(s2Messages::accessDenied())));
        }

        $res = $this->Listing->feature($listing_id);
        
        if($res['success'])
        {
            return json_encode(array('error'=>false));        
        } 

        return json_encode(array('error'=>true,'msg'=>stripslashes((!$res['access'] ? s2Messages::accessDenied() : s2Messages::submitErrorDb()))));
    }
    
    function _frontpage()
    {
        $listing_id = isset($this->data['Listing']) ? Sanitize::getInt($this->data['Listing'],'id') : 0;
        
         # Stop form data tampering 
        $formToken = cmsFramework::getCustomToken($listing_id);
        if(!$listing_id || !$this->__validateToken($formToken)) 
        {    
            return json_encode(array('error'=>true,'msg'=>stripslashes(s2Messages::accessDenied())));
        }
        
        $res = $this->Listing->frontpage($listing_id); 

        if($res['success'])
        {
            return json_encode(array('error'=>false));        
        }
        
        return json_encode(array('error'=>true,'msg'=>stripslashes((!$res['access'] ? s2Messages::accessDenied() : s2Messages::submitErrorDb()))));
    }
    
    function _publish($params) 
    {        
        $listing_id = isset($this->data['Listing']) ? Sanitize::getInt($this->data['Listing'],'id') : 0;

         # Stop form data tampering 
        $formToken = cmsFramework::getCustomToken($listing_id);
        if(!$listing_id || !$this->__validateToken($formToken)) 
        {    
            return json_encode(array('error'=>true,'msg'=>stripslashes(s2Messages::accessDenied())));
        }

        $res = $this->Listing->publish($listing_id); 

        if($res['success'])
        {
            return json_encode(array('error'=>false));        
        }
        
        return json_encode(array('error'=>true,'msg'=>stripslashes((!$res['access'] ? s2Messages::accessDenied() : s2Messages::submitErrorDb()))));
    }    
    
    function _imageDelete($params) 
    {            
        $response = array();

        $listing_id = Sanitize::getInt($this->data,'listing_id');
        $delete_key = Sanitize::getInt($this->data,'delete_key');
        $main_image = Sanitize::getString($this->data,'image_path');
        
        # Stop form data tampering 
        $owner = $this->Listing->getListingOwner($listing_id);
        $formToken = cmsFramework::getCustomToken($listing_id, $delete_key, $main_image);
        if(!$this->Access->canEditListing($owner['user_id']) || !$this->__validateToken($formToken))
        {
            return $this->ajaxError(s2Messages::accessDenied());         
        }

        $query = "SELECT images FROM #__content WHERE id = " . $listing_id;
        $this->_db->setQuery($query);
        $listing_images = $this->_db->loadResult();
                
        if($listing_images) 
        {    
            $images = explode("\n",trim($listing_images));
            
            foreach($images AS $key=>$image) {
                if(strstr($image,$main_image)) {
                    unset($images[$key]);
                    break;
                }
            }
            
            $listing = array();
            $listing['Listing']['id'] = $listing_id;
            $listing['Listing']['images'] = implode("\n",$images);
            
            if($this->Listing->store($listing)) 
            {
                # Remove image files
                @unlink(PATH_ROOT . _JR_PATH_IMAGES . $main_image);
                
                // get file name without extension and remove the content id prefix
                $filename = basename($main_image);
                $filename = '*'.str_replace($listing_id.'_','',$filename);
                
                // delete thumbnail files recursively
                $deleted = $this->__rfr(PATH_ROOT . _JR_PATH_IMAGES . 'jreviews' . DS,$filename);
                    
                $response[] = "jQuery('#image{$delete_key}').slideUp(1000).remove();";    
                
                // If image limit is enforced then add a new upload field
                if($this->Config->content_images_total_limit) 
                {
                    $response[] = "jQuery('#image_upload_container').show().append('<div class=\"jr_imageUpload\"><input size=\"20\" type=\"file\" name=\"image[]\" /></div>');";
                }
            }

        }
        
        return $this->ajaxResponse($response,true);
    }
    
    function _imageSetMain() 
    {        
        $response = array();

        $listing_id = Sanitize::getInt($this->data,'listing_id');
        $image_path = Sanitize::getString($this->data,'image_path');
        
        # Stop form data tampering 
        $owner = $this->Listing->getListingOwner($listing_id);
        $formToken = cmsFramework::getCustomToken($listing_id, $image_path);
        if(!$this->Access->canEditListing($owner['user_id']) || !$this->__validateToken($formToken))
        {
            $response[] = "s2Alert('".s2Messages::accessDenied()."');";
            return $this->ajaxResponse($response);    
        }

        $query = "SELECT images FROM #__content WHERE id = " . $listing_id;
        $this->_db->setQuery($query);
        $listing_images = $this->_db->loadResult();

        if($listing_images) 
        {    
            $images = explode("\n",trim($listing_images));
            
            foreach($images AS $key=>$image) {
                if(strstr($image,$image_path)) {
                    unset($images[$key]);
                    array_unshift($images,$image);
                    break;
                }
            }
            $listing = array();
            $listing['Listing']['id'] = $listing_id;
            $listing['Listing']['images'] = implode("\n",$images);
            if($this->Listing->store($listing))
            {
                $response[] = "s2Alert('".__t('The main image was successfully changed.',true,true)."');";
            }

        }

        return $this->ajaxResponse($response);    
    }
    
    function _delete($params) 
    {                    
        $response = array();
   
        $listing_id = $this->data['Listing']['id'] = Sanitize::getInt($this->params,'id');
       
        # Stop form data tampering 
        $formToken = cmsFramework::getCustomToken($listing_id);
        if(!$listing_id || !$this->__validateToken($formToken)) 
        {    
            return $this->ajaxError(s2Messages::accessDenied());         
        }

        # Load current listing author id
        $query = "SELECT Listing.created_by, Listing.images FROM #__content AS Listing WHERE Listing.id = " . $listing_id;
        $this->_db->setQuery($query);
        $row = end($this->_db->loadAssocList());

        # Check access
        if(!$this->Access->canDeleteListing($row['created_by'])) 
        {            
            return $this->ajaxError(s2Messages::accessDenied());         
        }
        
        $this->data['Listing']['images'] = $row['images'];    
            
        # Delete listing and all associated records and images    
        if($this->Listing->delete($this->data))
        {        
            $msg = __t("The listing has been removed.",true);
            $response[] = "jQuery('#jr_listing_manager{$listing_id}').hide('fast').html('{$msg}').fadeIn(1000).effect('highlight',{},5000);";
            return $this->ajaxResponse($response);          
        } 

        return $this->ajaxError(s2Messages::submitErrorDb());
    }

    /*
    * Loads the categories for the selected section in new item submission
    */
    function _loadCategories() 
    {                        
        $this->autoRender = false;
        $this->autoLayout = false;
        $response = array();

        $listing_id = Sanitize::getInt($this->data['Listing'],'id');
        $section_id = Sanitize::getInt($this->data['Listing'],'sectionid');
        $cat_id = Sanitize::getInt($this->data['Listing'],'catid');
        $cat_ids = '';
                  
        if ($section_id) 
        {    
            // If in edit mode get the criteria id to limit the categories to the ones with the same
            // criteriaid and custom fields
            if ((int) $this->data['Listing']['id']) 
            {
                $query = "SELECT catid FROM #__content WHERE id = " . Sanitize::getInt($this->data['Listing'],'id'); 
                $this->_db->setQuery($query);
                $cat_id = $this->_db->loadResult();
                
                $this->_db->setQuery("SELECT criteriaid FROM #__jreviews_categories WHERE id = '$cat_id' AND `option` = 'com_content'");
                
                $criteriaid = $this->_db->loadResult();
    
                $query = "SELECT id FROM #__jreviews_categories WHERE criteriaid = '$criteriaid' AND `option` = 'com_content'";
                
                $this->_db->setQuery($query);
                
                $cat_ids = implode(",",$this->_db->loadResultArray());
            }
    
            $categories = $this->Category->getList($section_id, $cat_ids);
    
            $this->set(array(
                'section_id'=>$section_id,
                'categories'=>$categories,
                'listing'=>array('Listing'=>array(
                        'listing_id'=>null,
                        'section_id'=>$section_id,
                        'cat_id'=>null
                    ))                    
            ));
            
            $categoryList = $this->render('elements','category_list');
            
            if(!$listing_id && !$cat_id) {
                $response[] = "jQuery('#jr_newFields').fadeOut().delay(100).html();";                    
            }
            return $this->ajaxUpdateElement('jr_Categories',$categoryList,$response);              
        }
    
        // No section selected and not in edit mode, so hide form
        if (!$section_id && !$listing_id) 
        { // Don't fade form if editing item                                              
            $response[] = "jQuery('#jr_newFields').fadeOut();";                    
            $response[] = "jQuery('#cat_id').attr('disabled','disabled');";                        
        }
        
        $this->set(array(
            'section_id'=>null,
            'categories'=>array(),
            'listing'=>array('Listing'=>array(
                    'listing_id'=>null,
                    'section_id'=>null,
                    'cat_id'=>null
                ))                    
        ));
    
        $categoryList = $this->render('elements','category_list');
            
        return $this->ajaxUpdateElement('jr_Categories',$categoryList,$response);              
    
    }
        
    /*
    * Loads the new item form with the review form and approriate custom fields
    */
    function _loadForm() 
    {                 
        if($this->cmsVersion == CMS_JOOMLA16) return $this->_loadForm_j16();
	
	    $this->autoRender = false;
        $this->autoLayout = false;
        $this->response = array();

        $dateFieldsEntry = array();
        $dateFieldsReview = array();
 
        $criteria = array();
 
        # If editing item then don't do anything and exit
        if ((int) $this->data['Listing']['id'] > 0) {
            $this->response[] = "jQuery('.jr_loadingSmall').hide();";
            $this->response[] = "jQuery('#jr_listingForm button').removeAttr('disabled');";
            return $this->ajaxResponse($this->response);            
        }
        
        $section_id = Sanitize::getInt($this->data['Listing'],'sectionid');
        $cat_id = Sanitize::getInt($this->data['Listing'],'catid');

        if ($section_id  && $cat_id) {
               
            $name_choice = $this->Config->name_choice;

            if ($name_choice == 'alias') {
                $name = $this->_user->username;
            } else {
                $name = $this->_user->name;
            }
            
            # Get criteria info for selected category
            $category  = $this->JreviewsCategory->findRow(array(
                'conditions'=>array('JreviewsCategory.id = ' . $cat_id,'JreviewsCategory.option = "com_content"')
            ));
            
            isset($category['ListingType']) and $this->Config->override($category['ListingType']['config']);

            # Set theme suffix
            $this->Theming->setSuffix(compact('cat_id'));
                        
            $criteria = $category['ListingType'];
            $criteria['criteria'] = explode("\n",$category['ListingType']['criteria']);
            $criteria['tooltips'] = explode("\n",$category['ListingType']['tooltips']);            
            $criteria['required'] = explode("\n",$category['ListingType']['required']);
            
            // every criteria must have 'Required' set (0 or 1). if not, either it's data error or data from older version of jr, so default to all 'Required'
            if ( count($criteria['criteria']) != count($criteria['required']) ) 
            {
                $criteria['required'] = array_fill(0, count($criteria['criteria']), 1);
            }
            # Get listing custom fields
            $listing_fields = $this->Field->getFieldsArrayNew($criteria['criteria_id'], 'listing', 'getForm');

            # Get review custom fields        
            $review_fields = $this->Field->getFieldsArrayNew($criteria['criteria_id'], 'review', 'getForm');            

            // Captcha security image
            if ($this->Access->showCaptcha()) 
            {
                $captcha = $this->Captcha->displayCode();
            } 
            else 
            {
                $captcha = array('image'=>null);
            }
    
            $this->set(array(
                    'User'=>$this->_user,
                    'Access'=>$this->Access,
                    'name'=>$name,
                    'captcha'=>$captcha['image'],
                    'listing_fields'=>$listing_fields,
                    'review_fields'=>$review_fields,
                    'criteria'=>$criteria,
                    'listing'=>array('Listing'=>array(
                            'listing_id'=>0,
                            'title'=>'',
                            'summary'=>'',
                            'description'=>'',
                            'metakey'=>'',
                            'metadesc'=>'',
                            'section_id'=>(int) $this->data['Listing']['sectionid'],
                            'cat_id'=>(int) $this->data['Listing']['catid']
                    ))                
            ));
             
            $update_html = $this->render('listings','create_form');        
            $this->response[] = "jQuery('#jr_listingForm button').removeAttr('disabled');";
            $this->response[] = "jreviews.review.starRating('-new',".Sanitize::getVar($this->Config,'rating_increment',1).");";
            return $this->ajaxUpdateElement('jr_newFields',$update_html,$this->response);
        }
          
        # Neither section nor category selected so hide form
        $this->response[] = "jQuery('#jr_newFields').fadeOut();";
        return $this->ajaxResponse($this->response);
    }
    
    /**
    * Loads the new item form with the review form and approriate custom fields
    **/
    function _loadForm_j16()  
    {            
        $this->autoRender = false;
        $this->autoLayout = false;
        $this->response = array();
        $dateFieldsEntry = array();
        $dateFieldsReview = array();
        $isLeaf = false;
        $level = str_replace('cat_id','',$this->data['level']);
   
        # If editing item then don't do anything and exit
        if (Sanitize::getInt($this->data['Listing'],'id')) {
            $this->response[] = "jQuery('.jr_loadingSmall').hide();";
            return $this->ajaxResponse($this->response);            
        }
         
        $cat_id = Sanitize::getInt($this->data,'catid');
        $cat_id_array =  Sanitize::getVar($this->data['Listing'],'catid');
         
        # No category selected
        if(!$cat_id)
        {
            $this->response[] = "jQuery('#jr_newFields').fadeOut();";
            $this->response[] = "
                var cat_lists = jQuery('#jr_Categories').find('select');
                var count = cat_lists.length;
                var remove = false;
                if( count > 1) {
                    cat_lists.each(function(index){
                        if(remove) jQuery(this).remove();
                        if(this.value == 0) remove = true;
                    });
                }
            ";
//            return $this->ajaxResponse($this->response);
        
			// Check if there's a new cat id we can use
			$catArray = Sanitize::getVar($this->data['Listing'],'catid',array());
			$catArray = array_slice($catArray, 0, array_search(0, $catArray));

			if(!empty($catArray)) {
				$level = count($catArray);
				$cat_id = array_pop($catArray);
			}
		} 
		
        # Category selected is not leaf. Need to show new category list with children, but clear every list to the right first!
        if(!$this->Category->isLeaf($cat_id))
        {           
            $categories = $this->Category->getCategoryList(array('parent_id'=>$cat_id,'indent'=>false,'disabled'=>false,'listing_type'=>true));
			
            if(!empty($categories))
            {
				// Remove categories without submit access
				foreach($categories AS $key => $row) 
				{
					$overrides = json_decode($row->config,true);
					if(!$this->Access->canAddListing($overrides['addnewaccess'])) {
						unset($categories[$key]);
					}
				}
				
				if(!empty($categories)) 
				{
					
					$cat = reset($categories);
					App::import('Helper','form','jreviews');
					$Form = ClassRegistry::getClass('FormHelper');
					$attributes = array('id'=>'cat_id'.$cat->level,'class'=>'jrSelect','size'=>'1','onchange'=>'jreviews.listing.submitCategory(this);');
					$select_list = $Form->select(
						'data[Listing][catid][]',
						array_merge(array(array('value'=>null,'text'=>__t("Select Category",true))),$categories),
						null,
						$attributes
					);            

					if($level >= 1 && count($cat_id_array) > 1) {
						$this->response[] = '
							var $cat_lists = jQuery("#jr_Categories").children("select");
							$cat_lists.each(function(index){if(index > '.($level-1).') { jQuery(this).remove(); }}); 
						';                
					}  
					$this->response[] = "jQuery('#jr_Categories').append('".$select_list."');";
				}
				else {
					$update_html = __t("You are not allowed to submit listings in this category.",true,true);
					return $this->ajaxUpdateElement('jr_newFields',$update_html);					 
				}

            }
            
            # Checks if this category is setup with a listing type. Otherwise hides the form.
            if(!$this->Category->isJReviewsCategory($cat_id))
            {
                return $this->ajaxUpdateElement('jr_newFields','',$this->response);         
            }
        }
        else
        {
            $isLeaf = true;
        }
        
        # Category selected is leaf or set up with listing type, so show form
        if ($cat_id) 
        {      
            # Set theme suffix
            $this->Theming->setSuffix(compact('cat_id'));
          
            $name_choice = $this->Config->name_choice;

            if ($name_choice == 'alias') {
                $name = $this->_user->username;
            } else {
                $name = $this->_user->name;
            }

            # Get criteria info for selected category
            $category  = $this->JreviewsCategory->findRow(array(
                'conditions'=>array('JreviewsCategory.id = ' . $cat_id,'JreviewsCategory.option = "com_content"')
            ));
            
            isset($category['ListingType']) and $this->Config->override($category['ListingType']['config']);

            # Set theme suffix
            $this->Theming->setSuffix(compact('cat_id'));
                        
            $criteria = $category['ListingType'];
            $criteria['criteria'] = explode("\n",$category['ListingType']['criteria']);
            $criteria['tooltips'] = explode("\n",$category['ListingType']['tooltips']);            
            $criteria['required'] = explode("\n",$category['ListingType']['required']);

            // every criteria must have 'Required' set (0 or 1). if not, either it's data error or data from older version of jr, so default to all 'Required'
            if ( count($criteria['criteria']) != count($criteria['required']) ) 
            {
                $criteria['required'] = array_fill(0, count($criteria['criteria']), 1);
            }
            # Get listing custom fields
            $listing_fields = $this->Field->getFieldsArrayNew($criteria['criteria_id'], 'listing', 'getForm');

            # Get review custom fields        
            $review_fields = $this->Field->getFieldsArrayNew($criteria['criteria_id'], 'review', 'getForm');            

            // Captcha security image
            if ($this->Access->showCaptcha()) {
                $captcha = $this->Captcha->displayCode();
            } else {
                $captcha = array('image'=>null);
            }
    
            $this->set(array(
                    'User'=>$this->_user,
                    'Access'=>$this->Access,
                    'name'=>$name,
                    'captcha'=>$captcha['image'],
                    'listing_fields'=>$listing_fields,
                    'review_fields'=>$review_fields,
                    'criteria'=>$criteria,
                    'listing'=>array('Listing'=>array(
                            'listing_id'=>0,
                            'title'=>'',
                            'summary'=>'',
                            'description'=>'',
                            'metakey'=>'',
                            'metadesc'=>'',
                            'cat_id'=>(int) $this->data['Listing']['catid']
                    ))                
            ));
             
            $update_html = $this->render('listings','create_form');        
            $this->response[] = "jQuery('#jr_listingForm button').removeAttr('disabled');";
            $this->response[] = "jreviews.review.starRating('-new',".Sanitize::getVar($this->Config,'rating_increment',1).");";

           // Remove cat select lists to the right of current select list if current selection is a leaf
            if($level && $isLeaf)
            {
                $this->response[] = "
                    var cat_lists = jQuery('#jr_Categories').find('select');
                    cat_lists.each(function(index){if(index > ".($level-1).") { jQuery(this).remove(); }}); 
                ";                
            }
           
            return $this->ajaxUpdateElement('jr_newFields',$update_html,$this->response);
        }
    
        # No category selected so hide form
        $this->response[] = "jQuery('#jr_newFields').fadeOut();";
        return $this->ajaxResponse($this->response);
    }    

    function _save() 
    {    
        /*******************************************************************
        * This method is processed inside an iframe
        * To access any of the DOM elements via jQuery it's necessary to prepend
        * all jQuery calls with $parentFrame (i.e. $parentFrame.jQuery)
        ********************************************************************/
        $this->autoRender = false;
        $this->autoLayout = false;
        $response = array();
        $parentFrame = 'window.parent'; 
        $validation = '';
        $listing_id = Sanitize::getInt($this->data['Listing'],'id',0);
        $isNew = $this->Listing->isNew = $listing_id == 0 ? true : false;
        $this->data['email'] = Sanitize::getString($this->data,'email');
        $this->data['name'] = Sanitize::getString($this->data,'name');
        $this->data['categoryid_hidden'] = Sanitize::getInt($this->data['Listing'],'categoryid_hidden');
        $cat_id = Sanitize::getVar($this->data['Listing'],'catid');
        $this->data['Listing']['catid'] = is_array($cat_id) ? (int) array_pop(array_filter($cat_id)) : (int) $cat_id; /*J16*/
        $this->data['Listing']['title'] = Sanitize::getString($this->data['Listing'],'title','');
        $this->data['Listing']['created_by_alias'] = Sanitize::getString($this->data,'name','');        
        if($this->cmsVersion == CMS_JOOMLA15)
	    {
	            $this->data['sectionid_hidden'] = Sanitize::getInt($this->data['Listing'],'sectionid_hidden');
	            $this->data['Listing']['sectionid'] = Sanitize::getInt($this->data['Listing'],'sectionid');
	    }
	    else
	    {
		    $this->data['Listing']['language'] = '*';
            $this->data['Listing']['access'] = 1;         
	    }

        $category_id = $this->data['Listing']['catid'] ? $this->data['Listing']['catid'] : $this->data['categoryid_hidden'];

        # Get criteria info    
        $criteria = $this->Criteria->findRow(array(
            'conditions'=>array('Criteria.id = 
                (SELECT criteriaid FROM #__jreviews_categories WHERE id = '.(int) $category_id.' AND `option` = "com_content")
            ')
        ));
        
        if(!$criteria)
        {
            $validation = __t("The category selected is invalid.",true,true);
            $response[] = "$parentFrame.jQuery('#jr_listingFormValidation').html('$validation');";   
            $response[] = "$parentFrame.jQuery('.button').removeAttr('disabled');";                             
            $response[] = "$parentFrame.jQuery('.jr_loadingSmall').hide();";                
            return $this->makeJS($response);   
        }

        $this->data['Criteria']['id'] = $criteria['Criteria']['criteria_id'];
        
        # Override global configuration
        isset($criteria['ListingType']) and $this->Config->override($criteria['ListingType']['config']);
        
        # Perform access checks
        if($isNew && !$this->Access->canAddListing())
        {
            return $this->makeJS("$parentFrame.s2Alert('".__t("You are not allowed to submit listings in this category.",true,true)."')");
        }
        elseif(!$isNew)
        {
             $query = "SELECT created_by FROM #__content WHERE id = " . $listing_id;
             $this->_db->setQuery($query);
             $listing_owner = $this->_db->loadResult();
             if(!$this->Access->canEditListing($listing_owner))
             {
                return $this->makeJS("$parentFrame.s2Alert('".s2Messages::accessDenied()."')");
             }
        }        
                                
        # Load the notifications observer model component and initialize it. 
        # Done here so it only loads on save and not for all controlller actions.
        $this->components = array('security','notifications');
        $this->__initComponents();
        if($this->invalidToken == true) 
        {
            return $this->makeJS("$parentFrame.s2Alert('".s2Messages::invalidToken()."')");
        }        

        # Override configuration
        $category = $this->Category->findRow(array('conditions'=>array('Category.id = ' . $this->data['Listing']['catid'])));
        $this->Config->override($category['ListingType']['config']);
    
        if ($this->Access->loadWysiwygEditor()) 
            {    
                $this->data['Listing']['introtext'] = Sanitize::stripScripts(Sanitize::stripWhitespace(Sanitize::getVar($this->data['__raw']['Listing'],'introtext')));
                $this->data['Listing']['fulltext'] = Sanitize::stripScripts(Sanitize::stripWhitespace(Sanitize::getVar($this->data['__raw']['Listing'],'fulltext')));
                $this->data['Listing']['introtext'] = html_entity_decode($this->data['Listing']['introtext'],ENT_QUOTES,cmsFramework::getCharset());
                $this->data['Listing']['fulltext'] = html_entity_decode($this->data['Listing']['fulltext'],ENT_QUOTES,cmsFramework::getCharset());
            } 
        else 
            {
                $this->data['Listing']['introtext'] = Sanitize::stripAll($this->data['Listing'],'introtext','');
                
                if(isset($this->data['Listing']['fulltext'])) 
                {
                    $this->data['Listing']['fulltext'] = Sanitize::stripAll($this->data['Listing'],'fulltext','');
                } else {
                    $this->data['Listing']['fulltext'] = '';
                }    
            }
                                        
        $this->data['Listing']['introtext'] = str_replace( '<br>', '<br />', $this->data['Listing']['introtext'] );
        $this->data['Listing']['fulltext']     = str_replace( '<br>', '<br />', $this->data['Listing']['fulltext'] );
        
        if($this->Access->canAddMeta())
        {
            $this->data['Listing']['metadesc'] = Sanitize::getString($this->data['Listing'],'metadesc');
            $this->data['Listing']['metakey'] = Sanitize::getString($this->data['Listing'],'metakey');
        }
        
        // Title alias handling
        $slug = '';
        $alias = Sanitize::getString($this->data['Listing'],'alias');
        if($isNew && $alias == '')
            {                
                $slug = S2Router::sefUrlEncode($this->data['Listing']['title']);
                if(trim(str_replace('-','',$slug)) == '') {
                    $slug = date("Y-m-d-H-i-s");
                }
            }
        elseif($alias != '')
            {
                // Alias filled in so we convert it to a valid alias
                $slug = S2Router::sefUrlEncode($alias);                
                if(trim(str_replace('-','',$slug)) == '') {
                    $slug = date("Y-m-d-H-i-s");
                }
            }

        $slug != '' and $this->data['Listing']['alias'] = $slug;

        # Check for duplicates
        switch($this->Config->content_title_duplicates) 
            {
                case 'category': // Checks for duplicates in the same category
                    $query = "
                        SELECT 
                            count(*) 
                        FROM 
                            #__content AS Listing WHERE Listing.title = " . $this->_db->Quote($this->data['Listing']['title']) . "
                            AND Listing.state >= 0 
                            AND Listing.catid = " . $this->data['Listing']['catid'] 
                            . (!$isNew ? " AND Listing.id <> " . $listing_id : '')
                        ;
                    $this->_db->setQuery($query);
                    $titleExists = $this->_db->loadResult();
                break;
                case 'no': // Checks for duplicates all over the place
                    $query = "
                        SELECT 
                            count(*) 
                        FROM 
                            #__content AS Listing
                        WHERE 
                            Listing.title = " . $this->_db->Quote($this->data['Listing']['title']) . "
                           AND Listing.state >= 0
                           " . (!$isNew ? " AND Listing.id <> " . $listing_id : '')
                    ;
                    $this->_db->setQuery($query);        
                    $titleExists = $this->_db->loadResult();
                break;
                case 'yes': // Duplicates are allowed, no checking necessary
                    $titleExists = false;
                break;
            }

        if ($titleExists /*&& $isNew */ && $this->data['Listing']['title'] != '') 
            {// if listing exists
                $validation = '<span>'.__t("A listing with that title already exists.",true,true)."</span>";
                $response[] = "$parentFrame.jQuery('#jr_listingFormValidation').html('$validation');";   
                $response[] = "$parentFrame.jQuery('.button').removeAttr('disabled');";                             
                $response[] = "$parentFrame.jQuery('.jr_loadingSmall').hide();";                
                return $this->makeJS($response);
            } 

        // Review form display check logic used several times below
        $revFormSetting = $this->Config->content_show_reviewform;
        if($revFormSetting == 'noteditors' && !$this->Config->author_review) {
            $revFormSetting = 'all';
        }
        
        $revFormEnabled = !isset($this->data['review_optional'])
            && $this->Access->canAddReview() 
            && $isNew             
            && (   ($revFormSetting == 'all' && ($this->Config->author_review || $this->Config->user_reviews))
                || ($revFormSetting == 'authors'  && $this->Access->isJreviewsEditor($this->_user->id))
                || ($revFormSetting == 'noteditors' && !$this->Access->isJreviewsEditor($this->_user->id))
            );

        // Validation of content default input fields
        if($this->cmsVersion == CMS_JOOMLA15)
	    {
		    if ( !$this->data['Listing']['catid'] || !$this->data['Listing']['sectionid'] ) 
            {
	            $this->Listing->validateSetError("sec_cat", __t("You need to select both a section and a category.",true));
	        }
	    }
	    else
	    {
            !$this->data['Listing']['catid'] and $this->Listing->validateSetError("sec_cat", __t("You need to select a category.",true));
	    }

        // Validate only if it's a new listing
        if ($isNew) 
        {                 
            if (!$this->_user->id) {
                $this->Listing->validateInput($this->data['name'], "name", "text", __t("You must fill in your name.",true), $this->Config->content_name == "required" ? 1 : 0);
                $this->Listing->validateInput($this->data['email'], "email", "email", __t("You must fill in a valid email address.",true), $this->Config->content_email == "required" ? 1 : 0);
                $this->data['name'] = Sanitize::getString($this->data,'name','');
                $this->data['email'] = Sanitize::getString($this->data,'email','');
            } else {
                $this->data['name'] = $this->_user->name;
                $this->data['email'] = $this->_user->email;
            }
        }

        $this->Listing->validateInput($this->data['Listing']['title'], "title", "text", __t("You must fill in a title for the new listing.",true,true), 1);
    
        # Validate listing custom fields
        $listing_valid_fields = &$this->Field->validate($this->data,'listing',$this->Access);
        $this->Listing->validateErrors = array_merge($this->Listing->validateErrors,$this->Field->validateErrors);            
        $this->Listing->validateInput($this->data['Listing']['introtext'], "introtext", "text", __t("You must fill in a summary for the new listing.",true,true), $this->Config->content_summary == "required" ? 1 : 0);
        $this->Listing->validateInput($this->data['Listing']['fulltext'], "fulltext", "text", __t("You must fill in a description for the new listing.",true,true), $this->Config->content_description == "required" ? 1 : 0);

        # Validate review custom fields    
        if ($revFormEnabled && $criteria['Criteria']['state'])  
        {    
            // Review inputs
            $this->data['Review']['userid'] = $this->_user->id;    
            $this->data['Review']['email'] = $this->data['email'];
            $this->data['Review']['name'] = $this->data['name'];
            $this->data['Review']['username'] = Sanitize::getString($this->data,'name','');        
            $this->data['Review']['title'] = Sanitize::getString($this->data['Review'],'title');
            $this->data['Review']['location'] = Sanitize::getString($this->data['Review'],'location'); // deprecated
            $this->data['Review']['comments'] = Sanitize::getString($this->data['Review'],'comments');

            // Review standard fields
            $this->Listing->validateInput($this->data['Review']['title'], "rev_title", "text", __t("You must fill in a title for the review.",true,true), ($this->Config->reviewform_title == 'required' ? true : false));
            
            if ( $criteria['Criteria']['state'] == 1 ) //ratings enabled
            {
                $criteria_qty = $criteria['Criteria']['quantity'];
                $ratingErr = 0;
    
                if(!isset($this->data['Rating']))
                {
                    $ratingErr = $criteria_qty;
                }
                else
                {
                    for ( $i = 0;  $i < $criteria_qty; $i++ ) 
                    {
                        if (!isset($this->data['Rating']['ratings'][$i]) 
                            || 
                            (empty($this->data['Rating']['ratings'][$i])
                                || $this->data['Rating']['ratings'][$i] == 'undefined' 
                                || (float)$this->data['Rating']['ratings'][$i] > $this->Config->rating_scale)
                        ) {
                            $ratingErr++;
                        }
                    }
                }
                $this->Listing->validateInput('', "rating", "text", sprintf(__t("You are missing a rating in %s criteria.",true,true),$ratingErr), $ratingErr);
            }

            // Review custom fields
            $this->Field->validateErrors = array(); // Clear any previous validation errors
            $review_valid_fields = $this->Field->validate($this->data,'review',$this->Access);
            $this->Listing->validateErrors = array_merge($this->Listing->validateErrors,$this->Field->validateErrors);
            $this->Listing->validateInput($this->data['Review']['comments'], "comments", "text", __t("You must fill in your comment.",true,true),  ($this->Config->reviewform_comment == 'required' ? true : false));

        } // if ($revFormEnabled && $criteria['Criteria']['state']) 


        # Validate image fields
        $this->Uploads->validateImages();

        # Validate Captcha security code            
        if ($isNew && $this->Access->showCaptcha()) 
            {
                if(!isset($this->data['Captcha']['code'])) 
                    {                    
                        $this->Listing->validateSetError("code", __t("The security code you entered was invalid.",true,true));
                            
                    } 
                elseif ($this->data['Captcha']['code'] == '') 
                    {        
                        $this->Listing->validateInput($this->data['Captcha']['code'], "code", "text", __t("You must fill in the security code.",true),  1);        
                    } 
                else 
                    {    
                        if (!$this->Captcha->checkCode($this->data['Captcha']['code'],$this->ipaddress)) 
                        {                        
                            $this->Listing->validateSetError("code", __t("The security code you entered was invalid.",true,true));                    
                        }    
                    }                    
            }
                    
        # Get all validation messages
        $validation = $this->Listing->validateGetError().$this->Uploads->getMsg();
       
        # Validation failed
        if($validation != '')
        {   
            $response[] = "var parentForm = $parentFrame.jQuery('#jr_listingForm');";        
            $response[] = "$parentFrame.jQuery('#jr_listingFormValidation').html('$validation');";  
            $response[] = "parentForm.find('.button').removeAttr('disabled');";        

            // Transform textareas into wysiwyg editors
            if($this->Access->loadWysiwygEditor())
                {
                    App::import('Helper','Editor','jreviews');
                    $Editor = new EditorHelper();
                    $response[] = $parentFrame.'.'.$Editor->transform(true);        
                }

            // Replace captcha with new instance
            if ($this->Access->in_groups($this->Config->security_image)) 
                {
                    $captcha = $this->Captcha->displayCode();
                    $response[] = "$parentFrame.jQuery('#captcha').attr('src','{$captcha['src']}');";        
                    $response[] = "$parentFrame.jQuery('#jr_captchaCode').val('');";        
                }

            $response[] = "parentForm.find('.jr_loadingSmall').hide();";
            return $this->makeJS($response); // Can't use ajaxResponse b/c we are in an iframe
        }

        # Validation passed, continue...
        if ($isNew) 
            {
                $this->data['Listing']['created'] = _CURRENT_SERVER_TIME; //gmdate('Y-m-d H:i:s');
                $this->data['Listing']['publish_up'] = _CURRENT_SERVER_TIME; //gmdate('Y-m-d H:i:s');
                $this->data['Listing']['created_by'] = $this->_user->id;
                $this->data['Listing']['publish_down'] = NULL_DATE;
                $this->data['Field']['Listing']['email'] = $this->data['email'];                    

                // If visitor, assign name field to content Alias
                if (!$this->_user->id) {
                    $this->data['Listing']['created_by_alias'] = $this->data['name'];
                }

                // Check moderation settings
                $this->data['Listing']['state'] = (int) !$this->Access->moderateListing();

                // If listing moderation is enabled, then the review is also moderated
                if(!$this->data['Listing']['state']){
                    $this->Config->moderation_reviews = $this->Config->moderation_editor_reviews = $this->Config->moderation_item;
                }
    
            }
        else    
            {
                if($this->Config->moderation_item_edit) // If edit moderation enabled, then check listing moderation, otherwise leave state as is
                {
                    $this->data['Listing']['state'] = (int) !$this->Access->moderateListing(); 
                }               

                $this->data['Listing']['modified'] = _CURRENT_SERVER_TIME; //gmdate('Y-m-d H:i:s');
                $this->data['Listing']['modified_by'] = $this->_user->id;
                
                $query = 'SELECT images FROM #__content WHERE id = ' . $this->data['Listing']['id'];
                $this->_db->setQuery($query);
                $this->data['Listing']['images'] = $this->_db->loadResult();
                
                // Check total number of images
                if (!$this->Uploads->checkImageCount($this->data['Listing']['images'])) 
                {
                    $validation .= '<span>'.sprintf(__t("The total number of images is limited to %s",true,true),$this->Config->content_images).'</span><br />';
                    $response[] = "$parentFrame.jQuery('#jr_listingFormValidation').html('$validation');";   
                    $response[] = "$parentFrame.jQuery('.button').removeAttr('disabled');";                             
                    $response[] = "$parentFrame.jQuery('.jr_loadingSmall').hide();";                
                    return $this->makeJS($response);                    
                }
            }

        // Process images and update data array
        if ($this->Uploads->success)
        {
            $imageUploadPath = PATH_ROOT . _JR_PATH_IMAGES . 'jreviews' . DS;
            
            $this->Uploads->uploadImages($this->data['Listing']['id'], $imageUploadPath);

            if ($isNew) { // New item

                $currImages = $this->Uploads->images;

            } elseif ($this->data['Listing']['images'] != '') { // Editing and there are existing images

                $currImages = array_merge(explode("\n",    $this->data['Listing']['images']),$this->Uploads->images);

            } else { // Editing and there are no existing images

                $currImages = $this->Uploads->images;                      
            }
            $this->data['Listing']['images'] = implode( "\n", $currImages );
        }

        # Save listing
        $savedListing = $this->Listing->store($this->data);                
        $listing_id = $this->data['Listing']['id'];

        if (!$savedListing) 
            {                    
                $validation .= __t("The was a problem saving the listing",true,true);                            
            }
        
        // Error on listing save
        if($validation!='')
        {
            $response[] = "$parentFrame.jQuery('#jr_listingFormValidation').html('$validation');";   
            $response[] = "$parentFrame.jQuery('.button').removeAttr('disabled');";                             
            $response[] = "$parentFrame.jQuery('.jr_loadingSmall').hide();";                
            return $this->makeJS($response);            
        }
  
        # Save listing custom fields
        $this->data['Field']['Listing']['contentid'] = $this->data['Listing']['id'];
        $this->Field->save($this->data, 'listing', $isNew, $listing_valid_fields);

        # Begin insert review in table
        if ($revFormEnabled && $criteria['Criteria']['state'])
            {
                // Get reviewer type, for now editor reviews don't work in Everywhere components
                $this->data['Review']['author'] = (int) $this->Access->isJreviewsEditor($this->_user->id);     
                $this->data['Review']['mode'] = 'com_content';                    
                $this->data['Review']['pid'] = (int) $this->data['Listing']['id'];
                                    
                // Force plugin loading on Review model
                $this->_initPlugins('Review');
                $this->Review->isNew = true;
                $savedReview = $this->Review->save($this->data, $this->Access, $review_valid_fields);
            }
  
         # Before render callback
        if($isNew && isset($this->Listing->plgBeforeRenderListingSaveTrigger))
        {
            $plgBeforeRenderListingSave = $this->Listing->plgBeforeRenderListingSave();
            switch($plgBeforeRenderListingSave)
            {
                case '0': $this->data['Listing']['state'] = 1; break;
                case '1': $this->data['Listing']['state'] = 0; break;
                case '': break;
                default: return $plgBeforeRenderListingSave; break;
            }
        }

        # Moderation disabled  
        if (!isset($this->data['Listing']['state']) || $this->data['Listing']['state']) 
            {    
                $fields = array(  
                    'Criteria.criteria AS `Criteria.criteria`',
                    'Criteria.tooltips AS `Criteria.tooltips`',
                );
                
                $listing = $this->Listing->findRow(array('fields'=>$fields,'conditions'=>array('Listing.id = ' . $listing_id)),array('afterFind' /* Only need menu id */)); 

                # Facebook wall integration
                $fb_checkbox = Sanitize::getBool($this->data,'fb_publish');
                $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable') 
                    && Sanitize::getBool($this->Config,'facebook_listings') 
                    && $fb_checkbox;
                
                $token = cmsFramework::getCustomToken($listing_id);
                
                $facebook_integration and $response[] = 
                    $parentFrame.'.jQuery.get('.$parentFrame.'.s2AjaxUri+'.$parentFrame.'.jreviews.ajax_params()+\'&url=facebook/_postListing/id:'.$listing_id.'&'.$token.'=1\');
                ';  
                             
                $url = cmsFramework::route($listing['Listing']['url']);

                $update_text = $isNew ? __t("Thank you for your submission.",true,true) : __t("The listing was successfully saved.",true,true);
                $update_html = "<a href=\"{$url}\">".__t("Click here to view the listing",true)."</a>";
                $jsonObject = json_encode(compact('target_id','update_text','update_html'));
                $response[] = '
                    var $parentForm = '.$parentFrame.'.jQuery(\'#jr_listingForm\');
                    $parentForm.scrollTo({duration:400,offset:-100});
                    $parentForm.s2ShowUpdate('.$jsonObject.');                                                       
                ';                
                
                return $this->makeJS($response);                    
        } 

        # Moderation enabled
        $update_text = __t("Thank you for your submission. It will be published once it is verified.",true);
        $update_html = '<div id=\"s2Msgjr_listingForm\" class=\"jr_postUpdate\">'.$update_text.'</div>';
        $response[] = '
            var $parentForm = '.$parentFrame.'.jQuery(\'#jr_listingForm\');
            $parentForm.scrollTo({duration:400,offset:-100},function(){
                $parentForm.fadeOut(250,function(){$parentForm.html("'.$update_html.'").show();});
            });
        ';    
        return $this->makeJS($response);
    }
} 
