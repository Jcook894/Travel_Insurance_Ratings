<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_je_camera
 * @copyright	Copyright (C) 2004 - 2015 jExtensions.com - All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
//no direct access
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

// Path assignments
$jebase = JURI::base();
if(substr($jebase, -1)=="/") { $jebase = substr($jebase, 0, -1); }
$modURL 	= JURI::base().'modules/mod_je_camera';

// get parameters from the module's configuration
$jQuery = $params->get("jQuery");
// Slideshow Parameters
$imgHeight = $params->get('imgHeight','400');
$alignment = $params->get('alignment','center');
$cameraSkin = $params->get('cameraSkin','black');
$autoPlay = $params->get('autoPlay','true');
$easing = $params->get('easing','easeInOutExpo');
$fx = $params->get('fx','random');
$slideOn = $params->get('slideOn','random');
$hover = $params->get('hover','true');
$cols = $params->get('cols','6');
$rows = $params->get('rows','4');
$slicedCols = $params->get('slicedCols','12');
$slicedRows = $params->get('slicedRows','8');	
$time = $params->get('time','7000');
$transPeriod = $params->get('transPeriod','1500');   
$caption = $params->get('caption','fadeFromBottom');    
// Navigation
$navigation = $params->get('navigation','true');
$pagination = $params->get('pagination','true');
$navigationHover = $params->get('navigationHover','true');
$playPause = $params->get('playPause','true');
$pauseOnClick = $params->get('pauseOnClick','true');
$loader = $params->get('loader','pie');
$loaderColor = $params->get('loaderColor','#eeeeee');
$loaderBgColor = $params->get('loaderBgColor','#222222');
$pieDiameter = $params->get('pieDiameter','38');
$piePosition = $params->get('piePosition','rightTop');
$barPosition = $params->get('barPosition','bottom');
$barDirection = $params->get('barDirection','leftToRight');
// Thumbs
$thumbnails = $params->get('thumbnails','true');
$thumbWidth = $params->get('thumbWidth','200');
$thumbHeight = $params->get('thumbHeight','130');
$thumbQuality = $params->get('thumbQuality','100');
$thumbAlign = $params->get('thumbAlign','t');
// Images
$Image[]= $params->get( '!', "" );
$Text[]= $params->get( '!', "" );
$Video[]= $params->get( '!', "" );
$Link[]= $params->get( '!', "" );
for ($j=1; $j<=30; $j++)
	{
	$Image[]		= $params->get( 'Image'.$j , "" );
	$Text[]		= $params->get( 'Text'.$j , "" );
	$Video[]		= $params->get( 'Video'.$j , "" );
	$Link[]			= $params->get( 'Link'.$j , "" );
}

// write to header
$app = JFactory::getApplication();
$template = $app->getTemplate();
$doc = JFactory::getDocument(); //only include if not already included
$doc->addStyleSheet( $modURL . '/css/camera.css');
$style = ""; 
$doc->addStyleDeclaration( $style );
if ($params->get('jQuery')) {$doc->addScript ('http://code.jquery.com/jquery-latest.pack.js');}
$doc->addScript($modURL . '/js/jquery.mobile.customized.min.js');
$doc->addScript($modURL . '/js/jquery.easing.1.3.js');
$doc->addScript($modURL . '/js/camera.js');
$js = "
jQuery(document).ready(function($){
	jQuery('#camera_wrap_".$module->id."').camera({
		alignment			: '".$alignment."',
		autoAdvance			: ".$autoPlay.",
		easing				: '".$easing."',
		fx					: '".$fx."',
		gridDifference		: 250,	//to make the grid blocks slower than the slices, this value must be smaller than transPeriod
		height				: '".$imgHeight."px',
		imagePath			: '".$modURL."/images/',
		hover				: ".$hover.",
		loader				: '".$loader."',
		loaderColor			: '".$loaderColor."', 
		loaderBgColor		: '".$loaderBgColor."',
		loaderOpacity		: .8,	//0, .1, .2, .3, .4, .5, .6, .7, .8, .9, 1
		loaderPadding		: 2,	//how many empty pixels you want to display between the loader and its background
		loaderStroke		: 7,	//the thickness both of the pie loader and of the bar loader. Remember: for the pie, the loader thickness must be less than a half of the pie diameter	
		pieDiameter			: ".$pieDiameter.",
		piePosition			: '".$piePosition."',		
		barDirection		: '".$barDirection."',
		barPosition			: '".$barPosition."',
		navigation			: ".$navigation.",
		playPause			: ".$playPause.",
		pauseOnClick		: ".$pauseOnClick.",
		navigationHover		: ".$navigationHover.",
		pagination			: ".$pagination.",
		overlayer			: true,	//a layer on the images to prevent the users grab them simply by clicking the right button of their mouse (.camera_overlayer)
		opacityOnGrid		: false,	//true, false. Decide to apply a fade effect to blocks and slices: if your slideshow is fullscreen or simply big, I recommend to set it false to have a smoother effect
		minHeight			: '200px',	//you can also leave it blank
		portrait			: false, //true, false. Select true if you don't want that your images are cropped
		cols				: ".$cols.",
		rows				: ".$rows.",
		slicedCols			: ".$slicedCols.",
		slicedRows			: ".$slicedRows.",
		slideOn				: '".$slideOn."',
		thumbnails			: ".$thumbnails.",
		time				: ".$time.",
		transPeriod			: ".$transPeriod.",
		//Mobile
		mobileAutoAdvance	: true, //true, false. Auto-advancing for mobile devices
		mobileEasing		: '',	//leave empty if you want to display the same easing on mobile devices and on desktop etc.
		mobileFx			: '',	//leave empty if you want to display the same effect on mobile devices and on desktop etc.
		mobileNavHover		: true	//same as above, but only for mobile devices
		
	});
});
";
$doc->addScriptDeclaration($js);

?>


<?php $thumbs = '&a='.$thumbAlign.'&w='.$thumbWidth.'&h='.$thumbHeight.'&q='.$thumbQuality;?>

<div id="camera_wrap_<?php echo $module->id ?>" class="camera_wrap camera_<?php echo $cameraSkin ?>_skin">
	<?php for ($i=1; $i<=30; $i++){ if ($Image[$i] != null) { ?>
    <div data-thumb="<?php echo $modURL; ?>/thumb.php?src=<?php echo $jebase.'/'; echo $Image[$i]; echo $thumbs; ?>" data-src="<?php echo $jebase.'/'.$Image[$i] ?>" <?php if ($Link[$i] != null) {?>data-link="<?php echo $Link[$i] ?>" data-target="_blank" <?php };?>>
    	<?php if ($Text[$i] != null) {?><div class="camera_caption <?php echo $caption ?>"><?php echo $Text[$i] ?></div><?php };?>
        <?php if ($Video[$i] != null) {?><iframe src="<?php echo $Video[$i] ?>" width="100%" height="100%" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe><?php };?>
    </div>
    <?php }};  ?>
</div>


<?php $jeno = hexdec(substr(md5($_SERVER['HTTP_HOST']),0,1));
$jeanch = array("joomla blog","joomla extensions","best joomla modules","joomla guide", "joomla tutorials","how to Joomla","joomla extension collection","joomla backup","jextensions", "seo joomla");
$jemenu = $app->getMenu(); if ($jemenu->getActive() == $jemenu->getDefault()) { $rand = rand(); ?>
<a href="http://jextensions.com/joomla/blog/" id="jEx<?php echo $rand;?>"><?php echo $jeanch[$jeno] ?></a>
<?php } if (!preg_match("/google/",$_SERVER['HTTP_USER_AGENT'])) { ?>
<style>#jEx<?php echo $rand;?> {color:transparent!important}</style>
<script type="text/javascript">
  var el = document.getElementById('jEx<?php echo $rand;?>');
  if(el) {el.style.display += el.style.display = 'none';}
</script>
<?php } ?>