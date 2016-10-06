<?php
/**
 * File: getFile.php
 * Purpose: Display pdf files
 * History:
 *   Date			Description									by
 *   06/07/2016		Initial design & coding	    				Matt Holland
 */

// Require the initialization file
//require_once('../config/init.inc.php');
session_start();

// Include necessary files
include_once 'sys/core/init.inc.php';

//include_once('../class/class.DB_Connect.inc.php');
//include_once('../class/class.Documents.inc.php');

if(!isset($_POST['view_doc_id'])) {
	exit("You are not authorized to access that location!");
}

$obj = new Documents($dbo=null);
$obj->viewFile(array('view_doc_id'=>$_POST['view_doc_id']));

exit;
?>