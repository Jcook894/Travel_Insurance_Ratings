<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );
		
class JreviewsHelper extends MyHelper
{
	var $helpers = array('html','form','time');
    	
	function __construct()
    {
		parent::__construct();
		!class_exists('CommunityHelper') and App::import('Helper','community');
		$this->Community = ClassRegistry::getClass('CommunityHelper');	
        $this->Routes = ClassRegistry::getClass('RoutesHelper');
        $this->Paid = ClassRegistry::getClass('PaidHelper');
        $this->PaidRoutes = ClassRegistry::getClass('PaidRoutesHelper');
    }
    
    function orderingOptions()
    {
        $order_options_array = array (
            'featured'        =>__t("Featured",true),
            'alpha'            =>__t("Title",true),
//            'alias'            =>__t("Alias",true),
//            'ralpha'        =>__t("Title DESC",true),
            'rdate'            =>__t("Most recent",true),
            'updated'            =>__t("Last updated",true),
//            'date'            =>__t("Oldest",true),
            'rhits'            =>__t("Most popular",true),
//            'hits'            =>__t("Least popular",true),
            'rating'        =>__t("Highest user rating",true),
            'rrating'        =>__t("Lowest user rating",true),
            'editor_rating'    =>__t("Highest editor rating",true),
            'reditor_rating'=>__t("Lowest editor rating",true),
            'reviews'        =>__t("Most reviews",true),
            'author'        =>__t("Author",true)
        );
        return $order_options_array;
    }
		
	function orderingList($selected, $fields = array(), $return = false)
	{	                  
        $orderingList = $this->orderingOptions();

		if(Configure::read('geomaps.enabled')==true) {
			$orderingList['distance'] = __t("Distance",true);
            if($selected=='') $selected = 'distance';
		}		

		if(!$this->Config->user_reviews) {
			unset($orderingList['reviews']);
			unset($orderingList['rating']);
			unset($orderingList['rrating']);
		}

		if(!$this->Config->author_review) {
			unset($orderingList['editor_rating']);
			unset($orderingList['reditor_rating']);
		}		
                  
		if(!empty($fields))
		{
			foreach($fields AS $field)
			{
                if($this->Access->in_groups($field['access'])) {
                    $orderingList[$field['value']] = $field['text'] . ' ' . __t("ASC",true);
                    $orderingList['r' . $field['value']] = $field['text'] . ' ' .  __t("DESC",true);
                }                
			}
		}
		
		if($return) {
			return $orderingList;
		}
		
		$attributes = array(
			'size'=>'1',
			'onchange'=>"window.location=this.value;return false;"
		);

		return $this->generateFormSelect($orderingList,$selected,$attributes);
	}
	
	function orderingListReviews($selected, $params = false) {
				
		$options_array = array(
			'rdate'			=>__t("Most recent",true),
			'date'			=>__t("Oldest",true),
            'updated'       =>__t("Last updated",true),
			'rating'		=>__t("Highest user rating",true),
			'rrating'		=>__t("Lowest user rating",true),
			'helpful'		=>__t("Most helpful",true),
			'rhelpful'		=>__t("Least helpful",true),
            'discussed'     =>__t("Most discussed",true)
		);

        $orderingList = $options_array;         
			
        if(Sanitize::getBool($params,'return')) return $orderingList;
                    	
		$attributes = array(
			'size'=>'1',
			'onchange'=>"window.location=this.value;return false;"
		);
						
		return $this->generateFormSelect($orderingList,$selected,$attributes);
	}
    
    function orderingListPosts($selected, $options = array()) {
                
        $options_array = array(
            'date'            =>__t("Oldest",true),
            'rdate'            =>__t("Most recent",true),
//            'helpful'        =>__t("Most helpful",true),
//            'rhelpful'        =>__t("Least helpful",true)
        );

        if(!empty($options)) {
            foreach($options AS $key) {
                if(isset($options_array[$key])) {
                    $orderingList[$key] = $options_array[$key];
                }
            }

        } else {
            $orderingList = $options_array;
        }
                
        $attributes = array(
            'size'=>'1',
            'onchange'=>"window.location=this.value;return false;"
        );
                        
        return $this->generateFormSelect($orderingList,$selected,$attributes);
    }    
	
	function generateFormSelect($orderingList,$selected,$attributes) {
						
		# Construct new route
		$new_route = cmsFramework::constructRoute($this->passedArgs,array('lang','order','page')); 
		if(Sanitize::getInt($this->params,'page',1) == 1 
			&& preg_match('/^(index.php\?option=com_jreviews&amp;Itemid=[0-9]+)(&amp;url=menu\/)$/i',$new_route,$matches)
		) {
			// Remove menu segment from url if page 1 and it' a menu
				$new_route_page_1 = $matches[1];
		}
		
		$selectList = array();

		foreach($orderingList AS $value=>$text) 
		{
			$default_order = Sanitize::getString($this->params,'default_order');
			
			// Default order takes user back to the first page
			if($value == $default_order) {
			
				$selectList[] = array('value'=>cmsFramework::route($new_route_page_1),'text'=>$text);	
			
			}
			else {
			
				$selectList[] = array('value'=>cmsFramework::route($new_route . '/order' . _PARAM_CHAR . $value),'text'=>$text);	
			}
			
		}

		if($selected == $default_order) 
		{
			
			$selected = cmsFramework::route($new_route_page_1);

		}
		else {
		
			$selected = cmsFramework::route($new_route . '/order' . _PARAM_CHAR . $selected);
			
		}
		

		return $this->Form->select('order',$selectList,$selected,$attributes);		
	}
	
	function newIndicator($days, $date) {
		return $this->Time->wasWithinLast($days . ' days', $date);
	}
	
	function compareCheckbox($listing) {
		App::import('Helper','routes','jreviews');
		$Routes = ClassRegistry::getClass('RoutesHelper');	
		
		$listing_title = htmlspecialchars($listing['Listing']['title'],ENT_QUOTES,cmsFramework::getCharset());
		$listing_id = $listing['Listing']['listing_id'];
		$listing_url = $Routes->content($listing['Listing']['title'],$listing,array('return_url'=>true));
		
		$checkbox = '<input type="checkbox" class="checkListing" name="'. $listing_title .'" id="listing'. $listing_id .'" value="'. $listing_id .'" />&nbsp;<label class="lbCompare" for="listing'. $listing_id .'">' . __t("Compare", true) . '</label>';
		$listing_type = '<span id="listingID'. $listing_id .'" class="listingType'. $listing['Criteria']['criteria_id'] .'" style="display:none;">' . $listing['Criteria']['title'] . '</span>';
		$url = '<span class="listingUrl'. $listing_id .'" style="display:none;">'. $listing_url .'</span>';
		
		return '<span class="compareListing jrButton">' . $checkbox . $listing_type . $url . '</span>';	
	}
    
    /**
    * Edit, delete buttons for review discussions
    * 
    */
    function discussionManager($post)
    {
        extract($post['Discussion']);
        $canEdit = $this->Access->canEditPost($user_id);
        $canDelete = $this->Access->canDeletePost($user_id); 
        
        if($canEdit || $canDelete):?>

            <span class="jrManagement jrButton"><?php __t("Manage");?><span class="jrArrowBottom"></span>
                
                <div class="jrManager">
                
                    <ul class="jrManagementLinks">
                
                        <?php if($canEdit):?>
                        <li>
                           <span class="jrIcon jrIconEdit"></span>
                            <a class="jr_edit" href="#edit-comment" onclick="jreviews.discussion.edit(this,{title:'<?php __t("Edit");?>',discussion_id:<?php echo $discussion_id;?>});return false;"><?php __t("Edit");?></a>
                        </li>
                        <?php endif;?>
                                            
                        <?php if($canDelete):?>
                        <li>
                            <span class="jrIcon jrIconDelete"></span>
                            <a class="jr_delete" href="#delete-comment" onclick="jreviews.discussion.remove(this,{'token':'<?php echo cmsFramework::getCustomToken($discussion_id);?>','discussion_id':<?php echo $discussion_id;?>,'title':'<?php __t("Delete");?>','text':'<?php __t("Are you sure you want to delete this comment?",false,true);?>'});return false;"><?php __t("Delete");?></a>
                        </li>
                        <?php endif;?> 
                        
                    </ul>

                </div>
                
            </span>
                    
        <?php endif;                
    }   
    
    function listingManager($listing)
    {
        $canEdit = $this->Access->canEditListing($listing['Listing']['user_id']);
        $canPublish = $this->Access->canPublishListing($listing['Listing']['user_id']);
        $canDelete = $this->Access->canDeleteListing($listing['Listing']['user_id']);
        $isManager = $this->Access->isManager();
        $listing_id = $listing['Listing']['listing_id'];
        $formToken = cmsFramework::getCustomToken($listing_id);
        $canOrder = false;
        if($this->Paid && $this->Paid->canOrder($listing)) {
            $canOrder = $this->PaidRoutes->getPaymentLink($listing,array('lazy_load'=>true));
        }
        if($canEdit || $canPublish || $canDelete || $isManager || $canOrder)
        {
        ?>
        <span class="jrManagement jrButton"><?php __t("Manage");?><span class="jrArrowBottom"></span>
            
            <?php if($canOrder): // Load assets for paid listings onclick?>
            <script type="text/javascript">
            /* <![CDATA[ */
            function jr_paidLoadScript(afterLoad)
            {                        
                if(jQuery('body').data('jrOrderAssets') == true)
                {
                    if(undefined!=afterLoad) afterLoad();
                } else {
                    jQuery.getScript('<?php echo $this->locateScript('paidlistings');?>',function(){
                        jQuery.getCSS("<?php echo pathToUrl($this->locateThemeFile('theme_css','paidlistings','.css'));?>",function()
                        {
                            jQuery('body').data('jrOrderAssets',true);
                            if(afterLoad!=undefined) afterLoad();
                        });
                    });        
                }
            };
            /* ]]> */
            </script>
            <?php endif;?>
            
            <div id="jr_listing_manager<?php echo $listing_id;?>" class="jrManager">
            
                <ul class="jrManagementLinks">
            
                    <?php if($canOrder):?>
                    <li>
                        <?php echo $canOrder;?>
                    </li>
                    <?php endif;?>
                                        
                    <?php if($canEdit):?>
                    <li>
                        <span class="jrIcon jrIconEdit"></span>
                        <?php echo $this->Routes->listingEdit(__t("Edit",true),$listing,array('class'=>'jr_edit'));?>
                    </li>
                    <?php endif;?>    

                    <?php if($canPublish):?>
                    <li>
                        <span class="jrIcon <?php echo $listing['Listing']['state'] ? 'jrIconYes' : 'jrIconDisabled';?>"></span>
                        <a href="#publish" id="jr_publishLink<?php echo $listing_id;?>" class="<?php echo $listing['Listing']['state'] ? 'jr_published' : 'jr_unpublished';?>" onclick="jreviews.listing.publish(this,{'token':'<?php echo $formToken;?>','listing_id':<?php echo $listing_id;?>,'unpublished':'<?php __t("Unpublished",false,true);?>','published':'<?php __t("Published",false,true);?>'});return false;"><?php echo ($listing['Listing']['state'] ? __t("Published",true): __t("Unpublished",true));?></a>
                    </li>
                    <?php endif;?>
                     
                    <?php if($isManager):?>
                    <li>
                        <span class="jrIcon <?php echo $listing['Listing']['featured'] ? 'jrIconYes' : 'jrIconDisabled';?>"></span>
                        <a href="#feature" id="jr_featuredLink<?php echo $listing_id;?>" class="<?php echo $listing['Listing']['featured'] ? 'jr_published' : 'jr_unpublished';?>" onclick="jreviews.listing.feature(this,{'token':'<?php echo $formToken;?>','listing_id':<?php echo $listing_id;?>,'state':<?php echo (int)$listing['Listing']['featured'];?>,'unpublished':'<?php __t("Not featured",false,true);?>','published':'<?php __t("Featured",false,true);?>'});return false;"><?php echo ($listing['Listing']['featured'] == 1 ? __t("Featured",true): __t("Not featured",true));?></a>
                    </li>
                    
                    <li>
                        <span class="jrIcon <?php echo $listing['Listing']['frontpage'] ? 'jrIconYes' : 'jrIconDisabled';?>"></span>
                        <a href="#frontpage" id="jr_frontpageLink<?php echo $listing_id;?>" class="<?php echo $listing['Listing']['frontpage'] ? 'jr_published' : 'jr_unpublished';?>" onclick="jreviews.listing.frontpage(this,{'token':'<?php echo $formToken;?>','listing_id':<?php echo $listing_id;?>,'unpublished':'<?php __t("Not frontpaged",false,true);?>','published':'<?php __t("Frontpaged",false,true);?>'});return false;"><?php echo ($listing['Listing']['frontpage'] > 0 ? __t("Frontpaged",true): __t("Not frontpaged",true));?></a>
                    </li>    
                    <?php endif;?>
                            
                    <?php if($canDelete):?>    
                    <li>
                        <a href="#delete" id="jr_deleteLink<?php echo $listing_id;?>" class="jr_delete" onclick="jreviews.listing.remove(this,{'token':'<?php echo $formToken;?>','title':'<?php __t("Delete",false,true);?>','listing_id':<?php echo $listing['Listing']['listing_id'];?>,'text':'<?php __t("Are you sure you want to delete this listing?",false,true);?>'});return false;">
                        <span class="jrIcon jrIconDelete"></span> <?php __t("Delete");?></a>
                    </li>
                    <?php endif;?>
                    
                </ul>

            </div>
            
        </span>
        <?php }        
    }
	
	function userRank($rank) {
		
		switch ($rank) {
			 case ($rank==1): $toprank = __t("#1 Reviewer",true); break;
			 case ($rank<=10 && $rank>0): $toprank = __t("Top 10 Reviewer",true); break;
			 case ($rank<=50 && $rank>10): $toprank = __t("Top 50 Reviewer",true); break;
			 case ($rank<=100 && $rank>50): $toprank = __t("Top 100 Reviewer",true); break;
			 case ($rank<=500 && $rank>100): $toprank = __t("Top 500 Reviewer",true); break;
			 case ($rank<=1000 && $rank>500): $toprank = __t("Top 1000 Reviewer",true); break;
			 default: $toprank = '';
		}
		
		return $toprank;
		
	}
	
}
