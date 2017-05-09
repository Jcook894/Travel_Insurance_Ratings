<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

App::import('Helper','form','jreviews');

class PaginatorHelper extends MyHelper {
    
    var $base_url = null;
    var $items_per_page;
    var $items_total;
    var $current_page;
    var $num_pages;
    var $mid_range = 6;
    var $num_pages_threshold = 10; // After this number the previous/next buttons show up.
    var $return;
    var $return_module;
    var $module_id = 0;
    var $default_limit = 25;
    var $controller = null;
    var $action = 'index';
    var $ajax_scroll = true; // Scrolls up to defined element on ajax pagination
    var $scroll_id = 'page';
    var $form_id = 'adminForm'; // Target form having all paginator related inputs
    var $update_id = 'page'; // Id of element to update with the new page info
	var $pageUrl = array();
    
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

		$this->mid_range = Sanitize::getInt($this->Config,'paginator_mid_range',6);

		# Construct new route
        if(isset($this->passedArgs) && is_null($this->base_url))
            $this->base_url = cmsFramework::constructRoute($this->passedArgs,array('page','limit','lang'));         
    }
    
    function addPagination($page,$limit) 
    {         
        if(cmsFramework::isAdmin()) /* no need for sef urls in admin */
        {
            $url = rtrim($this->base_url,'/') . ($page > 1 ? '/' . 'page'._PARAM_CHAR.$page.'/limit'._PARAM_CHAR.$limit.'/' : '');
        }
        else 
        {
			$order = Sanitize::getString($this->params,'order');
			$default_limit = Sanitize::getInt($this->params,'default_limit');
			
			$url_params = $this->passedArgs;
			unset($url_params['page'],$url_params['Itemid'],$url_params['option'],$url_params['view']);
			
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
				
				$page > 1 and $url = rtrim($url,'/') . '/' . 'page'._PARAM_CHAR . $page . '/';
				
				if($this->limit != $default_limit) {
					$url = rtrim($url,'/').'/limit'._PARAM_CHAR.$limit.'/';
				}
			}

			// Remove menu segment from url if page 1 and it' a menu
			if($page == 1 && preg_match('/^(index.php\?option=com_jreviews&amp;Itemid=[0-9]+)(&amp;url=menu\/)$/i',$url,$matches)) {
				$url = $matches[1];
			}
			
			$url = cmsFramework::route($url);	
			

		}
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
    
    function paginate($params, $update_id = 'page')
    {                     
        $this->return = '';
        $this->update_id = $update_id;
        $this->initialize($params);

        if(!is_numeric($this->items_per_page) OR $this->items_per_page <= 0) {
            $this->items_per_page = $this->default_limit;
        }
        
        $this->num_pages = ceil($this->items_total/$this->items_per_page);

        if($this->current_page < 1 || !is_numeric($this->current_page)) $this->current_page = 1;

        if($this->current_page > $this->num_pages) $this->current_page = $this->num_pages;

        $prev_page = $this->current_page-1;
        
        $next_page = $this->current_page+1;

        # More than num_pages_threshold pages
        if($this->num_pages > $this->num_pages_threshold)
        {
            // PREVIOUS PAGE
            if($this->ajaxRequest) 
				{
                $onclick = "
                    " . ($this->ajax_scroll ? "jQuery('#".$this->scroll_id."').scrollTo(500,100);" : '')."        
                    var \$form = jQuery(this).parents('form');
                    \$form.find('input[name=&#34;data[page]&#34;]').val({$prev_page});
                    \$form.find('input[name=&#34;data[limit]&#34;]').val({$this->items_per_page});
                    \$form.find('input[name=&#34;data[action]&#34;]').val('{$this->action}');
                    jQuery.post(s2AjaxUri,\$form.serialize(),function(s2Out){jQuery('#{$this->update_id}').html(s2Out);},'html');
                    return false;
                ";                

                $this->return = ($this->current_page != 1 && $this->items_total >= 10) ? '<a class="paginate" href="javascript:void(0);" onclick="'.$onclick.'">'.__t("&laquo;",true).'</a> ' : '<span class="inactive">'.__t("&laquo;",true).'</span>';                
            } 
			else {
                $url = $this->addPagination($prev_page,$this->items_per_page);
				
                $this->return = ($this->current_page != 1 && $this->items_total >= 10) ? '<a class="paginate" href="'.$url.'">'.__t("&laquo;",true).'</a> ' : '<span class="inactive">'.__t("&laquo;",true).'</span> ';
				
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
                    if($this->ajaxRequest) {
                        $onclick = "
                            " . ($this->ajax_scroll ? "jQuery('#".$this->scroll_id."').scrollTo(500,100);" : '') ."        
                            var \$form = jQuery(this).parents('form');
                            \$form.find('input[name=&#34;data[page]&#34;]').val({$i});
                            \$form.find('input[name=&#34;data[limit]&#34;]').val({$this->items_per_page});
                            \$form.find('input[name=&#34;data[action]&#34;]').val('{$this->action}');
                            jQuery.post(s2AjaxUri,\$form.serialize(),function(s2Out){jQuery('#{$this->update_id}').html(s2Out);},'html');
                            return false;
                        ";
                                    
                        $this->return .= ($i == $this->current_page) ?
                        '<a title="'.sprintf(__t("Go to page %s",true),$i,$i,$this->num_pages).'" class="current" href="#">'.$i.'</a> ' : 
                        '<a class="paginate" title="'.sprintf(__t("Go to page %s of %s",true),$i,$this->num_pages).'" href="javascript:void(0);" onclick="'.$onclick.'">'.$i.'</a> ';
                        
                    } else {
						
                        $url = $this->addPagination($i,$this->items_per_page);    
                        
						$this->return .= ($i == $this->current_page) ? 
                        '<a title="'.sprintf(__t("Go to page %s",true),$i,$i,$this->num_pages).'" class="current" href="#">'.$i.'</a> ' : 
                        '<a class="paginate" title="'.sprintf(__t("Go to page %s of %s",true),$i,$this->num_pages).'" href="'.$url.'">'.$i.'</a> ';
						
						$this->pageUrl[$i] = $url;
                    }
                }
                
                if($this->range[$this->mid_range-1] < $this->num_pages-1 && $i == $this->range[$this->mid_range-1]) $this->return .= " ... ";
            }
            
            // NEXT PAGE
            if($this->ajaxRequest) {        
                $onclick = "
                    " . ($this->ajax_scroll ? "jQuery('#".$this->scroll_id."').scrollTo(500,100);" : '') ."  
                    var \$form = jQuery(this).parents('form');
                    \$form.find('input[name=&#34;data[page]&#34;]').val({$next_page});
                    \$form.find('input[name=&#34;data[limit]&#34;]').val({$this->items_per_page});
                    \$form.find('input[name=&#34;data[action]&#34;]').val('{$this->action}');
                    jQuery.post(s2AjaxUri,\$form.serialize(),function(s2Out){jQuery('#{$this->update_id}').html(s2Out);},'html');
                    return false;
                ";

                $this->return .= ($this->current_page != $this->num_pages && $this->items_total >= 10) ? 
                "<a class=\"paginate\" href=\"javascript:void(0);\" onclick=\"$onclick\">".__t("&raquo;",true)."</a>\n" : "<span class=\"inactive\" href=\"#\">".__t("Next &raquo;",true)."</span>\n";            
            } 
			else {
            
				$url = $this->addPagination($next_page,$this->items_per_page);            
                
				$this->return .= ($this->current_page != $this->num_pages && $this->items_total >= 10) ? 
                "<a class=\"paginate\" href=\"$url\">".__t("&raquo;",true)."</a>\n" : "<span class=\"inactive\" href=\"#\">".__t("&raquo;",true)."</span>\n";                
            }
        
        }
        # num_pages_threshold pages or less
        else {

            // INDIVIDUAL PAGES            
            for($i=1;$i<=$this->num_pages;$i++)
            {
                // Ajax request
                if($this->ajaxRequest) 
				{
                    $onclick = "
                        " . ($this->ajax_scroll ? "jQuery('#".$this->scroll_id."').scrollTo(500,100);" : '') . "
                        var \$form = jQuery(this).parents('form');
                        \$form.find('input[name=&#34;data[page]&#34;]').val({$i});
                        \$form.find('input[name=&#34;data[limit]&#34;]').val({$this->items_per_page});
                        \$form.find('input[name=&#34;data[action]&#34;]').val('{$this->action}');
                        jQuery.post(s2AjaxUri,\$form.serialize(),function(s2Out){jQuery('#{$this->update_id}').html(s2Out);},'html');
                        return false;
                    ";
                                                            
                    $this->return .= ($i == $this->current_page) ? '<span class="inactive">'.$i.'</span> ' : '<a class="paginate" href="javascript:void(0);" onclick="'.$onclick.'">'.$i.'</a> ';
                // Get request
                } else {
                    
					$url = $this->addPagination($i,$this->items_per_page);
                    
					$this->return .= ($i == $this->current_page) ? '<span class="inactive">'.$i.'</span> ' : '<a class="paginate" href="'.$url.'">'.$i.'</a> ';

					$this->pageUrl[$i] = $url;
                }
                
            }
        }
    
	}

    /**
     * Generates the dropdown list for number of items per page
     * @return html select list
     */
    function display_items_per_page()
    {
        if(!defined('MVC_FRAMEWORK_ADMIN') && !$this->Config->display_list_limit) return;
		
		$args = func_get_args();
        
		if(func_num_args()==2) // For compat with old themes that had the update id var as the 1st param
        {
            $this->update_id = array_shift($args);
            $items_per_page = array_shift($args);            
        } else {
            $items_per_page = array(5,10,15,20,25,30,35,40,45,50);            
        }
        
        $Form = ClassRegistry::getClass('FormHelper');
        
        $segments = '';
        $url_param = array();
        $passedArgs = $this->passedArgs;

        if($this->ajaxRequest) 
        {                    
            foreach($items_per_page as $limit) {
                $selectList[] = array('value'=>$limit ,'text'=>$limit);
            }
    
            $selected = $this->limit; //Sanitize::getInt($this->data,'limit');
            
            $onchange = 
                ($this->ajax_scroll ? "jQuery('#".$this->scroll_id."').scrollTo(500,100);" : '') . 
                "        
                var \$form = jQuery(this).parents('form');
                \$form.find('input[name=&#34;data[page]&#34;]').val(1);
                \$form.find('input[name=&#34;data[limit]&#34;]').val(this.value);
                \$form.find('input[name=&#34;data[action]&#34;]').val('{$this->action}');
                jQuery.post(s2AjaxUri,\$form.serialize(),function(s2Out){jQuery('#{$this->update_id}').html(s2Out);},'html');
            ";

            return __t("Results per page",true). ': ' . $Form->select('order_limit',$selectList,$selected,array('onchange'=>$onchange));            
        
        } else {
                  
			$default_limit = Sanitize::getInt($this->params,'default_limit');

            foreach($items_per_page as $limit) 
            {
                if(defined('MVC_FRAMEWORK_ADMIN'))
                {
                    $url = $this->base_url . 'limit' . _PARAM_CHAR . $limit;
                }
                else {
					if($limit != $default_limit) {
						$url = rtrim($this->base_url,'/') . '/limit' . _PARAM_CHAR . $limit . '/';
					}
					else {
						$url = $this->base_url;
					}
					$url = cmsFramework::route($url);
				}

				$selectList[] = array('value'=>$url,'text'=>$limit);
            }

            if(defined('MVC_FRAMEWORK_ADMIN'))
            {
                $selected = $this->base_url . 'limit' . _PARAM_CHAR . $this->limit;
            }
            else
            {
				if($this->limit != $default_limit) {
					$selected = rtrim($this->base_url,'/') . '/limit' . _PARAM_CHAR . $this->limit . '/';
				}
				else {
					$selected = $this->base_url;
				}

				$selected = cmsFramework::route($selected);
            }

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
	
	function getPageUrl($page) {
		return $this->pageUrl[$page];
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
