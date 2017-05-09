<?php
defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );
/**
 *  $Id: config.php 119 2009-09-05 04:51:37Z rowan $
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
define('Review_Module_MAX_RATING', '5');
define('Review_Module_USE_STAR_RATING', '1');
define('Review_Module_HEAD','');
define('Review_Module_MID','');
define('Review_Module_FOOT','Comment_Form::Standard_Comment_Form||Comment_Display::Standard_Comment');
define('Review_Module_URL','index.php?option=com_simple_review&Itemid={sr_itemID}&review={sr_reviewID}-{sr_pageName}');
define('Review_Module_RATING_DECIMAL_PLACES', 1);
?>