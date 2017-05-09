<?php
 /**
 *------------------------------------------------------------------------------
 * @package Purity III Template - JoomlArt
 * @version 1.0 Feb 1, 2014
 * @author JoomlArt http://www.joomlart.com
 * @copyright Copyright (c) 2004 - 2014 JoomlArt.com
 * @license GNU General Public License version 2 or later;
 *------------------------------------------------------------------------------
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// Define default image size (do not change)
$attribs = new JRegistry($this->item->attribs);
$params = $this->item->params;
$images = json_decode($this->item->images);
?>

<div class="thumbnail">

	<a href="<?php echo JRoute::_(ContentHelperRoute::getArticleRoute($this->item->slug, $this->item->catid)); ?>" title="">
		<?php if (isset($images->image_intro) && !empty($images->image_intro)) : ?>
    <div class="item-image">
		  <img src="<?php echo htmlspecialchars($images->image_intro); ?>" alt="<?php echo htmlspecialchars($images->image_intro_alt); ?>"/>
			<?php if ($attribs->get('portfolio-state')) : ?>
			<span class="item-state state-<?php echo $attribs->get('portfolio-state') ?>">
				<?php echo $attribs->get('portfolio-state') ?>
			</span>
			<?php endif ?>
    </div>
		<?php endif; ?>
		<h3><?php echo $this->item->title ?></h3>
	</a>

	<?php if ($params->get('show_intro')) : ?>
		<?php echo $this->item->introtext ?>
	<?php endif ?>

	<?php if ($attribs->get('portfolio-demo')) : ?>
		<p class="item-demo-url">
			<a class="btn btn-default" href="<?php echo $attribs->get('portfolio-demo') ?>">Live Demo</a>
		</p>
	<?php endif ?>
    
        <?php if ($params->get('show_readmore') && $this->item->readmore) :
        if ($params->get('access-view')) :
            $link = JRoute::_(ContentHelperRoute::getArticleRoute($this->item->slug, $this->item->catid, $this->item->language));
        else :
            $menu = JFactory::getApplication()->getMenu();
            $active = $menu->getActive();
            $itemId = $active->id;
            $link1 = JRoute::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
            $returnURL = JRoute::_(ContentHelperRoute::getArticleRoute($this->item->slug, $this->item->catid, $this->item->language));
            $link = new JUri($link1);
            $link->setVar('return', base64_encode($returnURL));
        endif;
    ?>
        <p class="readmore btn btn-default">
            <a href="<?php echo $link; ?>">
                <?php if (!$params->get('access-view')) :
                    echo JText::_('COM_CONTENT_REGISTER_TO_READ_MORE');
                elseif ($readmore = $this->item->alternative_readmore) :
                    echo $readmore;
                    if ($params->get('show_readmore_title', 0) != 0) :
                        echo JHtml::_('string.truncate', ($this->item->title), $params->get('readmore_limit'));
                    endif;
                elseif ($params->get('show_readmore_title', 0) == 0) :
                    echo JText::sprintf('COM_CONTENT_READ_MORE_TITLE');
                else :
                    echo JText::_('COM_CONTENT_READ_MORE');
                    echo JHtml::_('string.truncate', ($this->item->title), $params->get('readmore_limit'));
                endif; ?>
            </a>
        </p>
    <?php endif; ?>

</div>