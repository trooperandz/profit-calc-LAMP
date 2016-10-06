<?php
/**
 * File: class.Modal.inc.php
 * Purpose: Build all system modals
 * PHP version 5.1.2
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description						by
 *   07/18/16   	Initial design & coding	    	Matt Holland
 */
	class Modal extends DB_Connect {
	
		public function __construct($dbo=NULL) {
			parent::__construct($dbo);
		}
		
		// Get privacy checkbox modal
		public function getPrivacyModal() {
			$html .='
			<div class="modal fade" id="privacy_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			        <h4 class="modal-title" id="myModalLabel">Worksheet Privacy</h4>
			      </div>
			      <div class="modal-body">';
			      	// Set public/private label, and set 'checked' status on checkboxes
			      	$privacy = ($_SESSION['profit_privacy'] == 1) ? 'Public' : 'Private';
			      	$public  = ($_SESSION['profit_privacy'] == 1) ? 'checked' : null;
			      	$private = ($_SESSION['profit_privacy'] == 0) ? 'checked' : null;
			      	$html .='
			        <p> Your current worksheet privacy is set to : 
			        	<span style="color: rgb(9, 114, 165);">'.$privacy.'</span>
			        </p>
			        <br>
			        <p> Change Privacy: </p>
			        <div class="radio">
			          <label>
			       	    <input type="radio" name="check" id="public_check" value="1" '.$public.'> Set to Public
			       	  </label>
			       	</div>
			       	<div class="radio">
			       	  <label>
			       	    <input type="radio" name="check" id="private_check" value="0" '.$private.'> Set to Private
			       	  </label>
			       	</div>
			       	<br>
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			        <button type="button" name="privacy_submit" id="privacy_submit" class="btn btn-primary">Save changes</button>
			      </div>
			    </div>
			  </div>
			</div>';
		  return $html;
		}
	}
?>