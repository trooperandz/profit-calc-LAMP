<?php
/**
 *File: class.User.inc.php
 *Purpose: Store user information
 *PHP version 5.5.29
 *@author Matthew Holland
 *
 *History:
 *   Date			Description						by
 *   10/29/2015		Initial design & coding	    	Matt Holland
 */
 
 class User {
	/**
	 * The user ID
	 *
	 * @var int
	 */
	public $user_id;
	
	/**
	 * The user's first name
	 *
	 * @var string
	 */
	public $fname;
	
	/**
	 * The user's last name
	 *
	 * @var string
	 */
	public $lname;
	
	/**
	 * The user's username
	 *
	 * @var string
	 */
	public $uname;
	
	/**
	 * The user's email address
	 *
	 * @var string
	 */
	public $email;
	
	// The user's associated dealer info (if any)
	public $dlr_id;
	public $dlr_name;
	public $dlr_code;
	public $dlr_team_id;
	
	/**
	 * The user's admin status
	 *
	 * @var int
	 */
	public $admin;
	
	/**
	 * The user's active status
	 *
	 * @var int
	 */
	public $active;
	
	/**
	 * The user's team affiliation
	 *
	 * @var int
	 */
	public $team_id;
	public $team_name;
	
	// The user's type information
	public $type_id;
	public $type_name;
	
	/**
	 * Accepts an array of event data and stores it
	 *
	 * @param array $event Associative array of event data
	 * @return void
	 */
	public function __construct($user) {
		if (is_array($user)) {
			$this->user_id      = $user['user_id'];
			$this->fname        = $user['user_fname'];
			$this->lname        = $user['user_lname'];
			$this->uname        = $user['user_uname'];
			$this->email        = $user['user_email'];
			$this->dlr_id		= $user['dealer_record_id'];
			$this->dlr_name		= $user['dealer_name'];
			$this->dlr_code  	= $user['dealer_code'];
			$this->dlr_team_id  = $user['dealer_team_id'];
			$this->admin        = $user['user_admin'];
			$this->active       = $user['user_active'];
			$this->team_id      = $user['user_team_id'];
			$this->team_name	= $user['team_name'];
			$this->type_id		= $user['user_type_id'];
			$this->type_name	= $user['user_type_name'];
		} else {
			throw new Exception("No user data was supplied.");
		}
	} 
 }