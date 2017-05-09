<?php
/**
 *  $Id: CategoryForm.php 120 2009-09-13 05:37:35Z rowan $
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
class TemplateCategoryForm
{
	var $_CategoryModule;
	var $_Tips;
	var $_CategoryList;
	var $_TemplateList;
	
	function TemplateCategoryForm(&$categoryModule, $tips, $lists)
	{
		$this->_CategoryModule =& $categoryModule;
		$this->_Tips = $tips;
		$this->_CategoryList = $lists->parentCategories;
		$this->_TemplateList = $lists->templates;
	}
		
	function _GetCategoryString($key)
	{
		return $this->_CategoryModule->GetString($this->_CategoryModule, $key);
	}	
	
	function Display($values)
	{
	?>
		<script type="text/javascript">
			function submitbutton(pressbutton)
			{
		    	validateSRForm(pressbutton);
			}
			
			jQuery(document).ready(function(){
				var tmLang = {
					None : '<?php echo $this->_GetCategoryString('FormTitleOptionNone');?>',
					NoneTip : '<?php echo $this->_GetCategoryString('FormTitleOptionNoneTip');?>',
					IsRateable: '<?php echo $this->_GetCategoryString('FormTitleOptionIsRateable');?>',
					IsRateableTip: '<?php echo $this->_GetCategoryString('FormTitleOptionIsRateableTip');?>',
					IsUrl: '<?php echo $this->_GetCategoryString('FormTitleOptionIsUrl');?>',
					IsUrlTip: '<?php echo $this->_GetCategoryString('FormTitleOptionIsUrlTip');?>'
				};			
				var tm = new CategoryTitleManager("tm", "title", "", 20, tmLang);
				
				jQuery("#addTitle").click(function(){
					tm.AddTitle('', tm.GetNextNumberedTitleName('Title'), 0, 'Text', 0);	
				});
				
			<?php
			foreach($values['titleNames'] as $catTitle):		 
				$titleName = Simple_Review_Common::EscapeNewlinesForJS($catTitle->titleName);
				$titleID = $catTitle->categoryTitleID;
				$titleType = $catTitle->titleType;	
				$mandatory = $catTitle->mandatory;		 
				
				echo "tm.AddTitle('$titleName', tm.GetNextNumberedTitleName('Title'), $titleID, '$titleType', $mandatory);";
			
			endforeach;
			?>							
			});																		
		</script>   
				
	<fieldset>
		<legend><?php echo $this->_GetCategoryString('Category');?></legend>
		
		<!--parent category-->
		<label for="parentCategoryID"><?php echo $this->_Tips->catListTip;?></label>
        <?php echo $this->_CategoryList;?><br/>	
		
		<!--page name-->
		<label for="pageName"><?php echo $this->_Tips->pageNameTip;?></label>
        <input type="text" name="pageName" id="pageName" value="<?php echo $values['pageName']; ?>"/><br/>			
		
		<!--category name-->
		<label for="name"><?php echo $this->_Tips->catNameTip;?></label>
		<input type="text" name="name" id="name" value="<?php echo $values['name']; ?>"/><br/>
		
		<!--template-->
		<label for="templateID"><?php echo $this->_Tips->catTemplateTip;?></label>
		<?php echo $this->_TemplateList; ?><br/>
		
		<!--description-->
		<label for="description"><?php echo $this->_Tips->descTip;?></label>
		<input type="text" name="description" id="description" value="<?php echo $values['description']; ?>"  size="100"/>
		
		<div id='titlesdiv'></div>
		
		<div>
			<label for="addTitle"></label>
			<input type="button" value="Add a new title" id="addTitle" name="addTitle"/>	
		</div>
		
		<!--catimage-->
		<label for="categoryImageURL"><?php echo $this->_Tips->catImgTip;?></label>	
		<input type="text" name="categoryImageURL" id="categoryImageURL" value="<?php echo $values['categoryImageURL']; ?>" size="100"/><br/>
		
		<!--category order-->
		<label for="catOrder"><?php echo $this->_Tips->orderTip;?></label>
		<input type="text" name="catOrder" id="catOrder" value="<?php echo $values['catOrder']; ?>" /><br/>	    
			
		
		<!--publish cat-->
		<label for="published"><?php echo $this->_Tips->publishTip;?></label>
		<input type="checkbox" name="published" id="published" value="1" <?php if($values['published']) echo "checked";?> /><br/>
		
		<!--userreview-->
		<div style="display:none">
		<label for="userReviews"><?php echo $this->_Tips->userReviewTip;?></label>
		<input type="checkbox" name="userReviews" id="userReviews" value="1" <?php if($values['userReviews']) echo "checked";?> /><br/>
		</div>
				
		<div id="titleTemplateToClone" style="display:none">
			<div>
				<!--fill in 'for' e.g. title1-->
				<label class="titleLabel">Title 1</label>
				<div class="titleText">
					<!--fill in 'name' and 'id' e.g. title1-->
					<textarea rows="1" cols="70" class="titleTextArea">Title name</textarea>
					<!--fill in expand 'id' e.g. expandtitle1-->
					<img src="images/expandall.png" alt="Expand" title="Expand" class="expand"/>
				</div>
				<!--fill in 'id' e.g. title1Options-->
				<div class="titleOption">
					<!-- fill in 'id' and 'name' e.g. title1Type-->
					<select class="titleType">
						<option value="Rating">Rating</option>
						<option value="Text">Text</option>
						<option value="Link">Link</option>
					</select>
					<!-- fill in 'for' e.g. mandatory-->
					<label class="mandatoryLabel"><?php echo $this->_Tips->mandatoryTip;?></label>
					<!-- fill in 'id' and 'name' e.g. mandatory -->
					<input type="checkbox" class="titleMandatory" value="1"/>
				</div>
				<!--fill in 'id' and 'name' e.g. titleID_0_1 and value e.g.29-->
				<input type="hidden" class="titleID"/>
				<div style="clear: both;"/>
			</div>		
		</div>					
				
		</fieldset>				
	
	<?php
	}
	
}

?>