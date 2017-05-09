<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class JreviewsReviewFieldModel extends MyModel  {

	var $name = 'JreviewsReviewField';

	var $useTable = '#__jreviews_review_fields AS `JreviewsReviewField`';

	var $primaryKey = 'JreviewsReviewField.review_id';

	var $realKey = 'reviewid';
}