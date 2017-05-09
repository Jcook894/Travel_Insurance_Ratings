<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FacebookController extends MyController {
        
    var $uses = array('menu','criteria','review','vote');
    
    var $helpers = array();
    
    var $components = array('access','config','everywhere');
    
    var $autoRender = false;
    
    var $autoLayout = false;
    
/**
* FB configuration
* You can customize the strings below for the FB messages
*/
    var $activities = array();    
         
    function beforeFilter(){
        # Call beforeFilter of MyController parent class
        
        $this->activities = array(
              'listing_new'=>__t("submitted a new listing titled %s",true), 
              'review_new'=>__t("wrote a review for %s",true), 
              'comment_new'=>__t("posted a new comment",true), 
              'vote helpful'=>__t("liked this review for %s",true)
         );    
             
        parent::beforeFilter();
    }

    function getEverywhereModel()
    {
        switch($this->action)
        {
            case '_postListing':
                return false;
            break;
            case '_postReview':
                return $this->Review;
            break;
            case '_postVote':
                return $this->Vote;
            break;
        }
    }
    
    function makeUrl($url)
    {                
        return cmsFramework::makeAbsUrl($url,array('sef'=>true,'ampreplace'=>true));                     
    }
    
    function _postListing()
    {    
        # Check if FB integration for reviews is enabled
        $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable') and Sanitize::getBool($this->Config,'facebook_listings');
        if(!$facebook_integration) return;
                            
        $listing_id = Sanitize::getInt($this->params,'id');
        # First check - listing id
        if(!$listing_id) return;

        # Stop form data tampering
        $formToken = cmsFramework::getCustomToken($listing_id); 
        if(!cmsFramework::isAdmin() && !$this->__validateToken($formToken)) {
            return s2Messages::accessDenied();    
        }
                
        $facebook = $this->_getFBClass();
		
		$uid = $facebook->getUser();

		if($uid) // There's a valid session for this user
		{
			try{
                //get user id
//                $user = $facebook->api('/me');

				$fql    =   "SELECT publish_stream FROM permissions WHERE uid = " . $uid;
                
				$param  =   array(
                    'method'    => 'fql.query',
                    'query'     => $fql,
                    'callback'  => ''
                );
				
                $fqlResult   =   $facebook->api($param);
                
                if(!$fqlResult[0]['publish_stream'])
                {
                    return false;    
                } 
                else
                {      
                    $this->Everywhere->loadListingModel($this,'com_content');
                    
                    $listing = $this->Listing->findRow(array(
                        'conditions'=>array('Listing.id = ' . $listing_id)
                    ),array('afterFind'));

                    $listing_url = $this->makeUrl($listing['Listing']['url']);
                    
                    $listing['Listing']['summary'] = strip_tags($listing['Listing']['summary']);
                    if($this->Config->facebook_posts_trim >= 0 && $listing['Listing']['summary'] != '') {
                        App::import('Helper','text','jreviews');
                        $Text = ClassRegistry::getClass('TextHelper');
						$message = $this->Config->facebook_posts_trim == '' ? $listing['Listing']['summary'] : $Text->truncateWords($listing['Listing']['summary'],$this->Config->facebook_posts_trim);						
						$listing['Listing']['summary'] = $message;
                    }
                    
                    # Publish stream permission granted so we can post on the user's wall!
                    # Begin building the stream $fbArray 
                    $fbArray = array();
                    $fbArray['method'] = 'stream.publish';
                    $fbArray['message'] = sprintf($this->activities['listing_new'],$listing['Listing']['title']);
                    $fbArray['attachment'] = array(
                        'name'=>$listing['Listing']['title'],
                        'href'=>$listing_url,
                        'description' => $listing['Listing']['summary'],
        //              'caption' => '{*actor*} rated the listing %s stars'
                    );            
                    $fbArray['attachment']['properties'][__t("Website",true)] = array('text'=>cmsFramework::getConfig('sitename'), 'href'=>WWW_ROOT);
                    
                    isset($listing['Listing']['images'][0]) and $fbArray['attachment']['media'] = array(
                        array(
                            'type'=>'image', 
                            'src'=>WWW_ROOT . _JR_WWW_IMAGES . $listing['Listing']['images'][0]['path'], 
                            'href'=>$listing_url            
                        )
                    );

                    $fbArray['attachment'] = json_encode($fbArray['attachment']);
                    
                    $fbArray['action_links'] = json_encode(array(
                        array(
                            'text' => __t("Read more",true),
                            'href' => $listing_url
                            )
                        )
                    );
                        
                    $fbArray['comments_xid']  = $listing['Listing']['listing_id'];
                    
                    $fb_update = $facebook->api($fbArray);
                    return true;
                }               
            }
            catch(Exception $o){
                // Error reading permissions
                return false;
            }
        }
        
        return false;                        
    } 
    
    function _postReview()
    {
        # Check if FB integration for reviews is enabled
        $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable') and Sanitize::getBool($this->Config,'facebook_reviews');
        if(!$facebook_integration) return;
                            
        $review_id = Sanitize::getInt($this->params,'id');

        # First check - review id
        if(!$review_id) return '';
        
        # Stop form data tampering
        $formToken = cmsFramework::getCustomToken($review_id); 
        if(!cmsFramework::isAdmin() && !$this->__validateToken($formToken)) {
            return s2Messages::accessDenied();    
        }
        
        $facebook = $this->_getFBClass();

		$uid = $facebook->getUser();

		if($uid) // There's a valid session for this user
        {

			try{
                //get user id
//                $user = $facebook->api('/me');

                $fql    =   "SELECT publish_stream FROM permissions WHERE uid = " . $uid;
                
				$param  =   array(
                    'method'    => 'fql.query',
                    'query'     => $fql,
                    'callback'  => ''
                );

				$fqlResult   =   $facebook->api($param);

                if(!$fqlResult[0]['publish_stream'])
                {
                    return false;    
                } 
                else
                {
                    $review = $this->Review->findRow(array(
                        'conditions'=>array('Review.id = ' . $review_id)
                    ),array());

                    $this->Everywhere->loadListingModel($this,$review['Review']['extension']);
                    
                    $listing = $this->Listing->findRow(array(
                        'conditions'=>array('Listing.'.$this->Listing->realKey.' = ' . $review['Review']['listing_id'])
                    ),array('afterFind'));

                    $listing_url = $this->makeUrl($listing['Listing']['url']);
                    
                    $review['Review']['comments'] = strip_tags($review['Review']['comments']);

					if($this->Config->facebook_posts_trim >= 0 && $review['Review']['comments'] != '') {
                        App::import('Helper','text','jreviews');
                        $Text = ClassRegistry::getClass('TextHelper');
						$message = $this->Config->facebook_posts_trim == '' ? $review['Review']['comments'] :$Text->truncateWords($review['Review']['comments'],$this->Config->facebook_posts_trim);
                        $review['Review']['comments'] = $message;
                    }
                    
                    # Publish stream permission granted so we can post on the user's wall!
                    # Begin building the stream $fbArray 
                    $fbArray = array();
                    $fbArray['method'] = 'stream.publish';
                    $fbArray['message'] = sprintf($this->activities['review_new'],$listing['Listing']['title']);
                    $fbArray['attachment'] = array(
                        'name'=>$listing['Listing']['title'],
                        'href'=>$listing_url,
                        'description' => $review['Review']['comments'],
        //              'caption' => '{*actor*} rated the listing %s stars'
                    );            
                    $fbArray['attachment']['properties'][__t("Website",true)] = array('text'=>cmsFramework::getConfig('sitename'), 'href'=>WWW_ROOT);
                    $review['Rating']['average_rating'] > 0 and $fbArray['attachment']['properties'][__t("Rating",true)] = sprintf(__t("%s stars",true),round($review['Rating']['average_rating'],1));
                    
                    isset($listing['Listing']['images'][0]) and $fbArray['attachment']['media'] = array(
                        array(
                            'type'=>'image', 
                            'src'=>WWW_ROOT . _JR_WWW_IMAGES . $listing['Listing']['images'][0]['path'], 
                            'href'=>$listing_url            
                        )
                    );

                    $fbArray['attachment'] = json_encode($fbArray['attachment']);
                    
                    $fbArray['action_links'] = json_encode(array(
                        array(
                            'text' => __t("Read review",true),
                            'href' => $listing_url
                            )
                        )
                    );
                        
                    $fbArray['comments_xid']  = $listing['Listing']['listing_id'];
                    
                    $fb_update = $facebook->api($fbArray);
                    return true;
                }               
            }
            catch(Exception $o){
                // Error reading permissions
                return false;
            }
        }
        
        return false;                
   }
   
   function _postVote()
   {         
        # Check if FB integration for reviews is enabled
        $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable') && Sanitize::getBool($this->Config,'facebook_reviews');
        if(!$facebook_integration) return;
                            
        $review_id = Sanitize::getInt($this->params,'id');
        # First check - review id
        if(!$review_id) return;
        
        # Stop form data tampering
        $formToken = cmsFramework::getCustomToken($review_id); 
        if(!cmsFramework::isAdmin() && !$this->__validateToken($formToken)) {
            return s2Messages::accessDenied();    
        }
                
        $facebook = $this->_getFBClass();

		$uid = $facebook->getUser();

		if($uid) // There's a valid session for this user
        {        
            try{
                //get user id
//                $user = $facebook->api('/me');

				$fql    =   "SELECT publish_stream FROM permissions WHERE uid = " . $uid;
                
				$param  =   array(
                    'method'    => 'fql.query',
                    'query'     => $fql,
                    'callback'  => ''
                );
                
				$fqlResult   =   $facebook->api($param);
                   
                if(!$fqlResult[0]['publish_stream'])
                {
                    return false;    
                } 
                else
                {
                    $review = $this->Review->findRow(array(
                        'conditions'=>array('Review.id = ' . $review_id)
                    ),array());
                    
                    $this->Everywhere->loadListingModel($this,$review['Review']['extension']);
                    
                    $listing = $this->Listing->findRow(array(
                        'conditions'=>array('Listing.'.$this->Listing->realKey.' = ' . $review['Review']['listing_id'])
                    ),array('afterFind'));

                    $listing_url = $this->makeUrl($listing['Listing']['url']);

                    $review['Review']['comments'] = strip_tags($review['Review']['comments']);

					if($this->Config->facebook_posts_trim >= 0 && $review['Review']['comments'] != '') {
                        App::import('Helper','text','jreviews');
                        $Text = ClassRegistry::getClass('TextHelper');
						$message = $this->Config->facebook_posts_trim == '' ? $review['Review']['comments'] : $Text->truncateWords($review['Review']['comments'],$this->Config->facebook_posts_trim);
                        $review['Review']['comments'] = $message;
                    }
                    
                    # Publish stream permission granted so we can post on the user's wall!
                    # Begin building the stream $fbArray 
                    $fbArray = array();
                    $fbArray['method'] = 'stream.publish';
                    
					$fbArray['message'] = sprintf($this->activities['vote helpful'],$listing['Listing']['title']);

					$fbArray['attachment'] = array(
                        'name'=>$listing['Listing']['title'],
                        'href'=>$listing_url,
                        'description' =>$review['Review']['comments'],
                    );            

					$fbArray['attachment']['properties'][__t("Website",true)] = array('text'=>cmsFramework::getConfig('sitename'), 'href'=>WWW_ROOT);
                    
					$review['Rating']['average_rating'] > 0 and $fbArray['attachment']['properties'][__t("Rating",true)] = sprintf(__t("%s stars",true),round($review['Rating']['average_rating'],1));
                    
                    isset($listing['Listing']['images'][0]) and $fbArray['attachment']['media'] = array(
                        array(
                            'type'=>'image', 
                            'src'=>WWW_ROOT . _JR_WWW_IMAGES . $listing['Listing']['images'][0]['path'], 
                            'href'=>$listing_url            
                        )
                    );

                    $fbArray['attachment'] = json_encode($fbArray['attachment']);
                    
                    $fbArray['action_links'] = json_encode(array(
                        array(
                            'text' => __t("Read review",true),
                            'href' => $listing_url
                            )
                        )
                    );
                        
                    $fbArray['comments_xid']  = $listing['Listing']['listing_id'];
                    
                    if($this->Config->facebook_optout) {
						$fbArray['display'] = Configure::read('System.isMobile') ? 'touch' : 'popup';
						return json_encode($fbArray);
					}
                                        
                    $fb_update = $facebook->api($fbArray);
                    
                    return true;
                }               
            }
            catch(Exception $o){
                // Error reading permissions
                return false;
            }
        } 
        return false;                
   }
   
   function _getFBClass()
   {
        !class_exists('Facebook') 
            and !class_exists('myapiFacebook') /* Avoid class conflict with myApi extension */ 
            and App::import('Vendor','facebook' . DS . 'facebook');

        class_exists('Facebook') and $facebook = new Facebook( array(
            'appId'   => trim(Sanitize::getString($this->Config,'facebook_appid')),
            'secret'  => trim(Sanitize::getString($this->Config,'facebook_secret')),
            'cookie'  => true
        ));

        /* Avoid class conflict with myApi extension */ 
        class_exists('myapiFacebook') and $facebook = new myapiFacebook( array(
            'appId'   => trim(Sanitize::getString($this->Config,'facebook_appid')),
            'secret'  => trim(Sanitize::getString($this->Config,'facebook_secret')),
            'cookie'  => true
        ));
        
        return $facebook;       
   }   
}
