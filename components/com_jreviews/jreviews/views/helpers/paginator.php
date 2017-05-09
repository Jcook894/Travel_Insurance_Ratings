<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Helper','form','jreviews');

class PaginatorHelper extends MyHelper {

	var $base_url = null;

	var $items_per_page;

	var $items_total;

	var $current_page;

	var $num_pages;

	var $mid_range = 6;

	var $num_pages_threshold = 10; // After this number the previous/next buttons show up.

	var $output = array('prev'=>'','prev_icon'=>'','pages'=>'','next'=>'','next_icon'=>'');

	var $return_module;

	var $default_limit = 25;

	var $pageUrl = array();

	function __construct()
	{
		$this->current_page = 1;

		$this->items_per_page = (!empty($_GET['limit'])) ? (int) $_GET['limit'] : $this->default_limit;
	}

	function initialize($params = array())
	{
		$this->mid_range = Sanitize::getInt($this->Config,'paginator_mid_range',6);

		if (count($params) > 0)
		{
			foreach ($params as $key => $val)
			{
				$this->$key = $val;
			}
		}

		# Construct new route

		if(isset($this->passedArgs) && is_null($this->base_url))
		{
			// Required for ajax filtering
			if ($this->ajaxRequest && _CMS_NAME == 'wordpress' && $searchUrl = Sanitize::getString($this->viewVars, 'search_url'))
			{
				$this->passedArgs['search_url'] = $searchUrl;
			}

			$this->base_url = cmsFramework::constructRoute($this->passedArgs,array(S2_QVAR_PAGE,'limit','lang'));
		}
	}

	function addPagination($page,$limit)
	{
		$url = '';

		$order = Sanitize::getString($this->params,'order');

		$default_limit = Sanitize::getInt($this->params,'default_limit');

		$url_params = $this->passedArgs;

		unset($url_params[S2_QVAR_PAGE],$url_params['Itemid'],$url_params['option'],$url_params['view']);

		if($page == 1
			&& $this->limit == $default_limit
			&& (
				$order == ''
				||
				$order == Sanitize::getString($this->params,'default_order')
			)
			&& empty($url_params)
		) {
			preg_match('/^index.php\?option=com_jreviews&amp;Itemid=[0-9]+/i',$this->base_url,$matches);

			$url = $matches[0];
		}
		else {

			$url = $this->base_url;

			$page > 1 and $url = rtrim($url,'/') . '/' . S2_QVAR_PAGE . _PARAM_CHAR . $page . '/';

			if($this->limit != $default_limit) {

				$url = rtrim($url,'/').'/limit'._PARAM_CHAR.$limit.'/';
			}
		}

		// Remove menu segment from url if page 1 and it' a menu
		if($page == 1 && preg_match('/^(index.php\?option=com_jreviews&amp;Itemid=[0-9]+)(&amp;url=menu\/)$/i',$url,$matches)) {

			$url = $matches[1];
		}

		$url = cmsFramework::route($url);

		return $url;
	}

	function sortArrayByArray($array,$orderArray) {

		$ordered = array();

		foreach($orderArray as $key) {

			if(array_key_exists($key,$array)) {

				$ordered[$key] = $array[$key];

				unset($array[$key]);
			}

		}

		return $ordered + $array;
	}

	function setStartEndRange()
	{
        $half = (int)($this->mid_range / 2);

        $end = $this->current_page + $half + $this->mid_range%2 /* adds one for odd ranges */;

        $pageCount = $this->num_pages;

        if ($pageCount <= $this->num_pages_threshold)
        {
        	$this->range = range(0, $pageCount - 1);
        }
        else {
	        if ($end > $pageCount)
	        {
	            $end = $pageCount;
	        }

	        $start = $this->current_page - ($this->mid_range - ($end - $this->current_page));

	        if ($start <= 1)
	        {
	            $start = 1;

	            $end = $this->current_page + ($this->mid_range - $this->current_page) + 1;
	        }

			$range = range($start,$end+1);

	        $this->range = $range;

			if ($this->current_page < 1 + $this->mid_range)
			{
				$this->range = array_splice($this->range,0,$this->mid_range);
			}
			elseif($this->current_page > $this->num_pages - $this->mid_range + 2) {
				$this->range = array_splice($this->range,1,$this->mid_range+1);
			}
			else {
				$this->range = array_splice($this->range,0,$this->mid_range);
			}
        }
	}

	function paginate($params)
	{
		$this->initialize($params);

		if(!is_numeric($this->items_per_page) OR $this->items_per_page <= 0) {
			$this->items_per_page = $this->default_limit;
		}

		$this->num_pages = ceil($this->items_total/$this->items_per_page);

		if($this->current_page < 1 || !is_numeric($this->current_page)) $this->current_page = 1;

		if($this->current_page > $this->num_pages) $this->current_page = $this->num_pages;

		$prev_page = $this->current_page-1;

		$next_page = $this->current_page+1;

		$url = $this->addPagination($prev_page,$this->items_per_page);

		$this->setStartEndRange();

		// PREVIOUS PAGE

		$this->output['prev'] = ($this->current_page != 1 && $this->items_total >= 10) ?

			'<a class="jr-pagenav-prev jrPageActive jrButton jrSmall" href="'.$url.'">'.__t("&laquo;",true).'</a> '

			:

			'<span class="jrPagePrev jrButton jrSmall jrDisabled">'.__t("&laquo;",true).'</span>&nbsp;';

		$this->output['prev_icon'] = $this->current_page != 1 ?

			'<a class="jr-pagenav-prev jrPageActive jrButton jrIconOnly" href="'.$url.'"><span class="jrIconLeft"></span></a> '

			:

			'<span class="jrPagePrev jrButton jrIconOnly jrDisabled"><span class="jrIconLeft"></span></span>&nbsp;';


		// INDIVIDUAL PAGES

		for($i=1;$i<=$this->num_pages;$i++)
		{
			// loop through all pages. if first, last, or in range, display

			if($i==1 || ($this->num_pages <= $this->num_pages_threshold && $i == $this->num_pages) || (in_array($i, $this->range)))
			{
				$url = $this->addPagination($i,$this->items_per_page);

				$this->output['pages'][$i] = ($i == $this->current_page) ?

					'<span title="'.sprintf(__t("Go to page %s",true),$i,$i,$this->num_pages).'" class="jr-pagenav-current jrPageCurrent jrButton jrSmall">'.$i.'</span> '

					:

					'<a class="jr-pagenav-page jrButton jrSmall" title="'.sprintf(__t("Go to page %s of %s",true),$i,$this->num_pages).'" href="'.$url.'">'.$i.'</a> ';

				$this->pageUrl[$i] = $url;
			}
		}

		// NEXT PAGE

		$url = $this->addPagination($next_page,$this->items_per_page);

		$this->output['next'] = ($this->current_page != $this->num_pages && $this->items_total >= 10) ?

			'<a class="jr-pagenav-next jrButton jrSmall" href="'.$url.'">'.__t("&raquo;",true).'</a>'

			:

			'<span class="jrPageNext jrButton jrSmall jrDisabled">'.__t("&raquo;",true).'</span>';

		$this->output['next_icon'] = $this->current_page != $this->num_pages ?

			'<a class="jr-pagenav-next jrButton jrIconOnly" href="'.$url.'"><span class="jrIconRight"></span></a>'

			:

			'<span class="jrPageNext jrButton jrIconOnly jrDisabled"><span class="jrIconRight"></span></span>';
	}

	/**
	 * Generates the dropdown list for number of items per page
	 * @return html select list
	 */
	function display_items_per_page()
	{
		if(!$this->Config->display_list_limit) return;

		$items_per_page = array(5,10,15,20,25,30,35,40,45,50);

		$Form = ClassRegistry::getClass('FormHelper');

		$segments = '';

		$url_param = array();

		$passedArgs = $this->passedArgs;

		$default_limit = Sanitize::getInt($this->params,'default_limit');

		foreach($items_per_page as $limit)
		{
			if($limit != $default_limit) {
				$url = rtrim($this->base_url,'/') . '/limit' . _PARAM_CHAR . $limit . '/';
			}
			else {
				$url = $this->base_url;
			}
			$url = cmsFramework::route($url);

			$selectList[] = array('value'=>$url,'text'=>$limit);
		}

		if($this->limit != $default_limit) {
			$selected = rtrim($this->base_url,'/') . '/limit' . _PARAM_CHAR . $this->limit . '/';
		}
		else {
			$selected = $this->base_url;
		}

		$selected = cmsFramework::route($selected);

		return __t("Results per page",true). ': ' . $Form->select('order_limit',$selectList,$selected,array('class'=>'jr-pagenav-limit','onchange'=>"window.location=this.value"));
	}

	function display_pages($isMobile = false)
	{
		$pages = '';

		if ($isMobile)
		{
			$output = str_replace('jrSmall','',$this->output['prev_icon'].$this->output['next_icon']);
		}
		else {

			foreach ($this->output['pages'] AS $i => $page)
			{
				if($this->num_pages > $this->num_pages_threshold && $this->range[0] > 2 && $i == $this->range[0]) {
					$pages .= " ... ";
				}

				$pages .= $page;

				if($this->num_pages > $this->num_pages_threshold && $this->range[$this->mid_range-1] < $this->num_pages-1 && $i == end($this->range) && $i != $this->num_pages) {

					$pages .= " ... ";
				}
			}

			if ($this->num_pages > $this->num_pages_threshold)
			{
				$output = $this->output['prev'].$pages.$this->output['next'];
			}
			else {
				$output = $pages;
			}
		}

		return $output;
	}

	function getPageUrl($page) {

		return str_replace('&amp;','&',$this->pageUrl[$page]);
	}

	/**
	 *	http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
	 * @param type $page
	 */
	function addPrevNextUrls(&$page) {

		if($this->num_pages > 1) {

			if($this->current_page == 1) {

				$page['next_url'] = $this->getPageUrl(2);

			}
			elseif($this->current_page == $this->num_pages) {

				$page['prev_url'] = $this->getPageUrl($this->num_pages-1);
			}
			else {

				$page['prev_url'] = $this->getPageUrl($this->current_page-1);

				$page['next_url'] = $this->getPageUrl($this->current_page+1);
			}
		}
	}
}
