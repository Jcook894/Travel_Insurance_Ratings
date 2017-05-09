/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2017 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

(function() {

(function($) {
	'use strict';
	var jrFilterTransformer;
	jrFilterTransformer = (function() {
		var DEFAULTS;

		DEFAULTS = {
			split_option_limit: 10,
			link_css: {
				item: 'jrLink'
			},
			linkboxed_css: {
				item: 'jrLinkBoxed'
			}
		};

		function jrFilterTransformer(element, options) {
			this.filter = {};
			this.$input = $(element).hide();
			this.$container = {};
			this.$form = this.$input.closest('form');
			this.$wrapper = this.$input.closest('.jr-filter-wrap');
			this.fieldName = this.$input.data('fieldName');
			this.filterType = '';
			this.$label = this.$wrapper.find('.jr-filter-label');
			this.$selectedFilterPreview = $('<p class="jr-selected-filters-preview jrSelectedFiltersPreview">').appendTo(this.$label);
			this.$applyLink = $('<a href="javascript:;" class="jr-apply-filter jrApplyFilter jrButton jrSmall jrIconOnly"><span class="jrIconApplyFilter"></span></a>');
			this.$filterClear = $('<div class="jr-filter-reset jrHidden"><a href="javascript:;" class="jr-filter-clear jrClearFilter">'+jreviews.__t('FILTERS_CLEAR_SELECTED')+'</a></div>');
			this.$showAll = $('<a href="javascript:;" class="jrFilterShowAll jrHidden">'+jreviews.__t('FILTERS_SHOW_ALL')+'</a>');
			this.$showLess = $('<a href="javascript:;" class="jrFilterShowLess jrHidden">'+jreviews.__t('FILTERS_SHOW_LESS')+'</a>');
			this.displayAs = this.$input.data('display-as');
			this.options = this.getOptions(options);
			this.init();
		}

		jrFilterTransformer.prototype.init = function() {
			var self = this, $container;

			self.$filter = this.loadFilter();

			self.$container = self.$filter.transform();

			// Add an empty element before the input to use as reference for filters that re-use the original input instead of creating a different UI

			var $domLocation = $('<span>').hide();

			$domLocation.appendTo(self.$wrapper);

			self.$container = self.$container.insertAfter($domLocation);

			self.updateSelectedOptionsPreview();

			self.addFilterClear();

			self.addShowAll();

			// For some reason checkbox fields hide the wrapper so we force it to show
			if (self.$input.data('isControlled') === false) {
				self.$wrapper.show();
			}

			self.$input.on('change', function(e) {
				self.resetListingTypeInputs($(e.target));
			});

			if (self.$label.length > 0 && self.$label.hasClass('jr-no-slideout') == false) {
				self.$label.on('click', function() {
					self.$container.slideToggle();
					self.$wrapper.toggleClass('jrIsOpen', self.$wrapper.hasClass('jrIsOpen') == false);
				});
			} else {
				self.$container.show();
			}

			self.$input.on('updateFilter', function() {
				self.updateSelectedOptionsPreview();
				self.contextAwarenessCheck();
				self.$input.trigger('change');
			});

			self.$input.on('updateFilterNoChange', function() {
				self.updateSelectedOptionsPreview();
				self.contextAwarenessCheck();
			});

			if (self.$wrapper.data('autoOpen') == 1) {
				self.$label.trigger('click');
			}

			self.contextAwarenessCheck();
		};

		jrFilterTransformer.prototype.updateSelectedOptionsPreview = function() {
			var selectedText = [],
				preview = this.$input.data('preview') == 0 ? 0 : 1;

			if (preview == 1) {
				this.$input.children('option:selected').each(function() {
					if ($(this).val() !== '' && $(this).val() !== null) {
						selectedText.push($(this).text());
					}
				});

				this.$selectedFilterPreview.html(selectedText.join(', '));
			}
		};

		/**
		 * If it's an option input and there are no options shown, then hide the entire filter
		 */
		jrFilterTransformer.prototype.contextAwarenessCheck = function() {
			if($.inArray(this.filterType,['checkbox','select','link','linkboxed']) > -1) {
				var $options = this.$input.children('option');
				if ($options.length === 0) {
					this.$wrapper.addClass('jrHidden');
					return;
				}
				else if ($options.length === 1) {
					if ($options.attr('value') == '') {
						this.$wrapper.addClass('jrHidden');
					}
					return;
				}
				this.$wrapper.removeClass('jrHidden');
			}
		};

		jrFilterTransformer.prototype.resetListingTypeInputs = function($element) {
			var $option = $element.children('option:selected').eq(0);
			if ($option.val() == '' || ($option.data('listingType') !== undefined && $option.data('listingType') != $element.data('oldListingType'))) {
				this.$form.find('[name^="data\[Field\]"],[name="data\[usematch\]"]').val('');
			}
		};

		jrFilterTransformer.prototype.getFieldQuerySwitch = function() {
			var self = this, $querySwitch;

			if (self.$input.data('matchSwitch') == 1) {
				$querySwitch = $(
					'<div class="jrToggleSwitchRow">'
						+'<div class="jrToggleLabel">'+jreviews.__t('FILTERS_MATCH_ALL')+'</div>'
						+'<div class="jrToggleSwitch">'
						  +'<input id="toggle-'+self.fieldName+'" class="jrToggle jrToggleRound" type="checkbox" name="data[matchall]['+self.fieldName+']" value="1" '+(self.$input.data('match-all') == 1 ? 'checked' : '')+'>'
						  +'<label for="toggle-'+self.fieldName+'"></label>'
						+'</div>'
					+'</div>');

				$querySwitch.on('click', function(e) {
					var checkbox = $(this).find('input');
					checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
				});

				$querySwitch.on('change', 'input', function(e) {
					e.stopPropagation();
					if (self.$input.val() !== null && self.$input.val().length > 1) {
						setTimeout(function() {
							self.$input.trigger('change');
						},500);
					}
				});

			} else if (self.$input.data('match-all') == 1) {
				$querySwitch = $('<input type="hidden" name="data[matchall]['+self.fieldName+']" value="1" />');
			}

			return $querySwitch;
		};

		jrFilterTransformer.prototype.getOptions = function(options) {
			var dataAttr = {
				split_option_limit: this.$input.data('splitOptionLimit')
			};
			return $.extend({}, DEFAULTS, options, dataAttr);
		};

		jrFilterTransformer.prototype.getContainer = function(css) {
			var $container = $('<div class="jr-filter-container jrFilterContainer '+css+'">'
								+'<div class="jr-filter-selected jrFilterSelected jrHidden"></div>'
								+'<div class="jr-filter-unselected jrFilterUnselected"></div>'
								+'</div>');
			$container.hide();
			return $container;
		};

		jrFilterTransformer.prototype.getContainerText = function(css) {
			var $container = $('<div class="jr-filter-container jrFilterContainer '+css+'">');
			$container.hide();
			return $container;
		};

		jrFilterTransformer.prototype.addFilterClear = function() {
			var self = this;

			if (self.$input.data('reset') == 1 && typeof self.$filter['clear'] == 'function') {
				self.$container.prepend(self.$filterClear);
				self.$filterClear.on('click', '.jr-filter-clear', function() {
					self.$filter.clear();
					self.$input.trigger('updateFilter');
				});
			}
		};

		jrFilterTransformer.prototype.addShowAll = function() {
			var self = this,
				show_limit = self.$input.data('showLimit') || 5;

			var $items = self.$container.find('.jr-filter-unselected .jr-filter-item');

			if (self.$filter.allowShowAll && self.$input.data('showAll') == 1 && $items.length > show_limit) {

				self.$container.append(self.$showAll.removeClass('jrHidden'), self.$showLess);

				self.$showAll.on('click', function(e) {
					e.preventDefault();
					$(this).hide();
					self.$showLess.removeClass('jrHidden').show();
					$items.show();
				});

				self.$showLess.on('click', function(e) {
					e.preventDefault();
					$(this).hide();
					self.$container.find('.jr-filter-unselected .jr-filter-item:gt('+(show_limit - 1)+')').hide();
					self.$showAll.show();
					if (!jreviews.mobi) {
						self.$container.jrScrollTo({duration:350, offset: -100});
					}
				}).trigger('click');
			}
		};

		jrFilterTransformer.prototype.getApplyLink = function() {
			return this.$applyLink;
		};

		jrFilterTransformer.prototype.loadFilter = function() {
			var filterType;

			switch(this.displayAs) {
				case 'linkboxed':
					filterType = 'link';
				break;
				default:
					filterType = this.displayAs;
				break;
			}

			this.filterType = filterType;

			return new this.filters[filterType](this);
		};

		jrFilterTransformer.prototype.filters = {}

		// Public methods

		jrFilterTransformer.prototype.reset = function() {
			var self = this;
			this.$filter.clear();
			this.updateSelectedOptionsPreview();
			this.$filter.updateFilter(this.$container);
			// Automatically hide dependent filters when the master reset is triggered
			if (self.$input.data('isControlled') === true) {
				self.$wrapper.toggleClass('jrIsOpen', false);
				self.$wrapper.hide();
				self.$wrapper.find('.jr-filter-container').hide();
			}
		};

		jrFilterTransformer.prototype.submit = function() {
			this.$input.trigger('change');
		};

		/******************************************
		* Checkbox filter
		******************************************/

		jrFilterTransformer.prototype.filters.checkbox = function($transformer) {
			this.$transformer = $transformer;
			this.$input = $transformer.$input;
			this.splitList = $transformer.$input.data('splitList');
			this.allowShowAll = true;
		}

		jrFilterTransformer.prototype.filters.checkbox.prototype = {
			constructor: jrFilterTransformer.prototype.filters.checkbox,
			transform: function() {
				var self = this, $container;
				$container = this.$transformer.getContainer('jrCheckboxFilter');
				$container.prepend(self.$transformer.getFieldQuerySwitch());

				self.init($container);
				self.watch($container);

				return $container.on('click', '.jr-filter-item', function(e) {
		            var $option, el = $(this), checkbox = el.find('span');
					$option = self.$transformer.$input.children("[value=\"" + (el.data('value')) + "\"]");
					$option.prop('selected', $option.is(':selected') ? false : true);
					self.$transformer.$input.trigger('updateFilter');
				});
			},
			init: function($container) {
				var self = this;
				self.setup($container);

				self.$input.on('updateFilter', function() {
					self.updateFilter($container);
				}).trigger('updateFilter');

				self.$input.on('updateFilterNoChange', function() {
					self.updateFilter($container);
				});
			},
			setup: function($container) {
				var self = this;
				var $unselected = $container.find('.jr-filter-unselected').html('');

				$container.find('.jr-filter-selected').find('.jr-filter-item').remove();

				self.optionCount = 	self.$input.children('option').length;

				self.$input.children('option').each(function() {
					var $option, checkbox;
					$option = $(this);
					if ($option.val() != '') {
					    checkbox = $('<a href="javascript:;" class="jr-filter-item jrFilterItem" data-value="'+$option.val()+'"><span></span>'+$option.text()+'</a>');
					  	$unselected.append(checkbox);
					}
				});
			},
			watch: function($container) {
				var self = this;
				if (self.$input.data('isControlled')) {
					setInterval(function() {
						if (self.$input.children('option').length !== self.optionCount) {
							self.setup($container);
							self.updateFilter($container);
						}
					},100);
				}
			},
			clear: function() {
				var self = this;
				self.$input.val('');
			},
			updateFilter: function($container) {
				var self = this;
				var $selectedDiv, $unselectedDiv, $input, optionCount, countLimit = self.$transformer.options.split_option_limit;
				$input = this.$transformer.$input;
				optionCount = $input.children('option').length;
				$selectedDiv = $container.find('.jr-filter-selected');
				$unselectedDiv = $container.find('.jr-filter-unselected');
				$input.children('option').each(function() {
					var $option = $(this);
					var selected = $unselectedDiv.find('a[data-value="'+$option.val()+'"]');
					var notSelected = $selectedDiv.find('a[data-value="'+$option.val()+'"]');
					if ($option.val() != '') {
						if ($option.is(':selected')) {
							selected.find('span').removeClass('jrIconUnchecked').addClass('jrIconChecked');
							if (optionCount > countLimit && self.splitList) {
								selected.after($('<span class="jrHidden" data-value="'+$option.val()+'">')).detach();
								$selectedDiv.append(selected);
							}
						}
						else {
							notSelected.find('span').removeClass('jrIconChecked').addClass('jrIconUnchecked');
							selected.find('span').removeClass('jrIconChecked').addClass('jrIconUnchecked');
							if (optionCount > countLimit && notSelected && self.splitList) {
								notSelected.detach();
								$unselectedDiv.find('span[data-value="'+$option.val()+'"]').replaceWith(notSelected);
							}
						}
					}
				});
				$selectedDiv.toggleClass('jrHidden', $selectedDiv.html() == '');
				self.$transformer.$filterClear.toggleClass('jrHidden', $selectedDiv.html() == '');
			}
		}

		/******************************************
		* Link filter
		******************************************/

		jrFilterTransformer.prototype.filters.link = function($transformer) {
			this.options = $transformer.options;
			this.$transformer = $transformer;
			this.$input = $transformer.$input;
			this.splitList = $transformer.$input.data('splitList');
			this.css = $transformer.options[$transformer.displayAs+'_css'];
			this.allowShowAll = $transformer.displayAs !== 'linkboxed' ? true : false;
		}

		jrFilterTransformer.prototype.filters.link.prototype = {
			constructor: jrFilterTransformer.prototype.filters.link,
			transform: function() {
				var self = this, $container;
				$container = this.$transformer.getContainer(self.$transformer.displayAs == 'linkboxed' ? 'jrLinkBoxedFilter' : 'jrLinkFilter');

				if (self.$transformer.displayAs == 'link') {
					$container.prepend(self.$transformer.getFieldQuerySwitch());
				}

				self.init($container);
				self.watch($container);

				return $container.on('click', '.jr-filter-item', function(e) {
		            var $option;

		            if (self.$input.data('deselect') == 0 && self.$input.val() == $(this).data('value')) {
		            	return false;
		            }

					self.$input.data('oldListingType', self.$input.children('option:selected').data('listingType'));

					$option = self.$input.children("[value=\"" + ($(this).data('value')) + "\"]");

					if ($option.prop('selected')) {
						$option.prop('selected',false);
						if (!self.$input.prop('multiple')) {
							self.$input.val('');
						}
					} else {
						$option.prop('selected', true);
					}
					self.$input.trigger('updateFilter');
				});
			},
			init: function($container) {
				var self = this;
				var $unselected = $container.find('.jr-filter-unselected').html('');

				$container.find('.jr-filter-selected').find('.jr-filter-item').remove();

				self.optionCount = 	self.$input.children('option').length;

				self.$input.children('option').each(function() {
					var $option, $link;
					$option = $(this);
					if ($option.val() != '') {
			          $link = $('<a href="javascript:;" class="jr-filter-item jrFilterItem '+self.css.item+'" data-value="'+$option.val()+'">'+$option.text()+'</a>');
			          $unselected.append($link);
					}
				});

				self.$input.on('updateFilter', function() {
					self.updateFilter($container);
				}).trigger('updateFilter');

				self.$input.on('updateFilterNoChange', function() {
					self.updateFilter($container);
				});
			},
			watch: function($container) {
				var self = this;
				if (self.$input.data('isControlled')) {
					setInterval(function() {
						if (self.$input.children('option').length !== self.optionCount) {
							self.init($container);
						}
					},100);
				}
			},
			clear: function() {
				var self = this;
				self.$input.val('');
			},
			updateFilter: function($container) {
				var self = this;
				var $selectedDiv, $unselectedDiv, optionCount, countLimit = self.$transformer.options.split_option_limit;
				optionCount = this.$input.children('option').length;
				$selectedDiv = $container.find('.jr-filter-selected');
				$unselectedDiv = $container.find('.jr-filter-unselected');
				this.$input.children('option').each(function() {
					var $option = $(this);
					var selected = $unselectedDiv.find('a[data-value="'+$option.val()+'"]').removeClass('jrChecked');
					var notSelected = $selectedDiv.find('a[data-value="'+$option.val()+'"]');
					if ($option.val() != '') {
						if ($option.prop('selected')) {
							selected.addClass('jrChecked');
							if (optionCount > countLimit && self.splitList) {
								selected.after($('<span class="jrHidden" data-value="'+$option.val()+'">')).appendTo($selectedDiv);
							}
						}
						else {
							if (optionCount > countLimit && notSelected && self.splitList) {
								notSelected.detach();
								$unselectedDiv.find('span[data-value="'+$option.val()+'"]').replaceWith(notSelected.removeClass('jrChecked'));
							}
						}
					}
				});
				$selectedDiv.toggleClass('jrHidden', $selectedDiv.html() == '');
				self.$transformer.$filterClear.toggleClass('jrHidden', self.$input.val() == null);
			}
		};

		/******************************************
		* Select filter
		******************************************/

		jrFilterTransformer.prototype.filters.select = function($transformer) {
			this.options = $transformer.options;
			this.$transformer = $transformer;
			this.$input = $transformer.$input;
			this.allowShowAll = false;
		},
		jrFilterTransformer.prototype.filters.select.prototype = {
			constructor: jrFilterTransformer.prototype.filters.select,
			transform: function() {
				var self = this, $container;
				$container = this.$transformer.getContainerText('jrSelectFilter');

				var $input = self.$transformer.$input.prop('multiple',false).removeClass('jrSelectMultiple').addClass('jrSelect').detach();

				$container.append($input.show());

				self.$input.on('updateFilter', function() {
					self.updateFilter($container);
				}).trigger('updateFilter');

				self.$input.on('updateFilterNoChange', function() {
					self.updateFilter($container);
				});

				self.$input.on('change', function() {
					self.$input.trigger('updateFilterNoChange');
				});

				return $container;
			},
			clear: function() {
				this.$input.val('');
			},
			updateFilter: function() {
				this.$transformer.$filterClear.toggleClass('jrHidden', this.$input.val() == '');
			}
		};

		/******************************************
		* Text filter
		******************************************/

		jrFilterTransformer.prototype.filters.text = function($transformer) {
			this.options = $transformer.options;
			this.$transformer = $transformer;
			this.$input = $transformer.$input;
			this.allowShowAll = false;
		}

		jrFilterTransformer.prototype.filters.text.prototype = {
			constructor: jrFilterTransformer.prototype.filters.text,
			transform: function() {
				var self = this, $container;
				$container = this.$transformer.getContainerText('jrTextFilter');

				var $input = self.$input.on('change', function(e) {
					// We don't want the change event to be triggered unless it's done via the trigger method using the apply filter link
					if (e.originalEvent !== undefined) {
						e.stopPropagation();
					}
				}).detach();

				$container.append($input.addClass('jrText').css('display','inline-block'));

				self.$transformer.getApplyLink().appendTo($container);

				$input.on('keydown',function (e) {
					if (e.keyCode == 13) {
						e.preventDefault();
						$container.find('.jr-apply-filter').trigger('click');
					}
				});

				self.$input.on('updateFilter', function() {
					self.updateFilter($container);
				}).trigger('updateFilter');

				self.$input.on('updateFilterNoChange', function() {
					self.updateFilter($container);
				});

				return $container.on('click', '.jr-apply-filter', function(e) {
					self.$input.trigger('updateFilter');
				});
			},
			clear: function() {
				var self = this;
				self.$input.val('');
			},
			updateFilter: function() {
				this.$transformer.$filterClear.toggleClass('jrHidden', this.$input.val() == '');
			}
		};

		/******************************************
		* Autosuggest filter
		* Since it's possible for options to be added dynamically to the select list we need to
		* watch the list for changes and update accordingly
		******************************************/

		jrFilterTransformer.prototype.filters.autosuggest = function($transformer) {
			this.options = $transformer.options;
			this.$transformer = $transformer;
			this.$input = $transformer.$input;
			this.$autosuggest = $transformer.$wrapper.find('.ui-autocomplete-input').addClass('jrText jrClear').detach();
			this.allowShowAll = false;
		}

		jrFilterTransformer.prototype.filters.autosuggest.prototype = {
			constructor: jrFilterTransformer.prototype.filters.text,
			transform: function() {
				var self = this, $container;
				$container = this.$transformer.getContainerText('jrTextFilter');
				this.$container = $container; // Used in clear method

				var $input = self.$transformer.$input.detach();
				var $role = self.$transformer.$wrapper.find('.ui-helper-hidden-accessible').detach();
				var $optionsDiv = self.$transformer.$wrapper.find('.ui-optionsDiv').detach();

				self.optionCount = 	self.$input.children('option').length;

				$container.append($input, self.$autosuggest, $role);

				self.init($container);
				self.watch($container);

				self.$input.on('updateFilter', function() {
					self.updateFilter($container);
				}).trigger('updateFilter');

				self.$input.on('updateFilterNoChange', function() {
					self.updateFilter($container);
				});

				self.$input.on('autoCompleteSelect', function() {
					self.updateFilter($container);
					self.$transformer.updateSelectedOptionsPreview();
					self.$autosuggest.val('');
				});

				return $container.on('click', '.jr-filter-item', function(e) {
		            var $option,
		            	el = $(this);

					$option = self.$transformer.$input.children("[value=\"" + (el.data('value')) + "\"]");
					$option.prop('selected', $option.is(':selected') ? false : true);
					self.$input.trigger('updateFilter');
				});
			},
			init: function($container) {
				var self = this;
				var $unselected = $container.find('.jr-filter-unselected').html('');

				$container.find('.jr-filter-item').remove();

				self.optionCount = 	self.$input.children('option').length;

				this.updateCheckboxes();

				self.$input.trigger('updateFilter');
			},
			updateCheckboxes: function() {
				var self = this;
				this.$input.children('option').each(function() {
					var $option, $checkbox;
					$option = $(this);
					if ($option.val() != '') {
					    $checkbox = self.$container.find('.jr-filter-item[data-value="'+$option.val()+'"]');
					    if ($checkbox.length) {
					    	if ($option.is(':selected')) {
					    		$checkbox.find('span').switchClass('jrIconUnchecked','jrIconChecked');
					    	} else {
					    		$checkbox.find('span').switchClass('jrIconChecked','jrIconUnchecked');
					    	}
					    } else {
							var checkboxClass = $option.is(':selected') ? 'jrIconChecked' : 'jrIconUnchecked';
					    	$checkbox = $('<a href="javascript:;" class="jr-filter-item jrFilterItem" data-value="'+$option.val()+'"><span class="'+checkboxClass+'"></span>'+$option.text()+'</a>');
					  		self.$container.append($checkbox);
					    }

					  	if ($option.is(':selected')) {
					  		$checkbox.css('display','block');
					  	} else {
					  		$checkbox.hide();
					  	}
					}
				});

			},
			watch: function($container) {
				var self = this;
				setInterval(function() {
					if (self.$input.children('option').length !== self.optionCount) {
						self.init($container);
					}
				},100);
			},
			clear: function() {
				this.$input.val('').children('option').remove();
				this.$container.find('.jr-filter-item').remove();
			},
			updateFilter: function() {
				var self = this;
				self.$transformer.$filterClear.toggleClass('jrHidden', self.$input.val() == '' || self.$input.val() == null);
				self.$autosuggest.val('');
				this.updateCheckboxes();
			}
		};

		/******************************************
		* Geosearch filter
		******************************************/

		jrFilterTransformer.prototype.filters.geosearch = function($transformer) {
			this.options = $transformer.options;
			this.$transformer = $transformer;
			this.$input = $transformer.$input;
			this.$radius = this.$transformer.$wrapper.find('[name="data\[Field\]\[Listing\]\[jr_radius\]"]');
			this.allowShowAll = false;
		}

		jrFilterTransformer.prototype.filters.geosearch.prototype = {
			constructor: jrFilterTransformer.prototype.filters.geosearch,
			transform: function() {
				var self = this, $container, $slider = $();
				$container = this.$transformer.getContainerText('jrGeosearchFilter');

				var $buttonRow = self.$input.closest('.jrInputButtonRow');

				var $input = self.$input.show().on('change', function(e) {
					// We don't want the change event to be triggered unless it's done via the trigger method using the apply filter link
					if (e.originalEvent !== undefined) {
						e.stopPropagation();
					}
				});

				var $inputElement = $buttonRow.length > 0 ? $buttonRow.detach() : $input.detach();

				if (self.$radius.length) {
					var $sliderValue = $('<div class="jrSliderInputValue">');
					// Prevent Mootools conflict
					$.ui.slider.prototype.widgetEventPrefix = 'slider';
					$slider = $('<div class="jrSliderInput">').after($sliderValue)
								.slider({
								      min: self.$radius.data('min'),
								      max: self.$radius.data('max'),
								      step: self.$radius.data('step'),
								      value: self.$radius.val(),
								      slide: function( event, ui ) {
										self.$radius.val(ui.value);
										$sliderValue.html(self.radiusString());
								      }
								    });

					self.$radius.on('change', function() {
				      $slider.slider('value', $(this).val());
				    });

				    $sliderValue.html(self.radiusString());
				}

				self.$transformer.getApplyLink().appendTo($container);

				$container.append($inputElement, $slider, $sliderValue);

				self.$input.on('updateFilter', function() {
					self.updateFilter($container);
				}).trigger('updateFilter');

				self.$input.on('updateFilterNoChange', function() {
					self.updateFilter($container);
				});

				return $container.on('click', '.jr-apply-filter', function(e) {
					self.$input.trigger('updateFilter');
				});
			},
			radiusString: function() {
				return this.$radius.val() + ' <span class="jrRadiusMetric">'+this.$radius.data('metric')+'</span>';
			},
			clear: function() {
				var self = this;
				self.$input.val('').trigger('keyup');
			},
			updateFilter: function() {
				this.$transformer.$filterClear.toggleClass('jrHidden', this.$input.val() == '');
			}
		};

		/******************************************
		* Number Range filter
		******************************************/

		jrFilterTransformer.prototype.filters.numberrange = function($transformer) {
			this.options = $transformer.options;
			this.$transformer = $transformer;
			this.$container = null;
			this.$input = $transformer.$input;
	        this.$lowrange = $transformer.$input.hide();
	        this.$highrange = this.$lowrange.next('span').hide().children('input');
	        this.$operator = this.$lowrange.prev('select').val('between').hide();
			this.allowShowAll = false;
		}

		jrFilterTransformer.prototype.filters.numberrange.prototype = {
			constructor: jrFilterTransformer.prototype.filters.numberrange,
			transform: function() {
				var self = this, $container, html, $inputs;
				$container = this.$transformer.getContainer('jrRangeFilter');
				html = '<div class="jrRangeFilterInner">';
				html += 	'<label class="jrRangeFilterLabel">'+jreviews.__t('FILTERS_RANGE_MIN')+'</label><input class="jrRangeInput" type="text" placeholder="0">';
				html += 	'<span class="jrRangeDelimiter">'+jreviews.__t('FILTERS_RANGE_DELIMITER')+'</span>';
				html += 	'<label class="jrRangeFilterLabel">'+jreviews.__t('FILTERS_RANGE_MAX')+'</label><input class="jrRangeInput" type="text" placeholder="100">';
				html += '</div>';

				var $html = $(html);
				self.$transformer.getApplyLink().appendTo($html);
				$container.html($html);
				$container.on('change', 'input', function(e) {
					e.stopPropagation();
				});

		        $inputs = $container.find('input');
		        if (this.$lowrange.val() != '') {
		        	$inputs.eq(0).val(this.$lowrange.val());
		        }
		        if (this.$highrange.val() != '') {
		        	$inputs.eq(1).val(this.$highrange.val());
		        }

		        self.$container = $container;

				self.$input.on('updateFilter', function() {
					self.updateFilter();
				}).trigger('updateFilter');

				self.$input.on('updateFilterNoChange', function() {
					self.updateFilter($container);
				});

				return $container.on('click', '.jr-apply-filter', function(e) {
					var inputs;
					inputs = $container.find('input');
					self.$lowrange.val(inputs.eq(0).val());
					self.$highrange.val(inputs.eq(1).val());
					self.$lowrange.trigger('updateFilter');
		        });
			},
			clear: function() {
				var self = this;
				self.$lowrange.val('');
				self.$highrange.val('');
				self.$container.find('input').val('');
			},
			updateFilter: function() {
				var self = this;
				this.$transformer.$filterClear.toggleClass('jrHidden', self.$lowrange.val() == '' && self.$highrange.val() == '');
			}
		};

		/******************************************
		* Date Range filter
		******************************************/

		jrFilterTransformer.prototype.filters.daterange = function($transformer) {
			this.options = $transformer.options;
			this.$transformer = $transformer;
			this.$input = $transformer.$input;
	        this.$lowrange = $transformer.$input.hide();
	        this.$highrange = this.$lowrange.next('img').hide().next('span').hide().children('input');
	        this.$operator = $transformer.$input.prev('select').val('between').hide();
			this.allowShowAll = false;
		}

		jrFilterTransformer.prototype.filters.daterange.prototype = {
			constructor: jrFilterTransformer.prototype.filters.daterange,
			transform: function() {
				var self = this, $container, html, $inputs;

				$container = this.$transformer.getContainer('jrRangeFilter');
				html = '<div class="jrRangeFilterInner">';
				html = '<input class="jr-date jrDate jrRangeInput" type="text" placeholder="">';
				html += '<span class="jrRangeDelimiter">'+jreviews.__t('FILTERS_RANGE_DELIMITER')+'</span>';
				html += '<input class="jr-date jrDate jrRangeInput" type="text" placeholder="">';
				html += '</div>';

				var $html = $(html);
				self.$transformer.getApplyLink().appendTo($html);
				$container.html($html);
				$container.on('change', 'input', function(e) {
					e.stopPropagation();
				});

		        $inputs = $container.find('input');
		        if (this.$lowrange.val() != '') {
		        	$inputs.eq(0).val(this.$lowrange.val());
		        }
		        if (this.$highrange.val() != '') {
		        	$inputs.eq(1).val(this.$highrange.val());
		        }

		        self.$container = $container;

				self.$input.on('updateFilter', function() {
					self.updateFilter();
				}).trigger('updateFilter');

				self.$input.on('updateFilterNoChange', function() {
					self.updateFilter($container);
				});

				return $container.on('click', '.jr-apply-filter', function(e) {
					var inputs;
					inputs = $container.find('input');
					self.$lowrange.val(inputs.eq(0).val());
					self.$highrange.val(inputs.eq(1).val());
					self.$lowrange.trigger('updateFilter');
		        });
			},
			clear: function() {
				var self = this;
				self.$lowrange.val('');
				self.$highrange.val('');
				self.$container.find('input').val('');
			},
			updateFilter: function() {
				var self = this;
				this.$transformer.$filterClear.toggleClass('jrHidden', self.$lowrange.val() == '' && self.$highrange.val() == '');
			}
		};


		/******************************************
		* Ratings filter
		******************************************/

		jrFilterTransformer.prototype.filters.rating = function($transformer) {
			this.$transformer = $transformer;
			this.$input = $transformer.$input;
			this.allowShowAll = false;
		}

		jrFilterTransformer.prototype.filters.rating.prototype = {
			constructor: jrFilterTransformer.prototype.filters.rating,
			transform: function() {
				var self = this, $container;
				$container = this.$transformer.getContainerText('jrRatingFilter');

				this.$input.children('option').each(function() {
					var $option, link;
					$option = $(this);
					if ($option.val() != '') {
						var pct = ($option.val()/5)*100 + '%';
						link = $('<a href="javascript:;" class="jr-filter-item jrFilterItem jrRatingStar" data-value="'+$option.val()+'">'+sprintf(jreviews.__t('FILTERS_RATINGS_AND_UP'),'<div class="'+self.$input.data('class')+'"><div style="width:'+pct+'"></div></div>')+'</a>');
						$container.append(link);
					}
				});

				this.$input.on('updateFilter', function() {
					self.updateFilter($container);
				}).trigger('updateFilter');

				self.$input.on('updateFilterNoChange', function() {
					self.updateFilter($container);
				});

				return $container.on('click', '.jr-filter-item', function(e) {
		            var $option;
					$option = self.$input.children('[value="'+$(this).data('value')+'"]');
					if ($option.prop('selected')) {
						self.$input.val('');
					} else {
						$option.prop('selected', true);
					}
					self.$input.trigger('updateFilter');
				});
			},
			clear: function() {
				var self = this;
				self.$input.val('');
			},
			updateFilter: function($container) {
				this.$input.children('option').each(function() {
		            var $option, $link;
		            $option = $(this);
		            $link = $container.find('.jr-filter-item[data-value="'+$option.val()+'"]');
					$link.toggleClass('jrChecked',$option.is(':selected'));
				});
				this.$transformer.$filterClear.toggleClass('jrHidden', this.$input.val() == '');
			}
		};

		return jrFilterTransformer;

	})();

	return jQuery.fn.jrFilterTransformer = function(options) {
		// if (options == null) {
		// 	options = null;
		// }
		// return this.each(function() {
		// 	return new jrFilterTransformer(this, options);
		// });

    	 var args = arguments;

        // Is the first parameter an object (options), or was omitted,
        // instantiate a new instance of the plugin.
        if (options === undefined || typeof options === 'object') {
            return this.each(function () {

                // Only allow the plugin to be instantiated once,
                // so we check that the element has no plugin instantiation yet
                if (!$.data(this, 'plugin_jrFilterTransformer')) {

                    // if it has no instance, create a new one,
                    // pass options to our plugin constructor,
                    // and store the plugin instance
                    // in the elements jQuery data object.
                    $.data(this, 'plugin_jrFilterTransformer', new jrFilterTransformer( this, options ));
                }
            });

        // If the first parameter is a string and it doesn't start
        // with an underscore or "contains" the `init`-function,
        // treat this as a call to a public method.
        } else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {

            // Cache the method call
            // to make it possible
            // to return a value
            var returns;

            this.each(function () {
                var instance = $.data(this, 'plugin_jrFilterTransformer');

                // Tests that there's already a plugin-instance
                // and checks that the requested public method exists
                if (instance instanceof jrFilterTransformer && typeof instance[options] === 'function') {

                    // Call the method of our plugin instance,
                    // and pass it the supplied arguments.
                    returns = instance[options].apply( instance, Array.prototype.slice.call( args, 1 ) );
                }

                // Allow instances to be destroyed via the 'destroy' method
                if (options === 'destroy') {
                  $.data(this, 'plugin_jrFilterTransformer', null);
                }
            });

            // If the earlier cached method
            // gives a value back return the value,
            // otherwise return this to preserve chainability.
            return returns !== undefined ? returns : this;
        }
	};

})(jQuery);

(function($) {
	'use strict';
	var jrFiltersPanel;
	jrFiltersPanel = (function() {
		var DEFAULTS;

		DEFAULTS = {
			resizeDelay: 250,
			container_class: 'mod-container',
			title_class: 'mod-title',
			desktop_width: 992
		};

		function jrFiltersPanel(element, options) {
			var self = this;
			this.$module = $(element);
			this.options = this.getOptions(options);
			var template =
				'<div class="jrFiltersPanel">'
				+   '<div class="jrFiltersPanelHead">'
				+      '<h3 class="jrFilterPanelTitle"></h3><a href="#" class="jr-filters-panel-close jrFiltersPanelclose" onclick="javascript:;"><span class="jrIconClose"></span></a>'
				+   '</div>'
				+   '<div class="jr-filters-panel-scrollable jrFiltersPanelScrollable"></div>'
				+'</div>'
				+'<div class="jr-filters-panel-overlay jrFiltersPanelOverlay"></div>'
				;
			this.$moduleContainer = this.options.container_class ? this.$module.closest('.'+this.options.container_class) : [];
			this.$panel = $(template).hide();
			this.$overlay = this.$panel.find('.jr-filters-panel-overlay');
			this.$modulePlaceholder = $('<span class="jr-module-placeholder">').css('visibility','hidden');
			this.$filtersPlaceholder = $('<span class="jr-filters-placeholder">').hide();

			if (this.$module.length === 1 && this.$moduleContainer.length) {
				this.init();

				$(document).on('jreviews-onAfterAjaxUpdate', function(url) {
					self.refresh();
				});
			}
		};

		jrFiltersPanel.prototype.init = function() {
			this.$modulePlaceholder.insertBefore(this.$moduleContainer);

			this.bindCloseEvents();

			this.bindFiltersButton();

			this.$filtersPlaceholder.insertBefore(this.$module);

			this.moduleOffsetTop = this.$modulePlaceholder.offset().top;

			if (this.options.title_class) {
				this.$panel.find('h3').html(this.$moduleContainer.find('.'+this.options.title_class).text());
			}

			this.bindResizeEvent();
		};

		jrFiltersPanel.prototype.refresh = function() {
			this.bindFiltersButton();
			if (this.desktopView()) {
				this.$moduleContainer.hide();
				this.displayMobileFilters();
			}
			else {
				this.$moduleContainer.show();
				this.displayDesktopFilters();
			}
		};

		jrFiltersPanel.prototype.bindCloseEvents = function() {
			var self = this;
			this.$panel
				.on('click', '.jr-filters-panel-close', function(e) {
					e.preventDefault();
					self.$panel.hide();
					$('html').removeClass('jrFiltersNoScroll');
				}).
				on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();
					if ($(this).hasClass('jr-filters-panel-overlay')) {
						self.$panel.hide();
						$('html').removeClass('jrFiltersNoScroll');
					}
				})
				.appendTo($('body'));
		};

		jrFiltersPanel.prototype.bindFiltersButton = function() {
			var self = this;
			this.$toggleButton = $('.jr-list-show-filters');
			this.$toggleButton.on('click', function(e) {
				e.preventDefault();
				self.$panel.toggle();
				$('html').toggleClass('jrFiltersNoScroll', self.$panel.is(':visible'));
			});
		};

		jrFiltersPanel.prototype.desktopView = function() {
			return $('body').width() < this.options.desktop_width || this.options.desktop_width == 0;
		};

		jrFiltersPanel.prototype.bindResizeEvent = function() {
			var self = this;
			var rtime;
			var timeout = false;
			var delta = this.options.resizeDelay;

			if (jreviews.mobi === 0) {
				$(window).resize(function() {
				    rtime = new Date();
				    if (timeout === false) {
				        timeout = true;
				        setTimeout(resizeend, delta);
				    }
				});
			}

			function resizeend() {
			    if (new Date() - rtime < delta) {
			        setTimeout(resizeend, delta);
			    } else {
			        timeout = false;

					if (self.desktopView()) {
						self.$moduleContainer.hide();
						self.displayMobileFilters();
					}
					else {
						self.$moduleContainer.show();
						self.displayDesktopFilters();
					}
			    }
			}

			resizeend();
		};

		jrFiltersPanel.prototype.displayMobileFilters = function() {
			if ($.contains(this.$panel, this.$module) === false) {
				this.$module.appendTo(this.$panel.find('.jr-filters-panel-scrollable'));
			}

			this.$toggleButton.show();
		};

		jrFiltersPanel.prototype.displayDesktopFilters = function() {
			$('html').removeClass('jrFiltersNoScroll');
			this.$panel.hide();
			this.$toggleButton.hide();
			this.$filtersPlaceholder.after(this.$module);
		};

		jrFiltersPanel.prototype.getOptions = function(options) {
			return $.extend({}, DEFAULTS, this.$module.find('form').data('options') || {}, options);
		};

		return jrFiltersPanel;
	})();

	return jQuery.fn.jrFiltersPanel = function(options) {
		if (options == null) {
			options = null;
		}

		if (this.length == 1) {
			return this.each(function() {
				return new jrFiltersPanel(this, options);
			});
		}

		return this;
	};
})(jQuery);

}).call(this);