<?php defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

class Standard_Comment_Html
{

	function Standard_Comment_Html()
	{
		
	}
	
	function AddComment(&$c)
	{
		?>
		<!-start editing from here-->
	    <table class='commentTable'>
			<tr>
				<th width=25%>
	    			<?php echo $c->commenter;?>
	    		</th>
				<th width=10%>
					<?php echo $c->userRating;?>
				</th>
				<th>
					<?php echo $c->createdDate;?>
				</th>
			</tr>
			<tr>
				<td align='left' colspan='3'>
					<img align='left' src="components/com_simple_review/images/avatars/<?php echo $c->avatar;?>"/ style="padding-right:5px;">
					<?php echo $c->comment;?>
				</td>		
			</tr>
			<tr>
				<td colspan=3>
					<?php echo $c->admin;?>
				</td>
			</tr>	
		</table>
	
		<!--stop editing here-->	
		<?php
		}
}