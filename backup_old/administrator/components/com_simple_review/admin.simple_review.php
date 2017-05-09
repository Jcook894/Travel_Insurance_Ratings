<?php
/**
 *  $Id: admin.simple_review.php 120 2009-09-13 05:37:35Z rowan $
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

$adminPath = "";

if(defined( '_JEXEC' ) )
{
	$adminPath = JPATH_COMPONENT_ADMINISTRATOR;
}
else
{
	$adminPath = "$mosConfig_absolute_path/administrator/components/com_simple_review";	
}
require_once("$adminPath/classes/SRBridgeCMS.php");
$bridge =& SRBridgeManager::Get();

if (!$bridge->CurrentUser->CanViewAdmin()) 
{
    $bridge->Redirect('index2.php', 'Not Authorized'); 
}    
			
require_once($mainframe->getPath( 'admin_html' ) );

require_once("$adminPath/addons/Addon_Base_Admin.php");

$task = $bridge->GetParameter( $_REQUEST, 'task', '' );
$module = $bridge->GetParameter( $_REQUEST, 'module', '' );

$categoryTable = "#__simplereview_category";
$reviewTable = "#__simplereview_review";
$templateTable = "#__simplereview_template";
$awardTable = "#__simplereview_awards";
$bannedIPTable = "#__simplereview_banned_ips";
$commentTable = "#__simplereview_comments";

$jsPath = $bridge->SiteUrl.'components/com_simple_review/javascript';
Simple_Review_Common::IncludeJavaScript("$jsPath/SRCore.js");

$cssFile =  $bridge->SiteUrl.'administrator/components/com_simple_review/admin.simple_review.css';
Simple_Review_Common::IncludeCSS($cssFile );

$bridge->IncludeJQuery();

switch ($task) 
{
	case "configuration":
   		$config = new SRConfiguration();
		$config->Display();
   		break;

	case "saveConfig":
	case "applyConfig":
		$config = new SRConfiguration();
		$config->SaveConfig($task == "applyConfig");
		break;
	
	case "help":
	HTML_Simple_Review::printHelp();
	break;
	
	case "license":
	HTML_Simple_Review::printLicense();
	break;
  
  default:
    if ($module != "")
    {
      	$addonManager =& Addon_Manager::Get();
     	$customModule  =& $addonManager->LoadAdminModule($module);
     	if($customModule != null)
     	{
      		$customModule->Display($task); 
      		break;
		}  
    }
    mainScreen();
    break;
}

function mainScreen()
{
    HTML_Simple_Review::mainScreen();
}
?>