<?php
/**
 *  $Id: SRBridgeControlsBase.php 89 2009-05-26 14:30:50Z rowan $
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

class SRHtmlControlsBase
{	
	function CheckBox($name, $id, $value, $checked=false)
	{
		if($checked)
		{
			$checked = 	"checked";
		}
		else
		{
			$checked = "";
		}
		return "<input type='checkbox' name='params[$name]' id='$id' value='$value' $checked />";
	}

	function DropDownList($name, $items, $selected, $displayStrings=null)
	{
		trigger_error('This class function has not been implemented!',E_USER_ERROR);
	}
	
	function EditorGetContents($editorArea, $hiddenField)
	{
		trigger_error('This class function has not been implemented!',E_USER_ERROR);
	}
	
	function EditorInsertArea($name, $content, $hiddenField, $width, $height, $col, $row)	
	{
		trigger_error('This class function has not been implemented!',E_USER_ERROR);
	}
	
	function IDBox($rowNum, $recId, $checkedOut=false, $name='cid' )
	{		
		trigger_error('This class function has not been implemented!',E_USER_ERROR);
	}	
	
	function Option($value, $text='', $value_name='value', $text_name='text', $disable=false)
	{
		trigger_error('This class function has not been implemented!',E_USER_ERROR);
	}	
	
	function SelectList( &$arr, $tag_name, $tag_attribs, $key, $text, $selected=NULL, $idtag=false, $flag=false )
	{
		trigger_error('This class function has not been implemented!',E_USER_ERROR);
	}	
	
	//'params[userreview][]',string[], 'MULTIPLE', string[]
	function SelectListBasic($listname, $items,  $tag_attribs='MULTIPLE', $selected)
	{
		$html = "<select name='params[$listname][]' class='inputbox' $tag_attribs>";
		
		if(count($items) > 0)
		{		  				
	        foreach ($items as $item)
			{
	          $html .= "<option value='$item'";
	          if (in_array ($item, $selected)) 
	          {
			  	$html.= "selected";
			  }
	          $html.= ">$item</option>";
	        }
		}
        $html.="</select>";
        
        return $html;
	}	
	
	function Text( $name, $value, $control_name, $size='25' ) {
		return '<input type="text" name="'. $control_name .'['. $name .']" value="'. $value .'" class="text_area" size="'. $size .'"/>';
	}
	
	function TextArea( $name, $value, $control_name, $rows='20', $cols='70' ) {
 		// convert <br /> tags so they are not visible when editing
 		$value 	= str_replace( '<br />', "\n", $value );

 		return '<textarea name="params['. $name .']" cols="'. $cols .'" rows="'. $rows .'" class="text_area">'. $value .'</textarea>';
	}	
	
	function YesNoRadio($tag_name, $tag_attribs, $selected, $yes="Yes", $no="No")
	{
		trigger_error('This class function has not been implemented!',E_USER_ERROR);
	}		
}

class SRControlsBase
{
	/*
	* Creates an admin listing of objects (reviews, categories etc)
	* @param formName 		: The name of the form
	* @param formAction		: The form action
	* @param columnNames	: An array of names/titles of the columns
	* @param propertyNames	: An array of the properties on the objects
	* @param rows			: An array of objects to print
	* @param task			: The forms default task
	* @param module			: The module object which corresponds to the objects
	
	* Note columnNames[0] and propertyNames[0] must refer to the objects id
	* Note to display a link the propertyName[N] should be an array ...
	
	* Example:
  	$formName						= "adminForm";	
  	$formAction						= "index2.php";
		
	$columnNames					= Array();
	
	$columnNames[]					= "Review ID";
	$columnNames[]					= "Title 1";
	$columnNames[]					= "Title 2";
	$columnNames[]					= "Title 3";		
	$columnNames[]					= "Published";
	$columnNames[]					= "Category";
	$columnNames[]					= "Score";
	$columnNames[]					= "Created By";
	
	$propertyNames					= Array();
	
	$propertyNames[]				= "reviewID";
	$propertyNames[]				= Array('title1');
	$propertyNames[]				= Array('title2');
	$propertyNames[]				= Array('title3');
	$propertyNames[]				= 'published';
	$propertyNames[]				= 'categoryName';
	$propertyNames[]				= 'score';
	$propertyNames[]				= 'createdBy';	
	*/
	function ItemListing($formName, $formAction, &$columnNames, &$propertyNames, &$rows, $task, $module, $pageNav=null)	    	
	{
		global $option;
		$bridge =& SRBridgeManager::Get();
		?>
				  
		<form action="<?php echo $formAction;?>" method="post" name="<?php echo $formName;?>">
		<table class="adminheading">
		<tr>
			<th><?php echo $module->friendlyName;?></th>
		</tr>
		</table>

		<table class="adminlist">
		
		<!--header-->
		<tr>
			<th width="5">
			#
			</th>

			<th align="left">
			<input type="checkbox" name="toggle" value="" onClick="checkAll('<?php echo count($rows);?>');" />
			</th>
			
			<?php		
			for ($i=0, $n=count( $columnNames ); $i < $n; $i++)			
			{
					echo '<th align="left">';
					echo $columnNames[$i];
					echo '</th>';
			}				
			?>
		</tr>
		
		<!--contents-->
		<?php
		$k = 0;
		for ($i=0, $n=count( $rows ); $i < $n; $i++) 
		{
			$row = &$rows[$i];	
			echo "<tr>";
			
			//#
			$count = $i+1;
			echo "<td>$count</td>";	
			
			$id = $propertyNames[0];
			
			echo "<td>".SRHtmlControls::IDBox( $i, $row->{$id}, false, $id )."</td>";	
				
			for ($k=0, $m=count( $propertyNames ); $k < $m; $k++)			
			{
					echo '<td>';
					$column = $propertyNames[$k];
					$islink = false;
					if(is_array($column))
					{
					  	$column = $column[0];
					  	$islink = true;
					}
					if($column == 'published')
					{
						$publishTask 	= $row->published ? 'unpublish' : 'publish';
						$img 	= $row->published ? 'publish_g.png' : 'publish_x.png';
						$img =  $bridge->SiteUrl."administrator/images/$img";
						$alt 	= $row->published ? 'Published' : 'Unpublished';
					
						 ?>
						 <a href="javascript: void(0);" onclick="return listItemTask('cb<?php echo $i;?>','<?php echo $publishTask;?>')">
					
						<img src="<?php echo $img;?>" width="12" height="12" border="0" alt="<?php echo $alt; ?>" />
						</a>
					<?php						  
					}
					else
					{
						$stripedCol = Simple_Review_Common::RemoveSlashes($row->{$column});
					  	if($islink)
					  	{						    
						    $link =  $module->GetURL("edit", "$id=".$row->{$id});
						    echo "<a href='$link'>$stripedCol</a>";
						}
						else
						{
							echo $stripedCol;
						}
					}
					echo '</td>';					
			}			
			echo "</tr>";		  
		}
		?> 
		</table> 
		<?php if($pageNav!=null) 
			{
				echo $pageNav->GetPagesLinks();
				echo $pageNav->GetLimitBox();
				echo $pageNav->GetOverview();
			} 
		?> 
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="task" value="<?php echo $task;?>" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="hidemainmenu" value="0">
		<input type="hidden" name="module" value="<?php echo $module->addonName;?>" />
		
		</form>	
			
		<?php
		}		    	
}

class SRPagerBase
{
	var $limit;
	var $limitStart;
	var $total;
	var $pageNav;

	function SRPagerBase($total, $defaultLimit=30)
	{
	    if(strtolower(get_class($this))=='srpagerbase' || !is_subclass_of ($this,'SRPagerBase'))
		{
	        trigger_error('This class is abstract. It cannot be instantiated!',E_USER_ERROR);
	    }		
	}
	
	function GetPagesLinks($link=null){}
	function GetLimitBox($link=null){}
	function GetOverview(){}
}

class SRBridgeMenuBarBase
{

	function SRBridgeMenuBarBase()
	{
	    if(strtolower(get_class($this))=='srbridgemenubarbase' || !is_subclass_of ($this,'SRBridgeMenuBarBase'))
		{
	        trigger_error('This class is abstract. It cannot be instantiated!',E_USER_ERROR);
	    }		
	}
	        
    function Config(){}	     
	function ModuleNew(){}	
	function ModuleEdit(){}	
    function ModuleList(){}	
}

class SRTabbedContainerBase
{
	var $_Tabs = null;
	function SRTabbedContainerBase($useCookies=false)
	{
	    if(strtolower(get_class($this))=='srtabbedcontainerbase' || !is_subclass_of ($this,'SRTabbedContainerBase'))
		{
	        trigger_error('This class is abstract. It cannot be instantiated!',E_USER_ERROR);
	    }		
	}
	
	function StartPane($name)
	{
		return $this->_Tabs->startPane($name);	
	}	
	
	function EndPane()
	{
		return $this->_Tabs->endPane();	
	}	
	
	function StartTab($title,$id){}	
	function EndTab(){}	
}
?>