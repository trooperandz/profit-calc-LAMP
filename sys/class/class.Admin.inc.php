<?php
/**
 * File: class.Admin.inc.php
 * Purpose: Contains methods to allow users to log in and out
 * and manages administrative actions in general
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description						by
 *   09/23/2015		Initial design & coding	    	Matt Holland
 */
 
class Admin extends DB_Connect {
	/**
	 * Determine the length of the salt to use in hashed passwords
	 *
	 * @var int the length of the password salt to use
	 */
	private $_saltLength = 7;
	
	/**
	 * Stores or creates a DB object and sets the salt length
	 *
	 * @param object $db a database object
	 * @param int $saltLength length for the password hash
	 */
	public function __construct($db=NULL, $saltLength=NULL) {
		parent::__construct($db);
		
		/*
		 * If an int was passed, set the length of the salt
		 */
		if (is_int($saltLength)) {
			$this->_saltLength = $saltLength;
		}
	}
	
	/**
	 * Checks login credentials for a valid user
	 *
	 * @return mixed TRUE on success, message on error
	 */
	public function processLoginForm() {
		/*
		 * Fails if the proper action was not submitted
		 */
		if ($_POST['action'] !='user_login') {
			//exit('post action incorrect');
			return "Invalid action supplied for processLoginForm.";
		}
		
		/*
		 * Escapes the user input for security
		 */
		$uname = htmlentities($_POST['login_uname'], ENT_QUOTES);
		$pword = htmlentities($_POST['login_pword'], ENT_QUOTES);
		
		// Save login inputs as SESSION vars for sticky form input functionality
		$_SESSION['login_uname'] = $uname;
		$_SESSION['login_pword'] = $pword;
		
		/*
		 * Retrieve the matching info from the DB if it exists
		 */
		$sql = "SELECT a.user_id, a.user_fname, a.user_lname, a.user_uname, a.user_pass, a.user_email, a.user_team_id,
					   b.team_name, a.user_type_id, c.user_type_name, a.dealer_record_id, a.user_admin, a.user_active
				FROM users a
				LEFT JOIN team b ON (a.user_team_id = b.team_id)
				LEFT JOIN user_type c ON (a.user_type_id = c.user_type_id)
				WHERE a.user_uname = :uname
				LIMIT 1";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(':uname', $uname, PDO::PARAM_STR);
			$stmt->execute();
			$user = $stmt->fetch();
			/** Note:  This was the original line from the book:  '$user = array_shift($stmt->fetchAll());'
			 ** Had to edit this due to a 'Strict Standard' PHP error to the above line (fetch). The original
			 ** instruction removed the outside array and just returned the inside array containing the four
			 ** details of the user.  The new fetch() instruction does the same thing, without using array_shift.
			 ** This is acceptable, as the above query will ALWAYS only return one user row.
			 **/
			$stmt->closeCursor();
		}
		catch (Exception $e) {
			die($e->getMessage());
		}
		
		/*
		 * Fails if username doesn't match a DB entry
		 */
		if (!is_array($user)) {
			$_SESSION['error'][] = '*Your username or password is incorrect!';
			return false;
		}
		
		// If user is inactive, deny access
		if($user['user_active'] == 0) {
			$_SESSION['error'][] = '*Access denied. Your account is no longer active!';
			return false;
		}
		
		/*
		 * Get the hash of the user-supplied password
		 */
		//$hash = $this->_getSaltedHash($pword, $user['user_pass']);
		//password_verify($login_password, $user_pass
		
		
		/*
		 * Checks if the hashed password matches the stored hash
		 * Stores user info in the session as an array
		 */
		//if ($user['user_pass'] == $hash) {
		if (password_verify($pword, $user['user_pass'])) {
			 $_SESSION['user'] = array(
				  'id' 		         => $user['user_id']		 ,
				  'fname'	         => $user['user_fname']	  	 ,
				  'lname'            => $user['user_lname']	  	 ,
				  'uname' 	         => $user['user_uname']	  	 ,
				  'email' 	         => $user['user_email']	  	 ,
				  'team_id'	         => $user['user_team_id']	 ,
				  'team_name'        => $user['team_name']		 ,
				  'type_id'          => $user['user_type_id']	 ,
				  'type_name'        => $user['user_type_name']	 ,
				  'dealer_record_id' => $user['dealer_record_id'],
				  'admin' 	  		 => $user['user_admin']	  	 ,
				  'active'    		 => $user['active']
			 );
			return true;
		// Fails if the passwords don't match
		} else {
			$_SESSION['error'][] = '*Your username or password is incorrect!';
			return false;
		}
	}
	
	/**
	 * Returns a single user object
	 *
	 * @param int $user_id a user ID
	 * @return object the user object
	 */
	private function _loadUserById($user_id) {
		/*
		 * If no ID is passed, return NULL
		 */
		if (empty($user_id)) {
			return NULL;
		}
		//echo '$user_id was not empty.<br>';
		
		/*
		 * Load the user's info array
		 */
		$user = $this->_loadUserData(array('user_id'=>$user_id));
		//echo '$user contents: ',var_dump($user),'<br>';
		
		/*
		 * Return a user object
		 */
		if (isset($user[0])) {
			//echo '$user[0] isset.<br>';
			//die();
			return new User($user[0]);
		} else {
			//echo '$user[0] was not set and returned null.<br>';
			//die();
			return NULL;
		}
	}
	
	/**
	 * Loads user(s') info into an array
	 * Note: this method is also used for reset pass functionality
	 * @param int $user_id an optional user ID to filter results
	 * @return array an array of user(s) from the database
	 */
	private function _loadUserData($array) {
		//echo 'entered _loadUserData()<br>';
		$sql = "SELECT a.user_id, a.user_fname, a.user_lname, a.user_uname, a.user_email, a.user_team_id, b.team_name, 
					   a.dealer_record_id, c.dealer_code, c.dealer_name, c.dealer_team_id, a.user_admin, a.user_active, a.user_type_id, 
					   d.user_type_name
			    FROM users a
				LEFT JOIN team b ON(a.user_team_id = b.team_id)
				LEFT JOIN dealers c ON(a.dealer_record_id = c.dealer_record_id)
				LEFT JOIN user_type d ON(a.user_type_id = d.user_type_id)";
		
		// Initialize $params[] array
		$params = array();
				
		/*
		 * If a user_id is supplied, add a WHERE clause 
		 * so only that user is returned
		 */
		if (isset($array['user_id']) && $array['user_id']) {
			//echo '$user_id was not empty inside of _loadUserData()<br>';
			$sql .= " WHERE a.user_id = ? ";
			$params[] = $array['user_id'];
		}
		// If a user has requested a password reset, verify email address
		if(isset($array['user_email']) && $array['user_email']) {
			$sql .= " WHERE a.user_email = ? ";
			$params[] = $array['user_email'];
		}
		$sql .= " ORDER BY a.user_lname";
		//echo '$sql statement: ',$sql,'<br>';
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			/*
			 * Debug code.  Remove when finished debugging
			 *
			echo 'var dump of $results:<br>';
			var_dump($results);
			echo'<br>';
			exit;*/
			
			$stmt->closeCursor();
			//echo 'about to return $results<br>';
			//echo '$results: ',var_dump($results),'<br>';
			return $results;
		}
		catch (Exception $e) {
			die($e->getMessage());
		}
	}
	
	public function displayUsers() {
		/*
		 * Load the user data array from the DB
		 */
		$users = $this->_loadUserData($array = null);
		
		$html=
		'<div class="container">
			<div class="row">
				<div class="col-md-12">
					<h2>System Users <span style="font-size: 15px; color: rgb(9, 114, 165);">&nbsp; &nbsp;Total Users: '.sizeof($users).'</span></h2>
				</div>
			</div><!--end div row-->
			<div class="row">
				<div class="col-sm-12">
					<div class="table-responsive">
						<table id="user_table" class="table user_table table-hover">
							<thead>
								<tr>
									<th>Action</th>
									<th>Name</th>
									<th>Username</th>
									<th>Email Address</th>
									<th>Team</th>
									<th>Dealer</th>
									<th>User Type</th>
									<th>Admin?</th>
									<th>Active?</th>
								</tr>
							</thead>
							<tbody>';
							for ($i=0; $i<sizeof($users); $i++) {
								if ($users[$i]['user_admin'] == 1) {
									$users[$i]['user_admin'] = 'Yes';
								} else {
									$users[$i]['user_admin'] = 'No';
								}
								if ($users[$i]['user_active'] == 1) {
									$users[$i]['user_active'] = 'Yes';
								} else {
									$users[$i]['user_active'] = 'No';
								}
								// Set dealer display based on if user is associated with dealer or not
								$dealer_code = ($users[$i]['dealer_code'] == 0) ? '<span style="color: #ccc;">----</span>' : $users[$i]['dealer_code'];
								$html .=
								'<tr>
									<td>
										<form action="users.php" method="post">
											<input type="submit" class="btn btn-primary btn-xs" value="Select" />
											<input type="hidden" id="edit_user_id" name="edit_user_id" value="'.$users[$i]['user_id'].'" />
										</form>
									</td>
									<td>'.$users[$i]['user_fname'].' '.$users[$i]['user_lname'].'</td>
									<td>'.$users[$i]['user_uname'].'</td>
									<td>'.$users[$i]['user_email'].'</td>
									<td>'.$users[$i]['team_name'].'</td>
									<td>'.$dealer_code.'</td>
									<td>'.$users[$i]['user_type_name'].'</td>
									<td>'.$users[$i]['user_admin'].'</td>
									<td>'.$users[$i]['user_active'].'</td>
								</tr>';
							}
							$html .=
							'</tbody>
						</table>
					</div>	
				</div>	
			</div>
		</div><!--end div container-->';
		
		/*
		 * Return the markup
		 */
		 return $html;
	}
	
	// Get user team options
	public function getUserTeams() {
		$sql = "SELECT team_id, team_name FROM team";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		} catch (Exception $e) {
			die($e->getMessage());
		}
		return $results;
	}
	
	// Get user type options
	public function getUserTypes() {
		$sql = "SELECT user_type_id, user_type_name FROM user_type";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		} catch (Exception $e) {
			die($e->getMessage());
		}
		return $results;
	}
	
	/**
	 * Displays the Add/Edit User form
	 *
	 * return string $html Add/Edit user form
	 */
	public function displayUserForm() {
		/* $_POST['user_id'] == POST from add user form 'Update User' button in edit mode
		 * $_POST['edit_user_id'] == POST from user table form 'Select' button
		 * $_SESSION['edit_user_id'] isset from process.inc.php by $_POST['user_Id']
		 * Check if an ID was passed
		 */
		if (isset($_POST['user_id']) || isset($_POST['edit_user_id']) || isset($_SESSION['edit_user_id'])) {
			if (isset($_SESSION['edit_user_id'])) {
				$user_id = (int) $_SESSION['edit_user_id'];
			} elseif (isset($_POST['edit_user_id'])) { // should $_SESSION['edit_user_id'] be set here instead of in process.inc.php?
				//echo '$_POST[edit_user_id] elseif entered.<br>$_POST[edit_user_id]: '.$_POST['edit_user_id'].'<br>';
				$user_id = (int) $_POST['edit_user_id']; // Force integer type to sanitize data
				$_SESSION['edit_user_id'] = $user_id;
			} else {
				$user_id = (int) $_POST['user_id']; // Force integer type to sanitize data
			}	
		} else {
			$user_id = NULL;
		}
		
		// Instantiate the headline/submit button text defaults
		$title  = "Create New User";
		$submit = "Create User";
		
		// Initialize variables so PHP does not throw a notice: 'Undefined variable'
		$fname       = NULL;
		$lname       = NULL;
		$uname       = NULL;
		$email       = NULL;
		$dlr_name    = NULL;
		$dlr_code    = NULL;
		$dlr_id      = NULL;
		$dlr_team_id = NULL;
		$type_id     = NULL;
		$type_name   = NULL;
		$team_id     = NULL;
		$team_name   = NULL;
		$pword1      = NULL;
		$pword2      = NULL;
		
		/* If user table 'Select' was submitted, then load user data to fill in form
		 * $_POST['edit_user_id'] comes from hidden form input from table 'Select' button form
		 */
		if (isset($_POST['edit_user_id'])) {
			$user = $this->_loadUserById($user_id);
			
			// Set variables so that you may use globals OR the ones below with the same variable name below in the $html form
			$fname		 = $user->fname;
			$lname 	 	 = $user->lname;
			$uname		 = $user->uname;
			$email		 = $user->email;
			$dlr_name    = $user->dlr_name;
			$dlr_code    = $user->dlr_code;
			$dlr_id      = $user->dlr_id;
			$dlr_team_id = $user->dlr_team_id;
			$type_id     = $user->type_id;
			$type_name   = $user->type_name;
			$team_id     = $user->team_id;
			$team_name   = $user->team_name;
			// Set <option> titles for $admin
			$admin 	= $user->admin;
			if ($admin == 1) {
				$admin_name = 'Yes';
			} else {
				$admin_name = 'No';
			}
			// Set <option> titles for $active
			$active	= $user->active;
			if ($active == 1) {
				$active_name = 'Yes';
			} else {
				$active_name = 'No';
			}
			
			/*
			 * If no object is returned, return NULL
			 */
			if (!is_object($user)) {
				return NULL;
			}
			// Save username as SESSION var so that can perform username dupe check if the user changes the username on the edit form
			$_SESSION['edit_username'] = $uname;
			
			// Set title and submit button headings
			$title  = "Update User";
			$submit = "Update User";
		}
		
		/* If any $_SESSION vars are set when code enters this function, a form error occurred.
		 * This is because a successful add new user or update existing user execution will unset sticky form elements.
		 * If action is to add new user, $user_id will == null.
		 * If action is to edit user, $user_id needs to be set == $_SESSION['edit_user_id'], which isset above if elseif block.
		 * Hidden input name="user_id" will be used at bottom of form & will determine identity of add or edit action:
		 * if $user_id == null, action is add user action, else is edit user action.
		 * If in edit user mode, $_SESSION['edit_user_id'] will always be set, hence the below $user_id = $_SESSION['edit_user_id']
		**/
		if (isset($_SESSION['fname'])) {
			//echo 'entered isset($_SESSION[fname]) block!';
			//echo 'SESSION[edit_user_id]: '.var_dump($_SESSION['edit_user_id']);
			if (isset($_POST['user_id'])) {
				$user_id = $_POST['user_id'];
			}	
			$fname 		 = $_SESSION['fname']	   ;
			$lname 		 = $_SESSION['lname']	   ;
			$uname 		 = $_SESSION['uname']	   ;
			$email 		 = $_SESSION['email']	   ;
			$dlr_name    = $_SESSION['dlr_name']   ;
			$dlr_code 	 = $_SESSION['dlr_code']   ;
			$dlr_id      = $_SESSION['dlr_id']	   ;
			$dlr_team_id = $_SESSION['dlr_team_id'];
			$type_name   = $_SESSION['type_name']  ;
			$type_id     = $_SESSION['type_id']	   ;
			$team_name   = $_SESSION['team_name']  ;
			$team_id     = $_SESSION['team_id']	   ;
			$admin 		 = $_SESSION['admin']	   ;
			$active 	 = $_SESSION['active']	   ;
			// Set <option> titles for $admin
			/*
			if (empty($_SESSION['admin'])) {
				$admin_name = 'Select...';
			} elseif ($admin == 1) {
				$admin_name = 'Yes';
			} else {
				$admin_name = 'No';
			}*/
			if ($_SESSION['admin'] == '') {
				$admin_name = 'Select...';
			} elseif ($admin == 1) {
				$admin_name = 'Yes';
			} else {
				$admin_name = 'No';
			}
			
			// Set <option> titles for $active
			if ($_SESSION['active'] == '') {
				$active_name = 'Select...';
			} elseif ($active == 1) {
				$active_name = 'Yes';
			} elseif ($active == 0) {
				$active_name = 'No';
			}
			
			$pword1 = $_SESSION['pword1'];
			$pword2 = $_SESSION['pword2'];
			
			// Set Title and submit button headings
			$title = $submit = (!empty($_POST['user_id'])) ? "Update User" : "Create New User";
			/*
			$title  = "Update User";
			$submit = "Update User";
			*/
		}
		/* ORIGINAL BEFORE LAYOUT REVAMP **
		$html = 
		'<!-- <form action="assets/inc/process.inc.php" method="post"> -->
		  <form action="users.php" method="post">
			<fieldset>
				<legend>'.$title.'</legend>
				
				<div class="col-md-6">
					<label for="fname">First Name</label>
					<input type="text" class="form-control" name="fname" id="fname" value="'.$fname.'" />
				</div>
				
				<div class="col-md-6">
					<label for="lname">Last Name</label>
					<input type="text" class="form-control" name="lname" id="lname" value="'.$lname.'" />
				</div>
				
				<div class="col-md-6">	
					<label for="uname">User Name</label>
					<input type="text" class="form-control" name="uname" id="uname" value="'.$uname.'" />
				</div>
				
				<div class="col-md-6">	
					<label for="email">Email Address</label>
					<input type="text" class="form-control" name="email" id="email" value="'.$email.'" />
				</div>
				
				<div class="col-md-6">
					<label for="admin">Admin User?</label>
					<select class="form-control" id="admin" name="admin">';
					if (isset($_POST['edit_user_id']) || isset($_SESSION['fname'])) {
					$html .=
					   '<option value="'.$admin.'">'.$admin_name.'</option>
						<option value="1">Yes</option>
						<option value="0">No</option>';
					} else {
					$html .=
					   '<option value="">Select...</option>
						<option value="1">Yes</option>
						<option value="0">No</option>';
					}
					$html .='
			   		</select>
			   	</div>
				
				<div class="col-md-6">
					<label for="active">Active?</label>
					<select class="form-control" id="active" name="active">';
					if (isset($_POST['edit_user_id']) || isset($_SESSION['fname'])) {
					$html .=
					   '<option value="'.$active.'">'.$active_name.'</option>
						<option value="1">Yes</option>
						<option value="0">No</option>';
					} else {
					$html .=
					   '<option value="">Select...</option>
						<option value="1">Yes</option>
						<option value="0">No</option>';
					}
					$html .='
					</select>
				</div>
				
				<div class="col-md-6">
					<label for="team">Team Affiliation</label>
					<select class="form-control" id="user_team" name="user_team">';
					$teams = $this->getUserTeams();
					if (isset($_POST['edit_user_id']) || isset($_SESSION['team_name'])) {
					$html .=
					   '<option value="'.$team_id.','.$team_name.'">'.$team_name.'</option>';
					   foreach ($teams as $team) {
					  		$html .=
					  		'<option value="'.$team['team_id'].','.$team['team_name'].'">'.$team['team_name'].'</option>';
						}
					} else {
					$html .=
					   '<option value="">Select...</option>';
					   foreach ($teams as $team) {
					  		$html .=
					  		'<option value="'.$team['team_id'].','.$team['team_name'].'">'.$team['team_name'].'</option>';
						}
					}
					$html .='
					</select>
				</div>
			   
			   <div class="col-md-6">
			    	<label for="user_type">User Type</label>
			    	<select class="form-control" id="user_type" name="user_type">';
			    	$types = $this->getUserTypes();
			    	if (isset($_POST['edit_user_id']) || isset($_SESSION['type_name'])) {
			    	  $html .='
			    	  	<option value="'.$type_id.','.$type_name.'">'.$type_name.'</option>';
			    	   	foreach ($types as $type) {
			    	  		$html .='
			    	  		<option value="'.$type['user_type_id'].','.$type['user_type_name'].'">'.$type['user_type_name'].'</option>';
			    	  	}
			    	} else {
			    		$html .='
			    		<option value="">Select...</option>';
			    		foreach($types as $type) {
			    			$html .='
			    	  		<option value="'.$type['user_type_id'].','.$type['user_type_name'].'">'.$type['user_type_name'].'</option>';
			    		}
			    	}
			    	$html .='
			    	</select>
			    </div>';
			   
			   $html .='
			   <div class="col-md-6">
			    	<label for="dlr">Dealer</label>
					<select class="form-control" id="user_dlr" name="user_dlr">';
					// Get dealer options for drowpdown
					$dealers = new DealerInfo();
					$dealers = $dealers->getDealerData();
					if (isset($_POST['edit_user_id']) || isset($_SESSION['dlr_code'])) {
						// Set correct 'N/A' display if user does not have a dealer assignment
						$dlr_name_code = ($dlr_name == "") ? 'N/A' : $dlr_name.' '.$dlr_code;
						/*
						if($dlr_id == 0) {
							$dlr_code = $dlr_name = $dlr_team_id = " ";
						}*/	
						/*
					   $html .=
						'<option value="'.$dlr_id.','.$dlr_code.','.$dlr_name.','.$dlr_team_id.'">'.$dlr_name_code.'</option>
						 <option value="0,0,0,0">N/A</option>'; // This is for SOS or Manuf user type
					   foreach ($dealers as $dealer) {
						 $html .='
						 <option value="'.$dealer['dealer_record_id'].','.$dealer['dealer_code'].','.$dealer['dealer_name'].','.$dealer['dealer_team_id'].'">'.$dealer['dealer_name'].' '.$dealer['dealer_code'].'</option>';
					   }
					} else {
						$html .='
						<option value="">Select...</option>
						<option value="0,0,0,0">N/A</option>';
			 		  foreach ($dealers as $dealer) {
						$html .='
						<option value="'.$dealer['dealer_record_id'].','.$dealer['dealer_code'].','.$dealer['dealer_name'].','.$dealer['dealer_team_id'].'">'.$dealer['dealer_name'].' '.$dealer['dealer_code'].'</option>';
					  }
					}
					$html .='
					</select>
				</div>';
				
				$html .='
				<div class="col-md-6">
					<label for="pword1">Password</label>
					<input type="password" class="form-control" name="pword1" id="pword1" value="'.$pword1.'" />
				</div>
				
				<div class="col-md-6">
					<label for="pword2">Verify Password</label>
					<input type="password" class="form-control" name="pword2" id="pword2" value="'.$pword2.'" />
				</div>
				
				<input type="hidden" name="user_id" value="'.$user_id.'" />
				<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
				<input type="hidden" name="action" value="add_user" />
				
				<div class="col-sm-12">
					<small style="color: blue; font-size: 10px;">*Passwords must be at least 8 characters, contain 1 upper and lower-case letter, 1 number and 1 special character.</small>
					<br/>';
				if (!empty($user_id)) {
					$html .='
					<small style="color: blue; font-size: 10px;">*Password fields are only required if you are updating the user\'s password!</small>';
				}
				$html .='
				</div>
				
				<div class="col-sm-12">
					<input type="submit" class="btn btn-primary form-submit" name="adduser_submit" value="'.$submit.'" />';
				if(!empty($user_id)) {
					$html .='
					&nbsp; or <a href="users.php">cancel</a>
				</div>';
				}
			$html .='
			</fieldset>
		</form>';*/

		/**** NEW LAYOUT TESTING ******/
		$html = 
		'<!-- <form action="assets/inc/process.inc.php" method="post"> -->
		  <form action="users.php" method="post">
			<!--<fieldset>-->
				<!--<legend>'.$title.'</legend>-->
				<!-- Start of new content -->
				<div class="col-md-3">
					<h4 style="color: rgb(9, 114, 165);">'.$title.'</h4>
					<br/>
					<ul>
						<li style="color: gray;"> Dealer users must be assigned to a dealer. </li>
						<br/>
						<li style="color: gray;"> Note: Only SOS personnel may be designated as Admin users. </li>
						<br/>
						<li style="color: gray;"> Passwords must be at least 8 characters, contain 1 upper and lower-case letter, 1 number and 1 special character. </li>
					</ul>
				</div>
				<div class="col-md-9" style="border-left: 1px solid #CCCCCC;">
					<div class="col-md-6">
						<label for="fname">First Name</label>
						<input type="text" class="form-control" name="fname" id="fname" value="'.$fname.'" />
					</div>
					
					<div class="col-md-6">
						<label for="lname">Last Name</label>
						<input type="text" class="form-control" name="lname" id="lname" value="'.$lname.'" />
					</div>
					
					<div class="col-md-6">	
						<label for="uname">User Name</label>
						<input type="text" class="form-control" name="uname" id="uname" value="'.$uname.'" />
					</div>
					
					<div class="col-md-6">	
						<label for="email">Email Address</label>
						<input type="text" class="form-control" name="email" id="email" value="'.$email.'" />
					</div>
					
					<div class="col-md-6">
						<label for="admin">Admin User?</label>
						<select class="form-control" id="admin" name="admin">';
						if (isset($_POST['edit_user_id']) || isset($_SESSION['fname'])) {
						$html .=
						   '<option value="'.$admin.'">'.$admin_name.'</option>
							<option value="1">Yes</option>
							<option value="0">No</option>';
						} else {
						$html .=
						   '<option value="">Select...</option>
							<option value="1">Yes</option>
							<option value="0">No</option>';
						}
						$html .='
			   			</select>
			   		</div>
					
					<div class="col-md-6">
						<label for="active">Active?</label>
						<select class="form-control" id="active" name="active">';
						if (isset($_POST['edit_user_id']) || isset($_SESSION['fname'])) {
						$html .=
						   '<option value="'.$active.'">'.$active_name.'</option>
							<option value="1">Yes</option>
							<option value="0">No</option>';
						} else {
						$html .=
						   '<option value="">Select...</option>
							<option value="1">Yes</option>
							<option value="0">No</option>';
						}
						$html .='
						</select>
					</div>
					
					<div class="col-md-6">
						<label for="team">Team Affiliation</label>
						<select class="form-control" id="user_team" name="user_team">';
						$teams = $this->getUserTeams();
						if (isset($_POST['edit_user_id']) || isset($_SESSION['team_name'])) {
						$html .=
						   '<option value="'.$team_id.','.$team_name.'">'.$team_name.'</option>';
						   foreach ($teams as $team) {
						  		$html .=
						  		'<option value="'.$team['team_id'].','.$team['team_name'].'">'.$team['team_name'].'</option>';
							}
						} else {
						$html .=
						   '<option value="">Select...</option>';
						   foreach ($teams as $team) {
						  		$html .=
						  		'<option value="'.$team['team_id'].','.$team['team_name'].'">'.$team['team_name'].'</option>';
							}
						}
						$html .='
						</select>
					</div>
			   	
			   		<div class="col-md-6">
			    		<label for="user_type">User Type</label>
			    		<select class="form-control" id="user_type" name="user_type">';
			    		$types = $this->getUserTypes();
			    		if (isset($_POST['edit_user_id']) || isset($_SESSION['type_name'])) {
			    		  $html .='
			    		  	<option value="'.$type_id.','.$type_name.'">'.$type_name.'</option>';
			    		   	foreach ($types as $type) {
			    		  		$html .='
			    		  		<option value="'.$type['user_type_id'].','.$type['user_type_name'].'">'.$type['user_type_name'].'</option>';
			    		  	}
			    		} else {
			    			$html .='
			    			<option value="">Select...</option>';
			    			foreach($types as $type) {
			    				$html .='
			    		  		<option value="'.$type['user_type_id'].','.$type['user_type_name'].'">'.$type['user_type_name'].'</option>';
			    			}
			    		}
			    		$html .='
			    		</select>
			    	</div>';
			   	
			   		$html .='
			   		<div class="col-md-6">
			    		<label for="dlr">Dealer</label>
						<select class="form-control" id="user_dlr" name="user_dlr">';
						// Get dealer options for drowpdown
						$dealers = new DealerInfo();
						$dealers = $dealers->getDealerData();
						if (isset($_POST['edit_user_id']) || isset($_SESSION['dlr_code'])) {
							// Set correct 'N/A' display if user does not have a dealer assignment
							$dlr_name_code = ($dlr_name == "") ? 'N/A' : $dlr_name.' '.$dlr_code;
							/*
							if($dlr_id == 0) {
								$dlr_code = $dlr_name = $dlr_team_id = " ";
							}*/	
							
						   $html .=
							'<option value="'.$dlr_id.','.$dlr_code.','.$dlr_name.','.$dlr_team_id.'">'.$dlr_name_code.'</option>
							 <option value="0,0,0,0">N/A</option>'; // This is for SOS or Manuf user type
						   foreach ($dealers as $dealer) {
							 $html .='
							 <option value="'.$dealer['dealer_record_id'].','.$dealer['dealer_code'].','.$dealer['dealer_name'].','.$dealer['	dealer_team_id'].'">'.$dealer['dealer_name'].' '.$dealer['dealer_code'].'</option>';
						   }
						} else {
							$html .='
							<option value="">Select...</option>
							<option value="0,0,0,0">N/A</option>';
			 			  foreach ($dealers as $dealer) {
							$html .='
							<option value="'.$dealer['dealer_record_id'].','.$dealer['dealer_code'].','.$dealer['dealer_name'].','.$dealer['	dealer_team_id'].'">'.$dealer['dealer_name'].' '.$dealer['dealer_code'].'</option>';
						  }
						}
						$html .='
						</select>
					</div>';
					
					$html .='
					<div class="col-md-3">
						<label for="pword1">Password</label>
						<input type="password" class="form-control" name="pword1" id="pword1" value="'.$pword1.'" />
					</div>
					
					<div class="col-md-3">
						<label for="pword2">Verify Password</label>
						<input type="password" class="form-control" name="pword2" id="pword2" value="'.$pword2.'" />
					</div>

					<div class="col-md-12">
						<input type="submit" class="btn btn-primary form-submit" name="adduser_submit" value="'.$submit.'" />';
				if(!empty($user_id)) {
					$html .='
						&nbsp; or <a href="users.php">cancel</a>';
				}
					$html .='
					</div>
					
					<input type="hidden" name="user_id" value="'.$user_id.'" />
					<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
					<input type="hidden" name="action" value="add_user" />
				</div> <!-- .col-md-9 -->

				<!--<div class="col-sm-12">
					<small style="color: blue; font-size: 10px;">*Passwords must be at least 8 characters, contain 1 upper and lower-case letter, 1 number and 1 special character.</small>
					<br/>-->';
				if (!empty($user_id)) {
					$html .='
					<!--<small style="color: blue; font-size: 10px;">*Password fields are only required if you are updating the user\'s password!</small>-->';
				}
				$html .='
				<!--</div>-->
				
				<!--<div class="col-sm-12">
					<input type="submit" class="btn btn-primary form-submit" name="adduser_submit" value="'.$submit.'" />-->';
				if(!empty($user_id)) {
					$html .='
					<!--&nbsp; or <a href="users.php">cancel</a>
				</div>-->';
				}
			$html .='
			<!--</fieldset>-->
		</form>';
		return $html;		
	}
	
	/**
	 * Adds a new user to the database
	 */
	public function processUserForm() {
		/*
		 * Exit if the action isn't set properly
		 */
		if ($_POST['action'] !='add_user') {
			$_SESSION['error'] = "The method processUserForm was accessed incorrectly";
		}
		
		// Save form data
		$fname       = htmlentities($_POST['fname'], ENT_QUOTES);
		$lname       = htmlentities($_POST['lname'], ENT_QUOTES);
		$uname       = htmlentities($_POST['uname'], ENT_QUOTES);
		$email       = htmlentities($_POST['email'], ENT_QUOTES);
		$dlr         = explode(',', $_POST['user_dlr']);
		$dlr_id      = $dlr[0];
		$dlr_code    = $dlr[1];
		$dlr_name    = $dlr[2];
		$dlr_team_id = $dlr[3];
		$admin       = $_POST['admin'];
		$active      = $_POST['active'];
		$team        = explode(',', $_POST['user_team']);
		$team_id     = $team[0];
		$team_name   = $team[1];
		$type        = explode(',', $_POST['user_type']);
		$type_id     = $type[0];
		$type_name   = $type[1];
		$pword1      = htmlentities($_POST['pword1'], ENT_QUOTES);
		$pword2      = htmlentities($_POST['pword2'], ENT_QUOTES);
		
		// Set sticky form elements in case of form error
		$_SESSION['fname']		= $fname	  ;
		$_SESSION['lname'] 		= $lname	  ;
		$_SESSION['uname'] 		= $uname	  ;
		$_SESSION['email'] 		= $email	  ;
		$_SESSION['dlr_id']		= $dlr_id	  ;
		$_SESSION['dlr_code'] 	= $dlr_code	  ;
		$_SESSION['dlr_name'] 	= $dlr_name	  ;
		$_SESSION['dlr_team_id']= $dlr_team_id;
		$_SESSION['admin'] 		= $admin	  ;
		$_SESSION['active'] 	= $active	  ;
		$_SESSION['team_id'] 	= $team_id	  ;
		$_SESSION['team_name'] 	= $team_name  ;
		$_SESSION['type_id'] 	= $type_id	  ;
		$_SESSION['type_name'] 	= $type_name  ;
		$_SESSION['pword1'] 	= $pword1	  ;
		$_SESSION['pword2'] 	= $pword2	  ;
		
		// Validate form inputs
		if ($fname == '' || !$this->_validName($fname)) {
			$_SESSION['error'][] = 'Please enter a valid first name!';
		}
		if ($lname == '' || !$this->_validName($lname)) {
			$_SESSION['error'][] = 'Please enter a valid last name!';
		}
		if ($uname == '' || !$this->_validName($uname)) {
			$_SESSION['error'][] = 'Please enter a valid username!';
		}
		if ($email == '' || !$this->_validEmail($email)) {
			$_SESSION['error'][] = 'Please enter a valid email address!';
		}
		if ($dlr[0] == '') {
			$_SESSION['error'][] = 'Please enter a valid dealer!';
		}
		if ($admin == '') {
			$_SESSION['error'][] = 'Please enter an Admin selection!';
		}
		if ($active == '') {
			$_SESSION['error'][] = 'Please enter an Active selection!';
		}
		if ($team[0] == '') {
			$_SESSION['error'][] = 'Please enter a team affiliation!';
		}
		if ($type[0] == '') {
			$_SESSION['error'][] = 'Please enter a user type!';
		}
		
		// If user is designated as admin, MUST be SOS type (val == 1)
		if($admin == 1 && $type[0] != '' && $type[0] != 1) {
			$_SESSION['error'][] = 'Error! Only SOS users may be designated as Admin!';
		}
		
		// If user is not a dealer user (type == 3), then make sure that dealer selection == 'N/A' (value of 0)
		if($type[0] != '' && $type[0] != 3 && $dlr[0] != '' && $dlr[0] != 0) {
			$_SESSION['error'][] = 'Error! Only Dealer users may be assigned a dealer!';
		}
		
		// If user is dealer (type == 3), dealer type cannot == 0 (N/A)
		if($type[0] != '' && $type[0] == 3 && $dlr[0] != '' && $dlr[0] == 0) {
			$_SESSION['error'][] = 'Error! Dealer users must be assigned to a dealer!';
		}
		
		// If dlr_team_id ($dlr[3]) != team affiliation ($team[0]), issue error
		if($team[0] != '' && $dlr[3] != '' && $type[0] != '' && $type[0] == 3 && $team[0] != $dlr[3]) {
			$_SESSION['error'][] = 'Error! Team Affiliation must match the dealer manufacturer selection!';
		}
		
		// If user type is manuf, DO NOT allow to be registered under team 'All'
		if($team[0] != '' && $type[0] != '' && $team[0] == 0 && $type[0] == 2) {
			$_SESSION['error'][] = 'Error! Manufacturer users are not allowed to be designated as team \'All\'';
		} 
		
		// If form is in edit mode, only verify password if a new one is being set (
		if (empty($_POST['user_id'])) {
			if ($pword1 == '' || !$this->_validPass($pword1)) {
				$_SESSION['error'][] = 'Please enter a valid password!';
			}
		} else {
			if($pword1 != '') {
				if(!$this->_validPass($pword1)) {
					$_SESSION['error'][] = 'Please enter a valid password!';
				}
			}
		}
		
		if ($pword2 != $pword1) {
			$_SESSION['error'][] = 'Your passwords do not match!';
		}
		
		// Before performing any update, ensure that username does not already exist if user is in edit mode, and changed it on the edit form
		if(!empty($_POST['user_id'])) {
			if($_SESSION['edit_username'] != $uname) {
				if(!$this->checkUsernameDupe($uname)) {
					$_SESSION['error'][] = 'That username is already taken! Please choose another.';
				}
			}
		}
			
		if (isset($_SESSION['error'])) {
			return false;
		}
		
		// Convert password into hashed string for DB security
		//$pass = $this->_getSaltedHash($pword1);
		/* Convert password into encrypted string for DB security. 
		 * Remember, this is complimented by the following retrieval method:
		 * password_verify($login_password, $user_pass)
		**/
		$pass = password_hash($pword1, PASSWORD_BCRYPT);
		
		/* If no user_id was passed, create a new user. 
		 * Check to make sure username is not already taken before executing INSERT statement
		 * Note: $_POST['user_id'] will always be empty if action is adding new user (empty == null)
		 */
		//if (empty($_POST['user_id']) && !isset($_SESSION['edit_user_id'])) {
		if (empty($_POST['user_id'])) {
			if($this->checkUsernameDupe($uname)) {
				$sql = "INSERT INTO users (user_fname, user_lname, user_uname, user_email, user_pass, 
										   user_team_id, user_type_id, dealer_record_id, user_admin, user_active) 
						VALUES (:fname, :lname, :uname, :email, :pass, :team_id, :type_id, :dlr_id, :admin, :active)";
			} else {
				$_SESSION['error'][] = 'That username is already taken!  Please choose another.';
				return false;
			}
		/*
		 * Update the user if they are being edited
		 */
		} else {
			/*
			 * Cast the user_id as an integer for security
			 */
			$user_id = (int) $_POST['user_id'];
			
			// If user has decided not to update the password (by leaving it blank), UPDATE without the password field
			if ($pword1 == "") {
				$sql = "UPDATE users
						SET 
							user_fname	 	= :fname,
							user_lname	 	= :lname,
							user_uname	 	= :uname,
							user_email	 	= :email,
							user_team_id 	= :team_id,
							user_type_id    = :type_id,
							dealer_record_id= :dlr_id,
							user_admin	 	= :admin,
							user_active		= :active
						WHERE user_id 		= $user_id";
			} else {
			// If user has decided to update the password, verify first and then UPDATE the password field.  Otherwise return false.
				if(!$this->_validPass($pword1)) {
					$_SESSION['error'][] = 'Please enter a valid password!';
					return false;
				}
				$sql = "UPDATE users
						SET 
							user_fname	 	= :fname,
							user_lname	 	= :lname,
							user_uname	 	= :uname,
							user_email	 	= :email,
							user_pass 		= :pass,
							user_team_id 	= :team_id,
							user_type_id    = :type_id,
							dealer_record_id= :dlr_id,
							user_admin	 	= :admin,
							user_active		= :active
						WHERE user_id 		= $user_id";
			}		
		}
		
		/*
		 * Execute the create or edit query after binding the data
		 */
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":fname",   $fname, PDO::PARAM_STR);
			$stmt->bindParam(":lname",   $lname, PDO::PARAM_STR);
			$stmt->bindParam(":uname",   $uname, PDO::PARAM_STR);
			$stmt->bindParam(":email",   $email, PDO::PARAM_STR);
			// Do not bind the parameter :pass if the user is editing without a password update
			if ($pword1 != "") {
				$stmt->bindParam(":pass", $pass, PDO::PARAM_STR);
			}	
			$stmt->bindParam(":team_id", $team_id, PDO::PARAM_INT);
			$stmt->bindParam(":type_id", $type_id, PDO::PARAM_INT);
			$stmt->bindParam(":dlr_id",  $dlr_id, PDO::PARAM_INT);
			$stmt->bindParam(":admin",   $admin, PDO::PARAM_INT);
			$stmt->bindParam(":active",  $active, PDO::PARAM_INT);
			$stmt->execute();
			$stmt->closeCursor();
			
			// Return success message based on add or edit mode
			$_SESSION['success'][] = (empty($_POST['user_id'])) ? 'User '.$uname.' has been added successfully!' : 'User '.$uname.' has been updated successfully!';
			
			/*
			 * This dialoque is from original copied method from Calendar class (processForm)
			 * Returns the ID of the event. Previously was 'return TRUE'.
			 * lastInsertId is a built-in PDO function.
			 * I had to modify this by adding the 'if' statement, as the
			 * result was returning false when the UPDATE statement was
			 * executed.  This was causing the admin.php page to die back to
			 * the $_SESSION['lastpage'], which was itself.
			 */
			 
			 // Unset sticky form elements
			 $this->unsetUserFormGlobals();
			 
			 return true;
		}
		catch (Exception $e) {
			die($e->getMessage());
		}
	}
	
	// Make sure that username is not already taken (username dupes are not allowed)
	public function checkUsernameDupe($uname) {
		$sql = "SELECT user_uname FROM users WHERE user_uname = :uname";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":uname", $uname, PDO::PARAM_STR);
			$stmt->execute();
			$results = $stmt->fetchAll();
			$stmt->closeCursor(); 
			$return = (count($results) > 0) ? false : true;
		}
		catch (Exception $e) {
			$_SESSION['error'][] = $e->getMessage();
			$return = false;
		}
		return $return;
	}
	
	// Unset add/edit user sticky form globals
	public function unsetUserFormGlobals() {
		unset(
			$_SESSION['edit_user_id'],
			$_SESSION['fname']		 ,
			$_SESSION['lname'] 		 ,
			$_SESSION['uname'] 		 ,
			$_SESSION['email'] 		 ,
			$_SESSION['dlr_id']		 ,
			$_SESSION['dlr_code'] 	 ,
			$_SESSION['dlr_name'] 	 ,
			$_SESSION['admin'] 		 ,
			$_SESSION['active'] 	 ,
			$_SESSION['team_id'] 	 ,
			$_SESSION['team_name'] 	 ,
			$_SESSION['type_id'] 	 ,
			$_SESSION['type_name'] 	 ,
			$_SESSION['pword1'] 	 ,
			$_SESSION['pword2'] 	
		);
		return;
	}
	
	/**
	 * Logs out the user
	 *
	 *@return mixed TRUE on success or message on failure
	 */
	public function processLogout() {
		/*
		 * Fails if the proper action was not submitted
		 */
		if ($_POST['action']!='user_logout') {
			return "Invalid action supplied for processLogout.";
		}
		
		/*
		 * Removes the user array from the current session
		 */
		session_destroy();
		return TRUE;
	}
	
	/* This function sends an email containing a reset password link to user requesting forgot password link
	 * will execute after user submits email address on the reset_password page 
	 * returns true or false based on successful user search and pass_rest table insert
	 * $array contains: 'user_email'
	**/
	public function emailPassResetLink($array) { // $user
		// Save 'user_email' input as global for sticky form action
		$_SESSION['forgot_pass_email'] = $array['user_email'];
		// First, lookup the email address user entered to see if it exists.  Make sure you specify active user!! 
		$user = $this->_loadUserData(array('user_email'=>$array['user_email'], 'user_active'=>1));
		//echo 'user: '.var_dump($user);
		// If there was no match, return false. Also make sure that there are no duplicates.
		if(count($user) == 0) {
			$_SESSION['error'][] = 'Sorry, we do not have your email address in our records! Please contact the administrator.';
			// Set global for email_reset_active so that correct form is loaded on index.php
			$_SESSION['email_reset_active'] = true;
			return false;
		} elseif (count($user) > 1) {
			$_SESSION['error'][] = '*Error! Multiple user email encountered.  Please contact the administrator with this message for assistance.';
			$_SESSION['email_reset_active'] = true;
			return false;
		}
 		
 		// Save user_id and user_name from user query
		$user_id = $user[0]['user_id'];
		$params = array($user_id);
		$params[] = $user[0]['user_uname'];
		
		// Create unique hash to append to URL and add to $params
		$hash = uniqid("",TRUE);
		$params[] = $hash;
		
		// Add reset_active param value of true (boolean)
		$params[] = 1;
		
		// Add current date and time to $params
		$params[] = date("Y-m-d H:i:s");
		
		// Build entire URL string to provide as link in reset email
		$urlHash = urlencode($hash);
		$site = 'http://www.soscompany.net/';
		//$site = $array['base_url'];
		$resetPage = "index.php";
		// Note: 'reset_code' is used in $_GET instruction on index.php for reset password form access
		$fullUrl = $site.$resetPage."?reset_code=".$urlHash;
		
		//echo 'user_id: ' + $user_id + 'user_name: ' + $params[1] + ' hash: ' + $hash + 'fullUrl: ' + $fullUrl;
		
		/* Insert reset info into reset_pass table. This is the only place query will be used.  Keep in this method.
		 * Note that email_id field is actually the user_id value from user table
		**/
		$stmt = "INSERT INTO reset_pass (email_id, user_uname, pass_key, reset_active, create_date)
				 VALUES (?,?,?,?,?)";
		
		// Prepare and execute statement
		if(!($stmt = $this->db->prepare($stmt))) {
			$_SESSION['error'][] = "Error! We were unable to process you reset request.  Please see that administrator with this message.";
			//sendErrorNew($dbo->errorInfo(), __LINE__, __FILE__);
			// Set global for email_reset_active so that correct form is loaded on index.php
			$_SESSION['email_reset_active'] = true;
			return false;
		}
		
		// If INSERT is successful, email user reset password link
		if(!($stmt->execute($params))) {
			$_SESSION['error'][] = "Error! We were unable to process you reset request.  Please see that administrator with this message.";
			//sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			// Set global for email_reset_active so that correct form is loaded on index.php
			$_SESSION['email_reset_active'] = true;
			return false;
		} else {
			// Build email message
			$to = $user[0]['user_email'];
			$subject = "Password Request Confirmation for SOS Service Portal";
			$msg = "Dear ".$user[0]['user_fname'].", \n\n";
			$msg.= "You have requested a password reset for the SOS Service Portal.\n\n";
			$msg.= "Please click on this link to reset your password:\n";
			$msg.= $fullUrl."\n\n";
			$msg.= "Thank you,\n";
			$msg.= "SOS Admin";
			//echo 'msg: '.$msg;
			// Make sure to unset global, as lookup and INSERTs were successful. The wrong form will load if you don't!!
			if(isset($_SESSION['email_reset_active'])) {
				unset($_SESSION['email_reset_active']);
			}
			// Make sure that email was successful
			$mail_status = mail($to,$subject,$msg);
			if($mail_status) {
				$_SESSION['success'][] = "Your password reset link was emailed successfully! Please check your email.";
			} else {
				$_SESSION['error'][] = "Whoops!  The system processed your request successfully, but your email failed.  Please see the administrator!";
			}
			// No need to return true or false; the SESSION vars take care of the next form load
			return;
		}
	} // end method emailPassResetLink()
	
	public function validateResetPassData($array) {
		// Get password values that user entered
		$pword1 = $array['reset_pword1'];
 		$pword2 = $array['reset_pword2'];
 		
 		// Save inputs as globals for sticky form action
 		$_SESSION['reset_email']  = $array['reset_email'];
 		$_SESSION['reset_pword1'] = $pword1;
 		$_SESSION['reset_pword2'] = $pword2;
 		
 		// Go ahead and set the reset pass error global to true.  If resetPass() method successful, unset it
 		$_SESSION['reset_pass_error'] = true;
 		
 		// Note: did not include email validation here, because decided to allow user to enter anything.  
 		// The system will tell them if the email does not exist.  Don't want to limit emails if my verification is incorrect :/
 		
 		// Make sure that first password entry conforms to the password requirements
 		if(!$this->_validPass($pword1)) {
 			$_SESSION['error'][] = "Your password does not meet the minimum requirements! Please re-enter your password again.";
 		}
 		
 		// Make sure that both emails entered by user match
		if ($pword1 != $pword2) { 
			$_SESSION['error'][] = "*Error! Your passwords do not match each other!";
		}
		
		if(isset($_SESSION['error'])) {
			return false;
		}
		
		// Note that hash should == pass_key field value in reset_pass table. $array['hash'] comes from SESSION var set in index.php
		$hash = $_SESSION['reset_hash']; 
		$params = array($hash);
		
		// reset_active field value has to be 1 (true)
		$params[] = 1;
		 
		// Email value comes from form entry
		$user_email = $array['reset_email'];
		$params[] = $user_email;
		
		//echo 'reset_pword1: '.$pword1.' , reset_pword2: '.$pword2.' , reset_email: '.$user_email;
		
		// Create query instruction
		$stmt = "SELECT a.user_id, a.user_uname, a.user_fname, a.user_email, b.id FROM users a
				 LEFT JOIN reset_pass b ON(a.user_id = b.email_id)
				 WHERE b.pass_key = ? AND b.reset_active = ? AND a.user_email = ? ";
		
		// Prepare and execute statement
		if(!($stmt = $this->db->prepare($stmt))) {
			$_SESSION['error'][] = "Sorry, there was a query processing error and your information was not processed.  Please see the administrator.";
			//sendErrorNew($dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		
		// If one row result is found, execute password update with _resetPass() method. Else return false.
		if(!($stmt->execute($params))) {
			$_SESSION['error'][] = "Sorry, there was a processing error and your information could not be processed correctly.  Please see the administrator.";
			//sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
			//exit('dump of $user_info: '.var_dump($user_info));
			// There should be 5 results in the array if find user was successful
			if(count($user_info) == 5) {
				//echo 'entered count user == 1<br>';
				//echo '$user_info: '.var_dump($user_info).'<br>';
				//echo 'params: '.var_dump($params).'<br>';
				// Use query results to reset password in user table using _resetPass() method. Send email confirm to user.
				if($this->_resetPass(array('user_id'=>$user_info['user_id'],'pass'=>$pword1))) {
					//echo 'entered _resetPass block<br>';
					/* Update reset_pass row just used to inactive status (reset_active = 0)
					 * Since user will refresh page upon successful pass update, set SESSION['error'] in here if necessary
					**/
					if(!$this->_updateResetPassActive(array('id'=>$user_info['id']))) {
						$_SESSION['error'][] = 'Password reset successful, but please contact Admin with \'reset_pass active error\' message. Thank you.';
					}
					
					// Send email to user confirming reset and reminding them of their username. Provide link to main login
					$to = $user_email;
					$subject = "Password Reset Confirmation for SOS Service Portal";
					$msg = "Dear ".$user_info['user_fname'].", \n\n";
					$msg.= "Your password for the SOS Service Portal has been successfully reset.\n";
					$msg.= "For your reference, the following is your username: ".$user_info['user_uname']."\n\n";
					$msg.= "You may proceed to the main login page here: \n";
					$msg.= "www.soscompany.net\n\n";
					$msg.= "Thank you,\n";
					$msg.= "SOS Admin";
					//echo 'msg: '.$msg;
					
					// Email confirmation to user and then return true. Notify user if email fn unsuccessful. Don't return false though
					if(!mail($to,$subject,$msg)) {
						$_SESSION['error'][] = 'Notice: email confirmation failed. Please altert the administrator of this error.';
					}
					// Unset sticky form elements, and reset pass error global so that correct form loading is not interrupted
					unset($_SESSION['reset_email'], $_SESSION['reset_pword1'], $_SESSION['reset_pword2'], 
						  $_SESSION['reset_pass_error'], $_SESSION['reset_hash']);
					
					// Issue success message for successful password reset
					$_SESSION['success'][] = "*Success: Your password was reset successfully! An email confirmation was sent to ".$user_email.".";
					
					// Return true regardless of if any of the above errors occurred. This is because password was successfully reset in reset_pass table.
					return;
				} else {
					$_SESSION['error'][] = '*Error!  The system was unable to process your reset password request.  Please see the administrator.';
					return;
				}
			// Do not attempt to reset password if more than one identical user is found.
			} elseif (count($user_info) > 5) {
				$_SESSION['error'][] = 'Multiple user error encountered.  Please contact the administrator.';
				return;
			// This will execute if count($user_info == 0)
			} else {
				$_SESSION['error'][] = '*We were unable to process your request, as that email address is not in our system! Please contact the administrator if you need assistance.';
				return;
			}
		}
	} // end function validateResetPassEmail
	
	// Update user table with new reset password
	private function _resetPass($array) {
		$new_pass = password_hash($array['pass'], PASSWORD_BCRYPT);
		$params   = array($new_pass);
		$user_id  = $array['user_id'];
		$params[] = $user_id;
		
		
		// Create query instruction
		$stmt = "UPDATE users SET user_pass = ?
				 WHERE user_id = ?";
		
		// Prepare and execute statement
		if(!($stmt = $this->db->prepare($stmt))) {
			sendErrorNew($dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			return true;
		}
	} // end _resetPass method
	
	/* This function executes after the _resetPass has executed successfully. 
	 * Updates reset_pass reset_active field to 0 so as to prevent future duplicate row results for reset_pass SELECT
	**/
	private function _updateResetPassActive($array) {
		$stmt = "UPDATE reset_pass SET reset_active = 0 WHERE id = ?";
		
		$params[] = $array['id'];
		
		// Prepare and execute statement
		if(!($stmt = $this->db->prepare($stmt))) {
			sendErrorNew($dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			return true;
		}
	} // end _updateResetPassActive method
	
	/**
	 * Generates a salted hash of a supplied string
	 *
	 * @param string $string to be hashed
	 * @param string $salt extract the hash from here
	 * @return string the salted hash
	 */
	private function _getSaltedHash($string, $salt=NULL) {
		/*
		 * Generates a salt if no salt is passed
		 */
		if ($salt==NULL) {
			$salt = substr(md5(time()), 0, $this->_saltLength);
		/*
		 * Extract the salt from the string if one is passed
		 */
		} else {
			$salt = substr($salt, 0, $this->_saltLength);
		}
		
		/*
		 * Add the salt to the hash and return it
		 */
		return $salt.sha1($salt.$string);
	}
	
	/**
	 * Validate the name input for first name and last name
	 */
	private function _validName($name) {
		/*
		 * Define a regex pattern to check the name format
		 */
		$pattern = '/^[A-Za-z]+$/';
		
		/*
		 * If a match is found, return TRUE. FALSE otherwise.
		 */
		return preg_match($pattern, $name) ==1 ? TRUE : FALSE;
	}
	
	/**
	 * Validate the email input
	 */
	private function _validEmail($email) {
		/*
		 * Define a regex pattern to check the email format
		 */
		$pattern = '/(^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$)|(^N\/A$)/';
		
		/*
		 * If a match is found, return TRUE. FALSE otherwise.
		 */
		return preg_match($pattern, $email) ==1 ? TRUE : FALSE;
	}
	
	/**
	 * Validate the password input for at least 8 characters, one upper case & one lower case, a number and a special character
	 */
	private function _validPass($pword) {
		/*
		 * Define a regex pattern to check the password
		 */
		$pattern = '/^(?=.*\d)(?=.*[@#\-_$%^&+=§!\?])(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z@#\-_$%^&+=§!\?]{8,}$/';
		
		/*
		 * If a match is found, return TRUE. FALSE otherwise.
		 */
		return preg_match($pattern, $pword) ==1 ? TRUE : FALSE;
	}
	
	/**
	 ** This was a test method provided by the book.  Was instructed 
	 ** to take it out after test was completed successfully 
	 **
	 ** public function testSaltedHash($string, $salt=NULL) {
	 **		return $this->_getSaltedHash($string, $salt);
	 **	}
	 **/
}
?>