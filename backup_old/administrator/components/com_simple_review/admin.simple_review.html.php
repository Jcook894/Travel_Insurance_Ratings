<?php
/**
 *  $Id: admin.simple_review.html.php 122 2009-09-13 12:39:25Z rowan $
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

// ensure this file is being included by a parent file
defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

/*
if (!($acl->acl_check('administration', 'edit', 'users', $my->usertype, 'components', 'all') || $acl->acl_check('administration', 'edit', 'users', $my->usertype, 'components', 'com_simple_review'))) 
{
    mosRedirect('index2.php', _NOT_AUTH); 
}
*/
$bridge =& SRBridgeManager::Get();

if (!$bridge->CurrentUser->CanViewAdmin()) 
{
    $bridge->Redirect('index2.php', 'Not Authorized'); 
}  

$adminPath = $bridge->PathComponentAdministrator;

require_once("$adminPath/classes/class.simple_review_common.php");
require_once("$adminPath/classes/SRConfiguration.php");

class HTML_Simple_Review {

    function mainScreen()
    {	
		$SR_Addon_Manager =& Addon_Manager::Get();
		$langModule =& $SR_Addon_Manager->GetModule('Language_Module', false);
        $version = _SR_Version;
		
		?>
		<div style="text-align:center;">
			<p><h1>Important</h1>
				<strong>This is a Beta release. As an Beta release it may contain bugs and incomplete features.<br/>
				It is for testing purposes and not intended for live sites.
				</strong>	
			</p>
		
			<?php HTML_Simple_Review::printOptions(); ?>
			<p>
				<strong><?php echo $langModule->GetString($langModule, 'CheckForUpdates');?></strong><br/>
				<small ><?php echo $langModule->GetString($langModule, 'Version').":$version";?><br/>
        			<a href='http://www.simple-review.com'>http://www.simple-review.com</a>
				</small>
			</p>
        </div>
		<?php
    }

    function printOptions()
    {
      	global $database;
		$SR_Addon_Manager =& Addon_Manager::Get();		
	  	$bridge =& SRBridgeManager::Get();
		$langModule =& $SR_Addon_Manager->GetModule('Language_Module', false);
	  
		if(_SR_GLOBAL_LOCK_TABLES == 1)
		{
			$query = "LOCK TABLES #__simplereview_category WRITE; UNLOCK TABLES;";
			$lockPriv = SRBridgeDatabase::BatchQuery($query);
			if($lockPriv == false)
			{
				?>
				<div style="text-align:left;font-size:large">
					<img src="<?php echo $bridge->SiteUrl;?>components/com_simple_review/images/simplereview_banner.gif" width='745' height='141'/>
					<p>Your current MySQL user does not have permission to lock tables.</p>
					<p>
					Please go to the Simple Review <a href='index2.php?option=com_simple_review&task=configuration'><?php echo $langModule->GetString($langModule, 'Configuration');?></a> page.<br/>
					Under advanced options set 'Lock Tables' to <strong>no</strong>.					
					</p>
				</div>	
				<?php
				return;
			}
		}

		$addonManager =& Addon_Manager::Get();
		$allModules =& $addonManager->LoadAdminModules();
		$banner = $bridge->SiteUrl.'components/com_simple_review/images/simplereview_banner.gif';

        ?>
        <table style='text-align:left;border-style: solid; border-width: 1px;margin:0px auto;'>
            <tr class='altOdd'>            
        	    <th colspan='2' align='center'><img src="<?php echo $banner;?>"/></th>
            </tr>

        	<tr class='altEven'>
				<td>
					<a href='index2.php?option=com_simple_review&task=license'><?php echo $langModule->GetString($langModule, 'License');?></a>
				</td>
				<td>
					<?php echo $langModule->GetString($langModule, 'LicenseDescription');?>
				</td>
			</tr>
			
        	<tr class='altOdd'>
				<td>
					<a href='index2.php?option=com_simple_review&task=help'><?php echo $langModule->GetString($langModule, 'Help');?></a>
				</td>
				<td>
					<?php echo $langModule->GetString($langModule, 'HelpDescription');?>
				</td>
			</tr>
        
			<tr class='altEven'>
				<td>
					<a href='index2.php?option=com_simple_review&task=configuration'><?php echo $langModule->GetString($langModule, 'Configuration');?></a>
				</td>
				<td>
					<?php echo $langModule->GetString($langModule, 'ConfigurationDescription');?>
				</td>
			</tr>
			
		        
        <?php 
        $i=1;
		foreach($allModules  as $module)
		{
		  $cssClass= (++$i%2 ? "altEven" : "altOdd");
		?>
		<tr class="<?php echo $cssClass;?>">
			<td>
				<a href='index2.php?option=com_simple_review&amp;task=list&amp;module=<?php echo $module->addonName;?>'>
				<?php echo $module->friendlyName;?>
				</a>
			</td>
			<td>
				<?php echo $module->defaultTaskName;?>
			</td>
		</tr>
		<?php } ?> 
        
		<tr>
			<td colspan='2'> 
			This software is provided to you free of charge.<br/>
			If you find this software useful please make a donation to support the Joomla team:<br/>		
			<a href='http://www.joomla.org/'>Joomla</a><br/>	
			</td>		
		</tr>      
              
        </table>
		<small>Simple Review logo and star icons by <a href='http://badthiseyenz.netpita.com/'>pEdRomAU @ BAD THISeyeNZ</a></small>
    <?php
    }



    function printLicense()
    {
      ?>
        <div align='left'>
        <h1>License</h1>
        Simple Review was created in my spare time. It took a lot of time and effort.<br/>
        All I want in return is if you use it you provide a link to my site <a href='http://simple-review.com'>http://simple-review.com</a>.<br/>
        It currently displays a small link at the bottom of every page which reads 'Powered by Simple Review'.<br/>
        If you want to remove this link that it fine as long but please credit me with a link to <a href='http://simple-review.com'>http://simple-review.com</a>
        somewhere on your page, the page must be easily accessible and not hidden away somewhere deep within your website structure.<br/>
        Please do not change 'Powered by Simple Review' to read something other than Simple Review.<br/>
        The avatars are provided with permisson by <a href='http://www.iconbuffet.com/'>http://www.iconbuffet.com/</a>.<br/>
        
		<h2>Copyright (C) 2005-2009  Rowan Youngson</h2>
		
		<p>
			Simple Review is free software: you can redistribute it and/or modify
			it under the terms of the GNU General Public License as published by
			the Free Software Foundation, either version 3 of the License, or
			(at your option) any later version.
 		</p>

		<p>
			Simple Review is distributed in the hope that it will be useful,
			but WITHOUT ANY WARRANTY; without even the implied warranty of
			MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
			GNU General Public License for more details.
		</p>
		<p>
			You should have received a copy of the GNU General Public License
			along with Simple Review.  If not, see <a href='http://www.gnu.org/licenses/'>http://www.gnu.org/licenses/</a> .		
		</p>
        </div>
        
      <?php
    }
    
    function printHelp()
    {
    ?>
    <div align='left'>
	Please visit <a href='http://simple-review.com/help'>http://simple-review.com/help</a> for help
	</div>
    <?php
    }
 
}

?>
