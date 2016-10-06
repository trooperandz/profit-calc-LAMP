<?php
/**
 * File: ajax.inc.php
 * Purpose: Process ajax calls, use echo to return output
 * Similar to process.inc.php
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description									by
 *   09/24/2015		Initial design & coding	    				Matt Holland
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
 */
$actions = array(
			'event_view' => array(
				'object' => 'Calendar',
				'method' => 'displayEvent'
			),
			'edit_event' => array(
				'object' => 'Calendar',
				'method' => 'displayForm'
			),
			'event_edit' => array(
				'object' => 'Calendar',
				'method' => 'processForm'
			),
			'delete_event' => array(
				'object' => 'Calendar',
				'method' => 'confirmDelete'
			),
			'confirm_delete' => array(
				'object' => 'Calendar',
				'method' => 'confirmDelete'
			),
			'cost_table_entry' => array(
				'object' => 'CostAvgCalcInfo',
				'method' => 'processCostTableInput'
			),
			'profit_analysis_entry' => array(
				'object'  => 'ProfitAnalysisInfo',
				'method'  => 'processProfitTableInput',
				'method2' => 'getExistingWorksheets'
			),
			'new_profit_worksheet' => array(
				'object'  => 'ProfitAnalysisInfo',
				'method1' => 'processNewProfitWorksheet',
				'method2' => 'getPageHeading'
			),
			'existing_profit_worksheet' => array(
				'object'  => 'ProfitAnalysisInfo',
				'method1' => 'processExistingProfitWorksheet',
				'method2' => 'getPageHeading'
			),
			'delete_profit_wksht' => array(
				'object'  => 'ProfitAnalysisInfo',
				'method1' => 'deleteProfitData',
				'method2' => 'deleteCostData',
				'method3' => 'buildProfitTables',
				'method4' => 'getExistingWorksheets'
			),
			'update_privacy_status' => array(
				'object'  => 'ProfitAnalysisInfo',
				'method1' => 'getExistingWorksheets',
				'method2' => 'updateProfitDailyInfoTable',
				'method3' => 'getPrivacyModal'
			),
			/* NOT possible with AJAX?? File viewing??
			'table_doc_select' => array(
				'object'  => 'Documents',
				'method1' => 'viewFile'
			),*/
			'file_submit' => array(
				'object'  => 'Documents',
				'method1' => 'processFileUpload',
				'method2' => 'getFileUploadForm',
				'method3' => 'getDocTable'
			),
			'delete_doc'  => array(
				'object'  => 'Documents',
				'method1' => 'deleteDoc',
				'method2' => 'getDocTable'
			),
			'edit_doc_form' => array(
				'object'  => 'Documents',
				'method1' => 'getFileUploadForm'
			),
			'file_update_submit' => array(
				'object'  => 'Documents',
				'method1' => 'updateDoc',
				'method2' => 'getFileUploadForm',
				'method3' => 'getDocTable'
			),
			'edit_doc_cancel_link' => array(
				'object'  => 'Documents',
				'method1' => 'getFileUploadForm'
			)
		);
/*
 * Make sure the anti-CSRF token was passed and that
 * the requested action exists in the lookup array
 */
if (isset($actions[$_POST['action']])) {
	$use_array = $actions[$_POST['action']];
	$obj = new $use_array['object']($dbo);
	
	/*
	 * Check for an ID and sanitize it if found
	 */
	if (isset($_POST['event_id'])) {
		$id = (int) $_POST['event_id'];
	} else {
		$id = NULL;
	}
	
	// Save svc_id POST for ajax cost table methods
	if (isset($_POST['cost_table_svc_id'])) {
		$svc_id = $_POST['cost_table_svc_id'];
	}
	
	// Process AJAX call for profit analysis page entry
	if ($_POST['action'] == 'profit_analysis_entry') {
		$result = $obj->$use_array['method'](); // This will run the processProfitTableInput method
		if($result == 'wksht_not_set') {
			echo $result;
		} else {
			$html = $obj->buildProfitTables();
			/* took out the below code, as decided to leave the <select> dropdown as is so that last selection remains in view
			// Now build <select> options to update 'Open Worksheet' and 'Export Worksheet' <option>'s
			$html.= '<div class="open_wksht_options">
					   <select class="form-control" id="dlr_select" name="dlr_select">
						<option value="">Select...</option>';
					$worksheets = $obj->$use_array['method2'](); // getExistingWorksheets() method
					foreach ($worksheets as $worksheet) {
						/* Put date in correct format for viewing.  Also pass both date formats so that SESSION 
						 * variables may be set in processExistingProfitWorksheet() method
						**/
						/*
						$sql_date = $worksheet['record_date'];
						$display_date = new DateTime($sql_date);
						$display_date = $display_date->format('m-d-Y');
						$html.= 	
						'<option value="'.$worksheet['dealer_record_id'].','.$worksheet['dealer_code'].','.$worksheet['dealer_name'].','.$sql_date.','.$display_date.','.$worksheet['public_access'].','.$worksheet['dealer_team_id'].'">'.$worksheet['dealer_name'].' '.$worksheet['dealer_code'].' ('.$display_date.')</option>';
					}
			$html.=	  '</select>
					</div> <!-- end .open_wksht_options -->'; 
			$html.='<div class="export_wksht_options">
					  <select class="form-control" id="dlr_export_select" name="dlr_export_select">
						<option value="">Select...</option>';
					foreach ($worksheets as $worksheet) {
						/* Put date in correct format for viewing.  Also pass both date formats so that SESSION 
						 * variables may be set in processExistingProfitWorksheet() method
						**/
						/*
						$sql_date = $worksheet['record_date'];
						$export_date = new DateTime($sql_date);
						$export_date = $export_date->format('m-d-Y');
						$html.=
						'<option value="'.$worksheet['dealer_record_id'].','.$worksheet['record_date'].','.$worksheet['dealer_name'].','.$export_date.','.$worksheet['dealer_team_id'].'">'.$worksheet['dealer_name'].' '.$worksheet['dealer_code'].' ('.$export_date.')</option>';
					}
			$html.=	  '</select>
				    </div> <!-- end .export_wksht_options -->';*/
			echo $html;
		}
	}
	
	// Process AJAX call for cost table entry
	if ($_POST['action'] == 'cost_table_entry') {
		$result = $obj->$use_array['method'](); // This will run the processCostTableInput method (INSERT into DB)
		if($result == 'wksht_not_set') {
			echo $result;
		} elseif ($result == 'no_rows_error') { 
			echo $result;
		} else {
			$ajax_table = $obj->getAjaxCostTable($svc_id);
			echo $ajax_table;
		}
	}
	
	// Process AJAX call for new profit worksheet
	if (($_POST['action'] == 'new_profit_worksheet') || ($_POST['action'] == 'existing_profit_worksheet')) {
		// This will run the processExistingProfitWorksheet() method which sets new SESSION variables for profitability calculator
		$obj->$use_array['method1'](); 
		$new_profit_page = $obj->buildProfitTables();
		$html = $new_profit_page;  // This will return the profitability page
		// Now build the Cost Avg tab page and echo results inside of 'ajax_cost_tables' div
		$html .= '<div class="cost_tab_heading">
					<div class="col-sm-12">
						'.$obj->$use_array['method2']().
				   '</div>
				  </div>';
		$html .= '<div id="ajax_cost_tables">';
			// Create new Cost Average Table object
			$tables = new CostAvgCalcInfo();
				// Return array()'s of individual Cost Average table objects  	 		
				$table_array = $tables->getTables();
				 // Loop through each table object, creating each Cost Average Table 		
				foreach ($table_array as $cost_avg_table) {
					$html .= $cost_avg_table->buildCostAvgTable();
				}
			$html .=
			  '</div>';
			$modal = new Modal();
			$html .= $modal->getPrivacyModal();
			
		echo $html;	  
	}
	
	/* Process AJAX call for entire worksheet deletion.
	 * Note: checkTableData() methods must both be run in both classes before executing delete statements
	 * Note: after successful deletion, generate blank worksheets
	 * $_SESSION['profit_dlr_id'] and $_SESSION['profit_date'] must also be unset for page reload of empty worksheet
	 * Note: try to re-write so that both profit/cost tables and side menu <select> options are updated concurrently.
	 * Need to also include code for re-generation of <select> dropdowns in buildProfitTables() method
	**/
	if ($_POST['action'] == 'delete_profit_wksht') {
		// Create new CostAvgCalcInfo object for executing cost actions. 
		$cost = new CostAvgCalcInfo;
		// Note: deleteProfitData() method uses global vars for targeting specific worksheet
		$obj->$use_array['method1'](); // deleteProfitData() method
		// Now delete all cost data. Pass false 'svc_id' so that all data is deleted (not just svc_id association)
		$cost->$use_array['method2'](array('svc_id'=>false)); // deleteCostData() method
		// Now unset globals so that worksheet is no longer displaying dealer data that was just removed
		unset($_SESSION['profit_dlr_id'], $_SESSION['profit_date']);
		// Now rebuild page tabs for display of empty worksheet to user
		$html = $obj->$use_array['method3'](); // buildProfitTables() method
		$html.= '<div class="cost_tab_heading"><h3 class="main_profit_title">Profitability Analysis: <span style="font-size: 15px;">(Select a new or existing worksheet to begin)</span></h3></div>';
		$html.= '<div id="ajax_cost_tables">';
		// Now build Cost Average tables
		$table_array = $cost->getTables();
		// Loop through each table object, creating each Cost Average Table 		
		foreach ($table_array as $cost_avg_table) {
			$html .= $cost_avg_table->buildCostAvgTable();
		}
		$html.= '</div> <!-- end #ajax_cost_tables -->';
		// Now build <select> options to update 'Open Worksheet' and 'Export Worksheet' <option>'s
		$html.= '<div class="open_wksht_options">
					<select class="form-control" id="dlr_select" name="dlr_select">
						<option value="">Select...</option>';
					$worksheets = $obj->$use_array['method4'](); // getExistingWorksheets() method
					foreach ($worksheets as $worksheet) {
						/* Put date in correct format for viewing.  Also pass both date formats so that SESSION 
						 * variables may be set in processExistingProfitWorksheet() method
						**/
						$sql_date = $worksheet['record_date'];
						$display_date = new DateTime($sql_date);
						$display_date = $display_date->format('m-d-Y');
						$html.= 	
						'<option value="'.$worksheet['dealer_record_id'].','.$worksheet['dealer_code'].','.$worksheet['dealer_name'].','.$sql_date.','.$display_date.','.$worksheet['public_access'].','.$worksheet['dealer_team_id'].'">'.$worksheet['dealer_name'].' '.$worksheet['dealer_code'].' &nbsp;('.$display_date.' &nbsp;'.$worksheet['user_uname'].')</option>';
					}
		$html.=		'</select>
				</div> <!-- end .open_wksht_options -->'; 
		$html.='<div class="export_wksht_options">
					<select class="form-control" id="dlr_export_select" name="dlr_export_select">
						<option value="">Select...</option>';
					foreach ($worksheets as $worksheet) {
						/* Put date in correct format for viewing.  Also pass both date formats so that SESSION 
						 * variables may be set in processExistingProfitWorksheet() method
						**/
						$sql_date = $worksheet['record_date'];
						$export_date = new DateTime($sql_date);
						$export_date = $export_date->format('m-d-Y');
						$html.=
						'<option value="'.$worksheet['dealer_record_id'].','.$worksheet['record_date'].','.$worksheet['dealer_name'].','.$export_date.','.$worksheet['dealer_team_id'].'">'.$worksheet['dealer_name'].' '.$worksheet['dealer_code'].' &nbsp;('.$export_date.' &nbsp;'.$worksheet['user_uname'].')</option>';
					}
		$html.=		'</select>
				</div> <!-- end .export_wksht_options -->';
		// Return $html data to user (new empty worksheet)
		echo $html;
	}
	
	if($_POST['action'] == 'update_privacy_status') {
		// Set privacy_update div to correct verbage, and update $_SESSION['profit_privacy']
		$privacy = ($_POST['privacy'] == 1) ? 'Public' : 'Private';
		$_SESSION['profit_privacy'] = $_POST['privacy'];
		$return = '<span class="privacy_update">'.$privacy.'</span>';
		
		// Update profit_daily_info table with new privacy status
		$obj->$use_array['method2'](array('privacy'=>$_POST['privacy']));
		
		// Update privacy modal with refreshed modal
		$modal = new Modal();
		$return .= $modal->getPrivacyModal();
		
		// Build 'Open Worksheet' <option>s to refresh options list
		$return .= 
			'<div class="open_wksht_options">
			  <select class="form-control" id="dlr_select" name="dlr_select">
				<option value="">Select...</option>';
			$worksheets = $obj->$use_array['method1'](); // getExistingWorksheets() method
			foreach ($worksheets as $worksheet) {
				/* Put date in correct format for viewing.  Also pass both date formats so that SESSION 
				 * variables may be set in processExistingProfitWorksheet() method
				**/
				$sql_date = $worksheet['record_date'];
				$display_date = new DateTime($sql_date);
				$display_date = $display_date->format('m-d-Y');
				$return .= 	
				'<option value="'.$worksheet['dealer_record_id'].','.$worksheet['dealer_code'].','.$worksheet['dealer_name'].','.$sql_date.','.$display_date.','.$worksheet['public_access'].','.$worksheet['dealer_team_id'].'">'.$worksheet['dealer_name'].' '.$worksheet['dealer_code'].' &nbsp;('.$display_date.' &nbsp;'.$worksheet['user_uname'].')</option>';
			}
		$return .=
			 '</select>
			</div> <!-- end .open_wksht_options -->'; 
		
		// Return html to init.js instructions for page update
		echo $return;
	}
	
	if($_POST['action'] == 'file_submit') {
		//echo 'entered POST[action] file_submit block';
		// Run the processFileUpload() method. $status will contain uploaded filename if successful. Else false (bool)
		$status = $obj->$use_array['method1']();
		
		// Build feedback msg based on $status value
		if($status) {
			$msg = '<p class="success">'.$status.' has been uploaded successfully!</p>';
		} else {
			$msg = '<p class="error"> There was an error uploading '.$status.'<br>Please try again, and contant the administrator if the problem persists.</p>';
		}
		
		// Send back html results
		echo $obj->$use_array['method2'](array('msg'=>$msg)).
		     $obj->$use_array['method3']($array=null);
	}
	
	// View document table
	if($_POST['action'] == 'view_doc_link') {
		$table = $obj->$use_array['method1']($array = null);
		echo $obj->$use_array['method2'](array('page_title'=>'System Documents - ', 'title_info'=>'View Documents', 'doc_count'=>true, 'export-icon'=>true, 'a_id'=>'add_doc_link', 'link_msg'=>'Add New Document')).
			 $table;
	}
	
	// View actual pdf document
	/* NOT possible with AJAX? File viewing?
	if($_POST['action'] == 'table_doc_select') {
		//echo 'view_doc_id: '.$_POST['view_doc_id'];
		//exit;
		$obj->$use_array['method1'](array('view_doc_id'=>$_POST['view_doc_id']));
	}*/
	
	// Delete document from db if user confirms delete icon
	if($_POST['action'] == 'delete_doc') {
		$result = $obj->$use_array['method1'](array('view_doc_id'=>$_POST['view_doc_id'], 'tmp_name'=>$_POST['tmp_name']));
		// Set sucess msg based on $result.
		$msg = ($result) ? "The document was successfully deleted!" : "Error: The document could not be deleted. Please see the administrator.";
		// Now reload doc table and page heading. Table must be loaded first to acquire doc count SESSION var
		$table = $obj->$use_array['method2'](array('msg'=>$msg));
		echo $table;
	}

	/* Run the getFileUploadForm() method. 
	 * $_POST['edit_doc_id'] is captured inside of method, no need to pass.
	 * This will return the file upload form, with inputs filled in from DB
	**/
	if($_POST['action'] == 'edit_doc_form') {
		echo $obj->$use_array['method1']();
	}


	if($_POST['action'] == 'file_update_submit') {
		// Run the updateDoc() method. Note: $_POST['']
		$file_name = $obj->$use_array['method1']();
		$msg = ($file_name) ? $file_name.' was updated successfully!' : 'Error: The file was not updated.  Please try again or see the administrator.';
		echo $obj->$use_array['method2'](array('msg'=>$msg)).
			 $obj->$use_array['method3']($array = null);
	}

	if($_POST['action'] == 'edit_doc_cancel_link') {
		// Run the getFileUploadForm() method for created of new, fresh add doc form
		echo $obj->$use_array['method1']($array = null);
	}
}

function __autoload($class_name) {
	$filename = "../../sys/class/class." .$class_name. ".inc.php";
	if (file_exists($filename)) {
		include_once $filename;
	}
}
exit;
?>