(function($){

	$.fn.jreviewsFields = function(options) {

		var controlFields = new jreviewsControlField(this);

		return controlFields.loadData(options);
	};

	$.fn.jrSetValidFields = function() {

		var controlFields = new jreviewsControlField(this);

		return controlFields.setValidFields(this);
	};

	$.fn.jrApplyFieldOptionLayout = function() {

		this.find('.jrFieldDiv:visible:has(.jr-option)').each(function() {
			var $fieldDiv = $(this);
			var $tab = $fieldDiv.parents('[role="tabpanel"]');
			if ($tab.length) {
				var display = $tab.css('display');
				$tab.css('display','block');
			}
			var $options = $fieldDiv.find('.jr-option');
			var maxWidth = Math.max.apply(Math, $options.map(function() {
				return $(this).width();
			}).get());

			if (maxWidth > 0) {
				$options.removeClass('jrOptionMinWidth').css('min-width', maxWidth+'px');
			}
			if ($tab.length) {
				$tab.css('display',display);
			}
		});
	};

	function jreviewsControlField(parent) {

		var debug = false,
			opts,
			$_this = this,
			$_parentElement = parent,
			$fields = $_parentElement.find(':input[class^="jr_"]'),
			$fieldGroups = $_parentElement.find('fieldset[id^="group_"]'),
			$tabs = $_parentElement.find('li[id^="tab_"]'),
			$fieldRespCache = {},
			groupArray,
			isPaidListing = false,
			entry_id;

		this.getTabs = function() {
			return $tabs;
		};

		// Used for fields without autocomplete UI
		this.click2add = function() {

			// Add new option
			$_parentElement.off('click','.jr-click2add-new').on('click','.jr-click2add-new',function(e) {

				e.preventDefault();

				// Use the jr-ready add/remove trick with delay to allow this to work in the listings form when the review form also loads

				$(this).closest('div.jrFieldDiv').find('div.jr-click2add-option:not(".jr-ready")').addClass('jr-ready').toggle(50,function() {
					$(this).removeClass('jr-ready');
				});
			});

			// Submit option
			$_parentElement.off('click','.jr-click2add-submit').on('click','.jr-click2add-submit',function(e) {

				e.preventDefault();

				var el = $(this),
					field = el.parent().siblings('select'),
					text = el.prev(':input'),
					parent_fname,
					parent_value,
					value = text.val(),
					controlledBy = '';

				if(field.data('controlledBy') && !(field.data('fieldName') in field.data('controlledBy'))) {

					$.each(field.data('controlledBy'),function(field,value){
						parent_fname = field;
						parent_value = value;
						controlledBy = '|'+field+'|'+value;
					});

				}

				var optionValue =  encodeURIComponent(value).replace(/'/g, "&#039;")+"|click2add"+controlledBy;

				var currOption = field.children('option[value="' + optionValue+'"]');

				if(value !== '' && currOption.length === 0) {

					field
						.append($("<option></option>")
							.attr({
								'value':optionValue,
								'selected':'selected',
								'data-ordering':99999, /* make sure it shows up last*/
								'data-controlledBy':parent_fname,
								'data-controlValue':parent_value
							})
							.text(value)
						)
						.data('isActive',true)
						.trigger('change');

					text.val('');

				} else if (currOption.length == 1) {
					currOption.prop('selected',true);
				}

				el.siblings('.jr_validation').remove();
			});
		};

		this.loadData = function(options) {

			var defaults = {
				fieldLocation: 'Listing',
				entry_id: null,
				context: null,
				value: [], // selected value,
				page_setup: 0, // flag to indicate if this is the initialization call
				recallValues: true,
				autocomplete: true,
				lang: {
						'select_field': jreviews.__t('FIELD_SELECT_FIELD'),
						'no_results': jreviews.__t('FIELD_NO_RESULTS'),
						'instructions': jreviews.__t('FIELD_AUTOCOMPLETE_HELP')
					}
				},
				selectedArray = {},
				fieldsArray = [];

			options = $.extend(defaults, options);

			entry_id = options.entry_id;

			options.page_setup = options.page_setup ? 1 : 0;

			opts = options;

			opts.res = opts.res || {};

			if(jreviews.paid !== undefined && opts.fieldLocation == 'Listing') {

				try {

					if(opts.res.planList !== undefined) {

						isPaidListing = true;

						jreviews.paid.plan.planList = opts.res.planList;

						jreviews.paid.plan.plan_selected = opts.res.plan_selected || jreviews.paid.plan.plan_selected;

						if(jreviews.paid.plan.plan_selected) {

							$_parentElement.find('#jr-paid-plan-list :radio[value="'+jreviews.paid.plan.plan_selected+'"]').prop('checked',true);
						}
					}
					else if(typeof jreviews.paid.plan.planList == "object" && jreviews.paid.plan.plan_selected !== null) {

						isPaidListing = true;
					}
				}
				catch(e) {
					// planList already set
				}
			}

			groupArray = $fieldGroups.map(function(){ return this.id.replace(/group_/g,''); });

			$fields.each(function(){

				if($(this).prop('type') !='button' /*&& this.id.replace(/[^A-Z]/g, "").length == 0*/) {

					var selected = $(this).data('selected'),
						fname = $(this).attr('class').split(' ')[0].replace(/[^a-zA-Z0-9_\s]+/g,'').match(/^jr_[a-z0-9]+/).toString();

					if(selected) {
						selectedArray[fname] = String(selected).search('_') > -1 ? selected.split('_') : [selected];
						$(this).removeAttr('data-selected');
						$(this).removeData('selected');
					}

					if($.inArray(fname,fieldsArray) == -1) {
						fieldsArray.push(fname);
					}
				}
			});

			fieldsArray = $.unique(fieldsArray);

			// Continue only if there are custom fields in the form
			if(fieldsArray.length) {

				if(!$.isEmptyObject(fieldsArray)) {
					options.value = selectedArray;
				}

				// Needs to be POST request because if there are too many custom fields the URL becomes too long

				var loadingFields = jreviews.dispatch({
					method:'POST',
					type:'json',
					data:{
						'data[controller]': 'fields',
						'data[action]': '_loadFieldData',
						'data[entry_id]':options.entry_id,
						'data[context]':options.context,
						'data[fields]':fieldsArray,
						'data[value]':selectedArray,
						'data[page_setup]':options.page_setup,
						'data[fieldLocation]':options.fieldLocation,
						'data[referrer]':options.referrer,
						'data[autocomplete]':options.autocomplete
						// ,'data[limit]': 5
					}
				});

				loadingFields.done(function(res) {

					$_this.processResponse(options.value, options.page_setup, res);

					// PaidListings integration
					if(isPaidListing) {

						jreviews.paid.plan.controlFieldClass = $_this;

						jreviews.paid.plan.formFilter($_parentElement);
					}

					$_this.click2add();

					if(opts.fieldLocation == 'Listing')  {

						$_parentElement.trigger('jrListingFieldsLoaded');

						// Convert select lists to checkboxes for filtering
						if (typeof $().jrFilterTransformer == 'function' && $.inArray(opts.referrer,['filtering']) > -1) {
							$_parentElement.find('[data-display-as][name^="data\[Field]"\]').jrFilterTransformer().data('plugin_jrFilterTransformer');
						}
					}

					if (opts.fieldLocation == 'Review') {
						$_parentElement.trigger('jrReviewFieldsLoaded');
					}

					// Mar 26, 2017 - Improve layout of checkbox, radio inputs into equally sized columns

					$_parentElement.jrApplyFieldOptionLayout();
				});

				return loadingFields;
			}

			return false;
		};

		this.getPaidFields = function() {

			var plans = jreviews.paid.plan.planList,
				planDiv = $_parentElement.find('#jr-paid-plan-list'),
				plan_id = jreviews.paid.plan.plan_selected;

			if(typeof plans == 'object' && plan_id !== null) {

				if(plans['plan'+plan_id] !== undefined) {

					return plans['plan'+plan_id].fields;
				}

				return [];
			}
		};

		this.getFieldOptions = function($jr_field,fieldType) {

			var out;

			switch(fieldType) {
				case 'select':
				case 'selectmultiple':
					out = $jr_field.children('option');
				break;
				default:
					out = $jr_field;
				break;
			}

			return out;
		};

		this.getFieldObj = function(field_name) {

			return field_name in $fieldRespCache ? $fieldRespCache[field_name] : false;
		};

		this.setValidFields = function(form) {

			var valid_fields = [];

			form = form || $_parentElement;

			$_parentElement.find(':input').filter(function() {

				if($(this).closest('fieldset').data('isActive') !== false && $(this).data("isActive") === true) {
					valid_fields.push($(this).data('fieldName'));
				}
			});

			var validFields = form.find('[name="data[valid_fields]"]');

			if(validFields.length) {
				validFields.val(valid_fields.join(','));
			}
			else {
				form.append($('<input type="hidden" name="data[valid_fields]" />').val(valid_fields.join(',')));
			}

			return $.unique(valid_fields);
		};

		this.getjQueryObj = function(res,field_name) {

			if(undefined === res.fields || !(field_name in res.fields)) return false;

			var convertInputToArray = false;

			if(field_name in $fieldRespCache) return $fieldRespCache[field_name];

			var multipleValueFields = ['selectmultiple','checkboxes','relatedlisting'];

			// Search form: check field type to allow field conversions to other types.
			// The input is already rendered as a different type than what is stored in the DB so we make it consistent here.
			if(/*res.page_setup == 1 && */$.inArray(opts.referrer,['adv_search_module','filtering']) > -1) {

				var $field = $_parentElement.find('.'+field_name),
					type;

				type = $field.prop('type');

				switch(type) {
					case 'select-one':
						res.fields[field_name].type = 'select';
					break;
					case 'select-multiple':
						res.fields[field_name].type = 'selectmultiple';
					break;
				}
			}

			// Only check this for search forms because integer/decimal/date fields are displayed as array inputs
			if($.inArray(opts.referrer,['adv_search_module','adv_search','filtering']) > -1 && $.inArray(res.fields[field_name].type,['integer','decimal','date']) > -1) {

				convertInputToArray = true;
			}

			var inputIsArray = $.inArray(res.fields[field_name].type,multipleValueFields) > -1 || convertInputToArray;

			var selector = ':input[name="data[Field]['+res.location+']['+field_name+']'+(inputIsArray ? '[]"]' : '"]');

			$fieldRespCache[field_name] = $_parentElement.find(selector);

			return $fieldRespCache[field_name];
		};

		this.storeValues = function($jr_field,oldVal) {

			var cachedResp = $jr_field.data('fieldOptions') || {},
				control_field_type = $jr_field.data('fieldType'),
				$fieldOptions = $_this.getFieldOptions($jr_field,control_field_type);

			oldVal = $.isArray(oldVal) ? oldVal : [oldVal];

			$(oldVal).each(function(i,old_val)
			{
				if(old_val in cachedResp)
				{
					/*******************************************
					* Store dependent groups field values
					*******************************************/
					$(cachedResp[old_val].dependent_groups).each(function(i,group)
					{
						$.each(cachedResp[old_val].fields, function(i,group_dep_field) {

							if (group_dep_field.group == group && cachedResp[oldVal] !== undefined) {
								var $dep_field = $_this.getjQueryObj(cachedResp[oldVal],group_dep_field.name);

								var dep_sel_val = $_this.getFieldValue($dep_field);

								if($dep_field.data('isControlled')===false || ($dep_field.data('isControlled')===true && $dep_field.data('hasControl')===true))
								{
		$_this.debug('6.1 >--- Store current val of ' + $dep_field.data('fieldName') + '[' + dep_sel_val + '] for '+ $jr_field.data('fieldName') + '['+old_val+']');

									var parentMemory = $dep_field.data('parentMemory') || [];

									var pm_key = $jr_field.data('fieldName')+'.'+old_val;

									parentMemory[pm_key] = dep_sel_val;

									$dep_field.data('parentMemory',parentMemory);
								}

								if($dep_field.data('hasControl')===true) {

		$_this.debug ('6.1 >--- Run recursively for ' + '['+$dep_field.attr('class').split(' ')[0]+'['+dep_sel_val+']');

									if(dep_sel_val !== null) $_this.storeValues($dep_field,dep_sel_val);
								}
							}
						});
					});

					/*******************************************
					* Store dependent field values
					*******************************************/
					$(cachedResp[old_val].dependent_fields).each(function(i,dep_fname)
					{
						// Get the dependent field object
						var $dep_field = $_this.getjQueryObj(cachedResp[old_val],dep_fname);

						if($dep_field.length === 0) return true;

						var dep_field_type = cachedResp[old_val].fields[dep_fname].type;

	$_this.debug('6.2 >--- Store current val of ' + dep_field_type + ' ' + $dep_field.data('fieldName') + '[' + $dep_field.val() + '] for '+ $jr_field.data('fieldName') + '['+old_val+']');

						var parentMemory = $dep_field.data('parentMemory') || [];

						var pm_key = $jr_field.data('fieldName') +'.'+old_val;

						/**
						 * Checkboxes and radio buttons require a different method to get the current selected options
						 * to store them in memory for later recall
						 */

						var dep_sel_val = $_this.getFieldValue($dep_field);

						parentMemory[pm_key] = dep_sel_val;

						$dep_field.data('parentMemory',parentMemory);

						// Remember values recursively for each controlled field
						if(dep_sel_val !== null)  {

							$_this.debug ('6.2 >--- Run recursively');

							$_this.storeValues($dep_field, dep_sel_val);
						}
					});
				}
			});
		};

		this.getFieldValue = function($field) {

			var value;

			switch($field.data('fieldType')) {
				case 'checkboxes':
				case 'radiobuttons':
					value = $field.filter(':checked').map(function () {
						return this.value;
					}).get();
				break;

				default:
					value = $field.val();
				break;
			}

			return value;
		},

		this.clearFieldValue = function($field) {

			switch($field.data('fieldType')) {
				case 'checkboxes':
				case 'radiobuttons':
					$field.prop('checked', false);
				break;

				default:
					$field.val('');
				break;
			}

			return $field;
		},


		this.recallValue = function($jr_field, control_fname, sel_val) {

			var parentMemory = $jr_field.data('parentMemory') || [],
				pm_key = control_fname + '.' + sel_val;

			if(parentMemory[pm_key] !== undefined)
			{
				$_this.debug('** Recall ParentMemory:' + $jr_field.data('fieldName') + ' = [' + control_fname + ':' + sel_val + '] => ' + parentMemory[pm_key]);

				var fieldType = $jr_field.data('fieldType');

				// Add any click2add options previously removed
	/*            var click2AddOptions = $jr_field.data('click2AddOptions') || [];
				if(click2AddOptions[control_fname][sel_val] != undefined) {
					$jr_field.append(click2AddOptions[control_fname][sel_val]);
				}
	*/
				if($.inArray(fieldType,['checkboxes','radiobuttons']) > -1)
				{
					$jr_field.each(function() {
						var currVal = $(this).val();
						$(this).prop('checked',$.inArray(currVal,parentMemory[pm_key]) > -1);
					});
				} else {
					$jr_field.val(parentMemory[pm_key]);
				}

				$jr_field.trigger('change');
			}
		};

		this.clearDependents = function($jr_field,val /*clear value*/,sel_val /*new value*/) {

			/**
			 * It was necessary to add the delay because the autocomplete UI element could not detect
			 * selected/checked options otherwise as they would appear unselected/unchecked without the delay
			 * @return {[type]} [description]
			 */
			setTimeout(function() {
				$_this.clearDependentsDelayed($jr_field,val /*clear value*/,sel_val /*new value*/);
			},100);
		};

		this.clearDependentsDelayed = function($jr_field,val /*clear value*/,sel_val /*new value*/) {

			var cachedResp = $jr_field.data('fieldOptions') || {},
				clearVal = [],
				control_val = val,
				control_field_type = $jr_field.data('fieldType'),
				$fieldOptions = $_this.getFieldOptions($jr_field,control_field_type);

	$_this.debug('5 >--- Clear dependents of ' + $jr_field.attr('class').split(' ')[0] + '['+val+']. Event type is ' + $jr_field.data('eventType'));

			clearVal = $.isArray(val) ? val : [val];

			$(clearVal).each(function(i,clear_val) {

				if(clear_val in cachedResp) {

					/*******************************************
					* Clear dependent groups
					*******************************************/
					$(cachedResp[clear_val].dependent_groups).each(function(i,group) {

	$_this.debug('Hide dependent group: ' + group);

						// Loop through parent control field checkboxes/select options to find if
						// other checked/selected options control this group
						var clear_flag = true;

						$fieldOptions.each(function(){

							var $option = $(this);

							if(cachedResp[$option.val()] && ($option.is(':checked') || $option.is(':selected'))) {

								// We care only about checked ones
								if($.inArray(group,cachedResp[$option.val()].dependent_groups) > -1) clear_flag = false;
							}
						});

						if(clear_flag) {

							var $fieldGroup = $fieldGroups.filter('fieldset#group_'+group);

							$fieldGroup.data('isActive',false).css('display','none');

							$.each(cachedResp[clear_val].fields, function(i, group_dep_field) {
								if (group_dep_field.group == group && cachedResp[clear_val] !== undefined) {
									var $dep_field = $_this.getjQueryObj(cachedResp[clear_val],group_dep_field.name);
									$_this.clearFieldValue($dep_field).data('doNotStore',true).trigger('change');
								}
 							});

							if($tabs.length) $tabs.filter('li#tab_'+group).hide();
						}
					});

					/*******************************************
					* Clear dependent fields
					*******************************************/
					$(cachedResp[clear_val].dependent_fields).each(function(i,dep_fname) {

						var $dep_field = $_this.getjQueryObj(cachedResp[clear_val],dep_fname);

						if($dep_field.length === 0) return true; // The element is not present on the page (adv. search module)

						var dep_field_type = cachedResp[clear_val].fields[dep_fname].type;

	$_this.debug('5.0 >--- Clear dependent [' + dep_fname + ']['+clear_val+']['+dep_field_type+']');

						var dep_sel_val = $dep_field.val();

						/**
						 * Select field options can be added and removed dynamically based on the established
						 * relations so we need to do a loop for each option
						 */
						if($.inArray(dep_field_type,['select','selectmultiple']) > -1) {

	$_this.debug('5.1 >---',dep_field_type);

							// Clear current value for dependent fields
							$(cachedResp[clear_val].fields[dep_fname].options).each(function() {

								var dep_value = this.value;

	$_this.debug(dep_fname+'['+dep_field_type+']'+'['+dep_value+']');

								if($.inArray(control_field_type,['checkboxes','selectmultiple'] > -1)) {

									// Loop through parent control field checkboxes/select options to find if
									// other checked/selected options control this group

									var clear_flag = true;

									$fieldOptions.each(function() {

										var $option = $(this);

										if(cachedResp[$option.val()] && ($option.is(':checked') || $option.is(':selected'))) {

											// We care only about checked/selected options in control field

											$(cachedResp[$option.val()].fields[dep_fname]).each(function() {

												$(this.options).each(function() {

													if(this.value == dep_value) clear_flag = false;
												});
											});
										}
									});

									if(clear_flag) {

	$_this.debug('5.1.1 >----- Removing field option [' + dep_value + ']');

										$dep_field.children('option[value="'+dep_value+'"]').remove();

									}
								}
								else {

	$_this.debug('5.1.2 >-----');

									$dep_field.children('option[value="'+dep_value+'"]').remove();
								}

								// Clear dependents recursively for each controlled field option
								if(dep_value !== null) $_this.clearDependents($dep_field,dep_value);
							});

							// Prevents newly populated values from getting cleared on event trigger
							$dep_field.data('old_val','');

							var option_count = $dep_field.children('option').length;

							if(
								(dep_field_type == 'select' && option_count == 1) ||

								(dep_field_type == 'selectmultiple' && option_count === 0)
							) {
								var click2add = $dep_field.data('click2add') || 0;

								if(click2add === 0 || (click2add == 1 && $dep_field.data('isControlled'))) {

									$dep_field.data('isActive',false).val('')/*.css('display','none')*/.closest('div.jrFieldDiv').css('display','none');
								}
							}

						}
						else {

						/**
						 * Checkboxes and radiobuttons can only be controlled as whole fields so they
						 * should be hidden from view if all parent control values are de-selected/un-checked
						 */

	$_this.debug ('5.2 >--- ' + dep_field_type);

							// Prevents newly populated values from getting cleared on event trigger
							$dep_field.data('old_val','');

							var clear_flag = true;

							if($.inArray(control_field_type,['checkboxes','selectmultiple'] > -1)) {

								// Loop through parent control field checkboxes/select options to find if other checked/selected options control this value

								$fieldOptions.each(function() {

									var $option = $(this);

									if(cachedResp[$option.val()] && ($option.is(':checked') || $option.is(':selected'))) {

										// If any selected option in control field has control over the dependent field
										// Then we don't hide the dependent field
										if($.inArray(dep_fname,cachedResp[$option.val()].dependent_fields) > -1) {

											clear_flag = false;
										}
									}
								});

								// Clear the values of the dependent field

								if(clear_flag) {
									$_this.clearFieldValue($dep_field);
								}
							}
							else {
								$_this.clearFieldValue($dep_field);
							}

							if(!clear_flag ||
								(sel_val !== undefined &&
									cachedResp[sel_val] !== undefined &&
									cachedResp[sel_val].dependent_fields.length > 0 &&
									$.inArray(dep_fname,cachedResp[sel_val].dependent_fields) > -1)) {

									// Don't hide the field
							}
							else {

								// Hide whole field

	$_this.debug('   ----- Hiding dependent field ' + dep_fname);

								$dep_field.data('isActive',false).closest('div.jrFieldDiv').css('display','none');
							}

						}

						$dep_field.trigger('change');
					});
				}
			});
		};

		this.processResponse = function(value, page_setup, res) {

			if(res.length === 0) return;

			/*********************************************************************
			* Edit mode - pre-cache responses for all fields with selected values
			**********************************************************************/
			if(res.responses !== undefined) {

				$.each(res.responses, function(fname,fresp) {

					var $jr_field = $_this.getjQueryObj(res,fname);

					if(!$jr_field) return true;

					$.each(fresp,function(k,resp) {

		$_this.debug('1 >-- Caching response data for ' + fname + '['+resp.control_value+']');

						// Cache response
						var fieldOptionsResp = $jr_field.data('fieldOptions') || {}; // get options object

						fieldOptionsResp[resp.control_value] = resp; // add new response

						$jr_field.data('fieldOptions',fieldOptionsResp); // store it

					});
				});

				// Initial load of control field options - run just once on form init
				if(page_setup) {

					$_this.populateOptions(res);

					// Bind relatedListings widget
					try {

						var $relatedField = $_parentElement.find('.jrRelatedListing');

						$relatedField.autocompletefield({lookup: 'listings', entry_id: entry_id, optionType: $relatedField.data('autocompleteType'), optionDivPosition: $relatedField.data('autocompletePos')});
					}
					catch (err) {
						//
					}
				}
			}

			// Attach event trigger to fields
			$.each(res.control_field, function(k,fname) {

				var $jr_field = $_this.getjQueryObj(res,fname);

				// If text field no need to bind it to any events
				if(undefined === res.fields || res.fields[fname].type == 'text') {

					return true;
				}

				if($jr_field.data('hasControl') === false && !$jr_field.hasClass('jrAutoComplete')) {

					return true;
				}

				$jr_field.on('change',function() {

	$_this.debug('2 >--- Change event triggered for ' + fname + ', id = ' + $jr_field.attr('class').split(' ')[0] + ', ' + res.fields[fname].type);

					var sel_val = '';
					var selArray = []; // For checkboxes and multiple selects
					var unselArray = [];
					var old_val = $jr_field.data('old_val') || ($.inArray(res.fields[fname].type,['selectmultiple'/*,'checkboxes'*/]) ? [] : null);
					var cache_key, cache_res;
					var clicked_val;

	$_this.debug('   Old val: ' + old_val);

					// Getting the selected value varies depending on the input type
					switch(res.fields[fname].type) {

						case 'selectmultiple':

							// Create array of non-selected values used to clear dependents
							$jr_field.children('option').not(':selected').each(function() {
								unselArray.push($(this).val());
							});

							selArray = $jr_field.val();

							if(selArray === null) selArray = [];

							if(selArray.length == 1) {

								sel_val = selArray[0];
							}
							else if(old_val !== null) {

								for(var i=0; i<selArray.length; i++) {

									if($.inArray(selArray[i], old_val) == -1) {

										sel_val = $jr_field.children('option[value="' + selArray[i] + '"]').val();

										break; // break out of for loop
									}
								}
							}

							if (old_val === null && selArray.length > 0) {
								sel_val = selArray;
							}

						break;

						case 'radiobuttons':
						case 'checkboxes':

							selArray = $_this.getFieldValue($jr_field);

							if($(this).is(':checked')) sel_val =  $(this).val();

							clicked_val = $(this).val();

						break;

						default:

							sel_val =  $jr_field.val();
						break;

					}

	$_this.debug('   Sel val: ' + sel_val);

					/**************************************************
					*  Set dependent clear value based on field type
					**************************************************/
					var clear_val;

					switch(res.fields[fname].type) {

						case 'checkboxes':

							if(sel_val === '') clear_val = clicked_val;

						break;

						case 'selectmultiple':

							if(unselArray.length > 0) clear_val = unselArray;

						break;

						default:

							clear_val = old_val;

						break;
					}

					var doNotStore = $jr_field.data('doNotStore') || false;

					if(opts.recallValues && old_val !== null && old_val.length > 0 && doNotStore === false) {

						$_this.storeValues($jr_field,old_val);
					}

					// Store curr val
					$jr_field.data('old_val', (selArray.length ? selArray : sel_val));

					// Check cached response object
					var fieldOptionsResp = $jr_field.data('fieldOptions') || {};

					if ($.isArray(sel_val)) {
						// It's a multiple select list so each selected option needs to be processed separately
						$.each(sel_val, function(i, val) {
							$_this.processResponsePopulateDependents($jr_field, res, opts, fieldOptionsResp, fname, val, clear_val);
						});
					} else {
						// Other fields have one or more inputs so those inputs are already being processed separately
						$_this.processResponsePopulateDependents($jr_field, res, opts, fieldOptionsResp, fname, sel_val, clear_val);
					}

					$jr_field.prop('disabled',false);

					$jr_field.data('doNotStore',false);

				});  // end bind event

				// Init auto complete fields
				if($jr_field.hasClass('jrAutoComplete') && !$jr_field.hasClass('jrRelatedListing') && $.inArray($jr_field.data('fieldType'),['select','selectmultiple']) > -1) {

	$_this.debug(' -> Activate AutoComplete UI for ' + fname);

					$jr_field.autocompletefield({lookup: 'options', optionType: $jr_field.data('autocompleteType'), optionDivPosition: $jr_field.data('autocompletePos')});
				}
			});
		};

		this.processResponsePopulateDependents = function($jr_field, res, opts, fieldOptionsResp, fname, sel_val, clear_val) {

			cachedResp = (sel_val === null || fieldOptionsResp == {}) ? null : fieldOptionsResp[sel_val];

$_this.debug('   Cached response');
$_this.debug(undefined === cachedResp ? '       Not cached' : cachedResp);

			/**************************************************
			*  Populate dependent fields and load response
			* from server if necessary
			**************************************************/

			if((cachedResp === undefined && sel_val !== '' && sel_val !== null)) {

$_this.debug(' -> Change Event AJAX');

				$jr_field.prop('disabled',true);

				var data = {
					'data[fields]':fname,
					'data[value]':sel_val,
					'data[page_setup]':false,
					'data[context]':opts.context,
					'data[fieldLocation]':opts.fieldLocation,
					'data[referrer]':opts.referrer,
					'data[autocomplete]':opts.autocomplete
				};

				var loadingFields = jreviews.dispatch({method:'GET',type:'json',controller:'fields',action:'_loadFieldData',data:data});

				loadingFields.done(function(valResp){

					// Cache response
$_this.debug('1 >-- Caching data for ' + fname + '['+sel_val+']');

					var fieldOptionsResp = $jr_field.data('fieldOptions') || {}; // get options object

					fieldOptionsResp[sel_val] = valResp; // add new response

					$jr_field.data('fieldOptions',fieldOptionsResp); // store it

					$jr_field.data('eventType','ajax');

					if(undefined === valResp.fields) valResp.fields = [];

					$_this.populateOptions(valResp, res.fields[fname], $jr_field, clear_val, sel_val);

					$_parentElement.trigger('jrAfterFieldChange');
				});
			}
			else if(!$.isEmptyObject(cachedResp) && sel_val !== '')
			{
$_this.debug(' -> Change Event CACHED');
				$jr_field.data('eventType','cached');

				$_this.populateOptions(cachedResp, res.fields[fname], $jr_field, clear_val, sel_val);

				$_parentElement.trigger('jrAfterFieldChange');
			}
			else {

$_this.debug(' -> Change Event CLEAR');

				$jr_field.data('eventType','clear');

				$_this.clearDependents($jr_field,clear_val,sel_val);

				$_parentElement.trigger('jrAfterFieldChange');
			}
		};

		this.populateOptions = function(res, controlField, $controlField, clear_val, sel_val) {

			if(res.length === 0) return;

			controlField = controlField !== undefined ? controlField : {'type':'','name':''};

	$_this.debug('4 >--- Populate options for ' + controlField.name + '['+ sel_val +'] and we clear it for [' + clear_val + ']');

			// When initializing the page show all groups that are not dependent groups
			if(res.page_setup) {


				$.each(groupArray,function(i,group){

					if($.inArray(group,res.dependent_groups) == -1) {

						var $GroupFieldset = $fieldGroups.filter('fieldset#group_'+group);

						var counter = 0;

						$GroupFieldset.find(':input').each(function() {
							if($(this).prop('type') != "hidden") counter++;
						});

						if(counter >0) {
							$GroupFieldset.show();
							if($tabs.length) $tabs.filter('li#tab_'+group).show(10);
						}
					}
				});
			}

			// Loop through controlled groups and toggle them
			$.each(res.dependent_groups,function(k,group){

				var $group = $fieldGroups.filter('fieldset#group_'+group);

				$group.data('isControlled',true);

				if(res.page_setup) {

					$group.css('display','none').data('isActive',false);
					if($tabs.length) $tabs.filter('li#tab_'+group).hide();
				}
				else {

					$group.show().data('isActive',true);
					if($tabs.length) $tabs.filter('li#tab_'+group).show(10);
				}
			});

			// Loop through fields for current action
			var paidFields = [];

			if(isPaidListing) {

				paidFields = $_this.getPaidFields();

				$_this.debug('   --- It is a paid listing!');

				$_this.debug(paidFields);
			}

			$.each(res.fields, function(k,field) {

				$_this.debug('   --- Populating field ' + field.name+'['+field.title+']['+sel_val+']');

				if($.isArray(field.name)) return true;

				var $jr_field = $_this.getjQueryObj(res,field.name),
					type;

				type = $jr_field.prop('type');

				if(field.type != 'relatedlisting')
				{
					switch(type) {
						case 'select-one': field.type = 'select'; break;
						case 'select-multiple': field.type = 'selectmultiple'; break;
					}
				}

				$jr_field.data('fieldTitle',field.title);

				if(res.page_setup === 0  &&
					/* don't clear it unless the page has already been set up */
					$.inArray(field.type,['select','selectmultiple']) > -1)
				{

					$jr_field.val(''); /* Resets selected value - required for click2add feature so switching to a different parent option doesn't show the new option selected */
				}

				if(res.page_setup) {
					$jr_field.data('fieldName',field.name);
					$jr_field.data('fieldType',field.type);
					$jr_field.data('orderBy',field.order_by);
					$jr_field.data('hasControl',field.control);
					$jr_field.data('isControlled',field.controlled);
					$jr_field.data('autocompleteType',field.autocompletetype);
					$jr_field.data('autocompletePos',field.autocompletepos);
				}

				var fieldInDependentGroup = $.inArray(field.group,res.dependent_groups) > -1;

				var groupIsActive = $fieldGroups.filter('fieldset#group_'+field.group).data('isActive');

				if(res.control_field.length == 1) {
					var controlledBy = $jr_field.data('controlledBy') || {};

					/* Click2Add options will use the current parent value */  /*$fieldRespCache[res.control_field[0]].val()*/
					controlledBy[res.control_field[0]] = res.control_value;

					if(!fieldInDependentGroup){
						$jr_field.data('controlledBy',controlledBy);
					}

					// Necessary check for text fields inside controlled groups
					if((res.page_setup === 0 ||
							res.page_setup === undefined /*because it's cached*/) &&
							field.type == 'text' && field.controlled === true && field.controlgroup === true) {
						return true;
					}
				}

				// Hide fields on startup
				if ($jr_field.data('isActive') === undefined /* forces unselected dependent fields to appear in adv. search module */ &&
					res.page_setup &&
					(
							($.inArray(field.type,['select','selectmultiple','relatedlisting']) > -1 &&
								( // in between field
									(field.control === true && field.controlled === true) ||
									// leaf field
									(field.control === false && field.controlled === true) ||
									// regular select list without options
									(field.autocomplete == "0" && field.options === undefined)
								)
							) ||

							($.inArray(field.type,['checkboxes','radiobuttons']) > -1 &&
								( // in between field
									(field.control === true && field.controlled === true) ||
								// leaf field
									(field.control === false && field.controlled === true)
								)
							) ||

							(// hides all other dependent fields
								!field.control && field.controlled && (field.selected === undefined || field.selected.length === 0)
							)
				)){
					// Don't remove existing options that may be there as dependents of other control fields
					if(field.type == 'select') {
						$jr_field
							.html(
								$('<option></option>')
									.attr('value','')
									.text(opts.lang.select_field.replace('%s', $jr_field.data('fieldTitle')))
									.data('ordering',0)
							)
							.children('option:eq(0)').prop('selected',true);
					}

					if(typeof $jr_field.relatedlisting == 'function' && !$jr_field.data('isControlled')) {

						$jr_field.data('isActive',true);

						return true;
					}

	$_this.debug('   ----- Hiding field ' + field.name + ' on startup');

					var click2add = $jr_field.data('click2add') || 0;

					if(click2add === 0 || (click2add == 1 && $jr_field.data('isControlled'))) {

						$jr_field.data('isActive',false)./*css('display','none').*/closest('div.jrFieldDiv').css('display','none');
					}

					return true;
				}

				// Don't remove existing options that may be there as dependents of other control fields
				if(field.type == 'select' && $jr_field.children('option').length === 0) {

					$jr_field.html(
						$('<option></option>')
							.attr('value','')
							.text(opts.lang.select_field.replace('%s',$jr_field.data('fieldTitle')))
							.data('ordering',0)
						);
				}

				if($.inArray(field.type,['select','selectmultiple','relatedlisting']) > -1) {

					$jr_field.children('option[data-controlledBy="'+res.control_field[0]+'"]').each(function(){
						var $option = $(this);
						if( $option.data('controlValue') != res.control_value) {
							$option.remove();
						}
					});

					$(field.options).each(function() {

						if($jr_field.children('option[value="'+this.value+'"]').length === 0) { // add the option if not already there

							$jr_field.append(

								$('<option></option>')
									.attr('value',this.value)
									.text(this.text)
									.attr('data-ordering',this.ordering)
							);
						}
					});
				}

				// Feb 9, 2017 - Removed the edit only check because now we can also pre-select default option values for new forms
				// Pre-select current values and show dependent groups
				if(field.selected !== undefined && field.selected.length > 0)
				{
					// Checkboxes and radiobuttons are already pre-selected
					if($.inArray(field.type,['checkboxes','radiobuttons']) == -1) {

						$jr_field.val(field.selected);
					}

					$jr_field.data('old_val',field.selected);

					$(field.selected).each(function(i,edit_val)
					{

						if(field.name in res.responses && edit_val in res.responses[field.name])
						{
$_this.debug('  +++++++ Start populateOptions recursion for ' + field.name);

							$jr_field.data('eventType','cached');

							// Run recursively to add options to all dependent select lists and show dependent groups
							$_this.populateOptions(res.responses[field.name][edit_val], field, $jr_field, undefined, edit_val);
						}
					});
				}

				// Some dependent fields end up with no pre-selected default so we force it to -- Select --
				if(field.type == 'select' && $jr_field.find(':selected').length === 0) {

					$jr_field.children('option:eq(0)').prop('selected',true);
				}

				// On listing edit, stop unpaid fields from showing up for the selected plan
				if(!res.page_setup && isPaidListing && $.inArray(field.name, paidFields) == -1) {

					$jr_field.data('isActive',true);

					return true;
				}

	$_this.debug('   ***** Start checks for displaying fields for ['+field.title+']['+field.type+']');

				if(fieldInDependentGroup && groupIsActive !== true) {

					$jr_field.data('isActive',false)/*.show('fast')*/.closest('div.jrFieldDiv').hide();
				}
				else {

					switch(field.type)
					{
						case 'select':
							if($jr_field.children('option').length > 1 || ($jr_field.data('click2add') == 1 && !fieldInDependentGroup) || ($jr_field.data('hasControl') === true && $jr_field.data('isControlled') === false) || ($jr_field.data('hasControl') === false && $jr_field.data('isControlled') === false) ) {

								$jr_field.data('isActive',true)/*.show('fast')*/.closest('div.jrFieldDiv').show();
							}
							else {
								$jr_field.data('isActive',false)/*.show('fast')*/.closest('div.jrFieldDiv').hide();
							}
						break;
						case 'selectmultiple':

							if($jr_field.children('option').length > 0 || ($jr_field.data('click2add') == 1 && !fieldInDependentGroup) || ($jr_field.data('hasControl') === true && $jr_field.data('isControlled') === false) || ($jr_field.data('hasControl') === false && $jr_field.data('isControlled') === false)) {

								$jr_field.data('isActive',true)/*.show('fast')*/.closest('div.jrFieldDiv').show();
							}
						break;

						case 'checkboxes':
						case 'radiobuttons':

							if(!fieldInDependentGroup || (fieldInDependentGroup && groupIsActive && ($jr_field.data('hasControl') === true && $jr_field.data('isControlled') === false) || ($jr_field.data('hasControl') === false && $jr_field.data('isControlled') === false))) {

								$jr_field.data('isActive',true)/*.show('fast')*/.closest('div.jrFieldDiv').show();
							}

						break;
						default:
								$jr_field.data('isActive',true)/*.show('fast')*/.closest('div.jrFieldDiv').show();
						break;
					}

				}

				// Re-sort list
				if($jr_field.children('option').length > 0 && $jr_field.data('orderBy') == 'ordering') {
					$_this.sort_by_field($jr_field);
				}

			});

			if(undefined !== $controlField && undefined !== clear_val) {

				$_this.clearDependents($controlField,clear_val,sel_val);
			}

			/**********************************************************
			* Recall previous vals for selected parent val
			**********************************************************/
			if(!res.page_setup && opts.recallValues)
			{
				$(res.dependent_fields).each(function(i,fname) {

					$_this.recallValue($('.'+fname), controlField.name, sel_val);
				});

				$.each(res.dependent_groups,function(k,group) {

					$fieldGroups.filter('fieldset#group_'+group).find(':input').each(function() {

						var hasControl = $(this).data('hasControl'),
							isControlled = $(this).data('isControlled');

						if(hasControl || (hasControl === false && isControlled === false)) {

							$_this.recallValue($(this), controlField.name, sel_val);
						}
					});
				});
			}
		};

		this.sort_by_field = function($field)
		{
			var selected = $field.val(),
				order_type = $field.data('orderBy'),
				type,
				selectOption;

			var primer = order_type == 'ordering' ? function(a){return parseInt(a,10);} : function(a){return a.toUpperCase();};

			type = $field.prop('type');

			if(type == 'select-one') {
				selectOption = $field.children('option[value=""]').detach();
			}

			var options = $field.children('option').sort(function(a,b)
			{
				a = order_type == 'ordering' ? $(a).data('ordering') : a.text;

				b = order_type == 'ordering' ? $(b).data('ordering') : b.text;

				if (typeof(primer) != 'undefined'){
					a = primer(a);
					b = primer(b);
				}

				if (a<b) return -1;
				if (a>b) return 1;
				return 0;
			});

			if(selectOption !== undefined) {

				$field.html(options).prepend(selectOption).val(selected);
			}
			else {

				$field.html(options).val(selected);
			}
		};

		this.debug = function(msg) {
			if(debug) console.log(msg);
		};
	}

})(jQuery);

/* Autocomplete UI widget for lists */

(function($) {

	$.widget("jreviews.autocompletefield",{

		lookup: 'options',
		// default options
		options: {
			entry_id: null,
			optionType: 'link', // 'link'|'checkbox'
			optionDivPosition: 'after', //'before'
			minSearchLength: 1,
			lang: {
				help: jreviews.__t('FIELD_AUTOCOMPLETE_HELP'),
				no_results: jreviews.__t('FIELD_NO_RESULTS')
			},
			helpClass: 'acInstructions',
			inputClass: 'jrAutoSuggest',
			disabledLinkClass: 'ui-disabled',
			checkboxClass: 'ui-option',
			optionsDivClass: 'ui-optionsDiv'
		},

		_create: function(options) {

			$.extend(this.options, options);

			var self = this,
				el = self.element.hide(),
				o = self.options,
				fname = (self.fname = el.data('fieldName')),
				widgetid = (self.widgetid = 'text-' + fname);

			self.lookup = o.lookup;

			el.data('validation',false); // Set initial status

			// Here we generate the widget that will replace the original element and insert it in the DOM
			var searchInput = (this.searchInput = $('<input type="text" />'))
					.attr({id: widgetid, name: widgetid})
					.addClass( o.helpClass + ' ' + o.inputClass)
					.insertAfter( el );

			if (el.attr('placeholder') != undefined) {
				searchInput.attr('placeholder', el.attr('placeholder'));
			}
			else {
				searchInput.val(o.lang.help);
			}

			var optionsDiv = (this.optionsDiv = $('<div></div'))
					.attr('data-fname', fname)
					.addClass(o.optionsDivClass + ' jrClearfix');

			var noResults = (this.noResults = $('<span></span>') )
					.html(o.lang.no_results)
					.css('margin-left','5px')
					.addClass('jrValidation');

			switch(o.optionDivPosition) {
				case 'before': optionsDiv.insertBefore(searchInput); break;
				case 'after': optionsDiv.insertAfter(searchInput); break;
			}

			this._bindEvents();
		},

		_init: function(options) {

			var self = this,
				el = self.element,
				o = self.options,
				optionsDiv = self.optionsDiv;

			// Find pre-selected options and add them to optionsDiv
			var $fieldOptions = el.children('option:selected');

			if($fieldOptions.length) {

				$.each($fieldOptions, function()
				{
					var $option = $(this);
					if($option.val() === '') return true;

					switch(o.optionType) {
						case 'link':
							var newOption = self._createLink({'value':$option.val(),'text':$option.text(),'checked':true});
						break;
						case 'checkbox':
							newOption = self._createCheckbox({'value':$option.val(),'text':$option.text(),'checked':true});
						break;
					}

					optionsDiv.append(newOption);
				});
			}

			switch(o.optionType) {
				case 'link':
					if(optionsDiv.find('a').length === 0) optionsDiv.hide(); else optionsDiv.show(5);
				break;
				case 'checkbox':
					if(optionsDiv.find('input').length === 0) optionsDiv.hide(); else optionsDiv.show(5);
				break;
			}
		},

		_createCheckbox: function(option) {

			var fname = this.fname,
				o = this.options,
				checkboxid = 'cb-'+fname+'_'+option.value.replace(/ /g,"_"),

				checkbox = $('<input type="checkbox" />')
					.attr({id: checkboxid, name: checkboxid})
					.addClass(o.checkboxClass)
					.val(option.value)
					.prop('checked',option.checked),

				label = $('<label for="'+checkboxid+'" />')
				.css('text-align','left')
				.append(checkbox)
				.append('<span>'+option.text+'</span>');

			return label;
		},

		_createLink: function(option) {

			var o = this.options;

			return $('<a href="javascript:void(0)"></a>')
				.addClass(o.checkboxClass)
				.html(option.text)
				.attr('data-value',option.value)
				.data('active',option.checked ? true : false);
		},

		_getListingData: function(value, callback, id) {

			var el = this.element;

			id = id || '';

			var data = {
				limit: 12,
				id: id,
				fname: el.attr('class').split(' ')[0],
				listingtype: el.data('listingtype') || '',
				value: value
			};

			var submittingAction = jreviews.dispatch({method:'get',type:'json',controller:'fields',action:'relatedListings',data: data});

			submittingAction.done(function(data) {
				callback(data);
			});

		},

		_bindEvents: function() {

			var self = this,
				el = self.element,
				o = self.options,
				searchInput = self.searchInput,
				optionsDiv = self.optionsDiv;

			// Events for the widget text input
			if (searchInput.attr('placeholder') == undefined) {
				searchInput
					.on('click focusin',function(){
						if(this.value == o.lang.help) {
							$(this).val('').removeClass(o.helpClass);
						}
					})
					.on('blur focusout',function(){
						if(this.value === '') {
							$(this).val(o.lang.help).addClass(o.helpClass);
						}
					});
			}

			this._bindAutoComplete(); // Adds jQuery UI autocomplete to searchInput

			// Events for the widget optionsDiv
			// Bind change event to current and future widget options
			optionsDiv
				.delegate(':input:checkbox','change',function(){
					var widgetOption = $(this);
					var value = widgetOption.val();
					var label = widgetOption.next('span').html();
					var sourceOption = el.children('option[value="'+value+'"]');

					if(widgetOption.is(':checked')) { // Checked
						if(sourceOption.length > 0) {
							sourceOption.prop('selected',true);
						}
						else {
							sourceOption.append($("<option></option>").attr("value",value).text(label));
						}
					}
					else if(sourceOption.length > 0) { // Unchecked
						sourceOption.prop('selected',false);
					}
					el.trigger('change');
					return false;
				})
				.delegate('a','click',function(){
					var widgetOption = $(this);
					var value = widgetOption.data('value');
					var label = widgetOption.html();
					var sourceOption = el.children('option[value="'+value+'"]');

					if(widgetOption.data('active') === false) { // Checked
						widgetOption.data('active',true).removeClass(o.disabledLinkClass);
						if(sourceOption.length > 0) {
							sourceOption.prop('selected',true);
						}
						else {
							sourceOption.append($("<option></option>").attr("value",value).text(label));
						}
					}
					else if(sourceOption.length > 0) { // Unchecked
						widgetOption.data('active',false).addClass(o.disabledLinkClass);
						sourceOption.prop('selected',false);
					}
					el.trigger('change');
					return false;
				});

			// Events for the widget source element
			switch(o.optionType) {
				case 'link':
					this._bindForLinks();
				break;
				case 'checkbox':
					this._bindForCheckboxes();
				break;
			}

			this._bindClick2Add();
		},

		_bindForCheckboxes: function() {

			// Add mirror effect. What happens to source element also happens to the widget
			var self = this,
				el = self.element,
				o = self.options,
				optionsDiv = self.optionsDiv;

			el
				.on('change',function() {

					//console.log('Errrrrmm something changed in '+el.data('fieldTitle'));
					var widgetOptions = optionsDiv.find(':input'),
						sourceOptions = el.children('option'),
						listArray = sourceOptions.map(function() { return this.value; });

					sourceOptions.each(function() {
						var sourceOption = $(this),
							value = sourceOption.val();

						if(value !== '') {

							var widgetOption = optionsDiv.find(':input[value="'+value+'"]');

							if(sourceOption.is(':selected') === false) {
								if(widgetOption.length) {
									widgetOption.prop('checked',false);
								}
							}
							else {
								if(widgetOption.length) {
									widgetOption.prop('checked',true);
								}
								else {

									optionsDiv.show(5).append(self._createCheckbox({'value':value,'text':sourceOption.text(),'checked':true}));
								}
							}
						}
					});

					// Loop through widget options and remove those not in the select list.
					// Important with controlled fields to remove old options.
					widgetOptions.each(function() {

						var widgetOption = $(this);

						if($.inArray(widgetOption.val(), listArray) == -1) {
							widgetOption.closest('label').remove();
						}
					});

					if(optionsDiv.find(':input').length === 0) optionsDiv.hide();
				});
		},

		_bindForLinks: function() {

			// Add mirror effect. What happens to source element also happens to the widget
			var self = this,
				el = self.element,
				o = self.options,
				optionsDiv = self.optionsDiv;

			el
				.on('change',function() {
					//console.log('Errrrrmm something changed in '+el.data('fieldTitle'));
					var widgetOptions = optionsDiv.find('a'),
						sourceOptions = el.children('option'),
						listArray = sourceOptions.map(function() { return this.value; });

					sourceOptions.each(function() {

						var sourceOption = $(this),
							value = sourceOption.val();

						if(value !== '') {

							var widgetOption = optionsDiv.find('a[data-value="'+value+'"]');

							if(sourceOption.is(':selected') === false) {

								if(widgetOption.length) {
									widgetOption.data('active',false).addClass(o.disabledLinkClass);
								}
							}
							else {

								if(widgetOption.length) {
									widgetOption.data('active',true).removeClass(o.disabledLinkClass);
								}
								else {
									optionsDiv.show(5).append(self._createLink({'value':value,'text':sourceOption.text(),'checked':true}));
								}
							}
						}
					});

					// Loop through widget options and remove those not in the select list.
					// Important with controlled fields to remove old options.

					widgetOptions.each(function() {

						var widgetOption = $(this);

						if($.inArray(String(widgetOption.data('value')), listArray) == -1) {
							widgetOption.remove();
						}
					});

					if(optionsDiv.find('a').length === 0) optionsDiv.hide();
				});
		},

		_bindAutoComplete: function() {

			var self = this,
				el = self.element,
				o = self.options,
				searchInput = self.searchInput,
				optionsDiv = self.optionsDiv,
				fid = el.data('field') !== undefined ? el.data('field').id : '',
				fname = self.fname;

			var positionMy = jreviews.isRTL ? "right top" : "left top",
				positionAt = jreviews.isRTL ? "right bottom" : "left bottom";

			// Attach auto complete function to text field
			searchInput
				.autocomplete({

					position: { my : positionMy, at: positionAt },

					minLength: o.minSearchLength,

					source: function( request, response ) {

						var term = request.term.toLowerCase(),
							dataResp = [],
							widgetOptionArray;

						switch(o.optionType) {

							case 'link':

								widgetOptionArray = optionsDiv.find('a').map(function() {
									return $(this).data('value');
								});

								break;

							case 'checkbox':

								widgetOptionArray = optionsDiv.find(':input').map(function() {
									return this.value;
								});

								break;
						}

						if(el.data('isControlled') && self.lookup != 'listings') {

							// The list of possible options is already pre-loaded in the target input list
							var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );

							dataResp = el.children( "option" ).map(function() {

								var text = $(this).text();

								if (this.value !== '' && ( !request.term || matcher.test(text) ) && $.inArray(this.value,widgetOptionArray) == -1) {

									return {
										label: text,
										value: this.value
									};
								}

							});

							response(dataResp);

							self._noMatch(dataResp);
						}
						else {

							switch(self.lookup) {

								case 'listings':

									self._getListingData(term,function(res) {

										$(res).each(function(i,row) {
											if(row !== undefined && ($.inArray(parseInt(row.value,10),widgetOptionArray) == -1 || $.inArray(row.value,el.val()))) {
												dataResp.push(row);
											}
										});

										response(dataResp);

										self._noMatch(dataResp);

									}, o.entry_id);

									break;

								case 'options':

									var data = { limit: 12, field_id: fid, value: term};

									var submittingAction = jreviews.dispatch({method:'get',type:'json',controller:'fields',action:'_loadValues',data:data});

									submittingAction.done(function(res) {

										$(res).each(function(i,row) {
											if(row !== undefined && ($.inArray(row.value,widgetOptionArray) == -1 || $.inArray(row.value,el.val()))) {
												dataResp.push(row);
											}
										});

										response(dataResp);

										self._noMatch(dataResp);
									});

									break;
								}
							}
					},

					search: function( event, ui) {

						if(this.lookup == 'options' && fid === '') return false;
					},

					focus: function( event, ui) { /* Force select label to show up in text input */

						searchInput.val(ui.item.label);

						return false;
					},

					select: function( event, ui ) {

						if(ui.item.value !== '')
						{
							searchInput.val('').focus();

							// Required for changes to parent list via UI to cascade down to child fields
							var currSel = el.val() || [],
								option = el.children('option[value="' + ui.item.value + '"]'),
								type;

							el.val('') /*.trigger('change') */;

							if(option.length === 0) {
								el.append($("<option></option>")
									.attr({'value':ui.item.value})
									.text(ui.item.label)
								);
							}

							type = el.prop('type');

							if(type == 'select-multiple') {
								currSel.push(ui.item.value); // for multiple select
								el.val(currSel);
							}
							else {
								el.val(ui.item.value);
							}

							el.trigger('change');
							el.trigger('autoCompleteSelect');
						}

						searchInput.val('');
						return false;
					}
				});

			$('.ui-autocomplete').addClass('jrAutoComplete');
		},

		_bindClick2Add: function() {

			var el = this.element,
				o = this.options,
				searchInput = this.searchInput;

			if(el.data('click2add') == 1)
			{
				$('<button class="jrButton" />')
					.html('<span class="jrIconNew"></span>'+jreviews.__t('FIELD_ADD_OPTION'))
					.on('click',function(e) {

						e.preventDefault();

						var value = searchInput.val(),
							controlledBy = '',
							parent_fname,
							parent_value;

						if(el.data('controlledBy') && !(el.data('fieldName') in el.data('controlledBy'))) {
							$.each(el.data('controlledBy'),function(field,value){
								parent_fname = field;
								parent_value = value;
								controlledBy = '|'+field+'|'+value;
							});
						}

						var optionValue =  encodeURIComponent(value).replace(/'/g, "&#039;")+"|click2add"+controlledBy;

						var currOption = el.children('option[value="' + optionValue+'"]');

						if(value !== '' && value != o.lang.help && currOption.length === 0) {

							el
								.append(

									$("<option></option>")
										.attr({
											'value':optionValue,
											'selected':'selected',
											'data-ordering':99999, /* make sure it shows up last*/
											'data-controlledBy':parent_fname,
											'data-controlValue':parent_value
										})
										.text(value)

								)
								.trigger('change');

							searchInput.val('');
						}
						else if (currOption.length == 1) {

							/********************
							*  Add check for single select list so only one option is selected at a time
							********************/

							currOption.prop('selected',true);

							el.trigger('change');
						}

						searchInput.focus();
					})
					.insertAfter(searchInput);
			}
		},

		_noMatch: function(data) {

			var el = this.element,
				noResults = this.noResults;

			if(data.length === 0 && el.data('validation') === false)
			{
				el.prevAll('label:eq(0)')
					.append(noResults)
					.data('validation',true);

				noResults.fadeIn(100).delay(3000).fadeOut(500,function(){
					$(this).detach();
					el.data('validation',false);
				});
			}
		},

		_destroy: function() {
		}

	});
})(jQuery);

(function($) {
	jreviewsFormBuilder = function($formbuilder, settings) {
		this.formbuilder = $formbuilder;
		this.settings = $.extend({showModel: false}, settings);
	};
	jreviewsFormBuilder.prototype = {
	    constructor: jreviewsFormBuilder,
	    options: {
	      theme: 'jreviews',
	      iconlib: 'jreviews',
	      schema: {},
	      ajax: true,
	      startval: null,
	      // object_layout: 'grid',
	      disable_collapse: true,
	      disable_array_delete_all_rows: true,
	      disable_array_delete_last_row: true,
	      disable_properties: true,
	      disable_edit_json: true,
	      no_additional_properties: true
	    },
	    form: null,
	    formbuilder: null,
	    schema: null,
	    model: null,
	    editor: null,
	    initialize: function() {
	        var self = this;

	    	if (self.formbuilder.data('initialized') === true) {
	    		console.log('FormBuilder already initialized');
	    		return self;
	    	}

	        self.schema = self.formbuilder.find('[jr-schema]').css('display','none'),
	        self.model = self.formbuilder.find('[jr-model]').css('display','none');

	        try {
	        	self.options.schema = JSON.parse(self.schema.val() || '{}');
	        	if (JSON.stringify(self.options.schema) == JSON.stringify({})) {
	    		return self;
	        	}
	        }
	        catch (e) {
	    		return self;
	        }

			self.formbuilder.data('initialized', true);

			try {
				var val = self.model.val();
				if (val !== '{}' && val != '[]' && val != '') {
	        		self.options.startval = JSON.parse(val);
				}
				else {
					delete self.options.startval;
				}
			} catch (e) {
				delete self.options.startval;
			}

	        var $editor,
	            $formContainer = self.formbuilder.find('[jr-form]');

	        if (this.settings.showModel ) {
	        	self.model.css('display','block');
	        }

	        // Stop select2 from getting initialized
			JSONEditor.defaults.editors.select.prototype.setupSelect2 = function() {
				this.select2 = null;
			};

			JSONEditor.prototype._loadExternalRefs = function(schema, callback) {

			    var self = this;
			    var refs = this._getExternalRefs(schema);
			    var ajaxUri;

			    var done = 0, waiting = 0, callback_fired = false;

			    $.each(refs,function(url) {

					if(self.refs[url]) return;

					if(!self.options.ajax) throw "Must set ajax option to true to load external ref "+url;
					self.refs[url] = 'loading';
					waiting++;

					// Replace definitions path based on the CMS
					switch (jreviews.cms) {
						case 1:
							ajaxUri = url.replace('DEFINITIONS_PATH', jreviews.relpath+'/components/com_jreviews/jreviews/views/themes/default/fields_formbuilder/definitions');
						break;
						case 2:
							ajaxUri = url.replace('DEFINITIONS_PATH', jreviews.relpath+'/wp-content/plugins/jreviews/jreviews/views/themes/default/fields_formbuilder/definitions');
						break;
					}

					var r = new XMLHttpRequest();
					r.open("GET", ajaxUri, true);
					r.onreadystatechange = function () {
						if (r.readyState != 4) return;
						// Request succeeded
						if(r.status === 200) {
						  var response;
						  try {
						    response = JSON.parse(r.responseText);
						  }
						  catch(e) {
						    window.console.log(e);
						    throw "Failed to parse external ref "+url;
						  }
						  if(!response || typeof response !== "object") throw "External ref does not contain a valid schema - "+url;

						  self.refs[url] = response;
						  self._loadExternalRefs(response,function() {
						    done++;
						    if(done >= waiting && !callback_fired) {
						      callback_fired = true;
						      callback();
						    }
						  });
						}
						// Request failed
						else {
						  window.console.log(r);
						  throw "Failed to fetch ref via ajax- "+url;
						}
					};
					r.send();
			    });

			    if(!waiting) {
			      callback();
			    }
			};

	        self.form = new JSONEditor($formContainer[0], self.options);

	        self.initializing = true;

	        self.form.on('ready', function() {
	        	hideEmptyHeadings($formContainer);
	        });

	        self.form.on('change',function() {
	        	if (self.initializing !== true) {
	            	self.model.val(JSON.stringify(self.form.getValue(),null,2));
	            	fixButtonGroup($formContainer);
	        	}
	            var validation_errors = self.form.validate();
	            if(validation_errors.length) {
	                console.log('FormBuilder Validation', JSON.stringify(validation_errors,null,2));
	            }
	            else {
	                console.log('FormBuilder Validation: valid');
	            }
	        	self.initializing = false;
	        });

	        function hideEmptyHeadings($formContainer) {
	        	$formContainer.find('.jrFormHeading').each(function() {
	        		if ($(this).parent().hasClass('jrFormBuilderPanel')) {
	        			return;
	        		}
	            	fixButtonGroup($formContainer);
	        	});
	        }

	        function fixButtonGroup($formContainer) {
				// Fix button groups with hidden elements
				$formContainer.find('.jrButtonGroup').each(function() {
					$(this).children('.jrButton:visible:last').addClass('jrButtonLast');
					$(this).children('.jrButton:visible:first').addClass('jrButtonFirst');
					var buttonCount = $(this).children('button').length;
					if ($(this).children('button:hidden').length == buttonCount) {
						if ($(this).find('.json-editor-btn-add').length == 0 && $(this).find('.json-editor-btn-delete').length == 0) {
							$(this).hide();
						}
					}
				});
	        }

	        return self;
	    },
	    updateSchema: function(schema) {
	        if (this.editor) {
	            this.editor.setValue(JSON.stringify(schema,null,2),-1);
	        } else {
	            this.schema.val(JSON.stringify(schema,null,2));
	        }
	        this.reload();
	    },
	    reload: function() {
	        this.destroy().initialize(this.formbuilder);
	    },
	    destroy: function() {
	    	this.formbuilder.data('initialized', false);
	    	if (this.form) {
	        	this.form.destroy();
	    	}
	        this.formbuilder.find('[jr-form]').html('');
	        this.model.val('[]');
	        return this;
	    },
	    addEditor: function() {
	        var self = this;
	        var $schemaEditor = self.formbuilder.find('[jr-schema-editor]');
	        if ($schemaEditor.length && this.editor == null) {
	            self.editor = ace.edit($schemaEditor[0]);
	            self.editor.getSession().setMode('ace/mode/'+$schemaEditor.data('format'));
	            self.editor.setValue(self.schema.val(), -1);
	            self.editor.on('change', function() {
	                self.schema.val(self.editor.getValue());
	            });
	        }
	        return self;
	    }
	};
})(jQuery);
