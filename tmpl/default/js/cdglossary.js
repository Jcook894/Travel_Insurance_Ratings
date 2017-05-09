/**
 * Core Design Glossary plugin for Joomla! 2.5
 * @author		Daniel Rataj, <info@greatjoomla.com>
 * @package		Joomla
 * @subpackage	Content
 * @category   	Plugin
 * @version		2.5.x.1.0.2
 * @copyright	Copyright (C) 2007 - 2012 Great Joomla!, http://www.greatjoomla.com
 * @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL 3
 * 
 * This file is part of Great Joomla! extension.   
 * This extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if (typeof(jQuery) === 'function') {
	
	(function($) {
		
		$.fn.cdglossary = function(options) {

			// set defaults
			var fncname = 'cdglossary',
			defaults 	= {
					uitheme : 'ui-lightness',
					term_corners : true,
					sticky : false,
					animationOpen : 'show',
					animationClose : 'hide',
					trackMouse : false
			},
			opts 		= $.extend(defaults, options),
			$PHP_JS 	= new PHP_JS();
			
			return this.each(function() {
				$this = $(this);
				
				$this
				.addClass( ( opts.term_corners ? 'ui-corner-all ' : '' ) + 'ui-state-default' )
				.wrap($('<span />', {
					'class' : opts.uitheme
				}));
				
				var tooltip_variables	= $PHP_JS.explode('::', $this.attr('title')),
				tooltip			= $('<div />', {
					'class' : fncname + '_tooltip' + ' ui-widget-content ui-corner-all'
				}),
				tooltip_header			= $('<div />', {
					'class' : fncname + '_tooltip_header ui-widget-header ui-corner-all',
					text : tooltip_variables[0] // only pure text is allowed as a tooltip title
				}),
				tooltip_content			= $('<div />', {
					'class' : fncname + '_tooltip_content',
					html : tooltip_variables[1] // HTML allowed
				});
				
				// sticky tooltip
				if ( opts.sticky === true ) {
					tooltip_header.prepend($('<span />', {
						'class' : fncname + '_tooltip_sticky' + ' ui-icon ui-icon-close'
					}).click(
						function() {
							$('.' + fncname + '_term').filter(function() {
								return $(this).data('active');
							}).triggerHandler('mouseleave.tooltip', [ false ] );
						}
					));
				}
				
				tooltip
				.append(tooltip_header)
				.append(tooltip_content);
				
				tooltip.appendTo('body');
				
				tooltip.wrap($('<div />', {
					'class' : opts.uitheme
				}));
				
				// remove title
				$this.removeAttr('title');
				
				$this.bind('mouseenter.tooltip', function(e) {
					
					// already opened
					if ( $('.' + fncname + '_term').filter(function() { return $(this).data('active'); }).length ) return false;
					
					$(this)
					.addClass('ui-state-hover');
					
					// position
					tooltip.position({
						of : e,
						my : 'left top',
						at : 'right bottom',
						offset : '10'
					});
					
					switch( opts.animationOpen ) {
						case 'show':
						default:
							tooltip.stop(true, true).show(0);
							break;
						
						case 'fadeIn':
							tooltip.stop(true, true).fadeIn();
							break;
							
						case 'slideDown':
							tooltip.stop(true, true).slideDown();
						break;
					}
					$(this).data({ active : true });
				});
				
				$this.bind('mouseleave.tooltip', {
					sticky : opts.sticky
				}, function(e, sticky) {
					
					// not opened yet
					if ( $('.' + fncname + '_term').filter(function() { return $(this).data('active'); }).length === 0 ) return false;
					
					sticky = ( typeof sticky !== 'undefined' ? sticky : e.data.sticky);
					
					// tooltip is sticky
					if ( sticky === true ) {
						return true;
					}
					
					$(this)
					.removeClass('ui-state-hover');
					
					switch( opts.animationClose ) {
						case 'hide':
						default:
							tooltip.stop(true, true).hide(0, function() {
								$(this).removeAttr('style');
							});
							break;
							
						case 'fadeOut':
							tooltip.stop(true, true).fadeOut( function() {
								$(this).removeAttr('style');
							} );
							break;
							
						case 'slideUp':
							tooltip.stop(true, true).slideUp( function() {
								$(this).removeAttr('style');
							} );
						break;
					}
					
					$(this).data({ active : false });
				});
				
				if ( opts.trackMouse ) {
					$this
					.bind('mousemove.tooltip', function(e) {
						// update position
						tooltip.position({
							of : e,
							my : 'left top',
							at : 'right bottom',
							offset : '10'
						});
					});
				}
				
			});
			
		};
		
	})(jQuery);
}