<?php
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

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

App::import('Helper','form','jrexpress');

class PaginatorHelper extends MyHelper {
	
	var $base_url = null;
	var $items_per_page;
	var $items_total;
	var $current_page;
	var $num_pages;
	var $mid_range = 7;
	var $return;
	var $return_module;
	var $module_id = 0;
	var $default_limit = 25;
	
	function __construct()
	{
		$this->current_page = 1;
		$this->items_per_page = (!empty($_GET['limit'])) ? (int) $_GET['limit'] : $this->default_limit;

	}
	
	function initialize($params = array())
	{
		if (count($params) > 0)
		{
			foreach ($params as $key => $val)
			{
				$this->$key = $val;
			}		
		}
		
		# Construct new route
		if(isset($this->passedArgs) && is_null($this->base_url))
			$this->base_url = cmsFramework::constructRoute($this->passedArgs,array('page','limit')); 		
	}
	
	function addPagination($page,$limit) {
		return cmsFramework::route(rtrim($this->base_url,'/') . '/' . 'page'._PARAM_CHAR.$page.'/limit'._PARAM_CHAR.$limit.'/');
	}
	
	function paginate($params, $scrollToId = 'page')
	{		
		$this->return = '';

		$this->initialize($params);

		if(!is_numeric($this->items_per_page) OR $this->items_per_page <= 0) {
			$this->items_per_page = $this->default_limit;
		}
		
		$this->num_pages = ceil($this->items_total/$this->items_per_page);

		if($this->current_page < 1 || !is_numeric($this->current_page)) $this->current_page = 1;

		if($this->current_page > $this->num_pages) $this->current_page = $this->num_pages;

		$prev_page = $this->current_page-1;
		
		$next_page = $this->current_page+1;

		# More than 10 pages
		if($this->num_pages > 10)
		{
			// PREVIOUS PAGE
			if($this->xajaxRequest) {
				$onclick = "jQuery('#{$scrollToId}').scrollTo(500,100);
							xajax.$('page_number').value=$prev_page;xajax.$('limit').value={$this->items_per_page};
							xajax.request({xjxfun:'xajaxDispatch'},{URI:'".getXajaxUri('jrexpress')."',parameters:[xajax.$('controller').value,xajax.$('action').value,'{$this->app}',xajax.getFormValues('adminForm')]});
							return false;";				
				$this->return = ($this->current_page != 1 && $this->items_total >= 10) ? '<a class="paginate" href="javascript:void(0);" onclick="'.$onclick.'">'.__t("&laquo; Previous",true).'</a> ' : '<span class="inactive" href="#">'.__t("&laquo; Previous",true).'</span>';				
			} else {
				$url = $this->addPagination($prev_page,$this->items_per_page);
				$this->return = ($this->current_page != 1 && $this->items_total >= 10) ? '<a class="paginate" href="'.$url.'">'.__t("&laquo; Previous",true).'</a> ' : '<span class="inactive" href="#">'.__t("&laquo; Previous",true).'</span> ';				
			}

			$this->start_range = $this->current_page - floor($this->mid_range/2);

			$this->end_range = $this->current_page + floor($this->mid_range/2);

			if($this->start_range <= 0)
			{
				$this->end_range += abs($this->start_range)+1;
				$this->start_range = 1;
			}
			if($this->end_range > $this->num_pages)
			{
				$this->start_range -= $this->end_range-$this->num_pages;
				$this->end_range = $this->num_pages;
			}
			$this->range = range($this->start_range,$this->end_range);

			// INDIVIDUAL PAGES
			for($i=1;$i<=$this->num_pages;$i++)
			{
				if($this->range[0] > 2 && $i == $this->range[0]) $this->return .= " ... ";
				
				// loop through all pages. if first, last, or in range, display
				if($i==1 Or $i==$this->num_pages || in_array($i,$this->range))
				{
					if($this->xajaxRequest) {
						$onclick = "jQuery('#{$scrollToId}').scrollTo(500,100);
									xajax.$('page_number').value=$i;xajax.$('limit').value={$this->items_per_page};
									xajax.request({xjxfun:'xajaxDispatch'},{URI:'".getXajaxUri('jrexpress')."',parameters:[xajax.$('controller').value,xajax.$('action').value, '{$this->app}', xajax.getFormValues('adminForm')]});
									return false;";
						$this->return .= ($i == $this->current_page) ?
						'<a title="'.sprintf(__t("Go to page %s",true),$i,$i,$this->num_pages).'" class="current" href="#">'.$i.'</a> ' : 
						'<a class="paginate" title="'.sprintf(__t("Go to page %s of %s",true),$i,$this->num_pages).'" href="javascript:void(0);" onclick="'.$onclick.'">'.$i.'</a> ';
						
					} else {
						$url = $this->addPagination($i,$this->items_per_page);	
						$this->return .= ($i == $this->current_page) ? 
						'<a title="'.sprintf(__t("Go to page %s",true),$i,$i,$this->num_pages).'" class="current" href="#">'.$i.'</a> ' : 
						'<a class="paginate" title="'.sprintf(__t("Go to page %s of %s",true),$i,$this->num_pages).'" href="'.$url.'">'.$i.'</a> ';
					}
				}
				
				if($this->range[$this->mid_range-1] < $this->num_pages-1 && $i == $this->range[$this->mid_range-1]) $this->return .= " ... ";
			}
			
			// NEXT PAGE
			if($this->xajaxRequest) {		
				$onclick = "jQuery('#{$scrollToId}').scrollTo(500,100);
							xajax.$('page_number').value=$next_page;xajax.$('limit').value={$this->items_per_page};
							xajax.request({xjxfun:'xajaxDispatch'},{URI:'".getXajaxUri('jrexpress')."',parameters:[xajax.$('controller').value,xajax.$('action').value, '{$this->app}',xajax.getFormValues('adminForm')]});
							return false;";
				$this->return .= ($this->current_page != $this->num_pages && $this->items_total >= 10) ? 
				"<a class=\"paginate\" href=\"javascript:void(0);\" onclick=\"$onclick\">".__t("Next &raquo;",true)."</a>\n" : "<span class=\"inactive\" href=\"#\">".__t("Next &raquo;",true)."</span>\n";			
			} else {
				$url = $this->addPagination($next_page,$this->items_per_page);			
				$this->return .= ($this->current_page != $this->num_pages && $this->items_total >= 10) ? 
				"<a class=\"paginate\" href=\"$url\">".__t("Next &raquo;",true)."</a>\n" : "<span class=\"inactive\" href=\"#\">".__t("Next &raquo;",true)."</span>\n";				
			}
		
		}
		# 10 pages or less
		else {

			// INDIVIDUAL PAGES			
			for($i=1;$i<=$this->num_pages;$i++)
			{
				// Ajax request
				if($this->xajaxRequest) {
					$onclick = "jQuery('#{$scrollToId}').scrollTo(500,100);
								xajax.$('page_number').value=$i;xajax.$('limit').value={$this->items_per_page};
								xajax.request({xjxfun:'xajaxDispatch'},{URI:'".getXajaxUri('jrexpress')."',parameters:[xajax.$('controller').value,xajax.$('action').value, '{$this->app}',xajax.getFormValues('adminForm')]});
								return false;";
					$this->return .= ($i == $this->current_page) ? '<a class="current" href="#">'.$i.'</a> ' : '<a class="paginate" href="javascript:void(0);" onclick="'.$onclick.'">'.$i.'</a> ';
				// Get request
				} else {
					$url = $this->addPagination($i,$this->items_per_page);
					$this->return .= ($i == $this->current_page) ? '<a class="current" href="#">'.$i.'</a> ' : '<a class="paginate" href="'.$url.'">'.$i.'</a> ';
				}
				
			}
		}
		
		# -------------------------------------------------------------------
		# Module Ajax Navigation
		# -------------------------------------------------------------------
		if(isset($this->params['module']))
		{
			$extension = Sanitize::getString($this->params['module'],'extension');
			$action = $this->action;
			// PREVIOUS PAGE
			$onclick = "jQuery('#jr_modContainer{$this->module_id}').fadeTo(1500,0.5);
						xajax.request({xjxfun:'xajaxDispatch'},{URI:'".getXajaxUri('jrexpress')."',
						callback: fadeInModule,module_id:'{$this->module_id}',
						parameters:['{$this->name}','{$action}', '{$this->app}',{data:{extension:'{$extension}',module_page:{$prev_page},module_limit:{$this->items_per_page},module_id:'{$this->module_id}'}}]});
						return false;";		
			$this->return_module = ($this->current_page != 1) ? 
									'<a class="paginate" href="javascript:void(0);" onclick="'.$onclick.'">&lt;</a> ' : '<span class="inactive">&lt;</span> ';
		
			// NEXT PAGE
			$onclick = "jQuery('#jr_modContainer{$this->module_id}').fadeTo(1500,0.5);
						xajax.request({xjxfun:'xajaxDispatch'},{URI:'".getXajaxUri('jrexpress')."',
						callback: fadeInModule,module_id:'{$this->module_id}',
						parameters:['{$this->name}','{$action}', '{$this->app}',{data:{extension:'{$extension}',module_page:{$next_page},module_limit:{$this->items_per_page},module_id:'{$this->module_id}'}}]});
						return false;";
			
			$this->return_module .= ($this->current_page != $this->num_pages) ? 
									"<a class=\"paginate\" href=\"javascript:void(0);\" onclick=\"$onclick\">&gt;</a>" : "<span class=\"inactive\">&gt;</span>";			
									
		}
	}

	/**
	 * Generates the dropdown list for number of items per page
	 *
	 * @param string $scrollToId
	 * @return html select list
	 */
	function display_items_per_page($scrollToId = 'page', $items_per_page = array(5,10,15,20,25,30,35,40,45,50))
	{
		$Form = RegisterClass::getInstance('FormHelper');
		
		$segments = '';
		$url_param = array();
		$passedArgs = $this->passedArgs;
		
		if($this->xajaxRequest) 
		{					
			foreach($items_per_page as $limit) {
				$selectList[] = array('value'=>$limit ,'text'=>$limit);
			}
	
			$selected = Sanitize::getInt($this->data,'limit');
			
			$onchange = "jQuery('#{$scrollToId}').scrollTo(500,100);"
			. " setPage(1);"
			. "setLimit(this.value);"
			. "xajax.request({xjxfun:'xajaxDispatch'},{URI:'".getXajaxUri('jrexpress')."',parameters:[xajax.$('controller').value,xajax.$('action').value, '{$this->app}',xajax.getFormValues('adminForm')]});"
			;

			return __t("Results per page",true). ': ' . $Form->select('order_limit',$selectList,$selected,array('onchange'=>$onchange));			
		
		} else {
			
			foreach($items_per_page as $limit) {
				$selectList[] = array('value'=>cmsFramework::route($this->base_url . '/page' . _PARAM_CHAR . '1/limit' . _PARAM_CHAR . $limit . (cmsFramework::mosCmsSef() ? '' : '/')) ,'text'=>$limit);
			}

			$selected = cmsFramework::route($this->base_url . '/page' . _PARAM_CHAR . '1/limit' . _PARAM_CHAR . $this->limit . (cmsFramework::mosCmsSef() ? '' : '/'));

			return __t("Results per page",true). ': ' . $Form->select('order_limit',$selectList,$selected,array('onchange'=>"window.location=this.value"));
						
		}
		
	}

	function display_pages()
	{
		return $this->return;
	}
	
	function display_pages_module() {
		return $this->return_module;
	}
}