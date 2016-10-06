/*
 * File: init.js
 * Purpose: Processes client-side actions (ajax calls etc.) for program
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description																by
 *   09/21/2015		Initial design & coding	    											Matt Holland
 *	 10/19/2015		Added an ajax call for the processing of 'Prev' and 'Next' buttons		Matt Holland
 *					$(document.body).on("click", "button.month_nav", function(event){...
 *   11/02/2015		Added event class color-coding via the .append("class") method			Matt Holland
 *   11/03/2015		Added an event edit instruction so that updated event edit changes are	Matt Holland
 *					displayed in real time on the screen via ajax.  The original code did 
 *					not have this functionality: 
 *					if ($("[name=event_id]").val().length!=0 && remove===false) {
 *   11/04/2015		Changed edata[1] and cdata[2] by subtracting one from them.  They were	Matt Holland
 *					incorrectly returning the next month since the setFullYear month
 *					parameter is on the scale 0-11 instead of 1-12.  Was causing, for example, 
 *					setFullYear(2015,1,31) to return March 2, 2015 since there aren't 31
 *					days in February
 *					
 *					
 */


/* Makes sure the document is ready before executing scripts
 * Note that this is equivalent to $(document).ready(function(){});
 * It is kind of a shortcut method
 */
jQuery(function($){

	// File to which AJAX requests should be sent
	var processFile = "assets/inc/ajax.inc.php";
	
	// Time delay for spinner display
	var timeDelay = 400;

	// A quick check to make sure the script loaded properly
	//console.log("init.js was loaded successfully.");
	
	// Input validation regex's
	var validDollarValue = /^(([1-9]\d{0,2}(,\d{3})*)|(([1-9]\d*)?\d))(\.\d\d)?$/; // Does not allow blank, .xx, x.x
	var validWholeNumber = /^[0-9]{1,}$/; // For whole numbers (no decimal), i.e. total ROs
	var validDecimal = /^[0-9]+(\.[0-9]{1,2})?$/; // For hours worked etc.
	var validPercentage = /^(?:|\d{1,2}(?:\.\d{1,2})?)$/; // For penet. percentages (xx.xx) -> these will be divided by 100 before going into DB
	var validDate = /^([0-1][0-9])\/([0-3][0-9])\/([0-9]{4})$/ // For date format mm/dd/yyyy
	// Functions to manipulate the modal window
	var fx = {
		/*
		 * Checks for a modal window and return sit, or
		 * else creates a new one and returns that
		 */
		"initModal":function() {
			/* 
			 * If no elements are matched, the length
			 * property will return 0
			 */
			if($(".modal-window").length == 0) {
				// Creates a div, adds a class, and appends it to the body tag
				return  $("<div>")
							.hide() // This prevents the modal from flickering upon click event
							.addClass("modal-window")
							.appendTo("body");
			} else {
				// Returns the modal window if one already exists in the DOM
				return $(".modal-window");
			}
		},
		
		// Adds the window to the markup and fades it in
		"boxin" : function(data, modal) {
			/* Creates an overlay for the site, adds
			 * a class and a click event handler, then
			 * appends it to the body element
			 */
			$("<div>")
				.hide()
				.addClass("modal-overlay")
				.click(function(event){
					// Removes event
					fx.boxout(event);
				})
				.appendTo("body");
				
			// Loads data into the modal window and appends it to the body element
			modal
				.hide()
				.append(data)
				.appendTo("body");
				
			// Fades in the modal window and overlay
			$(".modal-window,.modal-overlay")
				.fadeIn(100);
		},
		
		// Fades out the window and removes it from the DOM
		"boxout" : function(event) {
			
			/* If an event was triggered by the element
			 * that called this function, prevents the 
			 * default action from firing
			 */
			if (event!=undefined) {
				event.preventDefault();
			}
			
			// Removes the active class from all links
			$("a").removeClass("active");
			
			/* Fades out the modal window and overlay, then
			 * removes them from the DOM entirely
			 */
			$(".modal-window,.modal-overlay")
				.fadeOut(100, function() {
					$(this).remove();
				});	
		},
		
		// Adds a new event to the markup after saving
		"addevent": function(data, formData) {
			// Converts the query string to an object
			var entry = fx.deserialize(formData),
			
			// Makes a date object for current month
			cal = new Date(NaN),
			
			// Makes a date object for the new event
			event = new Date(NaN),
			
			// Extracts the calendar month from the H2 ID
			cdata = $("h2").attr("id").split('-'),
			
			// Extracts the event day, month and year
			date = entry.event_start.split(' ')[0],
			
			// Extracts the etype_id so that class may be applied correctly
			etype_id = entry.etype_id,
			
			// Splits the event data into pieces
			edata = date.split('-');
			
			// Sets the date for the calendar date object
			// The setFullYear JS method returns the year, month and day of the parameters specified: d.setFullYear(year,month,day)
			// The month parameter represents the month, based on an array of months starting with zero (i.e. 1 will equal February)
			// Subtract 1 from the month so that it matches up with the scale 0-11
			set_calmonth = cdata[2]-1;
			cal.setFullYear(cdata[1], set_calmonth, 1);
			
			// Sets the date for the event date object
			// Subtract 1 from the month so that it matches up with the scale 0-11
			set_eventmonth = edata[1]-1;
			event.setFullYear(edata[0], set_eventmonth, edata[2]);
			
			/* Since the date object is created using GMT,
			 * then adjusted for the local time zone, adjust
			 * the offset to ensure a proper date
			 */
			event.setMinutes(event.getTimezoneOffset());
			
			/* If the year and month match, start the
			 * process of adding the new event to the calendar
			 */
			if (cal.getFullYear()==event.getFullYear() && cal.getMonth()==event.getMonth()) {
				// Gets the day of the month for the event
				var day = String(event.getDate());
				
				// Adds a leading zero to 1-digit days
				day = day.length==1 ? "0"+day : day;
				
				// Create event_type array to store class type
				var class_type = ["assessment", "install", "followup", "followup", "followup", "followup", "sustain", "sustain", "sustain", "sustain", "vacation", "travel", "workshop"];
				
				// Applies applicable class to link based on etype_id
				for (var i=0; i<class_type.length; i++) {
					if (etype_id == i) {
						var event_type = class_type[i];
					}
				}
				
				// Adds the new date link
				$("<a>")
					.hide()
					.attr("href", "view.php?event_id="+data)
					.attr("class", event_type)
					.text(entry.event_title)
					.insertAfter($("h5:contains("+day+")"))
					.delay(1000)
					.fadeIn("slow");
			}
			//console.log('etype_id: '+etype_id);
			//console.log('data: '+data);
			//console.log('day: '+day);
		},
		
		// Removes an event from the markup after deletion
		"removeevent" : function() {
			// Removes any event with the class 'active'
			$(".active")
				.fadeOut("slow", function() {
					$(this).remove();
				});
		},
		
		// Deserializes the query string and returns an event object
		"deserialize" : function(str) {
			// Breaks apart each name-value pair
			var data = str.split("&"),
			
			// Declares variables for use in the loop
			pairs = [], entry = {}, key, val;
			
			// Loops through each name-value pair
			for (x in data) {
				// Splits each pair into an array
				pairs = data[x].split("=");
				
				// The first element is the name
				key = pairs[0];
				
				// Second element is the value
				val = pairs[1];
				
				/* Reverses the URL encoding and stores each
				 * value as an object property
				 */
				entry[key] = fx.urldecode(val);
			}
			return entry;
		},
		
		// Decodes a query string value
		"urldecode" : function(str) {
			// Converts plus signs to spaces
			var converted = str.replace(/\+/g, ' ');
			
			// Converts any encoded entities back
			return decodeURIComponent(converted);
		}
		
		// Sort the dealer <option> list alphabetically (had to do this so that the dealer SESSION array list keys matched with the query results indexes)
		//"sortlist" : function
		
	};
	
	/*
	 * Changes the month displayed on the page and builds
	 * new month by changing $_SESSION['useDate'] and replacing the
	 * calendar HTML
	 * Original: $(button.month_nav).on("click", function(event){
	 */
	$(document.body).on("click", "button.month_nav", function(event){
		// Prevents process.inc.php file from loading
		event.preventDefault();
		
		// Get the button value to determine if 'prev_month' or 'next_month' was selected
		var button_value = $(this).val();
		
		// Output test results to the console
		console.log("change month button was clicked!" + button_value);
		
		var dataString = {action:button_value};
		
		// Initiate ajax call
		$.ajax({
			type: "POST",
			//url: "assets/inc/ajax_test3.php",
			url: processFile,
			data: dataString,
			success: function(data){
				console.log('you entered the ajax success area');
				// Replace calendar markup with new month
				console.log(data);
				$(".content-calendar").html(data);
			},
			error: function(msg) {
				alert("There was an error processing your request");
			}
		});
	});
	
	/* 
	 * Pulls up events in a modal window and attaches a zoom effect
	 * Note that line 14 originally was $("li>a").live("click"....
	 * .live() is deprecated and was replaced with .on()....
	 * This one was replaced with: $("li>a").on("click", function(event) {....
	 * but this did not work for the newly added events.
	 * Had to write a .on() method with event delegation:  $(document.body).on("click", "li>a", function(event) (....
	 * but then I read that this can be expensive as it bubbles all the way up the DOM....
	 * So I rewrote as follows and it works for old and newly added events: $("li").on("click", "a", function(event) {...
	 * Note:  the dateZoom function won't work unless the intruction reads $("li").dateZoom().on("click", "li>a", function(event) {
	 */
	// Set a default font-size value for dateZoom
	// $.fn.dateZoom.defaults.fontsize = "13px";
	// $("li a").dateZoom();  ***This function magnifies the event title on hover.  
	// Cannot get it to initiate correctly with below.  Original:  $("li a").dateZoom().live("click", function(event)
	// **Original:**$("table tr td").on("click", "a", function(event) {
	/*
	$(document.body).on("click", "table tr td a", function(event) {
		// Stops the link from loading view.php
		event.preventDefault();
		
		// Adds an "active" class to the link
		$(this).addClass("active");
		
		// Gets the query string from the link href
		var data = $(this)
						.attr("href")
						.replace(/.+?\?(.*)$/, "$1"),
						// Checks if the modal window exists and selects it, or creates a new one
						modal = fx.initModal();
						
		// Logs the query string
		console.log(data);
		
		// Proves the event handler worked by logging the link text
		console.log($(this).text());
		
		// Creates a button to close the window
		$("<a>")
			.attr("href", "#")
			.addClass("modal-close-btn")
			.html("&times;")
			.click(function(event) {
				// Prevent the default action
				event.preventDefault();
				
				// Removes the modal window
				fx.boxout(event);	
			})
			.appendTo(modal);
		
		// Loads the event data from the DB
		$.ajax({
			type: "POST",
			url: processFile,
			data: "action=event_view&" + data,
			success: function(data){
				// Alert event data for now
				//modal.append(data);
				fx.boxin(data, modal);
			},
			error: function(msg) {
				modal.append(msg);
			}
		});
	});

	// Displays the edit form as a modal window
	$(document.body).on("click", ".admin-options form, .admin", function(event){
	//$(".admin").on("click", function(event) {   **This is the original selector**
		// Prevents the form from submitting
		event.preventDefault();
		
		// Sets the action for the form submission
		var action = $(event.target).attr("name") || "edit_event";
		
		// Saves the value of the event_id input
		id = $(event.target).siblings("input[name=event_id]").val();
		
		// Creates an additional param for the ID if set
		id = (id!=undefined) ? "&event_id="+id : "";
		
		// Loads the editing form and displays it
		$.ajax({
			type: "POST",
			url: processFile,
			data: "action="+action+id,
			success: function(data) {
				// Hides the form
				var form = $(data).hide(),
				
				// Make sure the modal window exists
				//modal = fx.initModal();
				modal = fx.initModal().children(":not(.modal-close-btn)").remove().end();
				
				// Call the boxin function to create the modal overlay and fade it in
				fx.boxin(null, modal);
				
				// Load the form into the window, fades in the content, and adds a class to the form
				form
					.appendTo(modal)
					.addClass("edit-form")
					.fadeIn(100);
				
				// Sorts the dropdown dealer list alphabetically.  The list should already be sorted by the query (ORDER BY dealername ASC),
				// but this is here to help ensure that the order is correct.
				var options = $(".dlr_list option");
					options.sort(function(a,b) {
					if (a.text > b.text) {
						return 1;
					} else if (a.text < b.text) {
						return -1;
					} else {
						return 0;
					}	
				});
				$(".dlr_list").empty().append(options);
				
				
			},
			error: function(msg) {
				alert(msg);
			}
		});
		
		// Logs a message to prove the handler was triggered
		console.log("Add a new event button clicked!");
	});

	// Edits events without reloading
	$(document.body).on("click", "form.edit-form input[type=submit]", function(event) {
		// Prevents the default form action from executing
		event.preventDefault();
		
		// Serializes the form data for use with $.ajax()
		var formData = $(this).parents("form").serialize(),
		
		// Stores the value of the submit button
		submitVal = $(this).val(),
		
		// Determines if the event should be removed
		remove = false;
		
		// Saves the start date input string
		start = $(this).siblings("[name=event_start]").val(),
		
		// Saves the end date input string
		end = $(this).siblings("[name=event_end]").val();
		
		// If this is the deletion form, appends an action
		if ($(this).attr("name")=="confirm_delete") {
			// Adds necessary info to the query string
			formData += "&action=confirm_delete" + "&confirm_delete="+submitVal;
			
			// If the event is really being deleted, sets a flag to remove it from the markup
			if (submitVal=="Yes, Delete It") {
				remove = true;
			}
		}
		
		// If creating/editing an event, checks for valid dates. The $.validDate method comes from jquery.validDate.js
		if ($(this).siblings("[name=action]").val()=="event_edit") {
			if (!$.validDate(start) || !$.validDate(end)) {
				alert("Valid dates only! (YYYY-MM-DD HH:MM:SS)");
			    return false;
			}
		}
		
		// Sends the data to the processing file
		$.ajax({
			type: "POST",
			url: processFile,
			data: formData,
			success: function(data) {
				// If this is a deleted event, removes it from the markup
				if (remove===true) {
					fx.removeevent();
				}
				
				// If this is an event edit, replace event item with new information
				if ($("[name=event_id]").val().length!=0 && remove===false) {
					// Adds the event to the calendar
					console.log('entered edit event if area');
					fx.removeevent();
					fx.addevent(data, formData);
				}
				
				// Fades out the modal window
				fx.boxout();
				
				// If this is a new event, add it to the calendar
				if ($("[name=event_id]").val().length==0 && remove===false) {
					// Adds the event to the calendar
					fx.addevent(data, formData);
				}
				
				// Logs a message to the console
				console.log("Event saved!");
			},
			error: function(msg) {
				alert(msg);
			}
		});
		
		// Logs a message to indicate the script is working
		console.log(formData);
	});
	*/

	/* Make the cancel button on editing forms behave like the
	 * close button and fade out modal windows and overlays
	 * Cannot get this code to fire!!
	 */
	$(document.body).on("click", "form.edit-form a:contains(cancel)", function(event) {
		fx.boxout(event);
		console.log("finally worked bravo!!!");
	});
	
	// Update the page via ajax when user selects a new profit worksheet (profitability calculator)
	$(document.body).on("click", "form#new_profit_form input[type=submit]", function(event) {
		// Prevent form default submit action
		event.preventDefault();
		
		// Set the action from the form submission so that this can be submitted to ajax.inc.php file
		var action = $(event.target).attr("name"); // This == 'new_profit_worksheet'
		console.log('action: ' + action);
		
		// Serialize the form data for use with $.ajax()
		var formData = $(this).parents("form").serialize();
		console.log('formData: ' + formData);
		
		// Establish error array and check for invalid inputs
		var errors = [];
		var serialize_array = $(this).parents("form").serializeArray();
		$.each(serialize_array, function(i, field) {
			if (field.name == 'dlr_select') {
				if (field.value == '') {
					errors.push("*You must select a dealer!\n");
				}
			}
			if (field.name == 'date_select') {
				if (!validDate.test(field.value)) {
					errors.push("*You entered an invalid date! (mm/dd/yyyy)\n");
				}
			}
			// Note: this field will not be present if $_SESSION['user']['type_id'] != 1 (SOS type)
			if (field.name == 'profit_privacy') {
				if (field.value == '') {
					errors.push("*You must choose a privacy setting!");
				}
			}
		});
		
		if (errors.length > 0) {
			var error_msg = "";
			for (var i=0; i<errors.length; i++) {
				error_msg += errors[i];
			}
			alert("Your input contains the following errors: \n\n" + error_msg + "\nPlease correct the errors and re-submit the form.");
			return false;
		}
		
		// Load the spinner to indicate processing
		/*orig spinner   $('.loader_div').html('<div class="loading">Loading&#8230;</div>'); */
		/*new spinner tests*/
		$('.loader_div').html('<div class="loader">Loading...</div>');
		
		// Set a time delay for the ajax call so that the user can see the spinner.  The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		
		// Save the ajax call as a function to execute within the setTimeout() function.
		// Processes the profit data for input, displays new page content and removes the loading spinner
		function ajaxCall() {
			$.ajax({
				type: "POST",
				url: processFile, 
				data: formData,
				success: function(data) {
					// Test return data
					// console.log(data);
					
					// Save the response as a jQuery object, and then save each tab content for html replacement
					var response = $(data);
					var cost_tables = response.filter('#ajax_cost_tables').html();
					var profit_tables = response.filter('div.profit_page').html();
					var cost_tab_heading = response.filter('div.cost_tab_heading').html();
					var privacy_modal = response.filter('div#privacy_modal').html();
					
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
					
					// Replace <div class="cost_tab_heading"> with hew title content
					$('div.cost_tab_heading').html(cost_tab_heading);
					
					// Replace <div id="ajax_cost_tables"> with new content
					$('#ajax_cost_tables').html(cost_tables);
					
					// Replace <div class="profit_page"> with new content
					$('div.profit_page').html(profit_tables);
					
					// Replace <div class="privacy_modal"> with new content
					$('div#privacy_modal').html(privacy_modal);
					
				},
				error: function(msg) {
					alert("There was an error processing your request");
				}
			});
		};
	});
	
	// Update the page via ajax when user selects an existing profit worksheet (profitability calculator)
	$(document.body).on("click", "form#existing_profit_form input[type=submit]", function(event) {
		// Prevent form default submit action
		event.preventDefault();
		
		// Set the action from the form submission so that this can be submitted to ajax.inc.php file
		var action = $(event.target).attr("name"); // This == 'existing_profit_worksheet'
		console.log('action: ' + action);
		
		// Serialize the form data for use with $.ajax()
		var formData = $(this).parents("form").serialize();
		console.log('formData: ' + formData);
		
		// Establish error array and check for invalid inputs
		var errors = [];
		var serialize_array = $(this).parents("form").serializeArray();
		$.each(serialize_array, function(i, field) {
			if (field.name == 'dlr_select') {
				if (field.value == '') {
					errors.push("*You must select a worksheet!\n");
				}
			}
		});
		
		if (errors.length > 0) {
			var error_msg = "";
			for (var i=0; i<errors.length; i++) {
				error_msg += errors[i];
			}
			alert("Your input contains the following errors: \n\n" + error_msg + "\nPlease correct the errors and re-submit the form.");
			return false;
		}
		
		// Load the spinner to indicate processing
		/*orig spinner   $('.loader_div').html('<div class="loading">Loading&#8230;</div>'); */
		/*new spinner tests*/
		$('.loader_div').html('<div class="loader">Loading...</div>');
		
		// Set a time delay for the ajax call so that the user can see the spinner.  The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		
		// Save the ajax call as a function to execute within the setTimeout() function.
		// Processes the profit data for input, displays new page content and removes the loading spinner
		function ajaxCall() {
			$.ajax({
				type: "POST",
				url: processFile, 
				data: formData,
				success: function(data) {
					// Test return data
					console.log(data);
					
					// Save the response as a jQuery object, and then save each tab content for html replacement
					var response = $(data);
					var cost_tables = response.filter('#ajax_cost_tables').html();
					var profit_tables = response.filter('div.profit_page').html();
					var cost_tab_heading = response.filter('div.cost_tab_heading').html();
					var privacy_modal = response.filter('div#privacy_modal').html();
					
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
					
					// Replace <div class="cost_tab_heading"> with hew title content
					$('div.cost_tab_heading').html(cost_tab_heading);
					
					// Replace <div id="ajax_cost_tables"> with new content
					$('#ajax_cost_tables').html(cost_tables);
					
					// Replace <div class="profit_page"> with new content
					$('div.profit_page').html(profit_tables);
					
					// Replaced <div class="privacy_modal"> with new content
					$('div#privacy_modal').html(privacy_modal);
					
				},
				error: function(msg) {
					alert("There was an error processing your request");
				}
			});
		};
	});
	
	// If user tries to export a worksheet, but does not select anything, notify him/her of an error
	$('form#export_profit_form input[type=submit]').on("click", function() {
		var input = $('#dlr_export_select').val();
		var error_msg = "You must select a dealer to export!\n";
		if (input == '') {
			alert("Your input contains the following errors: \n\n" + error_msg + "\nPlease correct the errors and re-submit the form.");
			return false;
		}
		console.log ('export value: ' + input);
	});
	
	/* Enable user to add and remove rows dynamically (non-ajax) on profit_calc.php
	 *
	 */
	// Add advisor rows on tab1
	$(document.body).on('click', '#add_advisor_row', function() {
	//$('#add_advisor_row').click(function() { // This would not work after the page had been updated by AJAX
		console.log('clicked advisor add row');
		$('#advisor_tbody').append('<tr><td>Advisor: </td><td><span class="glyphicon glyphicon-minus-sign" id="" name="" aria-hidden="true"></span></td><td><input class="unit_input form-control" id="adv_hours[]" name="adv_hours[]" type="text" value=""/></td><td><input class="unit_input form-control" id="adv_rate[]" name="adv_rate[]" type="text" value=""/></td><td>$0.00</td></tr>');
	});
	
	// Add tech rows on tab1
	$(document.body).on('click', '#add_tech_row', function() {
		console.log('clicked tech add row');
		$('#tech_tbody').append('<tr><td>Tech: </td><td><span class="glyphicon glyphicon-minus-sign" id="" name="" aria-hidden="true"></span></td><td><input class="unit_input form-control" id="tech_hours[]" name="tech_hours[]" type="text" value=""/></td><td><input class="unit_input form-control" id="tech_rate[]" name="tech_rate[]" type="text" value=""/></td><td>$0.00</td></tr>');
	});
	
	// Add cost table rows when user clicks 'Add Row', by using the <a> id as an identifier
	$(document.body).on('click', 'th a.add_row_link', function() {
		var addrow_svc_id = $(this).attr('id');
		console.log('clicked add_svc_row id: ' + addrow_svc_id);
		var row = '<tr><td id="added_cost_row[]" name="added_cost_row[]"><span class="glyphicon glyphicon-minus-sign" id="remove_span_empty_button" name="remove_span_empty_button" style="color: #c7254e; vertical-align: middle; margin-top: 5px;" aria-hidden="true"></span></td><td><input class="ops_input form-control" type="text" id="cost_desc[]" name="cost_desc[]" value=""/></td><td><input class="ops_input form-control" type="text" id="cost_code[]" name="cost_code[]" value=""/></td><td><input class="ops_input form-control" type="text" id="cost_rocount[]" name="cost_rocount[]" value=""/></td><td><input class="ops_input form-control" type="text" id="cost_parts_sale[]" name="cost_parts_sale[]" value=""/></td><td><input class="ops_input form-control" type="text" id="cost_parts_cost[]" name="cost_parts_cost[]" value=""/></td><td>$0.00</td><td><input class="ops_input form-control" type="text" id="cost_labor_sale[]" name="cost_labor_sale[]" value=""/></td><td>$0.00</td></tr>';
		$("tbody#" + addrow_svc_id).append(row);
	});
	
	// Remove cost rows when user clicks minus sign glyphicon
	$(document.body).on('click', '.glyphicon-minus-sign', function() {
		console.log('Remove button clicked!');
		$(this).parent().parent().remove();
	});
	// Update Profit table page dynamically via AJAX
	$(document.body).on("click", "form#profit_analysis_form input[type=submit]#profit_analysis_entry", function(event) {
		// Prevent form default submit action
		event.preventDefault();
		
		// Set the action from the form submission so that this can be submitted to ajax.inc.php file
		var action = $(event.target).attr("name"); // This == 'profit_analysis_entry'
		console.log('action: ' + action);
		
		// Save the <div> id so that the correct html is replaced
		var profit_div = $(this).parents('div.profit_page').attr("class");
		console.log('profit_div: ' + profit_div);
		
		// Serialize the form data for use with $.ajax()
		var formData = $(this).parents("form").serialize();
		console.log('formData: ' + formData);
		
		// Establish error array
		var errors = [];
		var serialize_array = $(this).parents("form").serializeArray();
		$.each(serialize_array, function(i, field) {
			if (field.name == 'days_week') {
				if (!validWholeNumber.test(field.value)) {
					errors.push("*You entered an invalid number of days!\n");
				}
			}
			
			if (field.name == 'ros_per_day') {
				if (!validWholeNumber.test(field.value)) {
					errors.push("*You entered an invalid number of ROs!\n");
				}
			}
			
			if (field.name == 'adv_hours[]') {
				if (!validDecimal.test(field.value)) {
					errors.push("*Advisor Hours must be a valid decimal (xx.xx)!\n");
				}
			}
			
			if (field.name == 'adv_rate[]') {
				if (!validDollarValue.test(field.value)) {
					console.log('You entered an invalid parts cost!');
					errors.push("*Advisor Rate must be a valid dollar amount!\n");
				}
			}
			
			if (field.name == 'adv_spiff') {
				if (!validDollarValue.test(field.value)) {
					console.log('You entered an invalid parts cost!');
					errors.push("*Advisor Spiff must be a valid dollar amount!\n");
				}
			}
			
			if (field.name == 'tech_hours[]') {
				if (!validDecimal.test(field.value)) {
					errors.push("*Tech Hours must be a valid decimal (xx.xx)!\n");
				}
			}
			
			if (field.name == 'tech_rate[]') {
				if (!validDollarValue.test(field.value)) {
					errors.push("*Tech Rate must be a valid dollar amount!\n");
				}
			}
			
			if (field.name == 'tech_spiff') {
				if (!validDollarValue.test(field.value)) {
					errors.push("*Tech Spiff must be a valid dollar amount!\n");
				}
			}
			
			if (field.name == 'pen_input[]') {
				if (!validPercentage.test(field.value)) {
					errors.push("*Pen % must be a valid number (i.e. xx.xx , x.x)!\n");
				}
			}
			
			if (field.name == 'labor_sale[]') {
				if (!validDollarValue.test(field.value)) {
					errors.push("*Labor Sale must be a valid dollar amount!\n");
				}
			}
			
			if (field.name == 'parts_sale[]') {
				if (!validDollarValue.test(field.value)) {
					errors.push("*Parts Sale must be a valid dollar amount!\n");
				}
			}
			
			if (field.name == 'parts_cost[]') {
				if (!validDollarValue.test(field.value)) {
					errors.push("*Parts Cost must be a valid dollar amount!\n");
				}
			}
			console.log('Serialize item: ' + field.value);
		});
		
		if (errors.length > 0) {
			var error_msg = "";
			for (var i=0; i<errors.length; i++) {
				error_msg += errors[i];
			}
			alert("Your input contains the following errors: \n\n" + error_msg + "\nPlease correct the errors and re-submit the table.");
			return false;
		}
		
		// Load the spinner to indicate processing
		/*orig spinner   $('.loader_div').html('<div class="loading">Loading&#8230;</div>'); */
		/*new spinner tests*/
		$('.loader_div').html('<div class="loader">Loading...</div>');
		
		// Set a time delay for the ajax call so that the user can see the spinner.  The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		
		// Save the ajax call as a function to execute within the setTimeout() function.
		// Processes the profit data for input, displays new page content and removes the loading spinner
		function ajaxCall() {
			$.ajax({
				type: "POST",
				url: processFile, 
				data: formData,
				success: function(data) {
					// Test return data
					console.log(data);
					
					// Remove the loading div
					$('.loader_div').empty();
					
					if(data == 'wksht_not_set') {
						// If user has not started a new worksheet, issue error before proceeding
						alert('Your data was not saved!  Please select \'New Worksheet\' on the left menu before proceeding!');
						return false;
					} else {
						/*
						var response = $(data);
						var profit_tables = response.filter('div.profit_page').html();
						var open_wksht_options = response.filter('div.open_wksht_options').html();
						var export_wksht_options = response.filter('div.export_wksht_options').html();
						
						// Replace <div class="open_wksht_options"> with new content
						$('div.open_wksht_options').html(open_wksht_options);
						
						// Replace <div class="export_wksht_options"> with new content
						$('div.export_wksht_options').html(export_wksht_options);
						*/
						// Replace <div class="profit_page"> contents with updated html markup
						$('div.profit_page').replaceWith(data);
						//$('div.profit_page').html(profit_tables);
					}
				},
				error: function(msg) {
					alert("There was an error processing your request");
				}
			});
		};
	});
	
	/* Delete user worksheet if user selects "Remove" button.
	 * Note: $_SESSION['profit_dlr_id'] && $_SESSION['profit_date'] will be used for SQL delete action
	 * System will display new empty profit form and success message upon deletion
	 * If user selects 'Remove' button before there has been any entry, show message saying 'No entries have been inserted yet'
	**/
	$(document.body).on("click", "form#profit_analysis_form input[type=submit]#delete_profit_wksht", function(event) {
		// Prevent form default submit action
		event.preventDefault();
		
		// Set the action from the form submission so that this can be submitted to ajax.inc.php file
		var action = $(event.target).attr("name"); // This == 'delete_profit_wksht'
		console.log('action: ' + action);
		
		// Load the spinner to indicate processing
		$('.loader_div').html('<div class="loader">Loading...</div>');
		
		// Set a time delay for the ajax call so that the user can see the spinner.  The spinner is only removed once the ajax call is complete.
		if(confirm("Are you sure you want to remove the current worksheet?")) {
			setTimeout(ajaxCall, timeDelay);
		} else {
			$('.loader_div').empty();
			return false;
		}	
		
		// Processes the profit data for input, displays new page content and removes the loading spinner
		function ajaxCall() {
			$.ajax({
				type: "POST",
				url: processFile, 
				data: 'action=' + action,
				success: function(data) {
					// Test return data
					console.log(data);
					
					// Save the response as a jQuery object, and then save each tab content for html replacement
					var response = $(data);
					var cost_tables = response.filter('#ajax_cost_tables').html();
					var profit_tables = response.filter('div.profit_page').html();
					var cost_tab_heading = response.filter('div.cost_tab_heading').html();
					var open_wksht_options = response.filter('div.open_wksht_options').html();
					var export_wksht_options = response.filter('div.export_wksht_options').html();
					
					// Remove the loading div
					$('.loader_div').empty();
					
					// If no entries have been saved yet, show alert. Else issue success alert and update the page with a blank profit worksheet.
					if(data == 'no_entries_found') {
						alert("You have not saved any data for this worksheet yet, so there is nothing to remove!")
						return false;
					} else {
						// Replace <div class="cost_tab_heading"> with hew title content
						$('div.cost_tab_heading').html(cost_tab_heading);
						
						// Replace <div id="ajax_cost_tables"> with new content
						$('#ajax_cost_tables').html(cost_tables);
						
						// Replace <div class="profit_page"> with new content
						$('div.profit_page').html(profit_tables);
						
						// Replace <div class="open_wksht_options"> with new content
						$('div.open_wksht_options').html(open_wksht_options);
						
						// Replace <div class="export_wksht_options"> with new content
						$('div.export_wksht_options').html(export_wksht_options);
						
						alert("You successfully deleted the worksheet!");
					}	
				},
				error: function(msg) {
					alert("There was an error processing your request");
				}
			});
		};
	});
	
	// Update Cost table dynamically via AJAX
	$(document.body).on("click", "form#cost_table_form input[type=submit]", function(event) {
	//$('form#cost_table_form').on('click', function(event) { **Could not use this because the DOM does not update correctly with new html, which affects some functionality**
		// Prevent form default submit action
		event.preventDefault();
		
		// Set the action from the form submission so that this can be submitted to ajax.inc.php file
		var action = $(event.target).attr("name");
		
		// Save the <div> id so that the correct html is replaced
		var div_svc_id = $(this).parents('div.cost_table').attr('id');
		
		// Serialize the form data for use with $.ajax()
		var formData = $(this).parents("form").serialize();
		
		// Establish error array
		var errors = [];
		var serialize_array = $(this).parents("form").serializeArray();
		// The serialize_array length should be == 10.  If anything less, user deleted all table rows with input values.
		if (serialize_array.length < 5) {
			errors.push("*You must submit service information to proceed!\n");
		}
		$.each(serialize_array, function(i, field){
			if (field.name == 'cost_desc[]') {
				if (field.value == '') {
					errors.push("*You left a Description field empty!\n");
				}
			}
			if (field.name == 'cost_code[]') {
				if (field.value == '') {
					errors.push("*You left a Code field empty!\n");
				}
			}
			if (field.name == 'cost_rocount[]') {
				if (!validWholeNumber.test(field.value)) {
					console.log('You entered an invalid ro count!');
					errors.push("*RO Count must be a whole number!\n");
				}
			}
			if (field.name == 'cost_parts_sale[]') {
				if (!validDollarValue.test(field.value)) {
					console.log('You entered an invalid parts cost!');
					errors.push("*Parts Sale must be a valid dollar amount!\n");
				}
			}
			if (field.name == 'cost_parts_cost[]') {
				if (!validDollarValue.test(field.value)) {
					console.log('You entered an invalid parts cost!');
					errors.push("*Parts Cost must be a valid dollar amount!\n");
				}
			}
			if (field.name == 'cost_labor_sale[]') {
				if (!validDollarValue.test(field.value)) {
					console.log('You entered an invalid labor sale!');
					errors.push("*Labor Sale must be a valid dollar amount!\n");
				}
			}
			console.log("field values: " + field.value);
            //$("#results").append(field.name + ":" + field.value + " ");
        });
		
		if (errors.length > 0) {
			var error_msg = "";
			for (var i=0; i<errors.length; i++) {
				error_msg += errors[i];
			}
			alert("Your table contains the following errors: \n\n" + error_msg + "\nPlease correct the errors and re-submit the table.");
			return false;
		}
		
		// Output submission to console for testing
		console.log("formData: " + formData);
		console.log("serialize_array: " + serialize_array);
		console.log("action: " + action);
		console.log("div_svc_id: " + div_svc_id);
		
		// Load the spinner to indicate processing
		/*orig spinner   $('.loader_div').html('<div class="loading">Loading&#8230;</div>'); */
		/*new spinner tests*/
		$('.loader_div').html('<div class="loader">Loading...</div>');
		
		// Set a time delay for the ajax call so that the user can see the spinner.  The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		
		// Function to execute AJAX call, which processes the form input, updates the Cost table and removes the loading spinner to indicate success to user
		function ajaxCall() {
			$.ajax({
				type: "POST",
				url: processFile, 
				data: formData,
				success: function(data) {
					// Test return data
					console.log(data);
					
					// Remove the loading div
					$('.loader_div').empty();
					
					if(data == 'wksht_not_set') {
						alert('Your data was not saved!  Please select \'New Worksheet\' on the left menu before proceeding!');
						return false;
					} else if(data == 'no_rows_error') {
						alert('Error! You must enter relevant data before submitting cost information!');
						return false;
					} else {
						// Replace <div class="cost_table" id=""> contents with updated table markup, using var div_svc_id as identifier
						//$("div.active_table").html(data);  **Tried this first but it removed the original div
						$('div.cost_table#' + div_svc_id).replaceWith(data);
					}
				},
				error: function(msg) {
					alert("There was an error processing your request");
				}
			});
		}
	});
	
	// Change profit worksheet privacy setting
	$(document.body).on("click", "button#privacy_submit", function(event) {
		event.preventDefault();
		
		// Set action var
		var action = 'update_privacy_status';
		
		// Get value of radio input with checked attribute
		var privacy = $('input[name="check"]:checked').val();
		
		// Set privacy feedback message
		var privacy_setting = (privacy == 1) ? 'Public' : 'Private';
		
		//console.log('action: ' + action + 'privacy: ' + privacy);
		//return false;
		
		// Manually close the modal
		$('#privacy_modal').modal('hide');
		
		// Load the spinner to indicate processing
		$('.loader_div').html('<div class="loader">Loading...</div>');
		
		// Set a time delay for the ajax call so that the user can see the spinner.  The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		
		// Function to execute AJAX call, which processes the form input, updates the Cost table and removes the loading spinner to indicate success to user
		function ajaxCall() {
			$.ajax({
				type: "POST",
				url: processFile, 
				data: 'action=' + action + '&privacy=' + privacy,
				success: function(data) {
					// Test return data
					console.log(data);
					
					// Save the response as a jQuery object, and then save content for html replacement
					var response = $(data);
					var privacy_update = response.filter('span.privacy_update').html();
					var open_wksht_options = response.filter('div.open_wksht_options').html();
					var privacy_modal = response.filter('div#privacy_modal').html();
					
					// Replace <div class="privacy_modal"> with new content
					$('div#privacy_modal').html(privacy_modal);
					
					// Remove the loading div
					$('.loader_div').empty();
					
					// Change the 'Public' or 'Private' status at top of worksheet
					$('span.privacy_update').html(privacy_update);
					
					// Replace <div class="open_wksht_options"> with refreshed <option>s list
					$('div.open_wksht_options').html(open_wksht_options);
					
					// Show alert so that user knows their request was processed
					alert("The worksheet privacy was successfully set to: " + privacy_setting + "!");
				},
				error: function(msg) {
					alert("There was an error processing your request");
				}
			});
		}
	});
	
	// Remove document from db when user clicks trash icon and confirms document removal
	$('body').on('click', 'a.glyphicon-trash', function() {
		console.log('Remove button clicked!');
		
		// Remove any error or success messages upon code execution
		$('p.error').remove();
		$('p.success').remove();
		
		// Get name attr to determine further action (if doc table trash icon, ask to confirm and then issue AJAX delete instruction)
		var name = $(this).attr("name");
		console.log('name: ' + name);
		
		// Get id attr so you can pass document id (file name) to method in Documents class
		var tmp_name = $(this).attr("id");
		console.log('tmp_name: ' + tmp_name);
		
		// If name == 'remove_doc_icon', issue confirm message to confirm document deletion. Then issue AJAX delete instruction
		if(name == 'remove_doc_icon') {
			if(confirm('Are you sure you want to delete the selected file?')) {
				// Load the spinner to indicate processing
				$('.loader_div').html('<div class="loader">Loading...</div>');
				
				// Get file_id for db delete action
				var view_doc_id = $(this).closest('tr').find('td form input#view_doc_id').val();
				console.log('view_doc_id: ' + view_doc_id);
				
				// Set action for ajax.inc.php process file
				var action = 'delete_doc';
		
				// The spinner is only removed once the ajax call is complete.
				setTimeout(ajaxCall, timeDelay);
				console.log('timeDelay: ' + timeDelay);
				
				//return false;
		
				// Save the ajax call as a function to execute within the setTimeout() function
				function ajaxCall() {
				 	$.ajax({
						type: "POST",
						url: processFile,
						data: 'action=' + action + '&view_doc_id=' + view_doc_id + '&tmp_name=' + tmp_name,
						success: function(returndata){
							console.log('returndata: ' + returndata);
		
							// Remove the loading div before the content is updated
							$('.loader_div').empty();
		
							if (returndata == "error_login") {
								if(confirm('You are no longer logged in! \n Proceed to login screen?')) {
									// If user is no longer logged in, display message and prompt 'okay' for page redirect to login page
									window.location.reload(true);
									return false;
								} else {
									return false;
								}
							}
							
							// Replace page content with returndata
							$('div.doc_table').html(returndata);
							
							// Re-initialize table functionality
							$("#user_doc_table").DataTable({
						   		paging: true,
						   		searching: true,
						   		order: []
				   			});
						},
						error: function(response){
							// Remove the loading div before the content is updated
							$('.loader_div').empty();
		
						 	alert("There was an error processing your request!");
						}
					});
				// End ajaxCall() fn
				} 
			// End if confirm
			} else { 
				return false;
			}
		// End if(name == 'remove_doc_icon')
		} else {
			return false;
			//$(this).parent().parent().remove();
		}
	});
	
	// Retrieve document info from db when user clicks edit icon and reload doc form with edit info filled in
	$('body').on('click', 'a.glyphicon-pencil', function() {
		console.log('Edit doc button clicked!');
		
		// Get name attr to determine further action (if doc table trash icon, ask to confirm and then issue AJAX delete instruction)
		var name = $(this).attr("name");
		console.log('name: ' + name);
		
		// If name == 'edit_doc_icon', load doc info and put into reloaded doc form
		if(name == 'edit_doc_icon') {
			// Load the spinner to indicate processing
			$('.loader_div').html('<div class="loader">Loading...</div>');
			
			// Get file_id for db delete action
			var edit_doc_id = $(this).closest('tr').find('td form input#view_doc_id').val();
			console.log('edit_doc_id: ' + edit_doc_id);
			
			// Set action for process_ajax.inc.php process file
			var action = 'edit_doc_form';
		
			// The spinner is only removed once the ajax call is complete.
			setTimeout(ajaxCall, timeDelay);
			console.log('timeDelay: ' + timeDelay);
		
			// Save the ajax call as a function to execute within the setTimeout() function
			function ajaxCall() {
			 	$.ajax({
					type: "POST",
					url: processFile,
					data: 'action=' + action + '&edit_doc_id=' + edit_doc_id,
					success: function(returndata){
						console.log('returndata: ' + returndata);
		
						// Remove the loading div before the content is updated
						$('.loader_div').empty();
		
						if (returndata == "error_login") {
							if(confirm('You are no longer logged in! \n Proceed to login screen?')) {
								// If user is no longer logged in, display message and prompt 'okay' for page redirect to login page
								window.location.reload(true);
								return false;
							} else {
								return false;
							}
						}

						// Filter out returndata content so it is in correct format (no div tags) for replacing div.doc_form
						var response = $(returndata);
						var doc_form_content  = response.filter('div.doc_form').html();
						
						// Replace page content with returndata
						$('div.doc_form').html(doc_form_content);
						
					},
					error: function(response){
						// Remove the loading div before the content is updated
						$('.loader_div').empty();
		
					 	alert(cxn_error);
					}
				});
			// End ajaxCall() fn
			}
		// End if(name == 'edit_doc_icon')
		} else {
			return false;
		}
	});
	
	/* If user clicks 'Upload File' button on add document form, process file addition to db.
	 * Note that this comes from the js-created class .fileinput-upload-button from the Bootstrap file upload plugin!!
	 * You will not find this class in your own html markup.
	**/
	$('body').on('click', 'button.fileinput-upload-button', function (event) {
		event.preventDefault();
		
		// Remove any error or success messages on the page
		$('p.error').remove();
		$('p.success').remove();
		
		// Initialize error array
		var errors = [];
		
		// Set action for process_ajax.inc.php. Note: probably need to grab this so it is dynamic (it is the same as the hidden input 'action'.
		var action = 'file_submit';
		
		// Get the document title
		var doc_title = document.getElementById("doc_title").value;
		//var doc_title = document.getElementById("doc_title").val();
		
		// Get the document description
		var doc_desc = document.getElementById("doc_desc").value;
		
		// Note: file name is not needed here, as it is shipped along with the $_POST['files'] data (var file)
		// Get the file name
		// var file_name = document.getElementById("file").value;
		//var file_name = document.getElementsByClassName("file-caption");
		
		// Get the document category type. Note: you took this out (was originally in Online Reporting revamp file)
		//var doc_category = document.getElementById("doc_category").value;
		
		// Get the file
		var file = document.getElementById("file_input").files[0];
		console.log('file: ' , file);
		
		// Check to make sure form inputs are not empty
		if(doc_title == "") {
			errors.push("Please enter a document title!");
		}
		
		/*
		if(file_name == "") {
			errors.push("Please enter a file name for your documents directory!");
		}*/
		/* Note: this was taken out (was in original Online Reporting revamp)
		if(doc_category == "") {
			errors.push("Please enter a document category!");
		}
		*/
		if(file == null) {
			errors.push("You must select a file before proceeding!");
		}
		
		// Establish error message string
		var error_msg = "The following input errors have occurred: \n\n";
		
		// Iterate through each error message if errors.length > 0
		if(errors.length > 0) {
			for(i=0; i<errors.length; i++) {
				error_msg += errors[i] + "\n";
			}
			error_msg += "\nPlease correct the errors and try again.";
			alert(error_msg);
			return false;
		}

		// Show dec description conirm dialogue only after title error, if user left it blank (leaving it blank is acceptable)
		if(doc_desc == "") {
			//errors.push("Please enter a document description!");
			if(!confirm("Are you sure you don\'t want to include a document description? This field is not required, but may help you to organize your files. Just saying!")) {
				return false;
			}
		}
		
		console.log('doc_title: ' + doc_title + 'doc_desc: ' + doc_desc);
		//return false;
		// Use FormData object to send data. This is the only way to send actual files via AJAX. Don't forget to check for null $_POST['dec_desc'].
		var formData = new FormData();
		formData.append('file_input', file);
		formData.append('action', action);
		formData.append('doc_title', doc_title);
		//formData.append('file_name', file_name);
		if(doc_desc != null) {
			formData.append('doc_desc', doc_desc);
		}
		//formData.append('doc_category', doc_category);
		
		// Check the file type
		var type_test = /^.*pdf$/;
  		if (!file.type.match(type_test)) {
    		alert("Error: Only pdf files are allowed!");
    		return false;
  		}
		
		// Load the spinner to indicate processing
		$('.loader_div').html('<div class="loader">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		//return false;

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: formData,
				processData: false,
				contentType: false,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}
					
					/* Save the response as a jQuery object, and then save content for html replacement
					 * Note: you are able to still preserve the original div here due to the filter().html() instruction,
					 * even though the <div>s are duplicated in the doc.php file AND the #$#$@#$@#%#@%@#%?? 
					**/
					var response = $(returndata);
					var doc_form_content  = response.filter('div.doc_form').html();
					var doc_table_content = response.filter('div.doc_table').html();

					// Replace document form with reloaded, empty form.  
					$('div.doc_form').html(doc_form_content);
					
					// Replace document table with updated doc table to reflect new file addition
					$('div.doc_table').html(doc_table_content);
					
					// Re-initialize bootstrap file input plugin
					$("#file_input").fileinput();
					
					// Re-initialize dataTables
					$("#user_doc_table").DataTable();
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Save Changes' button on edit document form, process file edit info and update db
	$('body').on('click', 'input#file_update_submit', function (event) {
		event.preventDefault();
		
		// Initialize error array
		var errors = [];
		
		// Set action for process_ajax.inc.php
		var action = 'file_update_submit';
		
		// Get the document title
		var doc_title = document.getElementById("doc_title").value;
		
		// Get the document description
		var doc_desc = document.getElementById("doc_desc").value;
		
		// Get the document type ....this was taken out for toolkit. Is active in edumix Online Reporting revamp
		//var doc_category = document.getElementById("doc_category").value;
		
		// Get the file
		var file_name = document.getElementById("file_name").value;
		
		// Get file id for db update
		var file_id = document.getElementById("edit_doc_id").value;
		
		// Check to make sure form inputs are not empty
		if(doc_title == "") {
			errors.push("Please enter a document title!");
		}
		
		/*
		if(doc_category == "") {
			errors.push("Please enter a document category!");
		}*/
		
		// If file_name != "", check to make sure the user has not added a '.pdf' file extension to it.  This is automatically added later
		if(file_name == "") {
			errors.push("Please enter a file name!");
		} else if (file_name.slice(-4) == ".pdf") {
			errors.push("Please remove the file extension from the file name!");
		}
		//console.log('file_name.slice(-4,0): ' + file_name.slice(-4));
		
		// Establish error message string
		var error_msg = "The following input errors have occurred: \n\n";
		
		// Iterate through each error message if errors.length > 0
		if(errors.length > 0 ) {
			for(i=0; i<errors.length; i++) {
				error_msg += errors[i] + "\n";
			}
			error_msg += "\nPlease correct the errors and try again.";
			alert(error_msg);
			return false;
		}

		// Show dec description conirm dialogue only after title error, if user left it blank (leaving it blank is acceptable)
		if(doc_desc == "") {
			//errors.push("Please enter a document description!");
			if(!confirm("Are you sure you don\'t want to include a document description? This field is not required, but may help you to organize your files. Just saying!")) {
				return false;
			}
		}
		
		console.log('doc_title: ' + doc_title + 'doc_desc: ' + doc_desc + 'file_name: ' + file_name + 'file_id: ' + file_id);
		
		// Use FormData object to send data. This is the only way to send actual files via AJAX
		var formData = new FormData();
		formData.append('file_name', file_name);
		formData.append('action', action);
		formData.append('doc_title', doc_title);
		if(doc_desc != null) {
			formData.append('doc_desc', doc_desc);
		}
		//formData.append('doc_category', doc_category);
		formData.append('file_id', file_id);
		console.log('formData: ' + formData);
		
		// Load the spinner to indicate processing
		$('.loader_div').html('<div class="loader">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function.
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: formData,
				processData: false,
				contentType: false,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					var response = $(returndata);
					var doc_form_content  = response.filter('div.doc_form').html();
					var doc_table_content = response.filter('div.doc_table').html();

					// Replace document form with reloaded, empty form.  
					$('div.doc_form').html(doc_form_content);
					
					// Replace document table with updated doc table to reflect new file addition
					$('div.doc_table').html(doc_table_content);
					
					// Re-initialize bootstrap file input plugin
					$("#file_input").fileinput();
					
					// Re-initialize dataTables
					$("#user_doc_table").DataTable();
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	/* If user clicks on the 'cancel' link on the edit document form, return fresh form with empty inputs */
	$('body').on('click', 'a.edit-doc-cancel', function (event) {
		event.preventDefault();
		
		// Set action for process_ajax.inc.php
		var action = 'edit_doc_cancel_link';
		
		// Load the spinner to indicate processing
		$('.loader_div').html('<div class="loader">Loading...</div>');

		var formData = new FormData();
		formData.append('action', action);

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function.
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: formData,
				processData: false,
				contentType: false,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					var response = $(returndata);
					var doc_form_content  = response.filter('div.doc_form').html();

					// Replace document form with reloaded, empty form.  
					$('div.doc_form').html(doc_form_content);
					
					// Re-initialize bootstrap file input plugin
					$("#file_input").fileinput();
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
});