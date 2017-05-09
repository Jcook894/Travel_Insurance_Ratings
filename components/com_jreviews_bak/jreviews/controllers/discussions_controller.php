<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class DiscussionsController extends MyController {
    
    var $uses = array('menu','captcha','user','criteria','review','field','discussion');
    
    var $helpers = array('cache','routes','libraries','html','assets','form','time','thumbnail','jreviews','custom_fields','rating','paginator','community','widgets');
    
    var $components = array('config','access','everywhere','notifications','activities');

    var $autoRender = true;

    var $autoLayout = true;

    var $formTokenKeys = array('discussion_id','type','review_id');
        
    function beforeFilter() 
    {        
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }
       
    // Need to return object by reference for PHP4
    function &getPluginModel() {
        return $this->Discussion;
    }
    
    // Need to return object by reference for PHP4
    function &getNotifyModel() {
        return $this->Discussion;
    }
    
    // Need to return object by reference for PHP4
    function &getEverywhereModel() {
        return $this->Review;
    } 
    
    // Need to return object by reference for PHP4
    function &getActivityModel() {
        return $this->Discussion;
    }      
       
    function _delete() 
    {
        $this->Discussion->data = & $this->params;
                
        if($post_id = Sanitize::getInt($this->params,'post_id'))
        { 
            $owner_id = $this->Discussion->getPostOwner($post_id);
            $token = Sanitize::getString($this->params,'token');
            if(!$this->Access->canDeletePost($owner_id) || 0 != strcmp($token,cmsFramework::getCustomToken($post_id)))
            {
                return $this->ajaxError(s2Messages::accessDenied());
            }
            
            if($this->Discussion->delete('discussion_id',$post_id))
            {
                return $this->ajaxUpdatePage("jr_post{$post_id}",__t("The comment has been removed.",true));
            }  
        } 
        
        return $this->ajaxError(__t("There was a problem removing the comment.",true,true));      
    }
    
    function _edit()
    { 
        $this->autoRender = false;
        $this->autoLayout = false;
        $post_id = Sanitize::getInt($this->params,'post_id');
        
        if($post_id)
        {
            if($post = $this->Discussion->findRow(array('conditions'=>array('Discussion.discussion_id = ' . $post_id))))              
            {
                if(!$this->Access->canEditPost($post['Discussion']['user_id']))
                {
                    return $this->ajaxError(s2Messages::accessDenied());            
                }                
                
                $this->set(array(
                    'post'=>$post,
                    'formTokenKeys'=>$this->formTokenKeys
                ));
                
                return $this->ajaxResponse($this->render('discussions','edit'),false);
            }            
        }    
        
        return $this->ajaxError(__t("The comment was not found",true,true));            
    }
    
    function _saveEdit() 
    {
        $this->autoRender = false;
        $this->autoLayout = false;  
        $this->Discussion->isNew = false;                             
        $response = array();
        
        # Load the notifications observer model component and initialize it. 
        # Done here so it only loads on save and not for all controlller actions.        
        $this->components = array('security');
        $this->__initComponents();
        
        # Validate form token            
        if($this->invalidToken) 
        {
            return $this->ajaxError(s2Messages::invalidToken());
        }        
        
        $post_id = Sanitize::getInt($this->data['Discussion'],'discussion_id');
        $isNew = (bool) !$post_id;
        if($isNew && !$this->Access->canAddPost())
        {
            return $this->ajaxError(s2Messages::accessDenied());
        }
        elseif(!$isNew)
        {
            # Stop form data tampering 
            $owner_id = $this->Discussion->getPostOwner($post_id);
            $formToken = cmsFramework::formIntegrityToken($this->data['Discussion'],$this->formTokenKeys,false);
            if (!$this->Access->canEditPost($owner_id) || !$this->__validateToken($formToken)) {
                return $this->ajaxError(s2Messages::accessDenied());
            }              
        } 
                    
        $text = str_replace("\n","<br />",htmlspecialchars($this->data['Discussion']['text'],ENT_QUOTES));
        $this->data['Discussion']['modified'] = date('Y-m-d H:i:s');
        $this->data['Discussion']['approved'] = 1;        
  
        if($this->Discussion->store($this->data))
        {   
            $update_text = __t("Your comment has been updated.",true); 
            $response[] = "jQuery('#jr_post{$post_id}').hide('fast');";            
            $response[] = "jQuery('#jr_post{$post_id} .jr_comments').html('$text');";            
            $response[] = "jQuery('#jr_post{$post_id}').slideDown(1000);";
            return $this->ajaxUpdatePage('jr_post'.$post_id,$update_text,'',compact('response')); 
        }   
             
        return $this->ajaxError(__t("There was an error saving the comment.",true,true));        
    }
    
    function _save()
    {
        $this->autoRender = false;
        $this->autoLayout = false;
        $this->Discussion->isNew = true;                    
        $response = array();
        $parent_id = Sanitize::getInt($this->data['Discussion'],'parent_post_id');
        $isNew = Sanitize::getBool($this->data['Discussion'],'discussion_id');

        # Load the notifications observer model component and initialize it. 
        # Done here so it only loads on save and not for all controlller actions.        
        $this->components = array('security');
        $this->__initComponents();
        
        # Validate form token            
        if($this->invalidToken) {
            return $this->ajaxError(s2Messages::invalidToken());
        }
                
        if(!$this->Config->review_discussions || !$this->Access->canAddPost()){ // Server side validation
            return $this->ajaxError(__t("You are not allowed to submit comments.",true,true));
        }        

        # Validate input fields         
        $this->Discussion->validateInput(Sanitize::getString($this->data['Discussion'],'name'), "name", "text", __t("You must fill in your name.",true), !$this->_user->id && ($this->Config->discussform_name == 'required' ? true : false));

        $this->Discussion->validateInput(Sanitize::getString($this->data['Discussion'],'email'), "email", "email", __t("You must fill in a valid email address.",true), ($this->Config->discussform_email == 'required' ? true : false) && !$this->_user->id && $isNew);        

        $this->Discussion->validateInput($this->data['Discussion']['text'], "text", "text", __t("You must fill in your comment.",true), true);
        
        # Validate security code
        if ($this->Access->showCaptcha())
        {
            if(!isset($this->data['Captcha']['code'])) 
            {                
                $this->Discussion->validateSetError("code", __t("The security code you entered was invalid.",true));
                    
            } elseif ($this->data['Captcha']['code'] == '') 
            {    
                $this->Discussion->validateInput($this->data['Captcha']['code'], "code", "text", __t("You must fill in the security code.",true),  1);
            } 
            else 
            {
                if (!$this->Captcha->checkCode($this->data['Captcha']['code'],$this->ipaddress)) 
                {                    
                    $this->Discussion->validateSetError("code", __t("The security code you entered was invalid.",true));                
                }    
            }
         }        
        
        $validation_text = implode('<br />',$this->Discussion->validateGetErrorArray());

        if($validation_text!='')
        {
            $response[] = "jQuery('#jr_postCommentSubmit{$parent_id}').removeAttr('disabled');";
            $response[] = "jQuery('#jr_postCommentCancel{$parent_id}').removeAttr('disabled');";
            // Replace captcha with new instance
            $captcha = $this->Captcha->displayCode();
            $response[] = "jQuery('.jr_captcha').find('img').attr('src','{$captcha['src']}');";                
            $response[] = "jQuery('.jr_captcha_code').val('');"; 
            return $this->ajaxValidation($validation_text,$response);
        }

        $this->data['Discussion']['user_id'] = $this->_user->id;
        $this->data['Discussion']['ipaddress'] = $this->ipaddress;
        
        if($this->_user->id)
        {
            $this->data['Discussion']['name'] = $this->_user->name;
            $this->data['Discussion']['username'] = $this->_user->username;
            $this->data['Discussion']['email'] = $this->_user->email;            
        } else {
            $this->data['Discussion']['username'] = $this->data['Discussion']['name'];
        }

        $this->data['Discussion']['created'] = date('Y-m-d H:i:s');
        $this->data['Discussion']['approved'] = (int)!$this->Access->moderatePost();

        if($this->Discussion->store($this->data))
        {    
            if(!$this->data['Discussion']['approved'])
            {
                $submit_text = __t("Thank you for your submission. It will be published once it is verified.",true,true);
                return $this->ajaxUpdatePage('jr_postCommentForm'.$parent_id,$submit_text);
            } 
            
            // Query post to get full info for instant refresh
            $discussion = $this->Discussion->findRow(array(
                'conditions'=>array(
                    'Discussion.type = "review"',
                    'Discussion.discussion_id = ' . $this->data['Discussion']['discussion_id']
                ))
            );

            $this->set(array(
                'Access'=>$this->Access,
                'User'=>$this->_user,
                'post'=>$discussion
            ));
            
            $update_text = __t("Thank you for your submission.",true,true);
            $update_html = $this->render('discussions','post');
            $target_id_after = 'jr_post'.$parent_id;
            $response[] = 'jreviews.discussion.parentCommentPopOver();';
            return $this->ajaxUpdatePage('jr_postCommentFormOuter'.$parent_id,$update_text,$update_html,compact('target_id_after','response'));                        
          }
    }          
    
    function getPost() 
    {
        $this->autoRender = false;
        $this->autoLayout = false;        
        
        $post_id = (int)$this->params['post_id'];
        
        $post = $this->Discussion->findRow(array(
            'conditions'=>array(
                'Discussion.discussion_id = ' . $post_id,
                'Discussion.approved = 1'
        )));   

        $this->set('post',$post);            

        return $this->render('discussions','parent_popover');
    }   
     
    function latest()
    {
		$this->params['default_order'] = 'rdate';

		$this->passedArgs['order'] = Sanitize::getString($this->params,'order',$this->params['default_order']);

		$menu_id = Sanitize::getInt($this->params,'Itemid');

		$conditions = array(
				'Discussion.type = "review"',
				'Discussion.approved = 1',
				'Discussion.review_id > 0'
				);

		$joins[] = 'INNER JOIN #__jreviews_comments AS Review ON Review.id = Discussion.review_id AND Review.published = 1';

		$posts = $this->Discussion->findAll(array(
			'conditions'=>$conditions,
			'joins'=>$joins,
			'offset'=>$this->offset,
			'limit'=>$this->limit,                        
			'order'=>array(
				$this->Discussion->processSorting($this->passedArgs['order'])
				)
		));                
		$count = $this->Discussion->findCount(array(
			'conditions'=>$conditions,
			'joins'=>$joins
		));

		// Set page title
		$title =  __t("Latest comments",true);         
		$menuParams = $this->Menu->getMenuParams($menu_id);
		$page['show_title'] = Sanitize::getInt($menuParams,'dirtitle');
		$page['title'] = $page['title_seo'] = trim(Sanitize::getString($menuParams,'title')) != '' ? Sanitize::getString($menuParams,'title') : $this->Menu->getMenuName($menu_id);

		if (!$page['show_title']) {
			$page['title'] = $title;
		}             

		$this->set(array(
			'Access'=>$this->Access,
			'User'=>$this->_user,
			'posts'=>$posts,
			'pagination'=>array(
				'total'=>$count,
				'offset'=>($this->page-1)*$this->limit
			),
			'page'=>$page                
		));        
    } 
    
    // Review discussions
    function review()
    {
        $this->limit = 10;
        
        $posts = array();
        $count = 0;
        $listing = array();
        $review = array();
        $review_id = Sanitize::getInt($this->params,'id');            
         
        if($review_id)
        {
            $this->Review->runProcessRatings = false;        
            $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model
            
            $listing = $review = $this->Review->findRow(
                array(
                    'conditions'=>array('Review.id = ' . $review_id,'Review.published = 1')
                )
            );

            if($listing)
            {      
                # Set the theme suffix
                if($listing['Listing']['extension'] == 'com_content')
                {
                    $this->Theming->setSuffix(array('cat_id'=>$listing['Listing']['cat_id']));
                }

                $listing['User'] = isset($listing['ListingUser']) ? $listing['ListingUser'] : array();
                $listing['Community'] = isset($listing['ListingCommunity']) ? $listing['ListingCommunity'] : array();            
                unset($listing['Field'],$listing['Vote'],$listing['ListingUser'],$listing['ListingCommunity']);
                unset($review['Listing'],$review['Section'],$review['Category'],$review['Directory']);

                if($this->Config->review_discussions)
                {
                    $posts = $this->Discussion->findAll(array(
                        'conditions'=>array(
                            'Discussion.type = "review"',
                            'Discussion.review_id = ' . $review_id,
                            'Discussion.approved = 1'
                            ),
                        'offset'=>$this->offset,
                        'limit'=>$this->limit,                        
                        'order'=>array(
                            $this->Discussion->processSorting(Sanitize::getString($this->params,'order'))
                            )
                    ));                

                    $count = $this->Discussion->findCount(array(
                        'conditions'=>array(
                            'Discussion.type = "review"',
                            'Discussion.review_id = ' . $review_id,
                            'Discussion.approved = 1')
                    ));
                
                }                            
            }
            
            $this->set(array(
                'Access'=>$this->Access,
                'User'=>$this->_user,
                'listing'=>$listing,
                'review'=>$review,
                'posts'=>$posts,
                'extension'=>$review['Review']['extension'],
                'pagination'=>array(
                    'total'=>$count,
                    'offset'=>($this->page-1)*$this->limit
                )                
            ));
        }
    }  
}
