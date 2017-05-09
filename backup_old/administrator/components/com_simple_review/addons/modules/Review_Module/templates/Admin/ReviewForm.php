<?php
/**
 *  $Id: ReviewForm.php 122 2009-09-13 12:39:25Z rowan $
 *
 * 	Copyright (C) 2005-2009  Rowan Youngson
 * 
 *	This file is part of Simple Review.
 *
 *	Simple Review is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.

 *  Simple Review is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with Simple Review.  If not, see <http://www.gnu.org/licenses/>.
*/

// ensure this file is being included by a parent file
defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );
class TemplateReviewForm
{
	var $_ReviewModule;
	var $_Tips;
	var $_CategoryList;
	var $_AwardList;
	function TemplateReviewForm(&$reviewModule, $tips, $lists)
	{
		$this->_ReviewModule =& $reviewModule;
		$this->_Tips = $tips;
		$this->_CategoryList = $lists->category;
		$this->_AwardsList = $lists->award;
	}
	
	function _GetReviewString($key)
	{
		return $this->_ReviewModule->GetString($this->_ReviewModule, $key);
	}
	
	function Display($values)
	{
	?>	
	<script type="text/javascript" src="http://view.jquery.com/trunk/plugins/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="http://view.jquery.com/trunk/plugins/metadata/jquery.metadata.min.js"></script>
	<script type="text/javascript">
		
		function submitbutton(pressbutton)
		{
		    if (pressbutton == "list")
		    {
		      	submitform( pressbutton );
				return;
		    }	
								

		  	<?php SRHtmlControls::EditorGetContents( 'editorcontent', 'content' ) ; ?>
		    //validateSRForm(pressbutton, reviewConfiguration, reviewWarnings);
			
			if(!jQuery("#adminForm").valid())
			{
				return;
			}
			submitform( pressbutton );			
		}
		
		var messages = {};
		
		messages.score =  {
			required: "<?php echo $this->_GetReviewString('FormWarningValidRating');?>",
			range: jQuery.format("<?php echo $this->_GetReviewString('FormWarningRatingBounds');?>"),
			score: "<?php echo $this->_GetReviewString('FormWarningValidRating');?>",
			number: "<?php echo $this->_GetReviewString('FormWarningValidRating');?>"
		};
		
		<?php
		$titleToValidateCount = 1; 
		foreach($values['titles'] as $revTitle):
		?>
		
			var messageName = '<?php echo "title$titleToValidateCount";?>';	
			messages[messageName] = {
					required: "<?php echo $this->_GetReviewString('FormWarningMandatoryTitle1');?>",
					range: jQuery.format("<?php echo $this->_GetReviewString('FormWarningRatingBounds');?>"),
					<?php echo "title$titleToValidateCount";?>: "<?php echo $this->_GetReviewString('FormWarningValidRating');?>",
					number: "<?php echo $this->_GetReviewString('FormWarningValidRating');?>",
					url: "<?php echo $this->_GetReviewString('FormWarningUrl');?>"
			};
		
		
		<?php
			$titleToValidateCount++;
		endforeach;
		?>
		var SRValidator = null;
		jQuery().ready(function() {

			var maxRating = <?php echo Review_Module_MAX_RATING;?>;
			var reviewWarnings = 
			{
				FormWarningValidRating: "<?php echo $this->_GetReviewString('FormWarningValidRating');?>",
				FormWarningMaxRating: String.SRFormat("<?php echo $this->_GetReviewString('FormWarningMaxRating');?>", maxRating),
				FormWarningMandatoryTitle1: "<?php echo $this->_GetReviewString('FormWarningMandatoryTitle1');?>",
				FormWarningThumbnailLength: "<?php echo $this->_GetReviewString('FormWarningThumbnailLength');?>",
				FormWarningImageLength: "<?php echo $this->_GetReviewString('FormWarningImageLength');?>"
			}			
			
				SRValidator = jQuery("#adminForm").validate({
				errorLabelContainer: jQuery("#adminForm div.errorContainer"),
				messages: messages
			});
						
		});
		
		jQuery(document).ready(function(){
			jQuery("#adminForm").valid();	
		});	
						
	</script>		
	<fieldset>
		<div class="errorContainer" style="display:none"></div>	
		
		<legend><?php echo $this->_GetReviewString('Review');?></legend>

		<!--category list-->
		<label for="categoryID"><?php echo $this->_Tips->catListTip;?></label>
        <?php echo $this->_CategoryList;?><br/>
		
		<!--award list-->
		<label for="awardID"><?php echo $this->_Tips->awardListTip;?></label>
        <?php echo $this->_AwardsList;?><br/>
		
		<!--rating-->
		<label for="score"><?php echo $this->_Tips->ratingTip;?></label>
        <input type="text" name="score" id="score" class="{required:true,number:true, range:[0,<?php echo Review_Module_MAX_RATING;?>]}" 
				value="<?php echo $values['score']; ?>"/><br/>

		<!--page name-->
		<label for="pageName"><?php echo $this->_Tips->pageNameTip;?></label>
        <input type="text" name="pageName" id="pageName" value="<?php echo $values['pageName']; ?>"/><br/>		
		
		<?php
		$titleCount = 1;
		foreach($values['titles'] as $revTitle)	
		{						
			$txtTitleID = "title$titleCount";
			$hfReviewTitle = "titleID_0_$titleCount";
			$hfCategoryTitle = "titleID_1_$titleCount";			
   			$label = Simple_Review_Common::EscapeNewlinesForJS($revTitle->titleName);
			if(strlen($label) > 20)
			{
			 	$label = substr($label, 0, 17) . "...";
			}	
			$titleText = Simple_Review_Common::EscapeNewlinesForJS($revTitle->title);	
			$showExpand = $revTitle->titleType != "Rating";
			
			$titleValidation = array();

			if($revTitle->mandatory)
			{
				$titleValidation[] = "required:true";
			}
						
			switch($revTitle->titleType)
			{
				case "Link":
					$titleValidation[] = "url:true";
					break;
				case "Rating":
					$titleValidation[] = 'number:true,range:[0,'.Review_Module_MAX_RATING.']';
				break;
			}
			
			$titleValidation = '{'.implode($titleValidation, ',').'}';						
		?>			
			<div>
				<label for="<?php echo $txtTitleID;?>" class="titleLabel"><?php echo $label;?></label>
				<div class="titleText">
					<textarea name="<?php echo $txtTitleID;?>" id="<?php echo $txtTitleID;?>" rows="1" cols="70" class="<?php echo $titleValidation; ?>"><?php echo $titleText;?></textarea>					
					<img src="images/expandall.png" id="expandtitle1" alt="Expand" title="Expand" onclick="srExpand(this, '<?php echo $txtTitleID;?>');" class="expand"/>
				</div>
			<div class="titleOption" id="title1Options">
				<select id="title1Type" name="title1Type" disabled="disabled">
					<option value="<?php echo $revTitle->titleType;?>" selected="selected"><?php echo $revTitle->titleType;?></option>
				</select>
			</div>
			<input type="hidden" name="<?php echo $hfReviewTitle;?>" value="<?php echo $revTitle->reviewTitleID;?>"/>
			<input type="hidden" name="<?php echo $hfCategoryTitle;?>" value="<?php echo $revTitle->categoryTitleID;?>"/>
			<span class="validationMessage"></span>
			<div style="clear: both;"/></div>			
		<?php	
			$titleCount++;
		}
		?>
								                        			  
    	<!--image thumbnail-->
		<label for="thumbnailURL"><?php echo $this->_Tips->thumbnailTip;?></label>
        <input type="text" name="thumbnailURL" id="thumbnailURL" class="{required:false,maxlength:255}" value="<?php echo $values['thumbnailURL']; ?>" size="70"/><br/>
		
		<!--image url-->
		<label for="imageURL"><?php echo $this->_Tips->imageTip;?></label>
		<input type="text" name="imageURL" id="imageURL" class="{required:false,maxlength:255}" value="<?php echo $values['imageURL']; ?>" size="70"/><br/>
		
		<!--blurb-->
		<label for="blurb"><?php echo $this->_Tips->blurbTip;?></label>
		<textarea name="blurb" id="blurb" cols="70" rows="10"><?php echo $values['blurb']; ?></textarea><br/>
		
		<!--publish review-->
		<label for="published"><?php echo $this->_Tips->publishTip;?></label>
		<input type="checkbox" name="published" id="published" value="1" <?php if($values['published']) echo "checked";?> /><br/>								    	
		
		<!--review-->
		<label for="content"><?php echo $this->_Tips->reviewTip;?></label>
		<?php
	          // parameters : areaname, content, hidden field, width, height, rows, cols
	          SRHtmlControls::EditorInsertArea( 'editorcontent',  $values['content'] , 'content', '350', '350', '75', '20' ) ;
	    ?>		
	</fieldset>
	<?php		
	}
}

?>