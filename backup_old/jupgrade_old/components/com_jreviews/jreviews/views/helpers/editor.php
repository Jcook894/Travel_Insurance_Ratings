<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
 * 
 * This is the default display for custom fields
 **/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EditorHelper extends MyHelper
{				
	var $helpers = array('html');
	var $editor = 'tinyMCE';	
	
	function load($inline = false) 
    {
		$this->Html->app = $this->app;
		$directionality = cmsFramework::isRTL() ? 'rtl' : 'ltr';
        
		switch($this->editor) {
			
			case 'tinyMCE':
				$this->Html->js(array('tiny_mce/tiny_mce'),$inline);								

				# Initialize editor
				$editorInit = '<script type="text/javascript">
					tinyMCE.init({						
						theme : "advanced",
						language : "en",
						mode : "none",
						gecko_spellcheck : true,
						document_base_url : "'.WWW_ROOT.'",
//						entities : "60,lt,62,gt",
                        entity_encoding : "raw",
						relative_urls : true,
						remove_script_host : false,
		//				save_callback : "TinyMCE_Save",
						invalid_elements : "script,applet,iframe",
        //              extended_valid_elements : "a[class|name|href|target|title|onclick|rel],img[float|class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[id|title|alt|class|width|size|noshade]",
						theme_advanced_toolbar_location : "top",
						theme_advanced_statusbar_location : "bottom",
						theme_advanced_resizing : true,
						theme_advanced_resize_horizontal : true,
						directionality: "'.$directionality.'",
						force_br_newlines : false,
						force_p_newlines : true,
                        forced_root_block : "p",
                        content_css: "'.WWW_ROOT.'templates/'.cmsFramework::getTemplate().'/css/template.css",
						debug : false,
						cleanup : true,
						cleanup_on_startup : false,
						safari_warning : false,
						//plugins : "advlink, advimage, searchreplace,insertdatetime,media,advhr,table,fullscreen,directionality,layer,style",
						plugin_insertdate_dateFormat : "%Y-%m-%d",
						plugin_insertdate_timeFormat : "%H:%M:%S",						
						fullscreen_settings : {
							theme_advanced_path_location : "top"
						}
					});
					</script>';
					cmsFramework::addScript($editorInit,$inline);			
				break;
                case 'JCE':

                    cmsFramework::addScript('<script type="text/javascript" src="'.WWW_ROOT.'plugins/editors/jce/tiny_mce/tiny_mce.js?version=150"></script>',$inline);
                    cmsFramework::addScript('<script type="text/javascript" src="'.WWW_ROOT.'plugins/editors/jce/libraries/js/editor.js?version=150"></script>',$inline);
                    
                    $editorInit = '<script type="text/javascript">                
                        tinyMCE.init({        
                            mode: "textareas",
                            theme: "advanced",
                            entity_encoding: "raw",
                            editor_selector: "mceEditor",
                            document_base_url: "'.WWW_ROOT.'",
                            site_url: "'.WWW_ROOT.'administrator/",
                            theme_advanced_toolbar_location: "top",
                            theme_advanced_toolbar_align: "left",
                            theme_advanced_path: true,
                            theme_advanced_statusbar_location: "bottom",
                            theme_advanced_resizing: true,
                            theme_advanced_resize_horizontal: true,
                            theme_advanced_resizing_use_cookie: true,
                            theme_advanced_source_editor_width: 750,
                            theme_advanced_source_editor_height: 550,
                            theme_advanced_source_editor_php: false,
                            theme_advanced_source_editor_script: false,
                            theme_advanced_source_editor_highlight: true,
                            theme_advanced_blockformats: "p,div,h1,h2,h3,h4,h5,h6,blockquote,dt,dd,code,samp,pre",
                            font_size_style_values: "8pt,10pt,12pt,14pt,18pt,24pt,36pt",
                            theme_advanced_buttons1: "help,newdocument,undo,redo,bold,italic,underline,strikethrough,justifyleft,justifycenter,justifyfull,justifyright,styleselect,formatselect,numlist,bullist,indent,outdent,sub,sup",
                            theme_advanced_buttons2: "cleanup,removeformat,cut,copy,paste,pasteword,pastetext,search,replace,anchor,charmap,fontselect,fontsizeselect,backcolor,forecolor,ltr,rtl",
                            theme_advanced_buttons3: "tablecontrols,insertlayer,moveforward,movebackward,absolute,cite,abbr,acronym,del,ins,attribs,styleprops,emotions",
                            theme_advanced_buttons4: "unlink,advlink,imgmanager,advcode,spellchecker,fullscreen,preview,print,visualchars,readmore,hr,visualaid,nonbreaking",
                            verify_html: false,
                            plugin_preview_width: 750,
                            plugin_preview_height: 550,
                            fix_list_elements: true,
                            fix_table_elements: true,
                            content_css: "'.WWW_ROOT.'templates/'.cmsFramework::getTemplate().'/css/template.css",
                            spellchecker_languages: "+English=en",
                            language: "en",
                            directionality: "ltr",
                            forced_root_block: false,
                            force_br_newlines: false,
                            force_p_newlines: true,
                            plugins: "contextmenu,directionality,emotions,fullscreen,paste,preview,table,print,searchreplace,style,nonbreaking,visualchars,xhtmlxtras,imgmanager,advlink,spellchecker,layer,help,browser,inlinepopups,readmore,media,safari,advcode",
                            inlinepopups_skin: "clearlooks2",
                            onpageload: "jceOnLoad",
                            cleanup_callback: "jceCleanup",
                            save_callback: "jceSave",
                            file_browser_callback: "jceBrowser"
                        });
                        JContentEditor.set({
                            pluginmode     : 0,
                            state         : "mceEditor",
                            allowToggle : 1,
                            php         : 0,
                            javascript     : 0,
                            toggleText     : "[show/hide]"
                        });
                        function jceSave(id, html, body){
                            return JContentEditor.save(html);
                        };
                        function jceCleanup(type, value){
                            return JContentEditor.cleanup(type, value);
                        };
                        function jceBrowser(name, url, type, win){
                            return JContentEditor.browser(name, url, type, win);
                        };
                        function jceOnLoad(){
                           jQuery(".wysiwyg_editor").tinyMCE();
                        };
                    </script>';                
                    cmsFramework::addScript($editorInit,$inline);            
                    
                break;
		}
	}
	
	function transform($return = false) 
    {
        switch($this->editor) 
        {
			case 'tinyMCE':				
            case 'JCE':                
				if($return == true) {
					return "jQuery('.wysiwyg_editor').tinyMCE();";
				} else {
					cmsFramework::addScript("<script type='text/javascript'>jQuery(document).ready(function() {jQuery('.wysiwyg_editor').tinyMCE();});</script>");
				}				
			break;
		}
	}
	
	function remove() {
		
		switch($this->editor) {			
			case 'tinyMCE':
					return "jQuery('.wysiwyg_editor').RemoveTinyMCE();";				
				break;
            case 'JCE':                                        
                    return "jQuery('.wysiwyg_editor').RemoveTinyMCE();";                
                break;				
		}		
		
	}
}