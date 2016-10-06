<?php
/**
 * File: process.inc.php
 * Purpose: This file processes add/edit event actions
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description																by
 *   09/21/2015		Initial design & coding	    											Matt Holland
 *	 10/19/2015		Added two new action arrays to the file	for the processing of the 		Matt Holland
 *					'Prev' and 'Next' navigation buttons.
 *	 10/20/2015		Added sticky form elements for processing of event edits				Matt Holland
 *	 10/27/2015		Added add_user event action array for processing of new users			Matt Holland
 *					Added sticky form elements for processing/editing of new users
 *
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
		      'user_login' => array (
			  	'object'  => 'Admin',
			  	'method'  => 'processLoginForm',
			  	'header1' => 'Location: ../../profit_calc.php',
			  	'header2' => 'Location: ../../index.php'
			  ),
			  
			  'user_logout' => array(
			    'object' => 'Admin',
				'method' => 'processLogout',
				'header' => 'Location: ../../index.php'
			  ),
			  
			  'forgot_pass_email_submit' => array(
			  	'object'  => 'Admin',
			  	'method1' => 'emailPassResetLink',
			  	'header1' => 'Location: ../../index.php'
			  ),
			  
			  'reset_pword' => array(
			  	'object'  => 'Admin',
			  	'method1' => 'validateResetPassData',
			  	'header1' => 'Location: ../../index.php'
			  ),
			  
			  'add_user' => array (
				'object' => 'Admin',
				'method' => 'processUserForm',
				'header' => 'Location: ../../users.php'
			  ),
			  
			  'new_profit_worksheet' => array (
				'object' => 'ProfitAnalysisInfo',
				'method' => 'processNewProfitWorksheet',
				'header' => 'Location: ../../profit_calc.php'
			  ),
			  
			  'existing_profit_worksheet' => array (
				'object' => 'ProfitAnalysisInfo',
				'method' => 'processExistingProfitWorksheet',
				'header' => 'Location: ../../profit_calc.php'
			  ),
			  
			  'profit_analysis_entry' => array (
				'object' => 'ProfitAnalysisInfo',
				'method' => 'processProfitTableInput',
				'header' => 'Location: ../../profit_calc.php'
			  ),
			  
			  'cost_table_entry' => array (
				'object' => 'CostAvgCalcInfo',
				'method' => 'processCostTableInput',
				'header' => 'Location: ../../profit_calc.php'
			  ),
			  'export_profit_data' => array (
				'object' => 'ProfitAnalysisInfo',
				'method' => 'exportProfitAnalysisData',
				'header' => 'Location: ../../profit_calc.php'
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
	
	if ($_POST['action'] == 'export_profit_data') {
		// Set export SESSION vars for functions
		$dealer_info = explode(',', $_POST['dlr_export_select']); // [0] == dealer, [1] == date
		$_SESSION['export_dlr_id'] = $dealer_info[0];
		$_SESSION['export_date'] = $dealer_info[1];
		$_SESSION['export_dlr_name'] = $dealer_info[2];
		$_SESSION['export_date_display'] = $dealer_info[3];
		
		// Generate CSV text
		$data = $obj->$use_array['method']();
		
		// Unset $_SESSION export globals
		unset($_SESSION['export_dlr_id'], $_SESSION['export_date'], $_SESSION['export_dlr_name'], $_SESSION['export_dlr_name'], $_SESSION['export_date_display']);
	
		// Download the file
		$filename = 'ProfitAnalysisExport.csv';
		header('Content-type: application/csv');
		header('Content-Disposition: attachment; filename='.$filename);
		echo $data;
		exit;
	}
	
	if ($_POST['action'] == 'add_user') {
		// Run processUserForm() method
		$obj->$use_array['method']();
		// Set sticky form elements for add/edit user actions if errors occur in processUserForm method
		// Need to revisit this: if new user is being added,
		/* 
		if(!empty($_POST['user_id'])) {
			// Both the add form and edit form will run through this.  
			// Need to check so that $_SESSION['edit_user_id'] is not being set when there is no $_POST['user_id']
			// This is because it could cause an add user sumbit to go through the UPDATE statement instead of the INSERT statement
			$_SESSION['edit_user_id'] = $_POST['user_id'];
		}*/
		/*	
		$_SESSION['fname'] 	= $_POST['fname']	;
		$_SESSION['lname'] 	= $_POST['lname']	;
		$_SESSION['uname'] 	= $_POST['uname']	;
		$_SESSION['email'] 	= $_POST['email']	;
		$_SESSION['admin'] 	= $_POST['admin']	;
		$_SESSION['active'] = $_POST['active']	;
		$_SESSION['team'] 	= $_POST['team']	;
		$_SESSION['pword1'] = $_POST['pword1']	;
		$_SESSION['pword2'] = $_POST['pword2']	;
		*/
		// Exit to designated page
		header("Location: ../../".$_SESSION['lastpage']);
		//header($use_array['header']);
		exit;
	}
	
	if ($_POST['action'] == 'user_login') {
		// Run processLogin() method
		if(!$obj->$use_array['method']()) {
			header($use_array['header2']);
		} else {
			// Return to designated page
			header($use_array['header1']);
		}
		exit;
	}
	
	if ($_POST['action'] == 'forgot_pass_email_submit') {
		// Run emailPassResetLink() method
		$obj->$use_array['method1'](array('user_email'=>$_POST['forgot_pass_email']));
		// Return to login (index.php) page 
		header($use_array['header1']);
	}
	
	if ($_POST['action'] == 'reset_pword') {
		// Execute validateResetPassData() method 
		$obj->$use_array['method1'](array('reset_email'=>$_POST['reset_email'], 'reset_pword1'=>$_POST['reset_pword1'], 'reset_pword2'=>$_POST['reset_pword2']));
		// Return to login (index.php) page
		header($use_array['header1']);
	}
	
	if ($_POST['action'] == 'user_logout') {
		// Run processLogout() method
		$obj->$use_array['method']();
		// Return to designated page
		header($use_array['header']);
		exit;
	}
	
	// If an error occurred, output it and end execution
	//header("Location: ../../".$_SESSION['lastpage']);
} else {
	// Redirect to the main index page (login) if the token/action is invalid
	header("Location: ../../index.php");
	exit;
}

function __autoload($class_name) {
	$filename = "../../sys/class/class." .$class_name. ".inc.php";
	if (file_exists($filename)) {
		include_once $filename;
	}
}
?>