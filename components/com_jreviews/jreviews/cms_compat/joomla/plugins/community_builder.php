<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommunityBuilderComponent extends S2Component {

    var $plugin_order = 100;

    var $name = 'community_builder';

    var $title = 'Community Builder';

    var $plugin_type = 'profile';

    var $published = false;
}
