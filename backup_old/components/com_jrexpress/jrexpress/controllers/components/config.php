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

class ConfigComponent extends S2Component {
	var $version = null;
//general tab
	var $community = null;
	var $name_choice = "realname";
	var $vote_summarize_period = "1";
	var $rating_scale = "5";
	var $rating_selector = "select"; // stars
	var $rating_graph = "1";
	var $template = "default";
	var $template_path = "/components/com_jrexpress";
	var $xajax_debug = 0;
	var $xajax_encoding = "ISO-8859-1";
	var $load_prototype = 1;
	var $load_scriptaculous = 1;
	var $load_xajax = 1;
	var $load_lightbox = "1";
	var $load_slimbox = "0";
	var $favorites_enable = 1;
	var $transliterate_urls = 1;	
//access tab
	var $security_image = "0,18";
	var $moderation = "0";	// no longer used
	var $registration = "1"; // no longer used
	var $moderation_item = "0,18";
	var $moderation_reviews = "0,18";
	var $editaccess = "24,25";
	var $editaccess_reviews = "24,25";
	var $addnewaccess = "24,25";
	var $addnewaccess_reviews = "18,19,20,21,23,24,25";
	var $addnewwysiwyg = "24,25";
	var $addnewmeta = "24,25";
	var $user_vote_public = "0,18,19,20,21,23,24,25";
	var $user_multiple_reviews	= "1";
	var $user_owner_disable	= "0";	
//directory tab
	var $dir_show_breadcrumb = "1";
	var $dir_show_alphaindex = "1";
	var $dir_columns = "2";
	var $dir_cat_num_entries = "1";
	var $dir_cat_format = "0";
	var $dir_section_order = "1";
	var $dir_category_order = "1";
	var $dir_category_limit = "0";
	var $dir_category_hide_empty = "0";
//item list tab
	var $list_display_type = "0";
	var $list_display_type_joomla = "blogjoomla_simple";
	var $list_show_addnew	= "1";
	var $list_show_sectionlist = "1";
	var $list_show_searchbox = "1";
	var $list_show_orderselect = "1";
	var $list_order_default	= "alpha";
	var $list_show_categories = "1"; // category list
	var $list_show_categories_section = "1"; // section list
	var $list_show_date = "1";
	var $list_show_author = "1";
	var $list_show_user_rating = "1";
	var $list_show_hits = "1";
	var $list_show_readmore = "1";
	var $list_show_readreviews = "1";
	var $list_show_newreview = "1";
	var $list_show_image = "1";
	var $list_image_resize = "150";
	var $list_category_image = "0";
	var $list_noimage_image = "0";
	var $list_noimage_filename = "noimage.png";
	var $list_show_abstract = "1";
	var $list_abstract_trim = "30";
	var $list_new = "1";
	var $list_new_days = "10";
	var $list_hot = "1";
	var $list_hot_hits = "1000";
	var $list_featured = "1";
	var $cat_columns = "3";
	var $list_limit = "10";
	//reviews tab
	var $location = "0";
	var $location_places = "";
	var $reviewform_title = "1";
	var $reviewform_email = "1";
	var $reviewform_comment = "1";
		//=> author reviews
	var $author_review = "0";
	var $authorids = "62";
	var $author_vote = "1";
	var $author_report = "1";
	var $author_forum = "";
	var $author_ratings = "1"; // detailed ratings box
	var $author_rank_link = "1";
	var $author_myreviews_link = "1";
    var $editor_rank_exclude = "0";
		// => user reviews
	var $user_reviews = "1";
	var $user_vote = "1";
	var $user_report = "1";
	var $user_forum = "";
	var $user_ratings = "1"; // detailed ratings box
	var $user_rank_link = "1";
	var $user_myreviews_link = "1";
	var $user_limit = "5";
	//images tab
	var $content_images = "4";
	var $content_images_edit = "1";
	var $content_images_total_limit = "0";
	var $content_max_imgsize = "300";
	var $content_max_imgwidth = "0";
	var $content_thumb_size = "65";
	var $content_intro_img_size = "230";
	var $content_intro_img = "1";
	var $content_default_image = "0";
	//forms tab
	var $content_title_duplicates = 'category';
	var $content_title = "1";
	var $content_summary = "required";
	var $content_description = "optional";
	var $content_pathway = "1";
	var $content_show_reviewform = "authors";
	//search tab
	var $search_itemid = "1";
	var $search_display_type = "0";
	var $search_tmpl_suffix = "";
	var $search_item_author = "1";
	var $search_field_conversion = "0";
	//notification tab
	var $notify_review = "0";
	var $notify_content = "0";
	var $notify_report = "0";
	var $notify_review_emails;
	var $notify_content_emails;
    var $notify_report_emails;
    var $notify_user_listing = "0";
    var $notify_user_listing_emails;
    var $notify_owner_review = "0";
    var $notify_owner_review_emails;
    var $notify_user_review = "0";
    var $notify_user_review_emails;    
	//rss tab
	var $rss_enable = "0";
	var $rss_limit = "10";
	var $rss_title;
	var $rss_image;
	var $rss_description;
	var $rss_item_images= "0";
	var $rss_item_image_align = "right";
	//seo manager
	var $seo_title = "0";
	var $seo_description = "0";
	//seo manager
	var $cache_disable = "1";
	var $cache_query = "1";
	var $cache_expires = "3600";
	var $cache_view = "0";
	var $cache_assets = "0";	
    var $file_registry = "1";	
	
	function startup(&$controller)
	{
		if($Config = Configure::read('JreviewsSystem.Config'))
		{
			$this->merge($Config);
		}else{
			$cache_file = 'jrexpress_config_'.md5(cmsFramework::getConfig('secret'));
			
			$Config = S2Cache::read($cache_file);

			if(false == $Config || empty($Config)) {
				$Config = $this->load();
				S2Cache::write($cache_file,$Config);
			}
			$this->merge($Config);				
			Configure::write('JreviewsSystem.Config',$Config);
		}

		Configure::write('System.version',strip_tags($this->version));
		Configure::write('Theme.name',$this->template);
		Configure::write('Community.extension', $this->community);
		Configure::write('Cache.enable',!(bool)$this->cache_disable);
		Configure::write('Cache.disable',(bool)$this->cache_disable);
		Configure::write('Cache.expires',$this->cache_expires);
		Configure::write('Cache.query',(bool)$this->cache_query);
		Configure::write('Cache.view',(bool)$this->cache_view);
		Configure::write('Cache.assets',(bool)$this->cache_assets);
        Configure::write('Jreviews.editor_rank_exclude',(bool)$this->editor_rank_exclude);
	}
	
	function load() {
		
		$Model = new MyModel();
		
		$Config = new stdClass();
		
		$Model->_db->setQuery("SELECT id, value FROM #__jreviews_config");
		
		$rows = $Model->_db->loadObjectList();
				
		if ($rows)
		{
			foreach ($rows as $row)
			{
				$prop = $row->id;
				$Config->$prop = stripcslashes($row->value);
			}
		}		
		
		$Config->rss_title = @$Config->rss_title != '' ? $Config->rss_title : cmsFramework::getConfig('sitename');
		$Config->rss_description = @$Config->rss_description != '' ? $Config->rss_description : cmsFramework::getConfig('MetaDesc');
		
		# Get current version number
		$xml = file(S2Paths::get('jrexpress', 'S2_CMS_ADMIN') . 'jrexpress.xml');
		
		foreach($xml AS $xml_line) {
			if(strstr($xml_line,'version')) {
				$version = trim($xml_line);
				continue;
			}
		}
		
		$Config->version = $version;

		return $Config;	
		
	}
	
	function merge(&$Config) {
		foreach($Config AS $key=>$value) {
			$this->{$key} = $value;
		}
	}

	function store()
	{
		$cache_file = 'jrexpress_config_'.md5(cmsFramework::getConfig('secret'));
//		clearCache($cache_file,'__data','');
		clearCache('', 'views');
		clearCache('', '__data');		
		clearCache('', 'assets');
		
		$Model = new MyModel();
		
		$arr = get_object_vars($this);
		
		while (list($prop, $val) = each($arr)) 
		{
			if($prop != 'c') 
			{
				$Model->_db->setQuery(
					 "update #__jreviews_config "
					 . "\n SET value='".$val."'"
					 . "\n WHERE id = '".$prop."'"
				);
				
				if (!$Model->_db->query()) {
					echo "<br/>".$Model->_db->getErrorMsg();
					exit;
				}
				
				$Model->_db->setQuery(
					"select count(*) from #__jreviews_config ".
					 "where id = '".$prop."'"
				);
				
				$saved = $Model->_db->loadResult();
				
				if (!$saved) 
				{
					$Model->_db->setQuery(
						"insert into #__jreviews_config (id, value) ".
						"values ('".$prop."', '".addcslashes($val,"\0..\37!@\@\177..\377")."')"
					);
					
					if (!$Model->_db->query()) {
						echo "<br/>".$Model->_db->getErrorMsg();
						exit;
	
					}				
				}
			}			
		}
	}

	function bindRequest($request)
	{
		$arr = get_object_vars($this);
		while (list($prop, $val) = each($arr))
			$this->$prop = Sanitize::getVar($request, $prop, $val);
	} // bindRequest
}