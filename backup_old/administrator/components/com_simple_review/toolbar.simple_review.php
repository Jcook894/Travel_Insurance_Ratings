<?php
/**
 *  $Id: toolbar.simple_review.php 81 2009-05-17 12:34:52Z rowan $
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
$bridge =& SRBridgeManager::Get();
// handle the task
$task = $bridge->GetParameter( $_REQUEST, 'task', '' );
$module = $bridge->GetParameter( $_REQUEST, 'module', '' );
if ($module)
{ 
	if ($task) 
	{

  		switch ( $task ) 
		{
				case 'new':
				SRBridgeMenuBar::ModuleNew();
				break;
			    case 'edit':
			    SRBridgeMenuBar::ModuleEdit();
			    break;
			    //case 'delete':
			    //menuSimple_Review::Module_Delete();
			    //break;			    
			    case 'list':
			    SRBridgeMenuBar::ModuleList();
			    break;	
				
				default:
			    SRBridgeMenuBar::ModuleEdit();
			    break;						    
			    
		}
	}		    
	else
	{
	  SRBridgeMenuBar::ModuleList();
	} 
}
else if ($task) 
{

  switch ( $task ) 
  {

/*
    case 'displayCategories':
    menuSimple_Review::DISPLAY_CATEGORY_MENU();
    break;
    
    case 'displayReviews':
    menuSimple_Review::DISPLAY_REVIEW_MENU();
    break;
    
    case 'displayTemplates':
    menuSimple_Review::DISPLAY_TEMPLATE_MENU();
    break;

    case 'displayAwards':
    menuSimple_Review::DISPLAY_AWARD_MENU();
    break;

    case 'editAward':
    case 'newAward':
       menuSimple_Review::NEW_AWARD_MENU();
      break;

    case 'displayBannedIPs':
      menuSimple_Review::DISPLAY_BANNED_IPS_MENU();
      break;
    
    case 'editCategory':
    case 'newCategory':
       menuSimple_Review::NEW_CATEGORY_MENU();
      break;
      
    case 'changeTemplate':  
    case 'keepTemplate':
    case 'editReview':
    case 'newReview':
      menuSimple_Review::NEW_REVIEW_MENU();
      break;
      
    case 'newTemplate':
    case 'editTemplate':
      menuSimple_Review::NEW_TEMPLATE_MENU();
      break;  
   */ 	  
    case 'configuration':
      SRBridgeMenuBar::Config();
      break;	   
  }
  
  
}  

?>
