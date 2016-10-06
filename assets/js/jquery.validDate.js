// Checks for a valid date string (YYYY-MM-DD HH:MM:SS)

/**Original Code in book.  Plugin method applied later in book below**
function validDate(date) {
	// Define the regex pattern to validate the format
	var pattern = /^(\d{4}(-\d{2}){2} (\d{2})(:\d{2}){2})$/;
	
	// Returns true if the date matches, false if it doesn't
	return date.match(pattern)!=null;
}
**/
(function($){
	// Extends the jQuery object to validate date strings
	$.validDate = function(date, options) {
		// Sets up default values for the method
		var defaults = {
			"pattern" : /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/
		},
		
		// Extends the defaults with user-supplied options
		opts = $.extend(defaults, options);
		
		// Returns true if a match is found, false otherwise
		return date.match(opts.pattern)!=null;
	};
})(jQuery);