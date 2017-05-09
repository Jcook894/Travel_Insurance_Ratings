<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

/**
* All required css/js assets are conviniently defined here per controller and controller action (per page)
*/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );
        
class AssetsHelper extends MyHelper
{
    var $helpers = array('html','libraries','custom_fields','editor');
    var $assetParams = array();
    /**
    * These arrays can be set at the controller level 
    * and in plugin callbacks with any extra css or js files that should be loaded
    * 
    * @var mixed
    */
    var $assets = array('js'=>array(),'css'=>array());
    
    function load()
    {                          
        $assetParams = func_get_args();
        $this->assetParams = array_merge($this->assetParams,$assetParams);
        $methodAction = Inflector::camelize($this->name.'_'.$this->action);
        $methodName = Inflector::camelize($this->name);
                  
        if(method_exists($this,$methodAction)){
            $this->{$methodAction}();
        } elseif(method_exists($this,$methodName)) {
            $this->{$methodName}();            
        } elseif(!empty($this->assets))
        {
            $this->send($this->assets);
        }
    }
    
    function send($assets,$inline=false)
    {         
        # Load javascript libraries
        $findjQuery = false;
        $this->Html->app = $this->app;
        
        unset($this->viewVars); 
        
        /**
        * Send cachable scripts to the head tag from controllers and components by adding it to the head array
        */
        if(!empty($this->assets['head-top'])) {
            foreach($this->assets['head-top'] AS $head) {
                cmsFramework::addScript($head);
            }
        }
                
        // Incorporate controller set assets before sending
        if(!empty($this->assets['js'])) 
            $assets['js'] = array_merge($assets['js'],$this->assets['js']);
  
        if(!empty($this->assets['css'])) 
            $assets['css'] = array_merge($assets['css'],$this->assets['css']);
			$assets['css'][] = 'custom_styles';	
            
        cmsFramework::isRTL() and $assets['css'][] = 'rtl';
         
        # Load CSS stylesheets
        if(isset($assets['css']) && !empty($assets['css']))
        {       
            $findjQueryUI = array_search('jq.ui.core',$assets['css']);
            if($findjQueryUI !== false)
            {
                if (defined('J_JQUERYUI_LOADED')) {
                    unset($assets['css'][array_search('jq.ui.core',$assets['css'])]);
                } else {
                    define( 'J_JQUERYUI_LOADED', 1 );  
                }
            } 
            $this->Html->css(arrayFilter($assets['css'], $this->Libraries->css()),$inline);                             
        } 
                
         // For CB 
         // Check is done against constants defined in those applications
        if(isset($assets['js']) && !empty($assets['js']))
        {
            $findjQuery = array_search('jquery',$assets['js']);
            $findjQueryUI = array_search('jq.ui.core',$assets['js']);

            if($findjQuery !== false)
            {
                if (defined('J_JQUERY_LOADED')
                    || JFactory::getApplication()->get('jquery') 
                    /*|| defined('C_ASSET_JQUERY')*/) 
                {
                    unset($assets['js'][$findjQuery]);
                } else {
                    define( 'J_JQUERY_LOADED', 1 ); 
//                    JFactory::getApplication()->set('jquery', true); This was for Warp, but it loads too late. jQuery must be manually disabled in the configuration                        
//                    define( 'C_ASSET_JQUERY', 1 );
                }
            }
            
            if($findjQueryUI != false) 
            {
                $locale = cmsFramework::locale();   
                $assets['js'][] = 'jquery/i18n/jquery.ui.datepicker-' . $locale;
            }               
        }

        if(isset($assets['js']) && !empty($assets['js']))
        {
            $this->Html->js(arrayFilter($assets['js'], $this->Libraries->js()),$inline);            
        }

        # Set jQuery defaults
        if($findjQuery && isset($assets['js']['jreviews'])){
        ?>
            <script type="text/javascript">
            /* <![CDATA[ */
            jreviews.ajax_init();
            /* ]]> */
            </script>
        <?php            
        }

        if(isset($this->Config) && Sanitize::getBool($this->Config,'ie6pngfix'))
        {
            $App = &App::getInstance($this->app);
            $AppPaths = $App->{$this->app.'Paths'};            
            $jsUrl = isset($AppPaths['Javascript']['jquery/jquery.pngfix.pack.js']) ? $AppPaths['Javascript']['jquery/jquery.pngfix.pack.js'] : false;         
            if($jsUrl)
            {
                cmsFramework::addScript('<!--[if lte IE 6]><script type="text/javascript" src="'.$jsUrl.'"></script><script type="text/javascript">jQuery(document).ready(function(){jQuery(document).pngFix();});</script><![endif]-->');        
            }
            unset($App,$AppPaths);
        }
        
        /**
        * Send cachable scripts to the head tag from controllers and components by adding it to the head array
        */
        if(!empty($this->assets['head'])) {
            foreach($this->assets['head'] AS $head) {
                cmsFramework::addScript($head);
            }
        }
    }

/**********************************************************************************
 *  Categories Controller
 **********************************************************************************/   
     function Categories()             
     {                                 
        $assets = array(
            'js'=>array('jreviews','jquery','jreviews.compare','jq.ui.core','jq.json','jq.jsoncookie','jq.scrollable','jq.tooltip'),
            'css'=>array('theme','theme.list','paginator','jq.ui.core')
        );
        
        $User = cmsFramework::getUser();
        $User->id > 0 and array_push($assets['js'],'jq.jreviews.plugins');     
        ?>

        <script type="text/javascript">     
        /* <![CDATA[ */
        jQuery(document).ready(function() {                                                 
            jreviewsCompare.set({
                'numberOfListingsPerPage':<?php echo Sanitize::getInt($this->Config,'list_compare_columns',3);?>,
				'maxNumberOfListings' : 15,
                'compareURL':'<?php echo cmsFramework::route('index.php?option=com_jreviews&url=categories/compare/type:type_id/');?>'
            });
            <?php if($this->action == 'compare'):?>jreviewsCompare.initComparePage();<?php endif; ?>    
            jreviewsCompare.initCompareDashboard();
            <?php if($this->action != 'compare'): ?>jreviewsCompare.initListingsSelection();<?php endif; ?>
        });
        /* ]]> */
        </script>   
       
		<?php
        $this->send($assets);        
     }
         
/**********************************************************************************
 *  ComContent Controller
 **********************************************************************************/ 
    function ComContentComContentView()
    {     
        $assets = array(
            'js'=>array('jreviews','jreviews.compare','jquery','jq.ui.core','jq.jreviews.plugins','jq.fancybox','jq.json','jq.jsoncookie','jq.scrollable'),
            'css'=>array('theme','modules'/* for related listings */,'custom_styles_modules','theme.detail','theme.form','paginator','jq.ui.core','jq.fancybox')
        );                     
                
        if($this->Access->canAddReview() || $this->Access->isEditor()) 
        {                 
            if($this->Config->rating_selector == 'stars'){
                $assets['js'][] = 'jq.ui.rating';
            }            
            
            $assets['js'][] = 'jq.tooltip';
            $assets['js'][] = 'jreviews.fields';
        }
        
        $facebook_id = Sanitize::getString($this->Config,'facebook_appid');
        $facebook_opengraph = Sanitize::getBool($this->Config,'facebook_opengraph',true);
        $facebook_xfbml = $facebook_id && $facebook_opengraph;
        $facebook_post = $facebook_id 
                            && $this->Access->canAddReview() 
                            && !$this->Access->moderateReview() 
                            && $this->Config->facebook_enable && $this->Config->facebook_reviews;
        ?>
        <script type="text/javascript">    
        /* <![CDATA[ */
        jQuery(document).ready(function() 
        {         
            jreviews.lightbox();
			
            jreviewsCompare.set({
                'numberOfListingsPerPage':<?php echo Sanitize::getInt($this->Config,'list_compare_columns',3);?>,
				'maxNumberOfListings' : 15,
                'compareURL':'<?php echo cmsFramework::route('index.php?option=com_jreviews&url=categories/compare/type:type_id/');?>'
            });
            jreviewsCompare.initCompareDashboard();
            jreviewsCompare.initListingsSelection();			

            <?php if($facebook_xfbml || $facebook_post):?>                               
            if(!jQuery('#fb-root').length) jQuery("body").append('<div id="fb-root"></div>');
            jreviews.facebook.init({
                'appid':'<?php echo $this->Config->facebook_appid;?>',
                'optout':<?php echo $this->Config->facebook_optout;?>,
                <?php if($facebook_post):?>                               
                'success':function(){
                    jreviews.facebook.checkPermissions({
                        'onPermission':function(){jreviews.facebook.setCheckbox('jr_submitButton',true);},
                        'onNoSession':function(){jreviews.facebook.setCheckbox('jr_submitButton',false);}
                    });
                },
                <?php endif;?>                   
                'publish_text': '<?php __t("Publish to Facebook",false,true);?>'
            });           
            <?php endif;?>                   

			var $tabs = jQuery('.jr_tabs');
			
			if ($tabs.length) { $tabs.tabs(); };
			
			var $inquiry = jQuery('#jrInquiryForm');
			
			if ($inquiry.length) {
				
				$inquiry.fancybox({
                        'speedIn': 500, 
                        'speedOut': 500,
						'transitionIn' : 'elastic',
						'transitionOut' : 'elastic',				
                        'overlayShow': true,
                        'overlayOpacity': 0.2,
                        'padding': 4,
						'opacity': true
                }); 
			};
        });       
        /* ]]> */
        </script> 
        <?php                              
        $this->send($assets);
    }
    
    function ComContentComContentBlog()
    {
        $assets = array(
            'js'=>array('jreviews'),
            'css'=>array('theme','theme.list')
        );

        $this->send($assets);       
    } 
     
/**********************************************************************************
 *  Community Listings Plugin   Controller
 **********************************************************************************/   
     function CommunityListings()
     {
        $assets = array();
        $assets['css'] = array('theme','plugins','paginator','custom_styles_modules');

        $total = Sanitize::getInt($this->viewVars,'total');
        $limit = Sanitize::getInt($this->viewVars,'limit');
        $page_count = ceil($total/$limit);
        $page_count > 1 and $assets['js'] = array('jreviews','jquery'=>'jquery','jq.scrollable');

        $this->send($assets);        
     } 
     
/**********************************************************************************
 *  Community Reviews Plugin   Controller
 **********************************************************************************/   
     function CommunityReviews()
     {
        $assets = array();
        $assets['css'] = array('theme','plugins','paginator','custom_styles_modules');

        $total = Sanitize::getInt($this->viewVars,'total');
        $limit = Sanitize::getInt($this->viewVars,'limit');
        $page_count = ceil($total/$limit);
        $page_count > 1 and $assets['js'] = array('jreviews','jquery'=>'jquery','jq.scrollable');

        $this->send($assets);        
     }          
     
/**********************************************************************************
 *  Directories Controller
 **********************************************************************************/   
     function DirectoriesDirectory()
     {
         $assets = array(
            'css'=>array('theme','theme.directory')
         );
         
        $this->send($assets);        
     }
         
/**********************************************************************************
 *  Discussions Controller
 **********************************************************************************/   
     function Discussions()
     {
        $assets = array(
            'js'=>array('jreviews','jquery','jq.ui.core','jq.jreviews.plugins','jq.tooltip'),
            'css'=>array('theme','jq.ui.core','theme.discussion','theme.detail','theme.form','paginator')
        );

        $this->send($assets);        

        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function() {
            jreviews.discussion.parentCommentPopOver();
        });
        /* ]]> */
        </script>    
        <?php     
     }
               
/**********************************************************************************
 *  Everywhere Controller
 **********************************************************************************/     
    function EverywhereIndex() 
    {                      
        // need to load jQuery for review edit/report and voting
        $assets = array(
            'js'=>array('jreviews','jquery'=>'jquery','jq.ui.core','jq.jreviews.plugins'),
            'css'=>array('theme','theme.detail','theme.form','jq.ui.core','paginator')
        );

        if($this->Access->canAddReview() || $this->Access->isEditor()) 
        {                 
            $assets['js'][] = 'jreviews.fields';
            
            if($this->Config->rating_selector == 'stars'){
                $assets['js'][] = 'jq.ui.rating';
            }           
            $assets['js'][] = 'jq.tooltip';
        }      
       
        $facebook_id = Sanitize::getString($this->Config,'facebook_appid');
        $facebook_post = $facebook_id 
                            && $this->Access->canAddReview() 
                            && !$this->Access->moderateReview() 
                            && $this->Config->facebook_enable && $this->Config->facebook_reviews;
        ?>

        <?php if($facebook_post):?>                               
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function() 
        {
			
            if(!jQuery('#fb-root').length) jQuery("body").append('<div id="fb-root"></div>');
            jreviews.facebook.init({
                'appid':'<?php echo $facebook_id?>',
                'optout':<?php echo $this->Config->facebook_optout;?>,
                'success':function(){
                    jreviews.facebook.checkPermissions({
                        'onPermission':function(){jreviews.facebook.setCheckbox('jr_submitButton',true);},
                        'onNoSession':function(){jreviews.facebook.setCheckbox('jr_submitButton',false);}
                    });
                },
                'publish_text': '<?php __t("Publish to Facebook",false,true);?>'
            });           
        });
        /* ]]> */
        </script>    
		<?php endif;?>  		

        <?php            
        $this->send($assets);               
    }
    
    function EverywhereCategory()
    {
        $assets = array();
        
        if(Sanitize::getString($this->params,'option')!='com_comprofiler'){
            $assets = array('css'=>array('theme'));
        }    
        
        $this->send($assets);
    }
    
/**********************************************************************************
 *  Listings Controller
 **********************************************************************************/    
    function ListingsCreate()
    {
        $assets = array(
            'js'=>array('jreviews','jquery','jq.ui.core','jreviews.fields','jq.ui.rating','jq.tooltip','jq.jreviews.plugins'),
            'css'=>array('theme','theme.form','jq.ui.core')
        );
        $this->send($assets);
        
        # Transforms class="wysiwyg_editor" textareas
        if($this->Access->loadWysiwygEditor()) {
            $this->Editor->load(); 
//            $this->Editor->transform();
        }
        
        if($this->Config->facebook_enable && $this->Config->facebook_listings && !$this->Access->moderateListing()):?>
            <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function() {
                jreviews.facebook.enable = true;
                if(!jQuery('#fb-root').length) jQuery("body").append('<div id="fb-root"></div>');
                jreviews.facebook.init({
                    'appid':'<?php echo $this->Config->facebook_appid;?>',
                    'optout':<?php echo $this->Config->facebook_optout;?>,
                    'publish_text': '<?php __t("Publish to Facebook",false,true);?>'
                });  
            });
            /* ]]> */
            </script>
        <?php endif;      
    }

    function ListingsEdit()
    {               
        $assets = array(
            'js'=>array('jreviews','jquery','jq.ui.core','jreviews.fields','jq.ui.rating','jq.tooltip','jq.jreviews.plugins'),
            'css'=>array('theme','theme.form','jq.ui.core')
        );
        
        $this->send($assets);
        
        # Transforms class="wysiwyg_editor" textareas
        if($this->Access->loadWysiwygEditor()) {
            $this->Editor->load(); 
            $this->Editor->transform();
        }
    }
                 
    function ListingsDetail()
    {
        $assets = array(
            'js'=>array('jreviews','jreviews.compare','jquery','jq.ui.core','jreviews.fields','jq.ui.rating','jq.jreviews.plugins','jq.tooltip','jq.json','jq.jsoncookie'),
            'css'=>array('theme','theme.detail','theme.form','paginator','jq.ui.core')
		);
                    
        $facebook_id = Sanitize::getString($this->Config,'facebook_appid');
        $facebook_opengraph = Sanitize::getBool($this->Config,'facebook_opengraph',true);
        $facebook_xfbml = $facebook_id && $facebook_opengraph;
        $facebook_post = $facebook_id 
                            && $this->Access->canAddReview() 
                            && !$this->Access->moderateReview() 
                            && $this->Config->facebook_enable && $this->Config->facebook_reviews;
        ?>
        <script type="text/javascript">    
        /* <![CDATA[ */
        jQuery(document).ready(function() 
        {         
            jreviewsCompare.set({
                'numberOfListingsPerPage':<?php echo Sanitize::getInt($this->Config,'list_compare_columns',3);?>,
                'maxNumberOfListings' : 15,
                'compareURL':'<?php echo cmsFramework::route('index.php?option=com_jreviews&url=categories/compare/type:type_id/');?>'
            });
            jreviewsCompare.initCompareDashboard();
            jreviewsCompare.initListingsSelection();            

            <?php if($facebook_xfbml || $facebook_post):?>                               
            if(!jQuery('#fb-root').length) jQuery("body").append('<div id="fb-root"></div>');
            jreviews.facebook.init({
                'appid':'<?php echo $this->Config->facebook_appid;?>'
            });
            <?php endif;?>                   
        });       
        /* ]]> */
        </script> 
        <?php                              
                            
        $this->send($assets);        
    }    
    
/**********************************************************************************
 *  Module Advanced Search Controller
 **********************************************************************************/    
    function ModuleAdvancedSearch()
    {
        $module_id = Sanitize::getInt($this->params,'module_id');
        $assets = array(
             'js'=>array('jreviews','jquery','jq.ui.core','jreviews.fields'),
             'css'=>array('theme','theme.form','jq.ui.core','custom_styles_modules')
        );

        // Load custom field data
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function(){
            var $controlFieldSearch<?php echo $module_id;?> = new jreviewsControlField('JreviewsAdvSearch_<?php echo $module_id;?>');
            $controlFieldSearch<?php echo $module_id;?>.loadData({'page_setup':true,'recallValues':true,'referrer':'adv_search_module'});  
        });
        /* ]]> */
        </script>            
            
        <?php        
        $this->send($assets);        
    } 
    
/**********************************************************************************
 *  Module Directories Controller
 **********************************************************************************/    
    function ModuleDirectories()
    {
        $module_id = Sanitize::getInt($this->params,'module_id',rand());        
        $assets = array('js'=>array('jquery','jq.treeview'),'css'=>array('theme','jq.treeview','custom_styles_modules'));
        $this->send($assets);        
        // Render tree view
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function() {
            jQuery('#jr_treeView<?php echo $module_id;?>').treeview({
                animated: 'fast',
                unique: true,
                collapsed: false
            });
        });
        /* ]]> */
         </script>            
        <?php
    }          
      
/**********************************************************************************
 *  Module Favorite Users Controller
 **********************************************************************************/    
    function ModuleFavoriteUsers()
    {
        $assets = array();
        $assets['css'] = array('theme','modules','paginator','custom_styles_modules');

        $total = Sanitize::getInt($this->viewVars,'total');
        $limit = Sanitize::getInt($this->viewVars,'limit');
        $page_count = ceil($total/$limit);
        $page_count > 1 and $assets['js'] = array('jreviews','jquery'=>'jquery','jq.scrollable');
        
        $this->send($assets);       
    } 
       
/**********************************************************************************
 *  Module Fields Controller
 **********************************************************************************/    
    function ModuleFields()
    {
        $assets= array();
        $assets['css'] = array('theme','modules','custom_styles_modules');
        $this->send($assets);        
    } 
    
/**********************************************************************************
 *  Module Range Controller
 **********************************************************************************/    
    function ModuleRange()
    {
        $assets= array();
        $assets['css'] = array('theme','modules','custom_styles_modules');
        $this->send($assets);        
    }     
           
/**********************************************************************************
 *  Module Listings Controller
 **********************************************************************************/    
    function ModuleListings()
    {
        $assets = array();
        $assets['css'] = array('theme','modules','paginator','custom_styles_modules');

        $total = Sanitize::getInt($this->viewVars,'total');
        $limit = Sanitize::getInt($this->viewVars,'limit');
        $page_count = ceil($total/$limit);
        $page_count > 1 and $assets['js'] = array('jreviews','jquery'=>'jquery','jq.scrollable');
        
        $this->send($assets);        
    } 
 
/**********************************************************************************
 *  Module Listings Controller
 **********************************************************************************/    
    function ModuleReviews()
    {
        $assets = array();
        $assets['css'] = array('theme','modules','paginator','custom_styles_modules');

        $total = Sanitize::getInt($this->viewVars,'total');
        $limit = Sanitize::getInt($this->viewVars,'limit');
        $page_count = ceil($total/$limit);
        $page_count > 1 and $assets['js'] = array('jreviews','jquery'=>'jquery','jq.scrollable');
        
        $this->send($assets);        
    } 
                    
/**********************************************************************************
 *  Reviews Controller
 **********************************************************************************/    
    function ReviewsCreate()
    {        
        //
    }

    function ReviewsLatest()
    {
        $assets = array(
            'js'=>array('jreviews','jquery','jq.ui.core','jq.jreviews.plugins'),
            'css'=>array('theme','theme.detail','theme.form','jq.ui.core','paginator')
        );
        
        if($this->Access->canAddReview() || $this->Access->isEditor()) 
        {                 
            if($this->Config->rating_selector == 'stars'){
                $assets['js'][] = 'jq.ui.rating';
            }            
            $assets['js'][] = 'jq.tooltip';
            $assets['js'][] = 'jreviews.fields';
        }             
                
        $this->send($assets);        
    }  

    function ReviewsMyReviews()
    {
       
        $assets = array(
            'js'=>array('jreviews','jquery','jq.ui.core','jq.jreviews.plugins'),
            'css'=>array('theme','theme.detail','theme.form','jq.ui.core','paginator')
        );
        
        if($this->Access->canAddReview() || $this->Access->isEditor()) 
        {                 
            if($this->Config->rating_selector == 'stars'){
                $assets['js'][] = 'jq.ui.rating';
            }            
            $assets['js'][] = 'jq.tooltip';
            $assets['js'][] = 'jreviews.fields';
        }        

        $this->send($assets);        
    }  
    
    function ReviewsRankings()
    {
        $assets = array(
            'css'=>array('theme','paginator')
        );
        $this->send($assets);        
    }      
     
/**********************************************************************************
 *  Search Controller
 **********************************************************************************/    
    function SearchAdvanced()
    {
        $assets = array(
            'js'=>array('jreviews','jquery','jq.ui.core','jreviews.fields','jq.tooltip','jq.jreviews.plugins'),
            'css'=>array('theme','theme.form','jq.ui.core')
        );
        
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function() {
        <?php if(Sanitize::getInt($this->viewVars,'criteria_id') > 0):?>
            var $controlField = new jreviewsControlField('jr-searchFields',<?php echo $this->viewVars['criteria_id'];?>);
            $controlField.loadData({'page_setup':true,'referrer':'adv_search'});  
        <?php else:?>
           jQuery('#jr-searchCriteriaId').bind('change',function(){
               var callbacks = {}; 
               if(this.value != '') {
                   callbacks = {
                        onAfterResponse: function(res){   
                            // Load custom field data
                            var $controlField = new jreviewsControlField('jr-searchFields','jr-searchCriteriaId');
                            $controlField.loadData({'page_setup':true,'referrer':'adv_search'});  
                        }
                    };       
               }
               jQuery(this).s2SubmitNoForm('search','_loadForm','data[Search][criteria_id]='+jQuery(this).val(),callbacks);
           }); 
        <?php endif;?>
        });
        /* ]]> */
        </script>    
        
        <?php
        $this->send($assets);        
    }    
}
