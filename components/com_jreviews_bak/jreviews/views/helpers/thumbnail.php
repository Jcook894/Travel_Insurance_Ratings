<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ThumbnailHelper extends HtmlHelper {
    
    var $quality = 85;
    var $path;
    var $path_tn;
    var $site;
    var $site_tn;
    var $image_size;
    var $catImage = false;
    var $noImage = false;
    var $jreviewsImg = true;
    
    function __construct() 
    {
        if(!class_exists('PhpThumbFactory')) {
            App::import('Vendor', 'phpthumb' . DS . 'ThumbLib.inc');
        }
        $this->path = PATH_ROOT . _JR_WWW_IMAGES;
        $this->path_tn = PATH_ROOT . _JR_WWW_IMAGES .'jreviews'._DS.'tn'._DS;
        $this->www = WWW_ROOT . _JR_WWW_IMAGES;
        $this->www_tn = $this->www . 'jreviews'._DS.'tn'._DS;     

        parent::__construct(); // Make parent class vars available here, like cmsVersion
    }
        
    function lightbox($listing, $position = 0, $attributes = array()) 
    {
        $image = null;
        $listing_id = $listing['Listing']['listing_id'];

        if(isset($listing['Listing']['images'][$position]))    
        {
            $image = $listing['Listing']['images'][$position];
        }       

        $thumb = $this->thumb($listing, $position, $attributes);

        if($thumb && $this->jreviewsImg) {    
            
            $this->jreviewsImg = true;
            
            // If listing has no images then this is a category or no image and it shouldnt be lightboxed
            if(!isset($listing['Listing']['images'][$position]) || !file_exists($this->path.$listing['Listing']['images'][$position]['path'])) {
                return $thumb;
            }                    

            $lightbox = $this->link($thumb,$this->www.$image['path'],array('sef'=>false,'class'=>'fancybox','rel'=>'gallery','title'=>$image['caption']));
            
            return $lightbox;
        }
        
    }

    function grabImgFromText($text)
    {
        /** Four scenarios for embedded images 
        * 1) Located within current site 
        * 2) Located in site within a folder of current site
        * 3) Located in a different domain with the same image path structure
        * 4) Located in a different domain with different path structure
        */
        $doc = new DOMDocument();
        @$doc->loadHTML($text);
        $imageTags = $doc->getElementsByTagName('img');   
        if($imageTags->length > 0)
        {
            $src = ltrim($imageTags->item(0)->getAttribute('src'),'/');
            $img = array('path'=>$src);
            substr($src,0,4) == 'http' and !strstr($src,WWW_ROOT) and $img['skipthumb'] = true; // Display external images, no thumbs
            $img['basepath'] = true;
            $this->jreviewsImg = false;
            return  $img;
        }

        return false;        
    }
    
    function thumb(&$listing, $position = 0, $attributes = array()) 
    {                
        $image = null;
        $cat_image = '';
        $img_title = htmlspecialchars($listing['Listing']['title'],ENT_QUOTES,'utf-8');
        $attributes = array_merge(array('border'=>0,'alt'=>$img_title,'title'=>$img_title),$attributes);
        
        // No JReviews uploaded images, so we search the summary for images
        if(!isset($listing['Listing']['images'][$position]) && isset($listing['Listing']['summary']) && strstr($listing['Listing']['summary'],"<img")){
            $img = $this->grabImgFromText($listing['Listing']['summary']);
            $img and $listing['Listing']['images'][0] = $img and $listing['Listing']['summary'] = Sanitize::stripImages($listing['Listing']['summary']);                
        }
 
        $listing_id = $listing['Listing']['listing_id'];
        
        if(isset($listing['Listing']['images'][$position]))    
        {
            $image = $listing['Listing']['images'][$position];
        }

        if($this->cmsVersion == CMS_JOOMLA15) {
            $cat_image = isset($listing['Listing']['category_image']) ? $listing['Listing']['category_image'] : '';
        }
        elseif(isset($listing['Category']['params'])) {
            $cat_params = !is_array($listing['Category']['params']) ? json_decode($listing['Category']['params'],true) : $listing['Category']['params'];
            $cat_image = isset($cat_params['image']) ? preg_replace('/'.str_replace('/','\/',_JR_WWW_IMAGES).'/','',$cat_params['image'],1) : '';
        }

        # Return the original image html tag instead of the thumb
        if(isset($attributes['return_orig'])) 
        {
            $origimg_src = '';
            unset($attributes['return_orig'],$attributes['tn_mode'],$attributes['location'],$attributes['dimensions']);

            if($image) {
                 $origimg_src = $this->www.$image['path'];    
                 $image_size = getimagesize($this->path.$image['path']);
            }
            elseif($this->Config->list_category_image && $cat_image != '') {
                $origimg_src = $this->www.$cat_image;    
                $image_size = getimagesize($this->path.$cat_image);
            }
            elseif($this->Config->list_noimage_image) {
                if($noImagePath = $this->locateThemeFile('theme_images',$this->Config->list_noimage_filename,'')) {
                    $origimg_src =  pathToUrl($noImagePath);                
                    $image_size = getimagesize($noImagePath);
                } 
            }

            if($origimg_src == '') return false;
            
            $attributes['style'] = 'width: '. $image_size[0] .'px; height: '. $image_size[1] .'px';
            
            return $this->image($origimg_src, $attributes);
        }        
        
        $output = $this->makeThumb($listing_id, $image, $cat_image, $attributes);

        if($output) {
            if(isset($attributes['return_src'])) {
                return $output['thumbnail'];
            }
                    
			if(isset($output['width']))
			{
				if(isset($attributes['style'])) {
					$attributes['style'] .= 'width: '. $output['width'] .'px; height: '. $output['height'] .'px';
				} else {
					$attributes['style'] = 'width: '. $output['width'] .'px; height: '. $output['height'] .'px';
				}    
			}
            
            unset($attributes['tn_mode'],$attributes['location'],$attributes['dimensions']);
            
            return $this->image($output['thumbnail'],$attributes);
        } 
        
        return false;
    }
    
    /**
     * Creates a thumbnail if it doesn't already exist and returns an array with full paths to original image and thumbnail
     * returns false if thumbnail cannot be created
     */
    function makeThumb($listing_id, $image, $cat_image, $attributes = array())
     {   
        $imageName = '';
        $this->catImage = false;        
        $this->noImage = false;
        
        $tn_mode = Sanitize::getString($attributes,'tn_mode','scale');
        
        $location = Sanitize::getString($attributes,'location','_');
        if($location != '_') {
            $location = '_'.$location.'_';
        }

        $dimensions = Sanitize::getVar($attributes,'dimensions',array());
        if(empty($dimensions)) {
            $dimensions = array($this->Config->list_image_resize);
        }

        if(isset($image['path']) && $image['path'] != '') 
        {       
            if(isset($image['skipthumb']) && $image['skipthumb']===true) {
                return array('image'=>$image['path'],'thumbnail'=>$image['path']);
            }

            $external_thumb = false; //Sanitize::getBool($image,'skipthumb');
            
            $temp = explode( '/', $image['path']);
            $imageName = $temp[count($temp)-1];
            $length = strlen($listing_id);

           if (substr($imageName,0,$length+1) == $listing_id.'_') {
                // Uploaded image already has entry id prepended so we remove it and put it before the content suffix
                $imageName = substr($imageName,$length+1);
            }
            
            $thumbnail = "tn_".$listing_id.$location.$imageName;
            
            $output = array(
                            'image'=>substr($image['path'],0,4) == 'http' ? $image['path'] : $this->www.$image['path'],
                            'thumbnail'=>$this->www_tn.$thumbnail
                        );                                                                                            
            
            
            $image_path = trim(isset($image['basepath']) && $image['basepath'] ? PATH_ROOT.str_replace(WWW_ROOT,'',$image['path']) : $this->path.$image['path']);
            // If in administration, then can't use relative path because it will include /administrator
            defined('MVC_FRAMEWORK_ADMIN') and strpos($image_path,PATH_ROOT)===false and $image_path = PATH_ROOT . str_replace(_DS,DS,$image_path);

            if ($imageName != '' && file_exists($image_path) || $external_thumb) 
            {   
                $this->image_size = $external_thumb ? array(1) /* thumb (re)creation only if thumb dimenions change */ : getimagesize($image_path);
                     
                if(file_exists($this->path_tn.$thumbnail)) 
                { // Thumbnail exists, so we check if current size is correct
    
                    $thumbnailSize = getimagesize($this->path_tn.$thumbnail);
  
                    // Checks the thumbnail width to see if it needs to be resized
                    if ($thumbnailSize[0] == $dimensions[0] 
                        || ($thumbnailSize[0] != $dimensions[0] && $this->image_size[0] < $dimensions[0] )
                        || ($tn_mode == 'crop' && $thumbnailSize[0] == $thumbnailSize[1] && $thumbnailSize[0] == $dimensions[0])
                    ) {
                        // No resizing is necessary
                        $output['width'] = $thumbnailSize[0];
                        $output['height'] = $thumbnailSize[1];
                        return $output;
                    }
                }

                // Create the thumbnail
                if($newDimensions = $this->$tn_mode($image_path, $this->path_tn.$thumbnail, $dimensions)) {
                    $output = array_merge($output,$newDimensions);
                    return $output;
                }                
                
            }
        }
        
        if ($this->Config->list_category_image && $cat_image != '') {
            
            $this->image_size = getimagesize($this->path.$cat_image);
            
            if($this->image_size[0] == min($this->image_size[0],trim(intval($dimensions[0])))) {
                // Image is smaller (narrower) than thumb so no thumbnailing is done
                return array(
                    'width'=>$this->image_size[0],
                    'height'=>$this->image_size[1],
                    'image'=>$this->www.$cat_image,
                    'thumbnail'=>$this->www.$cat_image
                );                        
            }

            // Create category thumb
            $cat_tn = basename($cat_image);
            if ($newDimensions = $this->$tn_mode($this->path.$cat_image, $this->path_tn.'tn'.$location.$cat_tn, $dimensions)) {
                $this->catImage = true;
                return array(
                    'width'=>$newDimensions['width'],
                    'height'=>$newDimensions['height'],
                    'image'=>$this->www.$cat_image,
                    'thumbnail'=>$this->www_tn.'tn'.$location.$cat_tn
                );
            }
        }

        // Create NoImage thumb         
        $this->viewSuffix = '';
        $noImagePath = $this->locateThemeFile('theme_images',$this->Config->list_noimage_filename,''); 
      
        if ($noImagePath && $this->Config->list_noimage_image && $this->Config->list_noimage_filename != '') 
        {            
            $noImageWww =  pathToUrl($noImagePath);
            $noImageThumbnailPath = $this->path_tn . 'tn'.$location.$this->Config->list_noimage_filename;        
            $thumbExists = file_exists($noImageThumbnailPath);
            
            if($thumbExists) 
            {
                $noImageSize = getimagesize($noImageThumbnailPath); 
            
                if($this->image_size[0] == min($noImageSize[0],trim(intval($dimensions[0])))) {
                    // Image is smaller (narrower) than thumb so no thumbnailing is done
                    return array(
                        'width'=>$noImageSize[0],
                        'height'=>$noImageSize[1],
                        'image'=>$noImageWww,
                        'thumbnail'=>$noImageWww
                    );                    
                }

                if(($noImageSize[0]!=$dimensions[0])) {
                    $newDimensions = $this->$tn_mode($noImagePath,$noImageThumbnailPath, $dimensions);                    
                }  
                else {
                    $newDimensions = array('width'=>$noImageSize[0],'height'=>$noImageSize[1]);
                }
            } else {
                $newDimensions = $this->$tn_mode($noImagePath,$noImageThumbnailPath, $dimensions);
            }

            $this->noImage = true;      

            return array(
                'width'=>$newDimensions['width'],
                'height'=>$newDimensions['height'],
                'image'=>$noImageWww,
                'thumbnail'=> $this->www_tn . 'tn' . $location . $this->Config->list_noimage_filename
            );
        }

        
        return false;
    }
    
    function crop($imagePath, $thumbnailPath, $dimensions) 
    {                   
        ob_start();
        $thumb = PhpThumbFactory::create($imagePath,array(
            'jpegQuality'=>$this->quality,
            'resizeUp'=>false
            ));
        $thumb->adaptiveResize($dimensions[0],$dimensions[0])->save($thumbnailPath);  
        ob_end_clean();

        return is_file($thumbnailPath) ? $thumb->getCurrentDimensions() : false;
    }    
    
    function scale($imagePath, $thumbnailPath, $dimensions) 
    {                                    
        ob_start();
        $thumb = PhpThumbFactory::create($imagePath,array(
            'jpegQuality'=>$this->quality,
            'resizeUp'=>false
            ));
        extract($thumb->getCurrentDimensions()); /* $width, $height */
        $thumb->resize($dimensions[0],$height)->save($thumbnailPath);
        ob_end_clean();
        
        return is_file($thumbnailPath) ? $thumb->getCurrentDimensions() : false;
    }
}
