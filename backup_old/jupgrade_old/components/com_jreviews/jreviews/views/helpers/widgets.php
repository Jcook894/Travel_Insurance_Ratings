<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class WidgetsHelper extends MyHelper
{

    function claim($listing)
    {
        $User = cmsFramework::getUser();
        $listing_id = $listing['Listing']['listing_id'];
        $approved = isset($listing['Claim']) && Sanitize::getBool($listing['Claim'],'approved');
		$claimable = $this->Config->claims_enable_userids == '' || ($this->Config->claims_enable_userids != '' && in_array($listing['Listing']['user_id'],explode(',',$this->Config->claims_enable_userids)));		
		
        if($this->Access->canClaimListing($listing) && !$approved) 
        {
            return '<span id="jr_claimImg'.$listing_id.'" class="jrButton jr_claimListing" title="'.__t("Claim This Business", true).'" onclick="jreviews.listing.claim(this,{title:\''. __t("Claim This Business", true).'\',listing_id:'.$listing_id.'}); return false;">'. __t("Claim This Business", true).'</span>';        
        }                                                     
        elseif($claimable && $User->id == 0 && !$approved) 
        {
            return '<span id="jr_claimImg'.$listing_id.'" class="jrButton jr_claimListing" title="'.__t("Claim This Business", true).'" onclick="s2Alert(\''.__t("Please register to claim this business",true,true).'\');">'. __t("Claim This Business", true).'</span>';        
        }
    }
    
    function inquiry($listing)
    {
        $enabled = Sanitize::getBool($this->Config,'inquiry_enable',false);
        $recipient = Sanitize::getString($this->Config,'inquiry_recipient');
        if($enabled) {
            switch($recipient) {
                case 'owner':
                    return $listing['User']['user_id'] > 0;
                break;
                case 'field':
                    $field = Sanitize::getString($this->Config,'inquiry_field');
                     return $field != '' && isset($listing['Field']['pairs'][$field]) && Sanitize::getString($listing['Field']['pairs'][$field]['text'],'0');
                break;
                case 'admin':
                    return true;
                break;
            }
        }
        
        return false;
    }
    
    function favorite($listing)
    {
        $output = '';
        $listing_id = $listing['Listing']['listing_id'];
        $User = cmsFramework::getUser();
        
        $output .= '<span class="jrFavoriteWidget" title="' . __t("Favorites", true) . '">';
            $output .= '<span class="jrIcon jrIconFavorites"></span>';
            $output .= '<span id="jr_favoriteCount' . $listing_id . '">' . $listing['Favorite']['favored'] . '</span>';
        $output .= '</span>';
        
        if($listing['Favorite']['my_favorite']) { // Already in user's favorites
            $output .= '<span id="jr_favoriteImg' . $listing_id . '" class="jrFavoriteButton jrButton" title="' . __t("Remove from favorites", true) . '" onclick="jreviews.favorite.remove(this,{listing_id:' . $listing_id . '})">' . __t("Remove", true) . '</span>';
        }
        elseif($User->id) { // Not in user's favorites
            $output .= '<span id="jr_favoriteImg' . $listing_id . '" class="jrFavoriteButton jrButton" title="' . __t("Add to favorites", true) . '" onclick="jreviews.favorite.add(this,{listing_id:' . $listing_id . '})">' . __t("Add", true) . '</span>';
        }
        else { // This is a guest user, needs to register to use the favorites widget
            $output .= '<span id="jr_favoriteImg' . $listing_id . '" class="jrFavoriteButton jrButton" title="' . __t("Add to favorites", true) . '" onclick="s2Alert(\''.__t("Register to add this entry to your favorites",true).'\');">' . __t("Add", true) . '</span>';    
        }
        
        return $output;
    }
    
    function reviewVoting($review) 
    {
        $review_id = $review['Review']['review_id'];
        $User = cmsFramework::getUser();

        $output = '<div class="reviewHelpful">'; 
            
            $output .= '<div class="jrHelpfulTitle">' . __t("Was this review helpful to you?", true) . '&nbsp;</div>';

            $output .= '<div id="jr_reviewVote' . $review_id . '" style="float:left;">';
        
                if ($this->Access->canVoteHelpful($review['User']['user_id'])) {
                    $output .= '<span class="jrVote jrButton" onclick="jreviews.review.voteYes(this,{review_id:' . $review_id . '})">';                   
                } elseif ($User->id > 0) {
                    $output .= '<span class="jrVote jrButton" onclick="s2Alert(\''.__t("You are not allowed to vote",true,true).'\');">';
                } else {
                    $output .= '<span class="jrVote jrButton" onclick="s2Alert(\''.__t("Login or register to vote",true,true).'\');">';
                }
                    $output .= '<span class="jrButtonText" style="color: green;">' . $review['Vote']['yes'] . '</span><span class="jrIcon jrIconThumbUp"></span>';
                $output .= '</span>';

                if ($this->Access->canVoteHelpful($review['User']['user_id'])) {
                    $output .= '<span class="jrVote jrButton" onclick="jreviews.review.voteNo(this,{review_id:' . $review_id . '})">';
                } elseif ($User->id > 0) {
                    $output .= '<span class="jrVote jrButton" onclick="s2Alert(\''.__t("You are not allowed to vote",true,true).'\');">';
                } else {
                    $output .= '<span class="jrVote jrButton" onclick="s2Alert(\''.__t("Login or register to vote",true,true).'\');">';
                }
                    $output .= '<span class="jrButtonText" style="color: red;">' . $review['Vote']['no'] . '</span><span class="jrIcon jrIconThumbDown"></span>';
                $output .= '</span>';

                $output .= '<span class="jr_loadingSmall jr_hidden"></span>';
        
            $output .= '</div>';

        $output .= '</div>';
        
        return $output;        
    }
    
    function relatedListingsJS($listing) 
    {
        # Detail page widgets
        $key = 0;
        $listingtype = Sanitize::getInt($listing['Criteria'],'criteria_id');
        $listing_id = Sanitize::getInt($listing['Listing'],'listing_id');
        $listing_title = Sanitize::getString($listing['Listing'],'title');
        $ajax_init = true;
        $target_id = $target_class = '';
        // Process related listings
        $related_listings = Sanitize::getVar($listing['ListingType']['config'],'relatedlistings',array());
        $related_listings = array_filter($related_listings);
        $created_by = Sanitize::getVar($listing['User'],'user_id');
        $field_pairs = $listing['Field']['pairs'];
        $type = 'relatedlistings';

        // Created an array of tab ids => tab indices
        ?>
        <script type="text/javascript">    
        /* <![CDATA[ */
        var jrTabArray = {};
        jQuery(document).ready(function() 
        {         
            jQuery('.jr_tabs').find('li>a').each(function(i,t) {
                var tabId = jQuery(t).attr('href');
                jrTabArray[tabId] = jQuery(t).parent('li');
            });
        });
        /* ]]> */
        </script>
        <?php
        foreach($related_listings AS $key=>$related_listing):

            if(!Sanitize::getInt($related_listing,'enable',0)) continue; 

            $module_id = 10000 + $listing_id + $key;
            $target_id = Sanitize::getString($related_listing,'target_id','jrRelatedListings');
            $target_class = Sanitize::getString($related_listing,'target_class');
            $moduleParams = compact('module_id','ajax_init','listing_id','type','key');
            extract($related_listing);
            $title = str_ireplace('{title}',$listing_title,__t(Sanitize::getString($related_listing,'title'),true,true));
            $title = htmlspecialchars($title,ENT_QUOTES,'utf-8');
            $targetElement = $target_class ? $target_class : $target_id;
            ?>
            <script type="text/javascript">    
            /* <![CDATA[ */
            jQuery(document).ready(function() 
            {                    
                jreviews.dispatch({'controller':'module_listings','action':'index',
                    'type':'json',
                    'data':<?php echo json_encode($moduleParams);?>,
                    'onComplete':function(res){     
                        var $<?php echo $targetElement;?> = <?php if($target_class):?>jQuery('.<?php echo $target_class;?>');<?php else:?>jQuery('#<?php echo $target_id;?>');<?php endif;?>
                        if(res.response != '') {  
                            var $widget = jQuery('<div id="<?php echo $targetElement;?>Widget<?php echo $key;?>"></div>').addClass('jrWidget')
                                    <?php if($title!=''):?>.append('<h4><?php echo $title;?></h4>')<?php endif;?>
                                    .append(res.response);
                            $<?php echo $targetElement;?>.append($widget);

                            var array = [0,1,2,3,4];
                            for(var i=0; i < array.length; i++) { array[i] = jQuery('#<?php echo $targetElement;?>Widget'+ array[i]); }    
                            $<?php echo $targetElement;?>.html();  
                            for(var i=0; i < array.length; i++) { $<?php echo $targetElement;?>.append(array[i]); }                                 

                            if(jrTabArray['#<?php echo $targetElement;?>'] != undefined && $<?php echo $targetElement;?>.html() != '') {   
                                jrTabArray['#<?php echo $targetElement;?>'].show();
                            }
                        }
                        else {
                            if(jrTabArray['#<?php echo $targetElement;?>'] != undefined && $<?php echo $targetElement;?>.html() == '') {   
                                jrTabArray['#<?php echo $targetElement;?>'].hide();
                            }
                        }
                        jreviews.module.pageNavInit(<?php echo json_encode(compact('module_id','columns','orientation','slideshow','slideshow_interval','nav_position'));?>);
                    }
                });
            });
            /* ]]> */
            </script>  
        <?php endforeach;    
        
        // Process favorite users                            
        $key++;
        $module_id = 11000 + $listing_id;
        $userfavorites = Sanitize::getVar($listing['ListingType']['config'],'userfavorites',array());
        if(Sanitize::getBool($userfavorites,'enable')) 
        {
            $target_id = Sanitize::getString($userfavorites,'target_id','jrRelatedListings');
            $target_class = Sanitize::getString($userfavorites,'target_class');        
            $id = $listing_id;
            $moduleParams = compact('module_id','listingtype','ajax_init','id');
            extract($userfavorites);
            $title = str_ireplace('{title}',$listing_title,__t(Sanitize::getString($userfavorites,'title'),true,true));
            $title = htmlspecialchars($title,ENT_QUOTES,'utf-8');
            $targetElement = $target_class ? $target_class : $target_id;
            ?>
            <script type="text/javascript">    
            /* <![CDATA[ */
            jQuery(document).ready(function() 
            {           
                jreviews.dispatch({'controller':'module_favorite_users','action':'index',
                    'type':'json',
                    'data':<?php echo json_encode($moduleParams);?>,
                    'onComplete':function(res){
                        var $<?php echo $targetElement;?> = <?php if($target_class):?>jQuery('.<?php echo $target_class;?>');<?php else:?>jQuery('#<?php echo $target_id;?>');<?php endif;?>
                        if(res.response != '') {
                            var $widget = jQuery('<div id="<?php echo $targetElement;?>Widget<?php echo $key;?>"></div>').addClass('jrWidget')
                                    <?php if($title!=''):?>.append('<h4><?php echo $title;?></h4>')<?php endif;?>
                                    .append(res.response);
                                    
                            $<?php echo $targetElement;?>.append($widget);
                            
                            var array = [0,1,2,3,4];
                            for(var i=0; i < array.length; i++) { array[i] = jQuery('#<?php echo $targetElement;?>Widget'+ array[i]); }    
                            $<?php echo $targetElement;?>.html();  
                            for(var i=0; i < array.length; i++) { $<?php echo $targetElement;?>.append(array[i]); }                                 
                            
                            if(jrTabArray['#<?php echo $targetElement;?>'] != undefined && $<?php echo $targetElement;?>.html() != '') {   
                                jrTabArray['#<?php echo $targetElement;?>'].show();
                            }                        
                        }
                        else {
                            if(jrTabArray['#<?php echo $targetElement;?>'] != undefined && $<?php echo $targetElement;?>.html() == '') {   
                                jrTabArray['#<?php echo $targetElement;?>'].hide();
                            }                            
                        }
                        jreviews.module.pageNavInit(<?php echo json_encode(compact('module_id','columns','orientation','slideshow','slideshow_interval','nav_position'));?>);
                    }
                });
            });
            /* ]]> */
            </script> 
            <?php        
        } 
    }        
}
