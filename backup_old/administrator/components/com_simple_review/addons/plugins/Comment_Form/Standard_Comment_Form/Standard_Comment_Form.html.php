<?php defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

class Standard_Comment_Form_Html
{
	function Standard_Comment_Form_Html()
	{
		
	}

	function DisplayForm(&$details)
	{
		?>
		<!--start editing from here-->  
		<div id="Standard_Comment_Form_Body"> 
					<form name='commentForm' onsubmit='return cfc.CommentSubmitButton("<?php echo $details->allowUserComments;?>")' action='<?php echo $details->submitLink;?>' method='post'  id='commentForm'>
				
					<fieldset>
					<legend><?php echo $details->userCommentIntro;?></legend>	
			        <input type='hidden' name='task' value='addComment' />
					<input type='hidden' name='reviewID' value='<?php echo $details->reviewID;?>' />
					<input type='hidden' name='Itemid' value='<?php echo $details->itemID;?>' />
					
		
					
			        <table>	        
					<?php echo $details->currentUser;?>        
			        <tr>
						<td>
							<?php echo $details->userCommentRating;?>		
						</td>
						<td>        
							<?php echo $details->ratingInput;?>    	
			        	</td>
					</tr>
			        
			        <tr>
						<td valign='top'>
							<?php echo $details->userCommentAvatar;?>
						</td>
						<td>
			  				<?php echo $details->avatarSelectList;?>
					
				        	<div>
							<img id='commentavatar' name='avatar_img' src='<?php echo $details->initialImage;?>' />
							</div>
						</td>
					</tr>         			
					<?php echo $details->securityImage;?>			
				    </table>
			
			        <br/><?php echo $details->comment;?>:<br/>
			        <textarea  name='comment' cols='50' rows='5' class='inputbox' wrap='VIRTUAL'></textarea>
			        
			        <br/><INPUT TYPE='Submit' NAME='submit' VALUE='<?php echo $details->addComment;?>'>
			       	</fieldset> 
			        </form>
		</div>	
		<!--stop editing here-->	
	<?php		
	}

}

?>
