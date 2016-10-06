<?php
/**
 * File: adduser.php
 * Purpose: This file calls the displayUserForm() method from class.Admin.inc.php
 * for adding and editing users
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description						by
 *   10/27/2015		Initial design & coding	    	Matt Holland
 */
 
/*
 * Include the necessary files
 */
include_once 'sys/core/init.inc.php';

/*
 * If the user is not logged in, sent them to the main file
 */
if (!isset($_SESSION['user'])) {
	header("Location: ./");
	exit;
}

/*
 * Output the header
 */
$page_title = "SOS Portal - Manage Users";
$css_files = array("style.css", "bootstrap.min.css", "admin.css"/*, "datatables.min.css", "dataTables.bootstrap.min.css"*/);
include_once 'assets/common/header.inc.php';

/*
 * Load the classes
 */
$nav = new Navigation();
$admin = new Admin($dbo);

// Run processUserForm() if $_POST['action'] occurred.  This will only occurr if user form is submitted (not if table 'Select' is submitted).
if(isset($_POST['action'])) {
	$admin->processUserform();
}

echo $nav->buildNavMenu($login_page = false);
	
?>
<div class="container">
<!--<div class="container" style="margin-top: 40px;">-->
	<div class="row">
		<div class="col-md-12">
			<h2 style="margin-top: 4px;">Manage Users <!--<span style="font-size: 15px; color: rgb(9, 114, 165);"> (See user list below) </span>--></h2>
		</div>
		<?php 
			displayErrors(); 
			displaySuccessMsg();
		?>
	</div>
	<div class="row">
		<!--<div class="col-md-6 center-block panel panel-default"> PUT BACK IN IF REVAMP DOESN'T WORK -->
		<!--<div class="col-sm-12 panel panel-default">-->
		<?php //displayErrors(); ?>
			<?php
			   echo $admin->displayUserForm(); 
			?>
		<!--</div>-->
	</div>
	<hr>
</div>

<?php
	// Display user table
	echo $admin->displayUsers();
?>
<br>
<?php
	// Unset sticky form elements upon page reload
	$admin->unsetUserFormGlobals();

	// Output the footer
	include_once 'assets/common/footer.inc.php';
?>