<?php
(defined( '_VALID_MOS') || defined( '_JEXEC')) or die( 'Direct Access to this location is not allowed.' );
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

/*
 * connect(regex, array('controller'=>'controller', 'action'=>'action'), array('param name',[regex or value]), {Optional more params});
 * connect(regex, array('controller'=>'controller', 'action'=>'action'), array(array(param names),[regex or value], {Optional param key]) , {Optional more params});
*/

// Custom category route
//S2Router::connect('/^digital-cameras/', array('controller'=>'categories', 'action'=>'category'), array('cat',7),array('Itemid',55));

// Custom section route
//S2Router::connect('/^camcorders/', array('controller'=>'categories', 'action'=>'section'), array('section',2),array('Itemid',60));

// Advanced Search
S2Router::connect('/^advanced-search/', array('controller'=>'search', 'action'=>'index'), array('Itemid','/_m([0-9]+)/'));

// New Listing
S2Router::connect('/^new-listing/', array('controller'=>'listings', 'action'=>'create'), array('cat','/_c([0-9]+)/'), array('section','/_s([0-9]+)/'));

// Alphaindex
S2Router::connect('/_alphaindex_[0-9a-z]{1}/', array('controller'=>'categories','action'=>'alphaindex'), array('index','/alphaindex_([0-9a-z]{1})/'),array('dir','/_d([0-9]+)/'),array('Itemid','/_m([0-9]+)/'));
S2Router::connect('/^alphaindex\//', array('controller'=>'categories','action'=>'alphaindex'));

// Click2Search Tag
# Works for tag/whatis/value/{something_else}
// Allows underscores in tag value - if there are problems, use the one below
S2Router::connect('/tag\/([a-z]+)\/([^\/]*)(_m|_m[\d]|\/[a-z]:|\/|$)/',
					array('controller'=>'categories', 'action'=>'search'), 
					array(array('field','value'),'/tag\/([a-z]+)\/([^\/]*)(_m|_m[\d]|\/[a-z]:|\/|$)/','tag'),
					array('Itemid','/_m([0-9]+)/')
);

/*S2Router::connect('/^tag\/([a-z]+)\/([^_\/]*)(_|_m|_m[\d]|\/[a-z]:|\/)/',
					array('controller'=>'categories', 'action'=>'search'), 
					array(array('field','value'),'/^tag\/([a-z]+)\/([^_\/]*)(_|_m|_m[\d]|\/[a-z]:|\/)/','tag'),
					array('Itemid','/_m([0-9]+)/')
);*/

// RSS All
S2Router::connect('/reviews_com_[0-9a-z]*[.]rss/', array('controller'=>'feeds', 'action'=>'reviews'), array('extension','/(com_[0-9a-z]*)/'));

// RSS Directory
S2Router::connect('/_d[0-9].*[.]rss/', array('controller'=>'feeds', 'action'=>'reviews'), array('dir','/_d([0-9]+)/'));

// RSS Section
S2Router::connect('/_s[0-9].*[.]rss/', array('controller'=>'feeds', 'action'=>'reviews'), array('section','/_s([0-9]+)/'));

// RSS Category		
S2Router::connect('/_c[0-9].*[.]rss/', array('controller'=>'feeds', 'action'=>'reviews'), array('cat','/_c([0-9]+)/'));

// RSS Listing				
S2Router::connect('/_l[0-9]+_com_[0-9a-z]*[.]rss/', array('controller'=>'feeds', 'action'=>'reviews'), array('id','/_l([0-9]+)/'),array('extension','/(com_[0-9a-z]*)/'));

// Directory
S2Router::connect('/_d[0-9]+/', array('controller'=>'directories', 'action'=>'index'), array('dir','/_d([0-9]+)/'),array('Itemid','/_m([0-9]+)/'));

// Section list
S2Router::connect('/_s[0-9]+_/', array('controller'=>'categories', 'action'=>'section'), array('section','/_s([0-9]+)/'), array('Itemid','/_m([0-9]+)/'));

// Category list
S2Router::connect('/_c[0-9]+_/', array('controller'=>'categories', 'action'=>'category'), array('cat','/_c([0-9]+)/'), array('Itemid','/_m([0-9]+)/'));

// Listing
S2Router::connect('/_l[0-9]+/', array('controller'=>'listings', 'action'=>'detail'), array('id','/_l([0-9]+)/'),array('Itemid','/_m([0-9]+)/'));

// My Listings
S2Router::connect('/^my-listings\//', array('controller'=>'categories', 'action'=>'mylistings'));

// My Reviews
S2Router::connect('/^my-reviews\//', array('controller'=>'reviews', 'action'=>'myreviews'));

// Favorites
S2Router::connect('/^favorites\//', array('controller'=>'categories', 'action'=>'favorites'));
 			
// Search Results
S2Router::connect('/^search-results/', array('controller'=>'categories', 'action'=>'search'), array('Itemid','/_m([0-9]+)/'));

// Reviewers
S2Router::connect('/^reviewers/', array('controller'=>'reviews', 'action'=>'rankings'));

// Errors
S2Router::connect('/^404$/', array('controller'=>'errors', 'action'=>'error404'));