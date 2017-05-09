<?php
/**
 * @version       1.0 Sept 13, 2016
 * @license       GNU General Public License version 2 or later
 * Shortcode functions from the WordPress source
 * @package WordPress
 * @subpackage Shortcodes
 * @since 2.5.0
 */

namespace S2Framework\Libs;

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/**
 * A replace method is needed on the calling code to replace the tag with the processed attribute output
 * $content = preg_replace_callback( "/$pattern/s", 'self::replace', $content );
 */
class Shortcode {

	protected $pattern;

	protected $content;

    protected $tag;

    public function setTag($name)
    {
        $this->tag = $name;

        $this->pattern =
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

        return $this;
    }

    public function setContent($content)
    {
    	$this->content = $content;

    	return $this;
    }

    public function exists()
    {
    	$pattern = $this->pattern;

    	$content = $this->content;

        if ( 1 !== preg_match( "/$pattern/s", $content ) ) {
            return false;
        }

        return true;
    }

/*
     protected function replace($match)
    {
        $attr = $this->getAttributes($match[3]);

        $out = do something with the attributes

        return $out;
    }
*/

    public function replace($callback)
    {
    	$pattern = $this->pattern;

    	$content = $this->content;

		$output = preg_replace_callback( "/$pattern/s", $callback, $content );

		return $output;
    }

    static function getAttributes($text)
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
}