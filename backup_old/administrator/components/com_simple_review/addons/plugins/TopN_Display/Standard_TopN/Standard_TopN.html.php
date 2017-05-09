<?php defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

class Standard_TopN_Html
{

	function Standard_TopN_Html()
	{
		
	}
	
	function AddTopN(&$details)
	{
		?>
		<!-start editing from here-->
		
		<div class='topN' onClick="location.href='<?php echo $details->reviewUrl;?>'" title='<?php echo $details->title;?>' style='cursor: pointer'>
		    <table width=99% class='topNTable' cellpadding='2' cellspacing='2'>
		            <tr>
		                <th>
		                    <?php echo "$details->title1 $details->title2 $details->title3"; ?>
		                </th>
		                <th width="20%">
		                    Rating : <?php echo $details->score;?>
		                </th>
		            </tr>
		            <tr>
		
		                <td colspan=2>
		                    <div >
		                    <img src='<?php echo $details->thumbnailUrl;?>' hspace='5' align='left'/>
		                    <?php echo $details->blurb;?>
		                    </div>
		                </td>
		            </tr>
		    </table>
		</div>
			
		<!--stop editing here-->	
		<?php	
	}

}
?>