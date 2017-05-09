<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact support@jreviews.com
**/

defined( '_JEXEC' ) or die;

ES::import('admin:/includes/apps/apps');

class SocialUserAppJreviews extends SocialAppItem
{
	var $contentlength = 200;

	var $activities;

    var $css_loaded = false;

    public function __construct($options = array())
    {
        ES::language()->loadApp('user' ,'jreviews');

        parent::__construct($options);
    }

	static protected function prx($var)
	{
	   echo '<pre>'.print_r($var,true).'</pre>';
	}

	protected function css()
	{
        if($this->css_loaded) return;

        $this->css_loaded = true;

		$template = JFactory::getApplication()->getTemplate();

		$doc = JFactory::getDocument();

		$path = JPATH_ROOT . '/templates/' . $template . '/html/com_easysocial/apps/user/jreviews/assets/styles';

		$uri = rtrim( JURI::root() , '/' ) . '/templates/' . $template . '/html/com_easysocial/apps/user/jreviews/assets/styles';

		jimport( 'joomla.filesystem.file' );

		// Test for overrides
		if( JFile::exists( $path . '/style.css' ) )
		{
			$doc->addStyleSheet( $uri . '/style.css' );
		}

		$path = dirname(__FILE__) . '/assets/styles';

		$uri = rtrim( JURI::root() , '/' ) . '/media/com_easysocial/apps/user/jreviews/assets/styles';

		// Test for core app stylesheet

		if( JFile::exists( $path . '/style.css' ) )
		{
			$doc->addStyleSheet( $uri . '/style.css' );
		}
	}

	public function onPrepareStream( SocialStreamItem &$item , $includePrivacy = true )
	{
        // Only process JReviews related activities
        preg_match('/(?P<activity>jreviews-[a-z]*)/', $item->context, $matches);

        if(!$matches) return;

        $config = $this->getParams();

		$this->css();

        // Privacy validation

        if($includePrivacy)
        {
            $my = ES::user();

            $Privacy = ES::privacy( $my->id );

            $item->privacy = $Privacy->form( $item->uid, 'jreviews', $item->actor->id, 'jreviews.view' );

            if(!$Privacy->validate( 'jreviews.view', $item->uid , 'jreviews' , $item->actor->id ) )
            {
                return;
            }
        }

        $this->activities = array(
          "guest"							=>	'PLG_JREVIEWS_ACTIVITY_GUEST',
          "listing_new"						=>  'PLG_JREVIEWS_ACTIVITY_LISTING_NEW',
          "listing_edit"					=>  'PLG_JREVIEWS_ACTIVITY_LISTING_EDIT',
          "review_new"						=>  'PLG_JREVIEWS_ACTIVITY_REVIEW_NEW',
          "review_edit"						=>  'PLG_JREVIEWS_ACTIVITY_REVIEW_EDIT',
          "favorite_add"					=>  'PLG_JREVIEWS_ACTIVITY_FAVORITE_ADD',
          "favorite_remove"					=>  'PLG_JREVIEWS_ACTIVITY_FAVORITE_REMOVE',
          "vote_yes"						=>  'PLG_JREVIEWS_ACTIVITY_VOTE_YES',
          "vote_no"							=>  'PLG_JREVIEWS_ACTIVITY_VOTE_NO',
          "vote_yes_guest"					=>  'PLG_JREVIEWS_ACTIVITY_VOTE_YES_GUEST',
          "vote_no_guest"					=>  'PLG_JREVIEWS_ACTIVITY_VOTE_NO_GUEST',
          "discussion_new"					=>  'PLG_JREVIEWS_ACTIVITY_DISCUSSION_NEW',
          "discussion_edit"					=>  'PLG_JREVIEWS_ACTIVITY_DISCUSSION_EDIT',
          "media_like_photo_yes"			=>	'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_PHOTO_YES',
          "media_like_photo_no"				=>	'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_PHOTO_NO',
          "media_like_photo_yes_guest"		=>	'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_PHOTO_YES_GUEST',
          "media_like_photo_no_guest"		=>	'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_PHOTO_NO_GUEST',
          "media_like_video_yes"			=>	'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_VIDEO_YES',
          "media_like_video_no"				=>	'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_VIDEO_NO',
          "media_like_video_yes_guest"		=>	'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_VIDEO_YES_GUEST',
          "media_like_video_no_guest"		=>	'PLG_JREVIEWS_ACTIVITY_MEDIA_LIKE_VIDEO_NO_GUEST',
          "media_photo"						=>  'PLG_JREVIEWS_ACTIVITY_MEDIA_PHOTO',
          "media_video"						=>  'PLG_JREVIEWS_ACTIVITY_MEDIA_VIDEO',
          "media_audio"						=>  'PLG_JREVIEWS_ACTIVITY_MEDIA_AUDIO',
          "media_attachment"				=>  'PLG_JREVIEWS_ACTIVITY_MEDIA_ATTACHMENT'
        );

        // Send variables to the views

        $this->set('contentlength', $this->contentlength);

        // Decode the params json object for each activity
        foreach($item->contextParams AS $key=>$val)
        {
            $item->contextParams[$key] = json_decode($val, true);
        }

        // For individual activities, replace the contextParam with the first element in the array

        if(count($item->contextIds) == 1) {

            $item->contextParams = array_shift($item->contextParams);

            $params = $item->contextParams;
        }

        $params['action'] = $item->verb;

        $item->display	= ( $item->display ) ? $item->display : SOCIAL_STREAM_DISPLAY_FULL;

        $this->set('item', $item);

        $this->set('params', $params);

        $this->set('activities', $this->activities);

        $this->set('title', $item->title);

        $this->set('content', $item->content);

        $this->set('actor', $item->actor);

        $this->set('actorLink', self::getProfileLink($item->actor));

        $target = count($item->targets) > 0 ? $item->targets[0] : false;

        $this->set('target', $target);

        $this->set('targetLink', self::getProfileLink($target));

        // Call activity specific methods

        $item->context = str_replace('-','_',$matches['activity']);

        $item->favicon = $matches['activity'];

        // Sets the icon for the stream item

        $item->fonticon = 'i' . $matches['activity'];

        $item->label = JText::_( 'COM_EASYSOCIAL_STREAM_CONTEXT_TITLE_'. strtoupper(str_replace('-','_',$matches['activity'])) );

        /**
         * Set the color for activity labels and icons. This value is overriden in some of the activity methods below
         * like favorites, review votes and media likes
         */

        $item->color = $config->get($matches['activity'],'#0DB806');

        switch($matches['activity'])
        {
            case 'jreviews-discussion':

                $this->discussionActivity($item);

            break;

            case 'jreviews-favorite':

                $this->favoriteActivity($item, $matches['activity']);

            break;

            case 'jreviews-listing':

                $this->listingActivity($item);

            break;

            case 'jreviews-medialike':

                $this->mediaLikeActivity($item, $matches['activity']);

            break;

            case 'jreviews-review':

            	$this->reviewActivity($item);

            break;

            case 'jreviews-photo':
            case 'jreviews-video':
            case 'jreviews-audio':
            case 'jreviews-attachment':

                $this->mediaActivity($item);

            break;

            case 'jreviews-vote':

                $this->voteActivity($item, $matches['activity']);

            break;
        }

        if($includePrivacy)
        {
            $item->privacy = $Privacy->form( $item->uid, 'jreviews', $item->actor->id, 'jreviews.view' );
        }

	}

    protected function getProfileLink(&$user)
    {
        if ((is_object($user) && !$user->id) || !$user) {
            return JText::_($this->activities['guest']);
        }

        if($user->isBlock()) return $user->getName();

		$profileLink = '<a href="'.$user->getPermalink().'">'.$user->getName().'</a>';

        return $profileLink;
    }

    protected function discussionActivity(&$item)
    {
    	// Determine display type

    	$params = $item->contextParams;

    	if($params['thumb_src'] == '' && trim($item->title) == '' && trim($item->content) == '')
    	{
    		$item->display = SOCIAL_STREAM_DISPLAY_MINI;
    	}
    	else {
			$item->content = parent::display('streams/comment.content');
    	}

		$item->title = parent::display('streams/comment.title');

		// Likes

		$likes = ES::likes();

		$likes->get($item->contextId, $item->context);

		$item->likes = $likes;

		// Comments

		$comments = ES::comments( $item->contextId , $item->context , SOCIAL_APPS_GROUP_USER , array(
			'url' => ESR::stream(array('layout' => 'item', 'id' => $item->contextId )) // => what is this?
		));

		$item->comments = $comments;
    }

   	protected function favoriteActivity(&$item, $activity)
    {
        $config = $this->getParams();

    	// Determine display type

        $item->fonticon .= '-' . $item->verb;

        $item->color = $config->get($activity . '-' . $item->verb, '#0DB806');

    	$params = $item->contextParams;

    	if($params['thumb_src'] == '' && trim($item->content) == '')
    	{
    		$item->display = SOCIAL_STREAM_DISPLAY_MINI;
    	}
    	else {
			$item->content = parent::display('streams/favorite.content');
    	}

		$item->title = parent::display('streams/favorite.title');

		// Likes

		$likes = ES::likes();

		$likes->get($item->contextId, $item->context);

		$item->likes = $likes;

		// Comments

		$comments = ES::comments( $item->contextId , $item->context , SOCIAL_APPS_GROUP_USER , array(
			'url' => ESR::stream(array('layout' => 'item', 'id' => $item->contextId )) // => what is this?
		));

		$item->comments = $comments;
    }

   protected function mediaActivity(&$item)
   {
        $params = $item->contextParams;

        if(count($item->contextIds) > 1)
        {
            $mediaArray = array();

            foreach($params AS $key=>$val) {

                $mediaArray[] = $val['media'];
            }

            $params = reset($item->contextParams);

            $params['media'] = $mediaArray;
        }
        else {

            $media = $params['media'];

            unset($params['media']);

            $params['media'] = array($media);
        }

        $media_type = str_replace('jreviews_','',$item->context);

        // limit the number of photos to 5 because EasySocial stream doesn't have css styles for more

        if($media_type == 'photo') {
            $params['media'] = array_slice($params['media'], 0, 5);
        }

        $this->set('params', $params);

   		$this->set('media_type', $media_type);

   		$this->set('media_count', count($item->contextIds));

		$item->preview = parent::display('streams/'.$media_type.'.content');

		$item->title = parent::display('streams/media.title');

		if(!in_array($media_type,array('photo','video'))) {

			// Likes

			$likes = ES::likes();

			$likes->get($item->contextId, $item->context);

			$item->likes = $likes;

			// Comments

			$comments = ES::comments( $item->contextId , $item->context , SOCIAL_APPS_GROUP_USER , array(
				'url' => ESR::stream(array('layout' => 'item', 'id' => $item->contextId )) // => what is this?
			));

			$item->comments = $comments;
		}

		// Repost

		$repost = ES::get( 'Repost', $item->uid , SOCIAL_TYPE_STREAM );

		$item->repost	= $repost;
    }

    protected function mediaLikeActivity(&$item, $activity)
    {
        $config = $this->getParams();

        // Determine display type

        $item->fonticon .= '-' . $item->verb;

        $item->color = $config->get($activity . '-' . $item->verb, '#0DB806');

        $params = $item->contextParams;

        $media_type = $params['media_type'];

   		$this->set('media_type', $media_type);

   		$this->set('media_count', count($item->contextIds));

		$item->context .= '_'.$media_type;

		!in_array($media_type,array('photo','video')) && $item->display = 'mini';

		$item->preview = in_array($media_type,array('photo','video')) ? parent::display('streams/'.$media_type.'.content') : '';

		$item->title = parent::display('streams/media.like.title');

		// Likes

		$likes = ES::likes();

		$likes->get($item->contextId, $item->context);

		$item->likes = $likes;

		// Comments

		$comments = ES::comments( $item->contextId , $item->context , SOCIAL_APPS_GROUP_USER , array(
			'url' => ESR::stream(array('layout' => 'item', 'id' => $item->contextId )) // => what is this?
		));

		$item->comments = $comments;

		// Repost

		$repost = ES::get( 'Repost', $item->uid , SOCIAL_TYPE_STREAM );

		$item->repost	= $repost;
    }

    protected function listingActivity(&$item)
    {
    	// Determine display type

    	$params = $item->contextParams;

    	if($params['thumb_src'] == '' && trim($item->content) == '')
    	{
    		$item->display = SOCIAL_STREAM_DISPLAY_MINI;
    	}
    	else {
			$item->content = parent::display('streams/listing.content');
    	}

		$item->title = parent::display('streams/listing.title');

		// Likes

		$likes = ES::likes();

		$likes->get($item->contextId, $item->context);

		$item->likes = $likes;

		// Comments

		$comments = ES::comments( $item->contextId , $item->context , SOCIAL_APPS_GROUP_USER , array(
			'url' => ESR::stream(array('layout' => 'item', 'id' => $item->contextId ))
		));

		$item->comments = $comments;

		// Repost

		$repost = ES::get( 'Repost', $item->uid , SOCIAL_TYPE_STREAM );

		$item->repost	= $repost;
    }

    protected function reviewActivity(&$item)
    {
    	// Determine display type

    	$params = $item->contextParams;

    	if($params['thumb_src'] == '' && trim($item->title) == '' && trim($item->content) == '')
    	{
    		$item->display = SOCIAL_STREAM_DISPLAY_MINI;
    	}
    	else {
			$item->content = parent::display('streams/review.content');
    	}

		$item->title = parent::display('streams/review.title');

		// Likes

		$likes = ES::likes();

		$likes->get($item->contextId, $item->context);

		$item->likes = $likes;

		// Comments

		$comments = ES::comments( $item->contextId , $item->context , SOCIAL_APPS_GROUP_USER , array(
			'url' => ESR::stream(array('layout' => 'item', 'id' => $item->contextId )) // => what is this?
		));

		$item->comments = $comments;

		// Repost

		$repost = ES::get( 'Repost', $item->uid , SOCIAL_TYPE_STREAM );

		$item->repost	= $repost;
    }

    protected function voteActivity(&$item, $activity)
    {
        $config = $this->getParams();

    	// Determine display type

        $item->fonticon .= '-' . $item->verb;

        $item->color = $config->get($activity . '-' . $item->verb, '#0DB806');

    	$params = $item->contextParams;

    	if($params['thumb_src'] == '' && trim($item->title) == '' && trim($item->content) == '')
    	{
    		$item->display = SOCIAL_STREAM_DISPLAY_MINI;
    	}
    	else {
			$item->content = parent::display('streams/vote.content');
    	}

		$item->title = parent::display('streams/vote.title');
    }
}
