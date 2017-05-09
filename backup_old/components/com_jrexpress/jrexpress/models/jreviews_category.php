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

class JreviewsCategoryModel extends MyModel  {
	
	var $name = 'JreviewsCategory';
	
	var $useTable = '#__jreviews_categories AS JreviewsCategory';
			
	var $primaryKey = 'JreviewsCategory.id';
	
	var $realKey = 'id';
	
	var $fields = array(
		'JreviewsCategory.id AS `JreviewsCategory.id`',
		'JreviewsCategory.dirid AS `JreviewsCategory.dir_id`',
		'JreviewsCategory.criteriaid AS `JreviewsCategory.criteria_id`',
		'JreviewsCategory.tmpl AS `JreviewsCategory.tmpl`',
		'JreviewsCategory.tmpl_suffix AS `JreviewsCategory.tmpl_suffix`'
	);
	
}