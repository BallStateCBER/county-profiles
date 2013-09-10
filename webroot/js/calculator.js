function setupCalculator() {
	var county_select = $('#calc_county');
	county_select.change(function() {
		filterIndustries();
		$('#calc_county_leading_choice').hide();
	});
	
	// If a county has already been selected (page was refreshed, etc.)
	if (county_select.val()) {
		filterIndustries();
		toggleInput();
	}
	
	$('#calc_industry_id').change(function() {

	});
	
	$('#calc_input_options').change(function() {
		$('#calc_input_option_leading_choice').hide();
		toggleInput();
	});
	
	// Automatically convert currency input into the right format
	$('#calc_annual_production').change(function() {
		$(this).val(moneyFormat($(this).val()));
	});
	
	// Automatically add commas to employees value
	$('#calc_employees').change(function() {
		$(this).val(addCommas($(this).val()));
	});
	
	// Setup popup that explains employees input
	$('#calc_employees_help_toggler').mouseover(function() {
		$('#calc_employees_help').show();
	}).mouseout(function() {
		$('#calc_employees_help').hide();
	});
	
	$('#calculate_button').click(function(event) {
		event.preventDefault();
		calculateImpact();
	});
}

// Hides industries that aren't found in the selected county
function filterIndustries() {
	var industry_select = $('#calc_industry_id');
	var selected_industry_id = industry_select.val();
	var county_id = counties.indexOf($('#calc_county').val());
	var applicable_industries = industries[county_id];
	var options = industry_select.children('option');
	if (applicable_industries.length > 0) {
		options.each(function() {
			var industry_id = parseInt($(this).val());
			
			// Not applicable industry
			if ($.inArray(industry_id, applicable_industries) == -1) {
				$(this).hide();
				
				// If the selected industry is now invalid, 
				if (selected_industry_id == industry_id) {
					// Switch the selection to "select an industry"
					$(options[0]).show();
					industry_select.selectedIndex = 0;
				}
				
			// Applicable industry
			} else {
				$(this).show();
			}
		});
		
	// Error finding applicable industries
	} else {
		options.each(function() {
			$(this).show();
		});
		industry_select.selectedIndex = 0;
	}
}

// Toggle whether or not production / employees input is visible, and which is displayed 
function toggleInput() {
	var selected = $('#calc_input_options').val();
	if (selected == 'production') {
		$('#option_a_input').show();
		$('#option_b_input').hide();
	} else if (selected == 'employees') {
		$('#option_a_input').hide();
		$('#option_b_input').show();
	} else {
		$('#option_a_input').hide();
		$('#option_b_input').hide();
	}
}

function moneyFormat(input) {
	return '$' + addCommas(input);
}

function addCommas(input) {
	input = inputToInt(input);
	for (var i = 0; i < Math.floor((input.length-(1+i))/3); i++) {
		input = input.substring(0,input.length-(4*i+3)) + ',' + input.substring(input.length-(4*i+3));
	}
	return input;
}

function inputToInt(input) {
	// If a decimal point exists in the input,
	// remove it and everything after it
	var index_of_point = input.indexOf('.');
	if (index_of_point > -1) {
		input = input.substring(0, index_of_point);
	}
	return input.replace(/[^0-9]/g, '');
}

function calculateImpact() {
	var county_slug = $('#calc_county').val();
	if (! county_slug) {
		return alert('Please select a county.');
	}
	var county_id = counties.indexOf(county_slug);
	
	var industry_id = $('#calc_industry_id').val();
	if (! industry_id) {
		return alert('Please select an industry.');
	}
	var valid_industries = industries[county_id];
	if (valid_industries.indexOf(parseInt(industry_id)) == -1) {
		return alert('Sorry, no information is available about that industry in the selected county. Please choose another.');
	}
	
	var method = $('#calc_input_options').val();
	if (! method) {
		return alert('Please select an input method.');
	}
	
	// Production
	if (method == 'production') {
		var amount = inputToInt($('#calc_annual_production').val());
		if (! amount) {
			return alert('Please enter the expected annual production of this company (in dollars).');
		}
	// Employees
	} else {
		var amount = inputToInt($('#calc_employees').val());
		if (! amount) {
			return alert('Please enter the expected number of employees for this company.');
		}
	}
	
	var output_container = $('#calc_output_container');
	var ajax_options = {
		url: '/calculators/output/county:'+county_id+'/industry:'+industry_id+'/method:'+method+'/amount:'+amount,
		success: function (data) {
			// Load content into outer container
			output_container.html(data);
			
			// Immediately hide inner container so it can be faded in
			var inner_container = output_container.children('div').first();
			inner_container.hide();
			
			// Fade in inner container
			inner_container.fadeIn(500, function() {
				// Unset specific height of outer container
				output_container.css('height', 'auto');
			});
		},
		error: function () {
			alert('There was a network error processing your request. Please try again.'); 
		},
		complete: function () {
			$('#calc_loading').hide();
		}
	};
	
	$('#calc_loading').show();
	
	if (output_container.is(':empty')) {
		$.ajax(ajax_options);
	} else {
		// Set specific height of outer container so it doesn't snap to 0px and back
		output_container.css('height', output_container.height());
		
		// Fade out inner container, then run AJAX request
		var inner_container = output_container.children('div').first();
		inner_container.fadeOut(500, function() {
			$.ajax(ajax_options);
		});
	}
}


/***************** OLD, UN-UPDATED JS BELOW *******************/

//When an industry is selected
function onIndustrySelection() {
	$('#calc_industry_id_leading_choice').hide();
	$('#calculate_button').prop('disabled', true);
	//var calc_input_options = $('#calc_input_options');
	//calc_input_options.enable();
	if (calc_input_options.selectedIndex == 0) {
		resetInputOptions();
	} else {
		onInputMethodSelection(calc_input_options.selectedIndex);
	}
}

function oldSetupCalculator() {
	var calc_industry_id = $('calc_industry_id');
	calc_industry_id.onchange = function() {onIndustrySelection(true);}
	$('calc_county_id').onchange = function() {onCountySelection($(this).getValue(), true);};
	$('calc_input_options').onchange = function() {onInputMethodSelection(this.selectedIndex);};
	$('calc_annual_production').onchange = function() {this.value = moneyFormat(this.value);};
	var calc_employees_help_toggler = $('calc_employees_help_toggler');
	calc_employees_help_toggler.onmouseover = function() {
		$('calc_employees_help').show();
	}; 
	calc_employees_help_toggler.onmouseout = function() {
		$('calc_employees_help').hide();
	};
	$('calculate_button').onclick = function() {calculateImpact(true, 'http://epa.cberdata.org');};
	$('calc_employees').onchange = function() {this.value = addCommas(this.value);};
	initializeTIFCalculator();
	var loading_image = new Image(16, 11);
	loading_image.src = 'http://epa.cberdata.org/img/loading2.gif'
}
	
/* This hides industries that aren't found in the selected county 
* (or shows all industries if there's an error looking the industries up)
* and resets both industry selection and input-type selection if reset_subsequent is set to TRUE. */
function onCountySelection(county_id, reset_subsequent) {
	$('#calc_county_leading_choice').hide();
	var industry_select = $('#calc_industry_id');
	industry_select.enable();
	if (reset_subsequent) {
		resetInputOptions();
		$('#calc_input_options').disable();
		$('#calculate_button').disable();
	}		
	var industry_ids = industries[county_id];
	var local_industry_count = industry_ids.length;
	//alert(local_industry_count + ' industries found');
	if (reset_subsequent) {
		industry_select.selectedIndex = 0;
	}
	if (local_industry_count > 0) {
		var options = $('option.foo_option');
		options.each(function(option) {
			var industry_id = option.value;
			pos = industry_ids.indexOf(industry_id);
			if (pos == -1) {
				option.hide();
			} else {
				option.show();
			}
		});
	}
}

function resetInputOptions() {
	$('#option_a_input').hide();
	$('#option_b_input').hide();
	$('#calc_input_option_leading_choice').show();
	$('#calc_input_options').selectedIndex = 0;
}

function onInputMethodSelection(selected_index) {
	$('#calc_input_option_leading_choice').hide();
	if (selected_index == 1) { // option A
		$('#option_a_input').show();
		$('#option_b_input').hide();
	} else if (selected_index == 2) { // option B
		$('#option_a_input').hide();
		$('#option_b_input').show();
	}
	$('#calculate_button').enable();
}

function updateCalculatorOutput(url, animate) {
	var container = $('#calc_output_container');
	var calc_loading_graphic_container = $('#calc_loading_graphic_container');
	if (animate) {
		var slide_duration = 0.8;
		var loading_fade_duration = 0.5;
		calc_loading_graphic_container.appear({
			duration: loading_fade_duration,
			afterFinish: function() {
				var myAjax = new Ajax.Updater(container, url, {
					method: 'get',
					onComplete: function() {
						calc_loading_graphic_container.fade({duration: loading_fade_duration});
						Effect.SlideDown(container, {
							queue: {position: 'end', scope: 'calculator', limit: 1},
							duration: slide_duration
						});
					}
				});
			}
		});
	} else {
		calc_loading_graphic_container.show();
		var myAjax = new Ajax.Updater(container, url, {
			method: 'get',
			onComplete: function() {
				calc_loading_graphic_container.hide();
				container.show();
			}
		});
	}
}

function showCalcIntroText() {
	var intro_text_teaser = $('#calc_intro_text_teaser');
	if (! intro_text_teaser.visible()) {
		return;
	}
	intro_text_teaser.hide();
	Effect.SlideDown('calc_intro_text', {
		queue: {position: 'end', scope: 'calculator_intro_text', limit: 1},
		duration: 0.5
	});
}
function hideCalcIntroText(animate) {
	var intro_text = $('#calc_intro_text');
	if (! intro_text) {
		return;
	}
	var intro_text_teaser = $('#calc_intro_text_teaser');
	if (! intro_text.visible()) {
		return;
	}
	if (animate) {
		Effect.SlideUp(intro_text, {
			queue: {position: 'end', scope: 'calculator_intro_text', limit: 1},
			duration: 0.5,
			afterFinish: function() {
				intro_text_teaser.show();
			}
		});
	} else {
		intro_text.hide();
		intro_text_teaser.show();
	}
}

// In fact, this hides industries that aren't found in the county,
// or just shows all industries if there's an error
function loadLocalIndustries() {
	$('industry_id').enable();
	resetInputOptions();
	$('input_options').disable();
	$('calculate_button').disable();


	var county_id = $('county_id').getValue();
	var url = 'control/economic_impact/get_local_industries.php?county_id=' + county_id;

	new Ajax.Request(url, {
		method: 'get',
		onSuccess: function(transport) {
			var select_element = $('industry_id');

			if (transport.responseText.match('Error')) {
				alert('Error finding industries for this county: ' + transport.responseText);
				select_element.childElements().each(function(option) {
					option.show();
				});
			} else {
				var industry_ids = $w(transport.responseText);
				var local_industry_count = industry_ids.length;
				select_element.selectedIndex = 0;
				if (local_industry_count > 0) {
					var options = $$('option.foo_option');
					options.each(function(option) {
						var industry_id = option.value;
						pos = industry_ids.indexOf(industry_id);
						if (pos == -1) {
							option.hide();
						} else {
							option.show();
						}
					});
				}

			}
		},
		onFailure: function() {
			var select_element = $('industry_id');
			var options = $$('option.foo_option');
			options.each(function(option) {
				option.show();
			});
		}
	});
}

function optionSelected(selected_index) {
	$('input_option_leading_choice').hide();
	if (selected_index == 1) { // option A
		//$('annual_production').clear();
		$('option_a_input').show();
		$('option_b_input').hide();
	} else if (selected_index == 2) { // option B
		//$('employees').clear();
		//$('average_salary').clear();
		$('option_a_input').hide();
		$('option_b_input').show();
	}
	$('calculate_button').enable();
}