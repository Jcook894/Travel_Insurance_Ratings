<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

App::import('Controller','common','jreviews');

class ModuleAdvancedSearchController extends MyController {
	
	var $uses = array('menu','field','category');
	
	var $helpers = array('libraries','html','assets','form','custom_fields');
	
	var $components = array('config','access');

	var $autoRender = false;
	
	var $autoLayout = false;
	
	var $fieldTags;
		
	function beforeFilter() 
    {
        parent::beforeFilter();
											
		$this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');
	
		# Set Theme	
		$this->viewTheme = $this->Config->template;
		$this->viewImages = S2Paths::get('jreviews', 'S2_THEMES_URL'). $this->viewTheme . _DS . 'theme_images' . _DS;	
		
		# Set Theme Vars
		$search_itemid = Sanitize::getInt($this->params['module'],'search_itemid');
		if($search_itemid) {
			$this->set('search_itemid',$search_itemid);
		}
	}
	
	/**
	 * Dynamically replace the field tags with their labels/form field equivalents
	 */
	function afterFilter() 
    {
		$output = &$this->output;
		$names = array();
		$labels = array();
		$select = array();
		$cat_tag = false;
		$date_field = false;
        $cat_auto = Sanitize::getInt($this->params['module'],'cat_auto');
        $dir_id = $section_id = $cat_id = $criteria_id = '';
		
		# Initialize FormHelper
		$Form = new FormHelper();
		$CustomFields = new CustomFieldsHelper();
		$CustomFields->Config = &$this->Config;
        
		# Process custom field tag attributes
		foreach($this->fieldTags AS $key=>$value) 
		{
			$var = explode('|',$value);

			if(!strstr($value,'_label')) {

				$names[$var[0]] = $value;
			
			} elseif (strstr($value,'_label')) {
				
				$labels[] = substr($value,0,-6);
				
			}
			
			if($value == 'category') {
				
				$cat_tag = true;
/************************/	
				if(isset($var[1]) && $var[1] == 'm') {
					$category_select_type = ' multiple="multiple"';
				}
				
				if(isset($var[2]) && (int) $var[2] > 0) {
					$category_select_size = ' size="'.$var[2].'"';
				}				
/************************/								
			}

			if (isset($var[1]) && strtolower($var[1]) == 'm') {         
				$select[$var[0]] = 'selectmultiple';
			} elseif (isset($var[1]) && strtolower($var[1]) == 's') {
				$select[$var[0]] = 'select';
			}
			
			$select_size[$var[0]] = isset($var[2]) ? $var[2] : 5;
					
			# Check for category select list
			if($var[0] == 'category') {
				if(isset($var[1]) && strtolower($var[1]) == 's') {
					$category_select_type=' multiple="multiple"';
				}
				if(isset($var[2]) && (int) $var[2] > 0) {
					$category_select_size = ' size="'.$var[2].'"';
				}
				
			}
		}

		# Get selected values from url
		$entry = array();
		foreach($this->params AS $key=>$value) {
			if(substr($key,0,3) == 'jr_') {
				$entry['Field']['pairs'][$key]['value'] = explode('_',$value);
			}
			// Add categories/sections
		}
        
		if(isset($this->params['tag'])) {
			$entry['Field']['pairs']['jr_'.$this->params['tag']['field']]['value'] = array($this->params['tag']['value']);
		}
		
		# Generate category list if tag found in view
		if($cat_tag)                  
        {		
            # Get module params before auto-detect
            $param_cat_id = Sanitize::getString($this->params['module'],'cat_id');
            $param_dir_id = Sanitize::getString($this->params['module'],'dir_id');
            $param_type_id = Sanitize::getString($this->params['module'],'criteria_id');

            # Category auto detect
            $ids = CommonController::_discoverIDs($this);
            if($cat_auto) 
            {            
                extract($ids);
            }
            elseif($this->cmsVersion != CMS_JOOMLA15) /* In J1.5 this breaks the auto-detect feature */
            {
                isset($ids['cat_id']) and $cat_id = $ids['cat_id'];
            }
            
            if($this->cmsVersion == CMS_JOOMLA15 && $section_id == '' && $cat_id != '')
            {
                $sql = "SELECT section FROM #__categories WHERE id IN (".$cat_id.")";
                $this->_db->setQuery($sql);
                $section_id = $this->_db->loadResult();                                  
            }
                                        
            $cat_id != '' and $this->params['module']['cat_id'] = $cat_id;
            $cat_id == '' and $section_id != '' and $this->params['module']['section_id'] = $section_id;                    
            $cat_id == '' and $criteria_id != '' and $this->params['module']['criteria_id'] = $criteria_id;                    
           
            if($this->cmsVersion == CMS_JOOMLA15)
            {
                $categorySelect = $this->Category->categoryTree($this->_user->gid, $this->params);
            }
            else 
            {     
                $options = array(
                    'disabled'=>false,
                    'cat_id'=>!empty($param_cat_id) && !$cat_auto ? $param_cat_id : ($cat_auto ? $cat_id : ''),
                    'parent_id'=>!empty($param_cat_id) && !$cat_auto ? $param_cat_id : ($cat_auto ? $cat_id : ''),
                    'dir_id'=>!empty($param_dir_id) && !$cat_auto ? $param_dir_id : ($cat_auto ? $dir_id : ''),
                    'type_id'=>!empty($param_type_id) && !$cat_auto ? $param_type_id : ($cat_auto ? $criteria_id : '')
                );   

                if($cat_auto && empty($options['cat_id'])) {
                    $options['level'] = 1;
                }

                $categories = $this->Category->getCategoryList($options);

                // Now get the parent and sibling categories
                if($cat_auto && isset($categories[$cat_id]) && count($categories) == 1) {
                    $options['cat_id'] = $options['parent_id'] = $categories[$cat_id]->parent_id;
                    $categories = $this->Category->getCategoryList($options);
                }

                $categorySelect = $Form->select(
                    'data[categories]',
                    array_merge(array(array('value'=>null,'text'=>'- '.__t("Select Category",true).' -')),$categories),
                    $cat_id,
                    array('class'=>'jrSelect')/*attributes*/
                );                
            }
             
			$output = str_replace('{'.$names['category'].'}',$categorySelect,$output);			
		}

		$fields = $this->Field->getFieldsArrayFromNames(array_keys($names),'listing',$entry);
         
		# Replace label tags and change field type based on view atttributes
		if($fields)
		{
			foreach($fields AS $key=>$group) {
				
				foreach($group['Fields'] AS $name=>$field) {
	                                                        
					if(/*isset($field['optionList']) && */isset($select[$name])) 
					    {		
						    $fields[$key]['Fields'][$name]['type'] = $select[$name];
						    $fields[$key]['Fields'][$name]['properties']['size'] = $select_size[$name];		
					    } 
                    elseif($fields[$key]['Fields'][$name]['type'] == 'textarea') 
                        {
                            $fields[$key]['Fields'][$name]['type'] = 'text';
                        }
	
					if(in_array($name,$labels)) {
						$output = str_replace('{'.$name.'_label}',$field['title'],$output);
					}

					if($field['type']=='date') {
						$date_field = true;
					}
				}			
			}
         
			$search = true;
			$location = 'listing';
            
            $CustomFields->form_id = Sanitize::getInt($this->params,'module_id');
            
			$formFields = $CustomFields->getFormFields($fields, $location, $search, __t("Select",true));

			# Replace input tags
            foreach($names AS $key=>$name) 
            {            
                if(isset($formFields["data[Field][Listing][{$key}]"])) {
                    $output = str_replace('{'.$names[$key].'}',$formFields["data[Field][Listing][{$key}]"],$output);
                }
            }
			
			# Load js and css			
			if($date_field) 
            {
				$Html = ClassRegistry::getClass('HtmlHelper');
				$Html->app = 'jreviews';
				$Libraries = ClassRegistry::getClass('LibrariesHelper');
				$Html->js(arrayFilter(array('jq.ui.core','jq.ui.datepicker'), $Libraries->js()));
				$Html->css(arrayFilter(array('jq.ui.core'), $Libraries->css()),false);
                ?>
                <script type="text/javascript">jreviews.datepicker();</script>
                <?php
			}		
		}

		return $output;
	}
	
	function index()
	{		
        $file = S2Object::locateThemeFile('modules','advanced_search');
		$this->fieldTags = $this->extractTags(file_get_contents($file));
		return $this->render('modules','advanced_search');
	}

	function extractTags($view) {

		$pattern = '/{([a-z0-9_|]*)}/i';

		$matches = array();

		$result = preg_match_all( $pattern, $view, $matches );
        
		if( $result == false ) {		
			return array();
		}

		return array_unique(array_values($matches[1]));
	}
}