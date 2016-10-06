<?php
/**
 * File: login.php
 * Purpose: Allow users to login via the login form
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description						by
 *   09/23/2015		Initial design & coding	    	Matt Holland
 */
 
/*
 * Include necessary files
 */
include_once 'sys/core/init.inc.php';

/*
 * Output the header
 */
$page_title = 'Please Log In';
$css_files = array("style.css", "admin.css", "bootstrap.min.css", "login_page.css");
include_once 'assets/common/header.inc.php';

// Load the main navigation menu bar
$nav = new Navigation();
echo $nav->buildNavMenu($login_page = true);

//$_GET['reset_code'] = true;

/* Set test values based on user form actions */
// If user clicks 'Forgot Pass' link, or submitted the email request form with an error, show reset password request form
if((isset($_POST['action']) && $_POST['action'] = 'forgot_pass_link') || isset($_SESSION['email_reset_active'])) {
	// Set the main form heading
	$form_title = 'Reset Password Request';
	$forgot_pass_link = true;
	// Set main submit button text
	$submit_value = 'Send Request';
	// Set hidden input action submit value for process file
	$submit_action = 'forgot_pass_email_submit';
	// Set form POST file reference
	$form_action = 'assets/inc/process.inc.php';
// If user clicked email link for reset password, or a password reset form error occurred, show the 'Reset Password Confirmation' form
} elseif (isset($_GET['reset_code']) || isset($_SESSION['reset_pass_error'])) {
	// Set the main form heading
	$form_title = 'Reset Password Confirmation';
	$reset_get_code = true;
	// Set main submit button text
	$submit_value = 'Confirm';
	// Set hidden input action submit value for process file
	$submit_action = 'reset_pword';
	// Set form POST file reference
	$form_action = 'assets/inc/process.inc.php';
	// Set reset_hash global for executing validatePassReset() method, if $_GET isset
	if(isset($_GET['reset_code'])) {
		$_SESSION['reset_hash'] = $_GET['reset_code'];
		//echo '$_SESSION[reset_hash]: '.$_SESSION['reset_hash'];
	}
// Code will fall into here if nothing else has been set (default form)
} else {
	// Set the main form heading
	$form_title = 'SOS Service Portal Login';
	$main_login_form = true;
	// Set main submit button text
	$submit_value = 'Proceed';
	// Set hidden input action submit value. Note: this action just reloads this page
	$submit_action = 'user_login';
	// Set form POST file reference
	$form_action = 'assets/inc/process.inc.php';
}
?>

<div class="container">
	<div class="row">
		<div class="col-sm-12">
			<p>&nbsp;</p>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<p>&nbsp;</p>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6 center-block panel panel-default">
			<!--<form class="no-mgn-pad" action="assets/inc/process.inc.php" method="post">-->
				<?php
					echo'
					<legend>'.$form_title;
					 if($main_login_form) {
					 	echo'
					 	<form action="index.php" method="post" style="padding: 0; margin: 0">
							<p class="helpful-form-link">
								<button class="forgot-pass-button blue" type="submit" name="forget_pass_btn">Forget Pass?</button>
							</p>
							
							<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
							<input type="hidden" name="action" value="'.$submit_action.'" />
						</form>';
					 } elseif ($forgot_pass_link) {
					 	echo'
					 	<p class="helpful-form-link">
					 		<a href="index.php" class="blue cancel-btn">Cancel</a>
					 	</p>';
					 } elseif ($reset_get_code) {
					 	echo'
					 	<p class="helpful-form-link">
					 		<a href="index.php" class="blue cancel-btn cancel-small">Main Login Form</a>
					 	</p>';
					 }
					 
					echo'
					</legend>
					<form class="no-mgn-pad" action="'.$form_action.'" method="post">';
					
				if($main_login_form) {
				 	echo'
					<div class="row">
						<div class="col-md-12">
							<label for="uname">Username</label>
							<input type="text" class="form-control" name="login_uname" id="login_uname" value="'.$_SESSION['login_uname'].'" />
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-12">
							<label for="pword">Password</label>
							<input type="password" class="form-control" name="login_pword" id="login_pword" value="'.$_SESSION['login_pword'].'" />
						</div>
					</div>
					
					<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
					<input type="hidden" name="action" value="'.$submit_action.'" />';
				}
				// If user has clicked the 'Forgot Pass' link, or has submitted the Reset Password form unsuccessfully
				if($forgot_pass_link) {
					/* This will be true if user has submitted the send email reset form.  
					 * If process failed, or there was an error, show main form with inputs and error msg.
					 * If process succeeded, show success message along with 'Return to login form' link (& remove <input>s)
					**/
					/*
					if($email_reset_active) {
						
						// If user email pass reset submit was successful, remove <input> content and show success message
						if(!isset($_SESSION['email_reset_active_fail'])) {
							echo'
							<div class="row">
								<div class="col-md-12">
									<p>Success! You will receive an email confirmation shortly with your reset code.</p>
									<br/>
									<p><a href="login.php" class="blue"> Return</a> to login form...</p>  
								</div>
							</div>';
						// Make sure the $_SESSION['error'][] message shows up in this case
						} else {
							echo'
							<div class="row">
								<div class="col-md-12">
									<label for="uname">Please Enter Your Email Address</label>
									<input type="text" class="form-control reset_pass_email" name="forgot_pass_email" id="forgot_pass_email" value="'.$_SESSION['forgot_link_email'].'" />
								</div>
							</div>
							
							<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
							<input type="hidden" name="action" value="'.$submit_action.'" />';
						}
					// Proceed to here if user simply clicked the 'Forgot Pass' link	
					} else {*/
						echo'
						<div class="row">
							<div class="col-md-12">
								<label for="uname">Please Enter Your Email Address</label>
								<input type="text" class="form-control reset_pass_email" name="forgot_pass_email" id="forgot_pass_email" value="'.$_SESSION['forgot_pass_email'].'" />
							</div>
						</div>
						
						<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
						<input type="hidden" name="action" value="'.$submit_action.'" />';
				}
				
				if($reset_get_code || isset($_SESSION['reset_pass_error'])) {
					echo'
					<div class="row">
						<div class="col-md-12">
							<label for="uname">Enter Your Email Address</label>
							<input type="text" class="form-control" name="reset_email" id="reset_email" value="'.$_SESSION['reset_email'].'" />
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-12">
							<label for="pword">New Password</label>
							<input type="password" class="form-control new_password" name="reset_pword1" id="reset_pword1" value="'.$_SESSION['reset_pword1'].'" />
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-12">
							<label for="pword">Confirm Password</label>
							<input type="password" class="form-control" name="reset_pword2" id="reset_pword2" value="'.$_SESSION['reset_pword2'].'" />
						</div>
					</div>
					
					<div class="row">
						<div class="col-sm-12">
							<small style="color: blue; font-size: 10px;">*Passwords must be at least 8 characters, contain 1 upper and lower-case letter, 1 number and 1 special character.</small>
						</div>
					</div>
					
					<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
					<input type="hidden" name="action" value="'.$submit_action.'" />';
				}	 
					// Show any login errors
					displayErrors(); 
					// Show any success messages
					displaySuccessMsg();
					
					// Note: main submit action isset above by <input type="hidden".../>.  Set button text with $submit_value.
					echo'     
					<input type="submit" class="btn btn-primary" value="'.$submit_value.'" />';
					
					/* Unset all possible sticky form globals upon page reload. 
					 * Don't unset $_SESSION['reset_hash'], as it may be needed again after reset form error
					**/
					unset ($_SESSION['login_uname'], $_SESSION['login_pword'], 
						   $_SESSION['forgot_pass_email'], 
					       $_SESSION['reset_email'], $_SESSION['reset_pword1'], $_SESSION['reset_pword2'],
					       $_SESSION['email_reset_active'], $_SESSION['reset_pass_error']);
				?>
			</form>
		</div>
	</div>	
</div><!--end div container-->
<!--
<footer class="footer">
	<div class="container-fluid">
		<p class="text-muted">&copy; <?php echo date('Y');?> Service Operations Specialists, Inc. <!--<span class="glyphicon glyphicon-info-sign"></span>--><!--</p>
	</div>
</footer>-->

<?php
include_once 'assets/common/footer.inc.php';
?>