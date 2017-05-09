<?php
/**
 *  $Id: SRConfiguration.php 122 2009-09-13 12:39:25Z rowan $
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
class SRConfiguration
{
	var $_AddonManager = null;
	function SRConfiguration()
	{
		$this->_AddonManager =& Addon_Manager::Get(); 	
		$this->_AddonManager->Bridge->IncludeBridgeControls();
	}
	
	function _IncludeConfigurationSubmitScript()
	{
    ?>
		<script language="javascript" type="text/javascript">
		var pluginLists = new Array();
		function SRAddToPluginList(selectListID)
		{
			pluginLists[pluginLists.length] = selectListID;  
		}
		function submitbutton(pressbutton)
		{
		    var form = document.adminForm;
		    var dateformat = getElementByName( document.adminForm, "params[dateformat]" );
		    //var numberoftoppreviews = getElementByName( document.adminForm, "params[numberoftoppreviews]" );
		    var topnlayout = getElementByName( document.adminForm, "params[topnlayout]" );
		    if (pressbutton == "cancel")
		    {
		      submitform( pressbutton );
		      return;
		    }
		    if (dateformat.value == '') {
				alert( "Date Format needs to be specified." );
			}
		    else
		    {
		      	for(var i =0; i < pluginLists.length; i++)
		      	{
		      		SRListSelectAll( pluginLists[i]); 
		      	}
		        submitform( pressbutton );
		    }
		}
		
		function reviewListingFieldsAllDeselected()
		{
		  var allDeselected = 
		    getElementByName( document.adminForm, "params[showtitle1]" ).checked &&
		    getElementByName( document.adminForm, "params[showtitle2]" ).checked &&
		    getElementByName( document.adminForm, "params[showtitle3]" ).checked &&
		    getElementByName( document.adminForm, "params[showrating]" ).checked &&
		    getElementByName( document.adminForm, "params[showreviewer]" ).checked &&
		    getElementByName( document.adminForm, "params[showdate]" ).checked &&
		    getElementByName( document.adminForm, "params[showtitle1]" ).checked;	    
		    return allDeselected;
		}
		
		function IsNumeric(sText)
		{
		   var ValidChars = "0123456789.";
		   var IsNumber=true;
		   var Char;
		
		
		   for (i = 0; i < sText.length && IsNumber == true; i++)
		      {
		      Char = sText.charAt(i);
		      if (ValidChars.indexOf(Char) == -1)
		         {
		         IsNumber = false;
		         }
		      }
		   return IsNumber;
		
		 }
		</script>
    <?php		
	}
	
	function Display()
	{
		global $option;
		$this->_IncludeConfigurationSubmitScript();
        $jsPath = $this->_AddonManager->Bridge->SiteUrl.'components/com_simple_review/javascript';
		Simple_Review_Common::IncludeJavaScript("$jsPath/SRCore.js");	
		Simple_Review_Common::IncludeJavaScript("$jsPath/pluginselect.js");		
		
		$cssFile =  $this->_AddonManager->Bridge->SiteUrl.'components/com_simple_review/css/Simple_Review.css';
		Simple_Review_Common::IncludeCSS($cssFile );        

  		$control_name = "params";	
		
		$tabs = new SRTabbedContainer();	
        echo "<table><tr><td width='60%' valign='top'>";
        echo "<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>";    
		
		echo "<div>";
		
		$tabs->StartPane( "sr_configuration" );
		$this->DisplayGeneralTab($tabs);
		$this->DisplayModuleTabs($tabs);	
		$this->DisplayAdvancedTab($tabs);	
		$tabs->EndPane();  	
		echo "</div>";	
		echo "<div style='clear:both;'/>";
		  
        //need to clean this up
        //echo $params->render();
        echo "<input type='hidden' name='option' value='$option' />";
        echo "<input type='hidden' name='task' value='' />";
        echo "</form>";
        echo "</td><td valign='top'>";

        $tagMod =& $this->_AddonManager->GetModule("Tag_Module");
        $tagMod->ListTags();       
        echo "</td></tr></table>";	
	}
	
  	function DisplayGeneralTab(&$tabs, $control_name="params")
	{
		//general start
		$tabs->StartTab("General","General-page");
		?>
		<div class="srConfigTab">
		
		<table>
		
		<?php
		
		
		//language
		$langModule =& $this->_AddonManager->GetModule('Language_Module' ,false);
		$languages = $langModule->GetLanguages();								
		$control = SRHtmlControls::SelectList( $languages, "params[languagefile]", 'class="inputbox"', 'value', 'text', _SR_GLOBAL_LANGUAGE);
		$text = $langModule->GetString($langModule, 'ConfigLanguage');
		$tip = $langModule->GetString($langModule, 'ConfigLanguageTip');
		$this->DisplaySingleTabParam($text, $tip, $control);
		
		
		//date format
		$control = SRHtmlControls::Text( 'dateformat', _SR_GLOBAL_DATE_FORMAT, $control_name );
		$text = $langModule->GetString($langModule, 'ConfigDateFormat');
		$tip  = $langModule->GetString($langModule, 'ConfigDateFormatTip');
		$this->DisplaySingleTabParam($text, $tip, $control);
	
		
		//use real name
		$control = SRHtmlControls::YesNoRadio( 'params[realname]', 'class="inputbox"', _SR_GLOBAL_USE_REAL_NAME );
		$text = $langModule->GetString($langModule, 'ConfigRealName');
		$tip  = $langModule->GetString($langModule, 'ConfigRealNameTip');		
		$this->DisplaySingleTabParam($text, $tip, $control);
		
		//SEO
		$control = SRHtmlControls::YesNoRadio( 'params[seo]', 'class="inputbox"', _SR_GLOBAL_SEO);
		$text = $langModule->GetString($langModule, 'ConfigSeo');
		$tip = $langModule->GetString($langModule, 'ConfigSeoTip');		
		$this->DisplaySingleTabParam($text, $tip, $control);	
	
		
		?>
		
		</table>
			
		</div>
		<?php		
		$tabs->EndTab(); 	
	}	
	
  function DisplayAdvancedTab(&$tabs, $control_name="params")
  {
		//general start
		$tabs->StartTab("Advanced","Advanced-page");
		?>
		<div class="srConfigTab">
		
		<table> 	
  			
		<?php 
		$control = SRHtmlControls::YesNoRadio( 'params[locktables]', 'class="inputbox"', _SR_GLOBAL_LOCK_TABLES );
		$text = 'Lock Tables';
		$tip = 'Recommended';		
		$this->DisplaySingleTabParam($text, $tip, $control);		
		?>  			
		
		</table>
			
		</div>
		<?php		
		$tabs->EndTab(); 
		//general end   
  }	
	
	function DisplayModuleTabs(&$tabs, $control_name="params")
	{	
		$allModules =& $this->_AddonManager->LoadAdminModules();
		$allPlugins =& $this->_AddonManager->LoadPlugins();	

		foreach($allModules as $module)
		{  
			if($module->showOnConfigScreen)
			{
				$friendlyName = $module->friendlyName;
				$tabs->StartTab($friendlyName,"$friendlyName-page");			
				?>
				<div class="srConfigTab">
					<h3><?php echo $friendlyName ;?></h3>		
					<?php echo $module->DisplayConfiguration($this,$allPlugins); ?>			
				</div>
				<?php
				$tabs->EndTab(); 
			}		
		}			
		//module end    	
	}	

	function DisplaySingleTabParam($text, $tip, $control)	
	{
		?>
		<!-- <?php echo $text;?> start-->
		<tr>
			<td>
			<?php echo Simple_Review_Common::CreateToolTip($text, $tip);?>
			</td>
			
			<td>
			<?php echo $control;?>
			</td>
		</tr>
		<!-- <?php echo $text;?>  end-->
		<?php    
	}	
	
  function DisplaySingleTabParamLine($description, $control)	
  {
    	?>
		<!-- <?php echo $description;?> start-->
		<tr>
			<td>
			<?php
			echo $description;
			?>
			</td>
			
			<td>
			<?php echo $control;?>
			</td>
		</tr>
		<!-- <?php echo $description;?>  end-->
		<?php    
  }	
  
	function SaveConfig ($apply = false)
	{
		global $option;
		$addonManager =& Addon_Manager::Get();
		//$params = mosGetParam( $_REQUEST, 'params', array(0) ,_MOS_ALLOWHTML);	
		$params = $addonManager->Bridge->GetParameter( $_REQUEST, 'params', array(0));	

		$allModules =& $addonManager->LoadAdminModules();
		foreach($allModules as $module)
		{
		  	if($module->showOnConfigScreen)
		  	{
		  		$module->SaveConfiguration($params);
		  	}
		}

		$configfile = $addonManager->Bridge->PathComponentAdministrator.'/config.simple_review.php';
		@chmod ($configfile, 0766);
		
		$permission = is_writable($configfile);
		
		if (!$permission) 
		{		
			$mosmsg = "Simple Review Config file not writeable!<br>$configfile";
			$addonManager->Bridge->Redirect("index2.php?option=$option&task=configuration",$mosmsg);
			return;
		}
	
		$config  = "<?php\n";
		
		$config .= "defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );\n";
		
		$config .= "/*\nThe contents of this file are subject to the Mozilla Public License\n"
		        ."Version 1.1 (the \"License\"); you may not use this file except in\n"
		        ."compliance with the License. You may obtain a copy of the License at\n"
		        ."http://www.mozilla.org/MPL/\n\n"
		        ."Software distributed under the License is distributed on an \"AS IS\"\n"
		        ."basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the"
		        ."License for the specific language governing rights and limitations\n"
		        ."under the License.\n\n"
		        ."The Original Code is Simple Review.\n\n"
		        ."The Initial Developer of the Original Code is Rowan J Youngson.\n"
		        ."Portions created by Rowan J Youngson are Copyright (C) December 17 2005.\n"
		        ."All Rights Reserved.\n\n"
		        ."Contributor(s): Rowan J Youngson.\n*/\n";


		//language
		$settingName = "_SR_GLOBAL_LANGUAGE";
		$config .="define('$settingName', '".$params['languagefile']."');\n";  
		
		//format to display the date, see http://dev.mysql.com/doc/mysql/en/date-and-time-functions.html\n";	
		$settingName = "_SR_GLOBAL_DATE_FORMAT";
		$config .="define('$settingName', '".$params['dateformat']."');\n"; 
		
		//real name
		$settingName = "_SR_GLOBAL_USE_REAL_NAME";
		$config .="define('$settingName', '".$params['realname']."');\n"; 	  			   
		
		//lock tables
		$settingName = "_SR_GLOBAL_LOCK_TABLES";
		$config .="define('$settingName', '".$params['locktables']."');\n"; 	  
		
		$config .= "\$sr_global['sr_sig'] = '<p><i><small>Powered by <a href=\'http://simple-review.com\'>Simple Review</a></small></i></p>';\n";
	  	  	
	
		//allow user review start
		//$allowuserreview = implode('||',$params['userreview']);
		//if didn't select any then make is super admin by default
		//if($allowuserreview =='')
		//{
		//	$allowuserreview = "super administrator";
		//}
		//$config .= "\$sr_global['userReview'] = '$allowuserreview';\n";	  
		//allow user review end
		
		//autopublish start
		//$autopublishuserreview = implode('||',$params['autopublishuserreview']);
		//if didn't select any then make is super admin by default
		//if($autopublishuserreview =='')
		//{
		//	$autopublishuserreview = "super administrator";
		//}
		//$config .= "\$sr_global['autoPublishUserReview'] = '$autopublishuserreview';\n";
		//autopublish end
		
		//$config .= "\$sr_global['forceUserReviewTemplate'] = '".$params['forceuserreviewtemplate']."';\n";
		
		//$config .= "\$sr_global['allowableTags'] = '".$params['allowableTags']."';\n";
		
		//$config .= "\$sr_global['reviewEmail'] = '".$params['reviewemail']."';\n";
		
		//$config .= "\$sr_global['defaultReviewTemplate'] = '".$defaultreviewtemplate."'; ";
		
		$settingName = '_SR_GLOBAL_SEO';
		$config .="define('$settingName', '".$params['seo']."');\n"; 		
		
		$settingName = '_SR_JQuery';
		$config .="define('$settingName', '1');\n"; 
		
		$config .= "\n?>";
	
	
		if ($fp = fopen($configfile, "w")) 
		{
			fputs($fp, $config, strlen($config));	
			fclose ($fp);	
		}

		if($apply)
		{
			$addonManager->Bridge->Redirect("index2.php?option=$option&task=configuration", "Settings saved");
		}
		else
		{
	  		$addonManager->Bridge->Redirect("index2.php?option=$option", "Settings saved");
		}
	} 
}
?>