<?php
/**
 * File: process.inc.php
 * Purpose: This file processes add/edit event actions
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description									by
 *   09/21/2015		Initial design & coding	    				Matt Holland
 *	 10/19/2015		Added two new action arrays to the file		Matt Holland
 *					for the processing of the 'Prev' and
 *					'Next' navigation buttons
 */
 
/*
 * Enable sessions
 */
session_start();

/*
 * Include necessary files
 */
include_once '../../sys/config/db-cred.inc.php';

/*
 * Define constants for config info
 */
foreach ($C as $name => $val) {
	define($name, $val);
}

/*
 * Create a lookup array for form actions
 *
 * The first item (array index) comes from the $_POST['action'] from a form
 * The first array item 'object' is the name of the class
 * The second array item 'method' is the method called from within the class
 * The third array item is where the header will be redirected, as these are not ajax calls
 */
$actions = array (
			   'event_edit' => array (
			  	'object' => 'Calendar',
			  	'method' => 'processForm',
			  	'header' => 'Location: ../../index.php'
			  ),
			  
			  'prev_month' => array(
			    'object' => 'Calendar',
				'method' => 'incrementMonth',
				'header' => 'Location: ../../index.php'
			  ),
			  
			  'next_month' => array(
			    'object' => 'Calendar',
				'method' => 'incrementMonth',
				'header' => 'Location: ../../index.php'
			  )
		   );

/*
 * Make sure the anti-CSRF token was passed and that the
 * requested action exists in the lookup array
 * The original if stmtn was if (TRUE===...).  I had to change because
 * it wouldn't work with my new incrementMonth function
 */
if ($_POST['token'] == $_SESSION['token'] && isset($actions[$_POST['action']])) {
	$use_array = $actions[$_POST['action']];
	$obj = new $use_array['object']($dbo);
	if (TRUE == $msg=$obj->$use_array['method']()) {
		/*
		 * If a change month action was requested, set $_SESSION['useDate']
		 * Tried to check with $use_array == 'prev_month' etc., but wouldn't work
		 */
		if ($_POST['action'] == 'prev_month' || $_POST['action'] == 'next_month') {
			$_SESSION['useDate'] = $msg;
		}
		header($use_array['header']);
		exit;
	} else {
		// If an error occurred, output it and end execution
		header("Location: ../../index.php");
		exit;
	}
} else {
	// Redirect to the main index if the token/action is invalid
	// No need to show an error, as this would only happen if the user tried to directly access this file.
	header("Location: ../../index.php");
	exit;
}

function __autoload($class_name) {
	$filename = "../../sys/class/class." .$class_name. ".inc.php";
	if (file_exists($filename)) {
		include_once $filename;
	}
}

/** This is the original code from another file: init.inc.php
 ** which seems to do the same thing as the code above.  Wasn't sure
 ** why the code above differs from this code.
 ** It may be an error?  Or maybe it is different for a reason??
function __autoload($class) {
	$filename = 'sys/class/class.'$class.'inc.php';
	if (file_exists($filename)) {
		include_once $filename;
	}
}
 **/
?>