<?php
/**
 * File: class.db_connect.inc.php
 * Purpose: Database actions (DB access, validation, etc.)
 * PHP version 5.1.2
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description						by
 *   09/11/2015		Initial design & coding	    	Matt Holland
 */

Class DB_Connect {

	/**
	 * Stores a database object
	 *
	 * @var object A database object
	 */
	protected $db;
	
	/**
	 * Checks for a db object or creates
	 * one if one is not found.
	 *
	 * @param object $dbo A database object
	 */
	protected function __construct($db=NULL) {
		if (is_object($db)) {
			$this->db = $db;
		} else {
			// Constants are defined in db-cred.inc.php
			$dsn = "mysql:host=".DB_HOST."; dbname=".DB_NAME;
			
			try {
				$this->db = NEW PDO($dsn, DB_USER, DB_PASS);
			}
			catch (Exception $e) {
				// If the DB connection fails, output the error
				die($e->getMessage());
			}
		}
	}
}
?>