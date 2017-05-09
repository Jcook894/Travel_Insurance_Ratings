<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommunityHelper extends MyHelper {

    var $helpers = array('html');

    function profileLink($name, $user_id, $url, $schema = false)
    {
        if($user_id > 0) {

            $attributes = array('sef' => false);

            if ($schema)
            {
                $attributes['itemprop'] = 'url';
                return $this->Html->link('<span itemprop="name">'.$name.'</span>', $url ,$attributes);
            }

            return $this->Html->link($name,$url, $attributes);
        }
        else {

            return $name;
        }
    }

    function avatar($entry, $options = array())
    {
        $defaults = array('return_url' => false, 'class' => 'jrAvatar');

        $options = array_replace_recursive($defaults, $options);

        $avatarKey = Sanitize::getBool($options, 'large') ? 'avatar_lg_path' : 'avatar_path';

        $avatarPath = ThemingComponent::getImageUrl('tnnophoto.jpg');

        if(isset($entry['Community']))
        {
            if(Sanitize::getBool($options, 'large') && Sanitize::getString($entry['Community'], 'avatar_lg_path') != '')
            {
                $avatarPath = $entry['Community']['avatar_lg_path'];
            }
            elseif(Sanitize::getString($entry['Community'], 'avatar_path') != '') {
                $avatarPath = $entry['Community']['avatar_path'];
            }
        }

        if($options['return_url'])
        {
            return $avatarPath;
        }

        if(isset($entry['Community']) && isset($entry['Community']['community_user_id']) && $entry['User']['user_id'] > 0)
        {
            $screenName = $this->screenName($entry,null,false);

            $attributes = array('class'=>$options['class'], 'alt'=>$screenName,'border'=>0);

            return $this->profileLink($this->Html->image($avatarPath,$attributes),$entry['Community']['community_user_id'],$entry['Community']['url']);
        }

        if ($this->Config->community != '') {

            $attributes = array('class'=>$options['class'], 'border'=>0);

            return $this->Html->image($avatarPath,$attributes);

        }

    }

    function avatarLarge($entry, $options = array())
    {
        $options['large'] = true;

        return $this->avatar($entry, $options);
    }

    function screenName(&$entry, $link = true, $schema = false)
    {
        // $Config param not being used
        $screenName = $this->Config->name_choice == 'realname' ? $entry['User']['name'] : $entry['User']['username'];

        if($link && !empty($entry['Community']) && isset($entry['Community']['community_user_id']) && $entry['User']['user_id'] > 0) {

            if ($schema) {

                $profileLink = $this->profileLink($screenName,$entry['Community']['community_user_id'],$entry['Community']['url'],true);

                return '<span itemprop="author" itemscope itemtype="http://schema.org/Person">' . $profileLink . '</span>';
            }

            return $this->profileLink($screenName,$entry['Community']['community_user_id'],$entry['Community']['url']);

        }

        $screenName = $screenName == '' ? __t("Guest",true) : $screenName;

        if ($schema) {

            $screenName = '<span itemprop="author" itemscope itemtype="http://schema.org/Person"><span itemprop="name">' . $screenName . '</span></span>';

        }

        return $screenName;
    }

    function addPreviewAttributes($entry)
    {
        $attributes = '';

        if(isset($entry['Community']) && isset($entry['Community']['community_user_id']) && $entry['User']['user_id'] > 0)
        {
            $className = Inflector::camelize($this->Config->community . '_component');

            if(method_exists($className, 'addPreviewAttributes'))
            {
                $output = $className::addPreviewAttributes($entry['Community']['community_user_id']);

                foreach($output AS $key => $val)
                {
                    $attributes .= $key . '="' . $val . '"';
                }
            }
        }

        echo $attributes;
    }

    function socialBookmarks($listing)
    {
        $output = '';

        $options = Sanitize::getVar($this->Config,'social_sharing_detail',array());

        $no_ssl = Sanitize::getBool($this->Config,'social_sharing_disable_secure_urls');

        $googlePlusOne = $twitter = $facebook = $pinterest = $linkedIn = $reddit = '';

        $countPosition = $this->Config->social_sharing_count_position;

        switch($countPosition)
        {
            case 'vertical':
                $countPositionClass = "Vertical";
                $twitterCount = 'vertical';
                $facebookCount = 'box_count';
                $gplusCount = 'tall';
                $linkedInCount = 'top';
                $pinterestCount = 'vertical';
                break;
            case 'horizontal':
                $countPositionClass = '';
                $twitterCount = 'horizontal';
                $facebookCount = 'button_count';
                $gplusCount = 'medium';
                $linkedInCount = 'right';
                $pinterestCount = 'horizontal';
                break;
            case 'none':
                $countPositionClass = '';
                $twitterCount = 'none';
                $facebookCount = 'button';
                $gplusCount = 'none';
                $linkedInCount = 'none';
                $pinterestCount = 'none';
                break;
            case 'none-custom':
                $countPositionClass = 'Custom';
                $twitterCount = 'none';
                $facebookCount = 'button';
                $gplusCount = 'none';
                $linkedInCount = 'none';
                $pinterestCount = 'none';
                break;
        }

        $facebook_xfbml = Sanitize::getBool($this->Config,'facebook_opengraph') && Sanitize::getBool($this->Config,'facebook_appid');

        if($no_ssl)
        {
            $href = cmsFramework::route($listing['Listing']['url'],array('ssl'=>-1));
        }
        else {

            $href = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));
        }

        $title = urlencode($listing['Listing']['title']);

        if (isset($listing['MainMedia']['media_info']['image']))
        {
            $thumb_url = $listing['MainMedia']['media_info']['image']['url'];
        }
        else {
            $thumb_url = '';
        }

        if(in_array('twitter',$options)) {

            $twitter = '
                <a href="https://twitter.com/share" data-url="'.$href.'" class="jr-tweet twitter-share-button" data-count="'.$twitterCount.'">'.__t("Tweet",true).'</a>'
            ;

            if ($countPosition == 'none-custom') {

                $twitter = '<a class="jrTwitter" target="_blank" href="https://twitter.com/intent/tweet?text='.$title.'&amp;url='.$href.'">'.__t("Twitter",true).'</a>';
            }
        }

        if(in_array('fbsend',$options)) {

            $facebook = '<div class="jr-fb-share fb-share-button" data-type="'.$facebookCount.'" data-href="'.$href.'" data-colorscheme="light"></div>';
        }

        if(in_array('fblike',$options)) {

            if ($facebook_xfbml) {
                $facebook .= '<div class="jr-fb-like fb-like" data-show-faces="false" data-href="'.$href.'" data-action="like" data-colorscheme="light" data-layout="'.$facebookCount.'"></div>';
            }
            else {
                $facebook .= '
                    <div class="jr-fb-like fb-like" data-layout="'.$facebookCount.'" data-show_faces="false"></div>';
            }

            if ($countPosition == 'none-custom') {

                $facebook = '<a class="jrFacebook" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u='.$href.'">'.__t("Facebook",true).'</a>';
            }
        }

        if(in_array('gplusone',$options)) {

            if ($countPosition == 'none') {

                $googlePlusOne = '<span class="jr-gplusone jrHidden"></span><g:plusone href="'.$href.'" count="false" size="'.$gplusCount.'"></g:plusone>';
            }
            elseif ($countPosition == 'none-custom') {

                $googlePlusOne = '<a class="jrGooglePlus" target="_blank" href="https://plus.google.com/share?url='.$href.'">'.__t("Google+",true).'</a>';
            }
            else {

                $googlePlusOne = '<span class="jr-gplusone jrHidden"></span><g:plusone href="'.$href.'" size="'.$gplusCount.'"></g:plusone>';
            }

        }

        if(in_array('linkedin',$options)) {

            $linkedIn = '<span class="jr-linkedin jrHidden"></span><script type="IN/Share" data-url="'.$href.'" data-counter="'.$linkedInCount.'"></script>';

            if ($countPosition == 'none-custom') {

                $linkedIn = '<a class="jrLinkedIn" target="_blank" href="https://www.linkedin.com/shareArticle?mini=true&amp;url='.$href.'">'.__t("LinkedIn",true).'</a>';
            }
        }


        if(in_array('pinit',$options)) {

            if ($thumb_url != '') {

                $pinterest = '<a href="https://pinterest.com/pin/create/button/?url='.$href.'&media='.$thumb_url.'" class="jr-pinterest pin-it-button" count-layout="'.$pinterestCount.'"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a>';
            }
            else {

                $pinterest = '<a href="https://pinterest.com/pin/create/button/?url='.$href.'" class="jr-pinterest pin-it-button" count-layout="'.$pinterestCount.'"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a>';
            }

            if ($countPosition == 'none-custom') {

                $pinterest = '<a class="jrPinterest" target="_blank" href="https://pinterest.com/pin/create/button/?url='.$href.'&amp;media='.$thumb_url.'&amp;description='.$title.'">'.__t("Pinterest",true).'</a>';
            }
        }

        if(in_array('reddit',$options)) {

            if ($countPosition == 'vertical') {

                $reddit = '<span class="reddit-button"><script type="text/javascript">reddit_url = "'.$href.'";</script><script type="text/javascript" src="//www.reddit.com/static/button/button3.js"></script></span>';
            }
            elseif ($countPosition == 'horizontal')  {

                $reddit = '<span class="reddit-button"><script type="text/javascript">reddit_url = "'.$href.'";</script><script type="text/javascript" src="//www.reddit.com/static/button/button1.js"></script></span>';
            }
            elseif ($countPosition == 'none') {

                $reddit = '<a href="https://www.reddit.com/submit?url='.$href.'&amp;title='.$title.'"> <img src="//www.reddit.com/static/spreddit7.gif" alt="Submit to reddit" border="0" /> </a>';
            }
            else {

                $reddit = '<a class="jrReddit" target="_blank" href="https://www.reddit.com/submit?url='.$href.'&amp;title='.$title.'">'.__t("Reddit",true).'</a>';
            }

        }

        $buttons = $facebook . $twitter . $googlePlusOne . $linkedIn . $pinterest . $reddit;

        if ($buttons != '') {

            $output .= '<div class="socialBookmarks'.$countPositionClass.'">';

            $output .= $buttons;

            $output .= '</div>';

        }

        echo $output;
    }

}