<?php
/**
 * File: wollard.php
 * Purpose: Profitability calculator
 * PHP version 5.1.2
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description						by
 *   11/19/2015		Initial design & coding	    	Matt Holland
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

// Load the page classes
$nav = new Navigation();
$profit_analysis = new ProfitAnalysisInfo();

// Set up the page title and CSS files
$page_title = 'SOS Portal - Profit Calculator';
// Note: took out admin.css array element below
$css_files = array("style.css", "ajax.css", "bootstrap.min.css", "profit_calc.css", "jquery-ui.min.css", "jquery-ui.theme.min.css", "print_profit_calc.css");
$print_css_files = array("print_profit_calc.css");

// Include the header
include_once 'assets/common/header.inc.php';

// Include necessary modals
$modal = new Modal();
echo $modal->getPrivacyModal();

// Display the page main menu
echo $nav->buildNavMenu($login_page = false);
?>

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-3 col-md-2 sidebar">
		  <ul class="nav nav-sidebar" style="margin-right: -12px; margin-left: -10px;">
            <li class="active sidebar_main_heading" style="background-color: #428bca;"><a style="color: #fff; padding: 10px 15px 10px 10px;"><span class="glyphicon glyphicon-menu-hamburger" aria-hidden="true"></span> Menu <span class="sr-only">(current)</span></a></li>
          </ul>
          <ul class="nav nav-sidebar" style="margin-bottom: 0px;">
            <li><a class="sidebar_heading"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> New Worksheet:</a></li>
			<form id="new_profit_form" action="assets/inc/process.inc.php" method="post">
				<li><label for="dlr_select">Select Dealer:</label>
					<select class="form-control" id="dlr_select" name="dlr_select">
					<?php
						// If user type == dealer (3), then do not show 'Select...' dropdown option. Show their dealer instead.
						if($_SESSION['user']['type_id'] != 3) {
							echo'<option value="">Select...</option>';
						}
						$dealers = new DealerInfo();
						$dealers = $dealers->getDealerData();
						foreach ($dealers as $dealer) {
							echo'
							<option value="'.$dealer['dealer_record_id'].','.$dealer['dealer_code'].','.$dealer['dealer_name'].','.$dealer['dealer_team_id'].'">'.$dealer['dealer_name'].' '.$dealer['dealer_code'].'</option>';
						}	
					?>	
					</select>
				</li>
				<li><label for="date_select">Select Date:</label>
					<input type="text" class="form-control" style="text-align: left; height: 34px;" id="date_select" name="date_select" placeholder="Select Date" />
				</li>
				<?php
				  // If user is SOS type, add 'Privacy' input field. 1 == public, 0 == private
				  if($_SESSION['user']['type_id'] == 1) {
				  	echo'
				  	<li><label for="privacy_select"> Privacy: </label>
				  		<select class="form-control" name="profit_privacy" id="profit_privacy">
				  			<option value=""> Select... </option>
				  			<option value="1"> Public </option>
				  			<option value="0"> Private </option>
				  		</select>
				  	</li>';
				  }
				?>
				<li>
					<input type="submit" name="new_profit_worksheet" class="btn btn-success form-submit btn-xs sidebar_button" value="Submit" />
					<input type="hidden" name="action" value="new_profit_worksheet" />
					<input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>" />
				</li>
			</form>
            <li><hr class="sidebar_hr"></li>
          </ul>
          <ul class="nav nav-sidebar">
            <li><a class="sidebar_heading"><span class="glyphicon glyphicon-folder-open" aria-hidden="true"></span> Open Worksheet:</a></li>
			<form id="existing_profit_form" action="assets/inc/process.inc.php" method="post">
				<li>
					<?php
					/* Get list of existing profit worksheets, or display 'No Worksheets Available' message.
					 * Note: .open_wksht_options div is for update of <select> options after user enters new wksht via AJAX call
					 * Note: .export_wksht_options div is for update of <select> options after user deletes existing wksht via AJAX call
					**/
					$worksheets = $profit_analysis->getExistingWorksheets();
					if(count($worksheets) > 0) {
						echo'
						<div class="open_wksht_options">
							<select class="form-control" id="dlr_select" name="dlr_select">
								<option value="">Select...</option>';
							foreach ($worksheets as $worksheet) {
								/* Put date in correct format for viewing.  Also pass both date formats so that SESSION 
								 * variables may be set in processExistingProfitWorksheet() method
								**/
								$sql_date = $worksheet['record_date'];
								$display_date = new DateTime($sql_date);
								$display_date = $display_date->format('m-d-Y');
								echo'<option value="'.$worksheet['dealer_record_id'].','.$worksheet['dealer_code'].','.$worksheet['dealer_name'].','.$sql_date.','.$display_date.','.$worksheet['public_access'].','.$worksheet['dealer_team_id'].'">'.$worksheet['dealer_name'].' '.$worksheet['dealer_code'].' &nbsp;('.$display_date.' &nbsp;'.$worksheet['user_uname'].')</option>';
							}
						echo'
							</select>
						</div> <!-- end .open_wksht_options -->';
					} else {
						echo'
						<p>No Worksheets Available</p>';
					}
					?>
				</li>
				<li>
				<?php
					if(count($worksheets) > 0) {
						echo'
						<input type="submit" name="existing_profit_worksheet" class="btn btn-success form-submit btn-xs sidebar_button" value="Submit" />';
					}
				?>	
					<input type="hidden" name="action" value="existing_profit_worksheet" />
					<input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>" />
				</li>
			</form>
            <li><hr class="sidebar_hr"></li>
          </ul>
          <ul class="nav nav-sidebar">
            <li><a class="sidebar_heading"><span class="glyphicon glyphicon-circle-arrow-down" aria-hidden="true"></span> Export Worksheet:</a></li>
            
				<?php
				// Get list of existing profit worksheets, or display 'No Worksheets Available' message
				if(count($worksheets) > 0) {
					echo'
					<form id="export_profit_form" method="POST" action="assets/inc/process.inc.php">
						<li>
							<div class="export_wksht_options">
								<select class="form-control" id="dlr_export_select" name="dlr_export_select">
									<option value="">Select...</option>';
									foreach ($worksheets as $worksheet) {
										// Format sql date so that it is in the format mm-dd-yyyy
										$export_date = new DateTime($worksheet['record_date']);
										$export_date = $export_date->format('m-d-Y');
										echo'<option value="'.$worksheet['dealer_record_id'].','.$worksheet['record_date'].','.$worksheet['dealer_name'].','.$export_date.'">'.$worksheet['dealer_name'].' '.$worksheet['dealer_code'].' &nbsp;('.$export_date.' &nbsp;'.$worksheet['user_uname'].')</option>';
									}
								echo'
								</select>
							</div> <!-- end .export_wksht_options -->
						</li>
						<li><input type="submit" class="btn btn-success form-submit btn-xs sidebar_button" value="Submit" /></li>
						<input type="hidden" name="action" id="action" value="export_profit_data" />
						<input type="hidden" name="token" id="token" value="'.$_SESSION['token'].'" />
					</form>';
				} else {
					echo'
					<li>
						<p>No Worksheets Available</p>
					</li>';
				}
				?>
            <li><hr class="sidebar_hr"></li>
          </ul>
		  <!--
		  <ul class="nav nav-sidebar">
            <li><a href="">Compare Worksheets:</a></li>
            <li>
				<select class="form-control" id="dlr_select" name="dlr_select">
					<option>Select...</option>
					<option>Nissan Valley</option>
					<option>Nissan Pines</option>
					<option>Nissan Yorktown</option>
				</select>
			</li>
			<li><input type="submit" class="btn btn-success form-submit btn-xs sidebar_button" value="Submit" /></li>
            <li><hr class="sidebar_hr"></li>
          </ul>
		  -->
        </div>
		<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
			<?php
			// Display page errors
			displayErrors();
			?>
			<div class="row">
				<div class="col-sm-12">
					<div id="tabs">
						<div class="row">
							<div class="col-sm-12">
								<div class="loader_div"><!--this is the spinning loader for AJAX calls-->
									<!--<div class="loader">Loading...</div>-->
								</div>	
								<ul class="ul_tabs">
									<li class="li_tab"><a href="#tabs-1" class="tab_link a_tab">Profit Analysis</a></li>
									<li class="li_tab"><a href="#tabs-2" class="tab_link a_tab">Cost Average Calculator</a></li>
								</ul>
							</div>
						</div>
						<div id="tabs-1">
							<?php
								echo $profit_analysis->buildProfitTables();
							?>
						</div>	
						<div id="tabs-2">
							<div class="row">
								<div class="cost_tab_heading">
									<div class="col-sm-12">
										<?php
										 echo $profit_analysis->getPageHeading();
										?>
									</div>	
								</div>
								<div class="col-sm-12 col-md-10 col-lg-10">
									<p class="section_title">Service Cost Data:</p>
								</div>
								<div class="col-sm-12 col-md-2 col-lg-2">
									<p class="printer_p">
									  <?php 
									  	if(isset($_SESSION['profit_dlr_id'])) {
									  	  echo'
											<a href="export_profit_calc.php">
												<span class="glyphicon glyphicon-download" style="color: green;" aria-hidden="true"></span>
											</a>
											&nbsp;';
										}
									  ?>
										<a href="" onclick="window.print();">
											<span class="glyphicon glyphicon-print" aria-hidden="true"></span>
										</a>
									</p>
								</div>
								<hr>
							</div> <!-- end .row -->
							<div id="ajax_cost_tables">
							<?php
								$tables = new CostAvgCalcInfo();  	 		// Create new Cost Average Table object
								$table_array = $tables->getTables(); 		// Return array()'s of individual Cost Average table objects
								foreach ($table_array as $cost_avg_table) { // Loop through each table object, creating each Cost Average Table
									echo $cost_avg_table->buildCostAvgTable();
								}
							?>
							</div>
						</div><!-- end div tabs-2 -->
					</div><!-- end div tabs -->
				</div><!-- end div col-md-12 -->
			</div><!-- end div row -->
			<div class="row">
				<div class="col-sm-12">
					<p> </p>
				</div>
			</div>
		</div><!-- end div col-sm-9 -->
		<!--
		<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
			<footer class="footer">
				<p class="text-muted">&copy; <?php //echo date('Y');?> Service Operations Specialists, Inc. <!--<span class="glyphicon glyphicon-info-sign"></span>--><!--</p>-->
			<!--</footer>
		</div>-->
	</div><!-- end div row -->
</div><!-- end div container-fluid -->

<footer class="footer">
	<div class="container-fluid">
		<p class="text-muted">&copy; <?php echo date('Y');?> Service Operations Specialists, Inc. <!--<span class="glyphicon glyphicon-info-sign"></span>--></p>
	</div>
</footer>

<?php
/*
 * Include the footer
 */
include_once 'assets/common/footer.inc.php';
?>