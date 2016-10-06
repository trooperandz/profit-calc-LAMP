<?php
/**
 * File: export_profit_data.php
 * Purpose: Export profitability data (including cost data)
 * History:
 *   Date			Description									by
 *   07/14/2016		Initial design & coding	    				Matt Holland
 */

// Enable sessions
session_start(); 

// Make sure that the user is logged in
if (!isset($_SESSION['user'])) {
	header("Location: login.php");
	exit;
}
 
// Include necessary files
include_once 'sys/core/init.inc.php';

// Set export SESSION globals so that correct data is processed
$_SESSION['export_dlr_id'] 		= $_SESSION['profit_dlr_id']	  ;
$_SESSION['export_date'] 		= $_SESSION['profit_date']		  ;
$_SESSION['export_dlr_name'] 	= $_SESSION['profit_dlr_name']	  ;
$_SESSION['export_date_display']= $_SESSION['profit_date_display'];

// Run the export function
$obj = new ProfitAnalysisInfo;
$data = $obj->exportProfitAnalysisData();

// Download the file
$filename = 'ProfitabilityCalcExport.csv';
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename='.$filename);
echo $data;

// Now unset SESSION export globals to ensure no future conflicts
unset($_SESSION['export_dlr_id'], $_SESSION['export_date'], $_SESSION['export_dlr_name'], $_SESSION['export_dlr_name'], $_SESSION['export_date_display']);

exit;
?>