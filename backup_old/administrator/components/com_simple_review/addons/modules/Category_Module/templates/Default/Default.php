<?php
/**
 *  $Id: Default.php 119 2009-09-05 04:51:37Z rowan $
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
class TemplateCategoryDefault
{
	var $CategoryModule = null;
	var $CategoryTitle = null;
	var $Categories = null;
	var $Reviews = null;
	var $ReviewHeadings = null;
	var $Navigation = null;
	
	function TemplateCategoryDefault(&$categoryModule, $reviewHeadings, &$categories, &$reviews, $navigation, $categoryTitle='')
	{
		$this->CategoryModule =& $categoryModule;
		$this->CategoryTitle = $categoryTitle;
		$this->Categories =& $categories;
		$this->Reviews =& $reviews;
		$this->ReviewHeadings = $reviewHeadings;
		$this->Navigation = $navigation;
		
		$cssFile =  $this->CategoryModule->_AddonManager->Bridge->SiteUrl."administrator/components/com_simple_review/addons/{$this->CategoryModule->addonType}/{$this->CategoryModule->addonName}/templates/Default/Default.css";
		Simple_Review_Common::IncludeCSS($cssFile);	
	}

	function Display()
	{
		$isAlt = true;
		$css = 'even';
	?>	
	
	<script type="text/javascript">
		function submitFilter(filterCharacter)
		{
			var form = document.srCatAndReviewForm;
			form.selectedFilter.value = filterCharacter;	
			form.submit();						
		}
	</script>
	
	<div id="srContentContainer">
		
        <div id="srContentContainer">

			<form name="srCatAndReviewForm" method="post">
				<input type="hidden" name="selectedFilter" id="selectedFilter"/>
        	
	            <div id="srContentHeader">
	            	<?php $this->CategoryModule->_DisplayHeader();?>
	            </div>			
				
	            <div id="srCategoryListingContainer">
	                <dl class="srCategoryListing">
	                	<dt><?php echo $this->CategoryTitle?></dt>
						
						<?php
						foreach($this->Categories as $c):
							$image = $c->imageUrl ? "<img  class='catImage' src='$c->imageUrl'/>" : '';											
							$css = $isAlt ? 'odd' : 'even';
							$isAlt = !$isAlt;	
						?>
	                    <dd class="<?php echo $css;?>" >
	                    	<?php echo $image;?>
							<span class="catName">
								<a href='<?php echo $c->linkUrl; ?>'><?php echo $c->catName; ?></a> (<?php echo $c->catCount; ?>)
							</span>
							<span class="catDesc"><?php echo $c->catDesc; ?></span>
	                    </dd>
						<?php endforeach?>				
	                </dl>
	            </div>
				
	            <div id="srReviewListingContainer">
	            	<div class='prefixFilter'>
	            		<a href="javascript:submitFilter(\'\')" class="prefixFilterAll"><?php echo $this->CategoryModule->GetString($this->CategoryModule, 'All');?></a>
						<a href="javascript:submitFilter(\'Other\')" class="prefixFilterOther"><?php echo $this->CategoryModule->GetString($this->CategoryModule, 'Other');?></a>
						
						<?php foreach(range('A','Z') as $i):?> 
						<a href="javascript:submitFilter('<?php echo $i;?>');" class="prefixFilterLetter"><?php echo $i;?></a>
						<?php endforeach?>											
					</div>
	
					<table id="reviewListingTable" <?php if(count($this->Reviews) == 0) echo "style='display:none'"; ?> >
						<tr>
			            	<th><?php echo $this->ReviewHeadings->title1Name;?></th>
			            	<th><?php echo $this->ReviewHeadings->title2Name;?></th>
			            	<th><?php echo $this->ReviewHeadings->title3Name;?></th>
			            	<th><?php echo $this->ReviewHeadings->rating;?></th>
				            <th><?php echo $this->ReviewHeadings->reviewer;?></th>
			            	<th><?php echo $this->ReviewHeadings->date;?></th>	            	
						</tr>
						<?php 
						$isAlt = false;
						foreach($this->Reviews as $r):
							$css = $isAlt ? 'odd' : 'even';
							$isAlt = !$isAlt;
							?>
						<tr class="<?php echo $css;?>">
			            	<td><?php echo $r->title1;?></td>
			            	<td><?php echo $r->title2;?></td>
			            	<td><?php echo $r->title3;?></td>
			            	<td><?php echo $r->rating;?></td>
				            <td><?php echo $r->reviewer;?></td>
			            	<td><?php echo $r->date;?></td>	  				
						</tr>	
						<?php endforeach?>	
					</table>	
					
	            </div>
				
				<div id="srCatNavigation">
					<div id="srCatNavigationPager"><?php echo $this->Navigation->pager;?></div>
					<div id="srCatNavigationSummary"><?php echo $this->Navigation->text;?><?php echo $this->Navigation->select;?><?php echo $this->Navigation->overview;?></div>					
				</div>						
				
	            <div id="srContentFooter">
	            	<?php $this->CategoryModule->_DisplayFooter();?>
	            </div>
						
				
			</form>
        </div>
	</div>
	<?php
	}
}
?>
