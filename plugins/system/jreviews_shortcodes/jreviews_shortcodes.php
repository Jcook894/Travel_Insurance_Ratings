<?php
/**
 * @version       1.0 Sept 13, 2016
 * @author        ClickFWD http://clickfwd.com
 * @copyright     Copyright (C) 2010 - 2016 ClickFWD LLC. All rights reserved.
 * @license       GNU General Public License version 2 or later
 *
 * Shortcode functions from the WordPress source
 * @package WordPress
 * @subpackage Shortcodes
 * @since 2.5.0
 */

defined('_JEXEC') or die;

class plgSystemJreviews_Shortcodes extends JPlugin
{
    protected $doc;

    protected $app;

    protected $isAdmin;

    protected function shouldNotRun()
    {
        return !$this->exists()
            || $this->isAdmin
            || $this->editingListing()
            || $this->app->input->get('layout') == 'edit'
            || $this->doc->getType() !== 'html'
        ;
    }

    public function onAfterRoute()
    {
        $this->doc = JFactory::getDocument();

        $this->app = JFactory::getApplication();

        $this->isAdmin = $this->app->isAdmin();

        $debug = $this->params->get('debug', 0);

        if($this->isAdmin)
        {
            return;
        }
    }

    /**
     * Needs to be processed here because we are able to send the CSS/JS to the head of the page in this event
     * If we use onAfterRender then it's already too late
     * @return [type] [description]
     */
    public function onBeforeRender()
    {
        if($this->shouldNotRun()) {
            return;
        }

        $this->loadFramework();

        $doc = JFactory::getDocument();

        $content = $doc->getBuffer('component');

        $pattern = $this->findTag('jreviews');

        if ( 1 !== preg_match( "/$pattern/s", $content ) ) {
            return;
        }

        require_once PATH_APP . '/cms_compat/joomla/includes/shortcodes/JReviewsShortCode.php';

        $content = preg_replace_callback( "/$pattern/s", 'self::replace', $content );

        $doc->setBuffer($content, 'component');
    }

    /**
     * We process here again in case the shortcodes are included in modules
     * @return [type] [description]
     */
    public function onAfterRender()
    {
        if($this->shouldNotRun()) {
            return;
        }

        $content = JResponse::getBody();

        $pattern = $this->findTag('jreviews');

        if ( 1 !== preg_match( "/$pattern/s", $content ) ) {
            return;
        }

        require_once PATH_APP . '/cms_compat/joomla/includes/shortcodes/JReviewsShortCode.php';

        $content = preg_replace_callback( "/$pattern/s", 'self::replace', $content );

        JResponse::setBody($content);
    }

    protected function replace($match)
    {
        $attr = $this->getAttributes($match[3]);

        $JReviewsShortCode = new JReviewsShortCode;

        $out = $JReviewsShortCode->render($attr);

        return $out;
    }

    protected function findTag($name)
    {
        return
              '\\['                              // Opening bracket
            . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
            . "($name)"                          // 2: Shortcode name
            . '(?![\\w-])'                       // Not followed by word character or hyphen
            . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
            .     ')*?'
            . ')'
            . '(?:'
            .     '(\\/)'                        // 4: Self closing tag ...
            .     '\\]'                          // ... and closing bracket
            . '|'
            .     '\\]'                          // Closing bracket
            .     '(?:'
            .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
            .             '[^\\[]*+'             // Not an opening bracket
            .             '(?:'
            .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
            .                 '[^\\[]*+'         // Not an opening bracket
            .             ')*+'
            .         ')'
            .         '\\[\\/\\2\\]'             // Closing shortcode tag
            .     ')?'
            . ')'
            . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
    }

    protected function getAttributes($text)
    {
        $atts = array();

        $pattern = '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';

        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);

        if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
            foreach ($match as $m) {
                if (!empty($m[1]))
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                elseif (!empty($m[3]))
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                elseif (!empty($m[5]))
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                elseif (isset($m[7]) && strlen($m[7]))
                    $atts[] = stripcslashes($m[7]);
                elseif (isset($m[8]))
                    $atts[] = stripcslashes($m[8]);
            }
        }
        else {
            $atts = ltrim($text);
        }

        return $atts;
    }

    protected function exists()
    {
        $jreviewsPath = JPATH_SITE . DS . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'framework.php';

        return file_exists($jreviewsPath);
    }

    protected function loadFramework()
    {
        $jreviewsPath = JPATH_SITE . DS . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'framework.php';

        require_once($jreviewsPath);
    }

    protected function editingListing()
    {
        if ($menuId = $this->app->input->get('Itemid'))
        {
            if ($menu = $this->app->getMenu()->getItem($menuId))
            {
                if ((int) $menu->params->get('action') == 102)
                {
                    return true;
                }
            }
        }

        if (strstr($this->app->input->get('url'), 'edit'))
        {
            return true;
        }

        return false;
    }
}