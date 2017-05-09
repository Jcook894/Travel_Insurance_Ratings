<?php
/**
 *  $Id: SRBridgeControlsJoomla10.php 79 2009-05-11 13:50:57Z rowan $
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
require_once("SRBridgeControlsBase.php");

class SRHtmlControls extends SRHtmlControlsBase
{
	function DropDownList($name, $items, $selected, $displayStrings=null)
	{
		$mosItems = array();
		$i = 0;
		if($displayStrings == null || count($items) != count($displayStrings))
		{
			$displayStrings = $items;
		}	  
        foreach ($items as $item)
		{
             $mosItems[] = mosHTML::makeOption( $item, $displayStrings[$i]);
			 $i++;
        }
      
        return mosHTML::selectList( $mosItems, "params[$name]", 'class="inputbox"', 'value', 'text', $selected );	
	}
	
	function EditorGetContents($editorArea, $hiddenField)
	{
		getEditorContents($editorArea, $hiddenField);
	}
	
	function EditorInsertArea($name, $content, $hiddenField, $width, $height, $col, $row)	
	{
		editorArea($name, $content, $hiddenField, $width, $height, $col, $row);		
	}	
	
	function IDBox($rowNum, $recId, $checkedOut=false, $name='cid' )
	{
		return mosHTML::idBox( $rowNum, $recId, $checkedOut, $name );
	}
	
	function Option($value, $text='', $value_name='value', $text_name='text', $disable=false)
	{
		return mosHTML::makeOption($value, $text, $value_name, $text_name);
	}
	
	function SelectList( &$arr, $tag_name, $tag_attribs, $key, $text, $selected=NULL, $idtag=false, $flag=false )
	{
		if($idtag != false)
		{
			$tag_attribs.= " id='$idtag'";
		}
		mosHTML::selectList( $arr, $tag_name, $tag_attribs, $key, $text, $selected );
	}
	
	function YesNoRadio($tag_name, $tag_attribs, $selected, $yes="Yes", $no="No")
	{
		return mosHTML::yesnoRadioList($tag_name, $tag_attribs, $selected, $yes, $no);
	}	
}

class SRControls extends SRControlsBase
{

}

class SRPager extends SRPagerBase
{
	function SRPager($total, $defaultLimit=30)
	{
		global $mosConfig_absolute_path, $mainframe, $option;;
		$this->total = $total;				
		$this->limit 	= intval( mosGetParam( $_REQUEST, 'limit', $defaultLimit ) );
		$this->limitStart = intval( mosGetParam( $_REQUEST, 'limitstart', 0 ) );      						

		if ( $this->total <= $this->limit ) 
		{ 
		    $this->limitStart = 0;
		} 
		
		require_once( "$mosConfig_absolute_path/includes/pageNavigation.php" );			
		$this->pageNav = new mosPageNav( $this->total, $this->limitStart, $this->limit  );						

	}
	
	function GetPagesLinks($link=null)
	{
		return $this->pageNav->writePagesLinks($link);	
	}
	
	function GetLimitBox($link=null)
	{	
		return $this->pageNav->getLimitBox($link);
	}
	
	function GetOverview()
	{
		return $this->pageNav->writePagesCounter();	
	}	
}

class SRBridgeMenuBar extends SRBridgeMenuBarBase
{
       
    function Config()
	{
      mosMenuBar::startTable();
      mosMenuBar::back();
      mosMenuBar::save('saveConfig');
	  mosMenuBar::apply('applyConfig');
      mosMenuBar::endTable();
	}	
	     
	function ModuleNew()
	{
      mosMenuBar::startTable();
      mosMenuBar::back();
      mosMenuBar::apply('apply');
      mosMenuBar::save('save');
      mosMenuBar::cancel( 'list', 'Cancel' );
      mosMenuBar::spacer();
      mosMenuBar::endTable();	 		
	}	
	
	function ModuleEdit()
	{
      mosMenuBar::startTable();
      mosMenuBar::back();
      mosMenuBar::apply('apply');
      mosMenuBar::save('save');
      mosMenuBar::cancel( 'list', 'Cancel' );
      mosMenuBar::spacer();
      mosMenuBar::endTable();	
	}	
	
    function ModuleList()
	{
      mosMenuBar::startTable();
      mosMenuBar::publishList('publish');
      mosMenuBar::unpublishList('unpublish');
      mosMenuBar::divider();
      mosMenuBar::addNew('new');
      mosMenuBar::editList('edit', 'Edit');
      //mosMenuBar::deleteList( 'Are you sure you want to delete this item?', 'delete', 'Remove' );
      mosMenuBar::deleteList( '', 'delete', 'Remove' );
      mosMenuBar::endTable();		
	}	
}

class SRTabbedContainer extends SRTabbedContainerBase
{
	function SRTabbedContainer($useCookies=false)
	{
		$this->_Tabs = new mosTabs( $useCookies );
	}
	
	function StartTab($title,$id)
	{
		$this->_Tabs->startTab($title,$id);
	}
		
	function EndTab()
	{
		$this->_Tabs->endTab(); 
	}			
}
?>