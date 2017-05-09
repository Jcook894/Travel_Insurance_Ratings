<?php
defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );
/**
 *  $Id: config.php 122 2009-09-13 12:39:25Z rowan $
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

define('Category_Module_URL','index.php?option=com_simple_review&Itemid={sr_itemID}&category={sr_categoryID}-{sr_pageName}');
define('Category_Module_NO_OF_ITEMS', '0');
define('Category_Module_NO_OF_REVIEW_ITEMS', '0');

define('Category_Module_N_TOP_REVIEWS', '5');
define('Category_Module_N_TOP_REVIEWS_CURRENT_CAT', '0');

define('Category_Module_SHOW_REVIEW_COUNT','1');
define('Category_Module_HEAD','TopN_Display::Horizontal_TopN');
define('Category_Module_MID','');
define('Category_Module_FOOT','');

define('Category_Module_SHOW_TITLE1','1');
define('Category_Module_SHOW_TITLE2','1');
define('Category_Module_SHOW_TITLE3','1');
define('Category_Module_SHOW_RATING','1');
define('Category_Module_SHOW_REVIEWER','1');
define('Category_Module_SHOW_DATE','1');
define('Category_Module_TITLE2_LINK','0');
define('Category_Module_TITLE3_LINK','0');

define('Category_Module_REVIEW_SORT_FIELD','title1');
define('Category_Module_REVIEW_SORT_ORDER','asc');

define('Category_Module_SINGLE_CAT_ROOT_BYPASS', '1');

define('Category_Module_TITLE2_IS_REVIEW_LINK','1');
define('Category_Module_TITLE3_IS_REVIEW_LINK','1');

?>