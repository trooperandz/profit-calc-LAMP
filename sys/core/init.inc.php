<?php
/**
 * File: init.inc.php
 * Purpose: Initialization file which collects data, loads files, 
 * 			and organizes information for the application.
 * PHP version 5.1.2
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description						by
 *   09/11/2015		Initial design & coding	    	Matt Holland
 *
 */

/*
 * Enable sessions
 */
if(!isset($_SESSION)) {
	session_start();
}

/*
 * Generate an anti-CSRF token if one doesn't exist.
 * Security measure to prevent cross-site request forgeries.
 * Token is posted along with form data for verification
 */
if (!isset($_SESSION['token'])) {
	$_SESSION['token'] = sha1(uniqid(mt_rand(), TRUE));
}
 
/*
 * Include the necessary configuration info
 */
include_once 'sys/config/db-cred.inc.php';

/* Set Last page session variable
 *
 */
$_SESSION['lastpage'] = substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);

/*
 * Define constants for configuration info
 */
foreach ($C as $name=>$val) {
	define($name, $val);
}

// Define constants for various program information
define('BASE_URL', 'www.soscompany.net');

/*
 * Create a PDO object 
 */
$dsn = "mysql:host=" .DB_HOST. "; dbname=" .DB_NAME;
$dbo = new PDO($dsn, DB_USER, DB_PASS);

/*
 * Function for displaying errors on the page
 */
function displayErrors() {
	if(isset($_SESSION['error'])) {
		// Set up row in accordance with Bootstrap grid
		/*echo '<div class="container"><div class="row"><div class="col-md-12">';*/
		echo '<div class="row"><div class="col-md-12">';
		foreach ($_SESSION['error'] as $error) {
			echo '<p class="error">',$error,'</p>';
		}
		/*echo '</div></div></div>';*/
		echo '</div></div>';
		// Unset errors for page refresh
		unset ($_SESSION['error']);
	}
}

/*
 * Function for displaying success msgs on the page
 */
function displaySuccessMsg() {
	if(isset($_SESSION['success'])) {
		// Set up row in accordance with Bootstrap grid
		/*echo '<div class="container"><div class="row"><div class="col-md-12">';*/
		echo '<div class="row"><div class="col-md-12">';
		foreach ($_SESSION['success'] as $success) {
			echo '<p class="success">',$success,'</p>';
		}
		/*echo '</div></div></div>';*/
		echo '</div></div>';
		// Unset errors for page refresh
		unset ($_SESSION['success']);
	}
}
			
/*
 * Define the auto-load function for classes
 */
function __autoload($class) {
	$filename = "sys/class/class." .$class. ".inc.php";
	if (file_exists($filename)) {
		include_once($filename);
	}
}
?>