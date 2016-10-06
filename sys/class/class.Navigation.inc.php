<?php
/**
 * File: class.Navigation.inc.php
 * Purpose:  provide navigation items for website
 * PHP version 5.5.29
 * @author Matthew Holland
 *
 *History:
 *   Date			Description						by
 *   10/08/2015		Initial design & coding	    	Matt Holland
 */
 
class Navigation {
	/**
	 * Returns html markup to display main navigation menu
	 * Note: $login_page used to be $_SESSION['user'], but $_SESSION['user'] was conflicting with menu display requirements.
	 * Decided to pass $login_page directly, to determine navigation markup more securely
	 * @ return string the top navigation HMTL markup
	 */
	public function buildNavMenu($login_page) {
		$html = 
		"<nav class='navbar navbar-inverse navbar-fixed-top'>
			<div class='container-fluid'>
				<div class='navbar-header'>
					<button type='button' class='navbar-toggle collapsed' data-toggle='collapse' data-target='#navbar' aria-expanded='false' aria-controls='navbar'>
						<span class='sr-only'>Toggle navigation</span>
						<span class='icon-bar'></span>
						<span class='icon-bar'></span>
						<span class='icon-bar'></span>
					</button>
					<a class='navbar-brand'>SOS Service Portal</a>
				</div>
				<div id='navbar' class='navbar-collapse collapse'>
					<ul class='nav navbar-nav navbar-right'>";
					// If the user has logged in, show all menu options.  Otherwise show 'Welcome' message.
					if (!$login_page) {
						$html .="
						<!--
						<li><a href='index.php'>Home</a></li>
						<li><a href='myevents.php'>My Events</a></li>
						<li><a href='#'>Help</a></li>-->
						<li>
							<div class='dropdown'>
								<button class='modules_button dropdown-toggle' type='button' id='dropdownMenu1' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true'>
									Modules
									<span class='caret'></span>
								</button>
								<ul class='dropdown-menu' aria-labelledby='dropdownMenu1'>
									<li><a href='profit_calc.php'>Profit Calculator</a></li>
								</ul>
							</div>
						</li>";

						if (!$login_page && $_SESSION['user']['admin'] == 1) {
							$html .= 
							"<li><a href=\"users.php\">Admin</a></li>";
						}

						// All users my use the My Docs section
						$html .=
						"<li><a href=\"docs.php\">My Docs</a></li>";
						
						$html .=
						"<form class='navbar-form navbar-right' action='assets/inc/process.inc.php' method='post'>
							<li>
								<div class='dropdown'>
									<button class='btn logout_button dropdown-toggle' type='button' id='dropdownMenu1' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true'>
										<a><span class='glyphicon glyphicon-user' aria-hidden='true'></span></a>
										".$_SESSION['user']['fname']."
										<span class='caret'></span>
									</button>
									<ul class='dropdown-menu' aria-labelledby='dropdownMenu1'>
										<!--
										<li><a href='#' class='logout_link'>My Documents</a></li>
										<li><a href='#' class='logout_link'>My Account</a></li>
										-->
										<form action='assets/inc/process.inc.php' method='POST'>
											<li><a><input type='submit' value='Logout'/></a></li>
											<input type='hidden' name='token' value='$_SESSION[token]' />
											<input type='hidden' name='action' value='user_logout' />
										</form>
									</ul>
								</div>
							</li>
						</form>";
					} else {
						$html .="
						<li><a>Welcome</a></li>";
					}
					$html .="
					</ul>
				</div>
			</div>
		</nav>";
		
		return $html;
	}
}
?>