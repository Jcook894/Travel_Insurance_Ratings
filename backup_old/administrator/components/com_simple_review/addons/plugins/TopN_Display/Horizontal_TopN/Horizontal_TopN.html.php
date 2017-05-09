<?php defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

class Horizontal_TopN_Html
{

	function Horizontal_TopN_Html()
	{
		
	}
	
	function _DisplayHeader()
	{
		echo '<div><strong>Top Reviews</strong></div>';
		echo '<div class="topNHoriontalContainer">';				  
	}
	
	function _DisplayFooter()
	{ 
		echo '<div style="clear:both"></div></div>';
	}		
	
	function AddTopN(&$details)
	{
		$titles = "$details->title1 $details->title2 $details->title3";
		$attrs = "class='topNHorizontal' onClick='location.href=\"$details->reviewUrl\"'  title='$titles' ";
		?>
		<!-start editing from here-->
		<div <?php echo $attrs; ?> >			
  			<a class="topNHLink" href='<?php echo $details->reviewUrl;?>'><img src='<?php echo $details->thumbnailUrl;?>' width='110' height='110'/></a>
			<div class="topNHTitles"><?php echo "$titles"; ?></div>
			<div class="topNHRating">Rating: <?php echo $details->score;?></div>
		</div>	
		<!--stop editing here-->	
		<?php	
	}

}
?>