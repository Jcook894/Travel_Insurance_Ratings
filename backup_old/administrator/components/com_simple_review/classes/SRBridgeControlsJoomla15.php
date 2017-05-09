<?php
/**
 *  $Id$
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
require_once('SRBridgeControlsBase.php');
class SRHtmlControls extends SRHtmlControlsBase
{
	function DropDownList($name, $items, $selected, $displayStrings=null)
	{
		$ddlItems = array();
		$i = 0;
		if($displayStrings == null || count($items) != count($displayStrings))
		{
			$displayStrings = $items;
		}	  
        foreach ($items as $item)
		{
			$ddlItems[] = JHTML::_('select.option', $item, $displayStrings[$i]);
			$i++;
        }
      
		return JHTML::_('select.genericlist', $ddlItems, "params[$name]", 'class="inputbox"', 'value', 'text', $selected, false, false );			
	}	
	
	function EditorGetContents($editorArea, $hiddenField)
	{
		jimport( 'joomla.html.editor' );
		$editor =& JFactory::getEditor();
		echo $editor->save( $hiddenField );
	}
	
	function EditorInsertArea($name, $content, $hiddenField, $width, $height, $col, $row)	
	{
		jimport( 'joomla.html.editor' );
		$editor =& JFactory::getEditor();
		echo $editor->display($hiddenField, $content, $width, $height, $col, $row);		
	}
	
	function IDBox($rowNum, $recId, $checkedOut=false, $name='cid' )
	{		
		return JHTML::_('grid.id',$rowNum, $recId, $checkedOut, $name  );
	}
	
	function Option($value, $text='', $value_name='value', $text_name='text', $disable=false)
	{
		return JHTML::_('select.option', $value, $text, $value_name, $text_name, $disable);
	}
	
	function SelectList( &$arr, $tag_name, $tag_attribs, $key, $text, $selected=NULL, $idtag=false, $flag=false )
	{
		return JHTML::_('select.genericlist', $arr, $tag_name, $tag_attribs, $key, $text, $selected, $idtag, $flag );
	}
			
	function YesNoRadio($tag_name, $tag_attribs, $selected, $yes="Yes", $no="No")
	{
		return JHTML::_('select.booleanlist',  $tag_name, $tag_attribs, $selected, $yes, $no, false ) ;
	}	
}

class SRControls extends SRControlsBase
{

}

class SRPager extends SRPagerBase
{	
	function SRPager($total, $defaultLimit=30)
	{
		global $mainframe, $option;;

		$this->total = $total;									
		$this->limit		= $mainframe->getUserStateFromRequest("$option.limit", 'limit', $defaultLimit/*$mainframe->getCfg('list_limit')*/, 0);
		$this->limitStart	= JRequest::getVar('limitstart', 0, '', 'int');			
		if ( $this->total <= $this->limit ) 
		{ 
		    $this->limitStart = 0;
		} 	
		jimport('joomla.html.pagination');	
		$this->pageNav = new JPagination($this->total, $this->limitStart, $this->limit);
	}
	
	function GetPagesLinks($link=null)
	{
		return $this->pageNav->getPagesLinks();			
	}
	
	function GetLimitBox($link=null)
	{
		return $this->pageNav->getLimitBox();

	}
	
	function GetOverview()
	{
		return $this->pageNav->getPagesCounter();
	}
}

class SRBridgeMenuBar extends SRBridgeMenuBarBase
{
       
    function Config()
	{
		JToolBarHelper::back();
		JToolBarHelper::save('saveConfig');
		JToolBarHelper::apply('applyConfig');
		JToolBarHelper::spacer();
	}	
	     
	function ModuleNew()
	{
      JToolBarHelper::back();
      JToolBarHelper::apply('apply');
      JToolBarHelper::save('save');
      JToolBarHelper::cancel( 'list', 'Cancel' );
      JToolBarHelper::spacer();		
	}	
	
	function ModuleEdit()
	{
      JToolBarHelper::back();
      JToolBarHelper::apply('apply');
      JToolBarHelper::save('save');
      JToolBarHelper::cancel( 'list', 'Cancel' );
      JToolBarHelper::spacer();		
	}	
	
    function ModuleList()
	{
      //JToolBarHelper::startTable();
      JToolBarHelper::publishList('publish');
      JToolBarHelper::unpublishList('unpublish');
      JToolBarHelper::divider();
      JToolBarHelper::addNew('new');
      JToolBarHelper::editList('edit', 'Edit');      
      JToolBarHelper::deleteList( '', 'delete', 'Remove' );		
	}	
}
class SRTabbedContainer extends SRTabbedContainerBase
{
	function SRTabbedContainer($useCookies=false)
	{
		jimport('joomla.html.pane');
		//$this->_Tabs =&  JPane::getInstance('sliders');
		$this->_Tabs =& JPane::getInstance('tabs');	
		//$this->_Tabs =& JPane::getInstance('tabs', array('startOffset'=>0));	
	}
	function StartPane($name)
	{
		echo parent::StartPane($name);	
	}	
	
	function EndPane()
	{
		echo parent::EndPane();	
	}	
	
	function StartTab($title,$id)
	{
		echo $this->_Tabs->startPanel($title,$id);
	}
		
	function EndTab()
	{
		echo $this->_Tabs->endPanel(); 
	}		
}
?>