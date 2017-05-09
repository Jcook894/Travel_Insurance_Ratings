<?php
/*
    JReviews Express - user reviews for Joomla
    Copyright (C) 2009  Alejandro Schmeichler

    JReviews Express is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JReviews Express is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ReviewReportModel extends MyModel {
		
	var $name = 'ReviewReport';
	
	var $useTable = '#__jreviews_report AS ReviewReport';

	var $primaryKey = 'ReviewReport.report_id';
	
	var $realKey = 'id';
	
	var $fields = array(
		'ReviewReport.id AS `ReviewReport.report_id`',
		'ReviewReport.reviewid AS `ReviewReport.review_id`',		
		'ReviewReport.message AS `ReviewReport.message`',
		'Review.id AS `Review.review_id`',
		'Review.title AS `Review.title`',
		'Review.pid AS `Review.listing_id`',
		'Review.`mode` AS `Review.extension`',
        'Review.created AS `Review.created`',
        'User.name AS `User.name`',
        'User.username AS `User.alias`'
	);
	
	var $joins = array(
		"LEFT JOIN #__jreviews_comments AS Review ON ReviewReport.reviewid = Review.id",
        "LEFT JOIN #__users AS User ON Review.userid = User.id"
	);
	
	var $conditions = array();
	
	var $group = array();
}
