<?php defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

class Bubble_Comment_Html
{
	function Bubble_Comment_Html()
	{	
	}
	
	function AddComment(&$c)
	{
		?>
		<!-start editing from here-->
		
						<div class='commentOutline'>
						
						<!--header-->
						<div class='commentBubble'>
							<span class='commenter'><?php echo $c->commenter;?></span>
							:: <?php echo $c->createdDate;?>:: 
							<span class='commenterRating'> <?php echo $c->userRating;?></span>
							<span><?php echo $c->admin;?></span>
						</div>				
						<!--header end-->
						
						
						<!-- round top -->
					   <div class="roundtop">
						 <img src="administrator/components/com_simple_review/addons/plugins/Comment_Display/Bubble_Comment/images/tl.gif" alt="" 
						 width="15" height="15" class="roundcorner" 
						 style="display: none"/>	 			 			 
					   </div>	
					   <!-- round top end -->
					   
					   <!-- content -->
					  <div class='roundCont'>
					  	<img align='left' src="components/com_simple_review/images/avatars/<?php echo $c->avatar;?>"/>
						<?php echo $c->comment;?>
					    <div class="clear"></div>
					  </div>
		  			  <!-- content end-->
		  			  
						<!--round bottom-->
		  			   <div class="roundbottom">
						 <img src="administrator/components/com_simple_review/addons/plugins/Comment_Display/Bubble_Comment/images/bl.gif" alt="" 
						 width="15" height="15" class="roundcorner" 
						 style="display: none" />
					   </div>
						<!--round bottom end-->
									   
						</div>
			
		<!--stop editing here-->			
		<?php	
	}
}
?>