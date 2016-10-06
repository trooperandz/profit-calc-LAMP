<?php
/**
 * File: docs.php
 * Purpose: This file calls the getFileUploadForm() method from class.Admin.inc.php, and loads other AJAX items
 * for adding and editing documents
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description						by
 *   7/26/2016		Initial design & coding	    	Matt Holland
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
$page_title = "SOS Portal - Manage Documents";
$css_files = array("style.css", "admin.css", "bootstrap.min.css", "fileinput.min.css", "docs.css"/*, "datatables.min.css", "dataTables.bootstrap.min.css"*/);
$js_files  = array("fileinput.min.js");
include_once 'assets/common/header.inc.php';

/*
 * Load the classes
 */
$nav = new Navigation();
$doc_obj = new Documents($dbo);

// Show the main navigation menu
echo $nav->buildNavMenu($login_page = false).'

</div>	
<div class="container">
	<div class="row">
		<div class="col-md-12">
			<div class="loader_div"><!--this is the spinning loader for AJAX calls-->
				<!--<div class="loader">Loading...</div>-->
			</div>	
			<h2 style="margin-top: 4px;">My Documents <!--<span style="font-size: 15px; color: rgb(9, 114, 165);"> (See user list below) </span>--></h2>
			<br/>
		</div>';
		// Show any errors or success messages here
		displayErrors(); 
		displaySuccessMsg();
	echo'
	</div>'
		.$doc_obj->getFileUploadForm($array=null).'
    <hr/>'
		.$doc_obj->getDocTable($array=null).'
</div> <!-- .container -->
<br>';

// Unset sticky form elements upon page reload
//$admin->unsetUserFormGlobals();

// Output the footer
include_once 'assets/common/footer.inc.php';

?>