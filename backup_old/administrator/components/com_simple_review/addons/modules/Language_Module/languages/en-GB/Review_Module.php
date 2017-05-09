<?php
/**
 *  $Id: Review_Module.php 121 2009-09-13 11:05:24Z rowan $
 *
 * 	Copyright (C) 2005-2009  Rowan Youngson
 * 
 *	This file is part of Simple Review.
 *
 *	Simple Review is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.

 *  Simple Review is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with Simple Review.  If not, see <http://www.gnu.org/licenses/>.
*/
return array(
'AdminDescription'			=> 'Add your reviews here.',
'ConfigMaxRating'			=> 'Max Rating:',
'ConfigMaxRatingTip'		=> 'The maximum rating a Review may receive.',
'ConfigReviewUrl'			=> 'Review URL:',
'ConfigReviewUrlTip'		=> 'Set the format of the URL to reviews. You may use some Simple Tags.',
'ConfigStarRating'			=> 'Use star rating:',
'ConfigStarRatingTip'		=> 'Ratings will be shown as stars and not number.',
'FormAward'					=> 'Award',
'FormAwardTip' 				=> 'Give the review an award. In your review you will have to use {sr_award}',
'FormBlurb'					=> 'Blurb',
'FormBlurbTip'				=> 'If Top N reviews is enabled a blurb will be shown on this reviews category page. The blurb should be a short summary of the review. If no blurb is specified and Top N Reviews is enabled then the first 400 characters (HTML removed) of the review will be shown.',
'FormCategory'				=> 'Category (*)',
'FormCategoryTip'			=> 'The category which the review belongs to.',
'FormCategorySelect' 		=> 'Please select this reviews category.',
'FormImageUrl'				=> 'Image URL',
'FormImageUrlTip'			=> 'The full URL to an image, e.g.http://row1.info/big.jpg.',
'FormPageName'				=> 'Page Name',
'FormPageNameTip'			=> 'The name of the review page to show in the URL. It will be URL encoded so it is recommended to only use Latin characters, numbers, periods, underscores and hyphens as Internet Explorer will not decode the URL in the status or URL bar.',
'FormPublished'				=> 'Published',
'FormPublishedTip'			=> 'Publish the review.',
'FormRating'				=> 'Rating',
'FormRatingTip'				=> 'The rating to give the reivew.',
'FormReview'				=> 'Review (*)',
'FormReviewTip'				=> 'The main content of the review.',
'FormTitleOptionNone'		=> 'None',
'FormTitleOptionNoneTip'	=> 'The title will be treated as a plain text title.',
'FormTitleOptionIsRateable'	=> 'Is Rateable',
'FormTitleOptionIsRateableTip'=> 'The title will be treated as a rateable title.',
'FormTitleOptionIsUrl'		=> 'Is Url',
'FormTitleOptionIsUrlTip'	=> 'The title be treated as a hypertext link.',
'FormThumbnailUrl'			=> 'Thumbnail URL',
'FormThumbnailUrlTip'		=> 'The full URL to the review thumbnail, e.g.http://row1.info/thumb1.jpg.',
'FormWarningValidRating'	=> 'Rating must be a valid number.',
'FormWarningRatingBounds'	=> 'Please enter a value between {0} and {1}.',
'FormWarningMandatoryTitle1'=> 'The Title needs to be specified.',
'FormWarningThumbnailLength'=> 'Thumbnail URL must not be greater than 255 characters.',
'FormWarningImageLength'	=> 'Image URL must not be greater than 255 characters.',
'FormWarningUrl'			=> 'Please enter a valid URL.',
'NoReviews'					=> 'There are currently no reviews.',
'OutOf'						=> 'out of ',
'Review'					=> 'Review',
'Reviews'					=> 'Reviews',
'ReviewFilter'				=> 'Review Filter',
'ReviewList'				=> 'Review List.'	
);
?>