<?php
/**
 *  $Id: config.simple_review.php 120 2009-09-13 05:37:35Z rowan $
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
defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );
$sr_global['sr_sig'] = "<p><i><small>Powered by <a href='http://row1.info'>Simple Review</a></small></i></p>";
$sr_global['allowUserComments'] = '1';

$sr_global['overLib'] = '1';
$sr_global['numberOfTopReviews'] = '2';
$sr_global['catReviewCount'] = '1';

$sr_global['onecomperip'] = '1';
$sr_global['oldCommentStyle'] = '0';
$sr_global['commentEmail'] = '';


$sr_global['showTitle1'] = '1';
$sr_global['showTitle2'] = '1';
$sr_global['showTitle3'] = '1';
$sr_global['showRating'] = '1';
$sr_global['showReviewer'] = '1';
$sr_global['showDate'] = '1';

$sr_global['title2Link'] = '0';
$sr_global['title3Link'] = '0';
$sr_global['userReview'] = 'super administrator';
$sr_global['autoPublishUserReview'] = 'super administrator';
$sr_global['forceUserReviewTemplate'] = '0';
$sr_global['allowableTags'] = '<b><i><u><em><strong><p><br><a><img><h1><h2><h3><div><table><tr><th><td><span>';
$sr_global['reviewEmail'] = '';

define('_SR_GLOBAL_LANGUAGE', 'english');
define('_SR_GLOBAL_DATE_FORMAT', '%a %e %b %y'); //format to display the date, see http://dev.mysql.com/doc/mysql/en/date-and-time-functions.html
define('_SR_GLOBAL_USE_REAL_NAME', '1');
define('_SR_GLOBAL_LOCK_TABLES', '0');
define('_SR_GLOBAL_SEO', '1');
define('_SR_JQuery', '1');
?>
