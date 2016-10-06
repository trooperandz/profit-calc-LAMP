<?php
/**
 * Program: class.Documents.inc.php
 * Created: 07/26/2016 by Matt Holland (adapted from Online Reporting upgrade)
 * Purpose: Display file upload form and file retrieval table
 * Methods: getPageHeading(): Build page heading for info displays
 			getDocTable(): Build table to display user files
 			processFileUpload(): Process file uploads - insert files into db
 			getSuccessMsg(): Display user feedback after form submission
 * Updates:
 */

Class Documents extends DB_Connect {	
	
	public function __construct($db=null) {
		// Call the parent construct to check for a database object
		parent::__construct($db);
	}

	public function getPageHeading($array) {
		$msg = $array['link_msg'];
		$html ='
		<div class="title_area">
           	<div class="row">
           		<div class="small-12 medium-9 large-9 columns">
           			<p class="large-title">'.$array['page_title'];
           				if($array['title_info']) {
           					$html .='
           					<span class="blue"> '.$array['title_info'].' </span>';
           				} 
           				if($array['a_id']) {
           					$html .='
           					<a id="'.$array['a_id'].'" class="'.$array['a_id'].'" style="color: green; font-size: 15px;"> &nbsp; '.$msg.' </a>';
           				}
           			$html .='
           			</p>
           		</div>
           		<div class="small-12 medium-3 large-3 columns">
					<p class="right-align large-title">';
					  if($array['export-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Export Document List" href="system/utils/export_doc_list.php">
							<span class="fontello-download"></span>
						</a>';
					  }
					  if($array['print-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Print Dealer Table" href="#" onclick="window.print();">
							<span class="fontello-print"></span>
						</a>';
					  }
					  if($array['doc_count']) {
					  	$html .='
						&nbsp;Total Documents: '.number_format($_SESSION['user_doc_count']);
					  }
					$html .='
					</p>
				</div>
           	</div>
        </div>
        <!-- Container Begin -->
        <div class="row" style="margin-top:-20px">';
		return $html;
	}
	
	/* Build user documents table */
	public function getFileUploadForm($array) {
		
		/* If $array['edit_doc_id'] == true, get doc details.
		 * Note that default form load includes: 'doc_title', 'doc_desc' & id="file_input"
		 * The edit form load includes: 'doc_title', 'file_name', 'doc_desc' (no 'file_input'
		**/

		// Set var for $_POST['edit_doc_id'] for DRY in case the string changes later
		$edit_doc_id = (isset($_POST['edit_doc_id'])) ? $_POST['edit_doc_id'] : false;

		if($_POST['edit_doc_id']) {
			$doc_data     = $this->getDocData(array('edit_doc_id'=>$edit_doc_id));
			$doc_title    = $doc_data[0]['doc_title'];
			$doc_desc     = $doc_data[0]['doc_desc'];
			$file_name    = substr($doc_data[0]['file_name'], 0, -4);
			$form_title   = 'Edit Document Details';
			$msg1	      = 'This form is for editing your document titles and descriptions.';
			$msg2	      = 'Note: If you are trying to change the actual stored file, please delete the existing file and create a new one.';
			// Note: These values are only set here so that they are grouped conveniently with the other values
			$submit_id    = 'file_update_submit';
			$submit_value = 'Save Changes';
		} else {
			$doc_title    = null;
			$doc_desc     = null;
			$form_title   = 'Upload New Document';
			$msg1	      = 'Use this form for uploading your personal documents.';
			$msg2         = 'Note: Only pdf documents are allowed to be uploaded to the system.';
			// Note: the below values do not need to be set as the file plugin automatically creates the submit file button
			// This was not the case previously, in the revamped Online Reporting System (where you do not use a file input plugin)
			//$submit_id    = 'file_input';
			//$submit_value = 'Upload Document';
		}
	
		// Note: div.doc_form is used for updating the document form content via jQuery .replaceWith() method
		$html ='
		<div class="doc_form">
			<div class="row">';
			// Show the feedback message if user completed an AJAX action
			if(isset($array['msg'])) {
			    $html .= '
			    <div class="col-md-12">
			    	<p style="color: green;">'.$array['msg'].'</p>
			    </div>';
			}
				$html .='
				<div class="col-md-3">
					<h4 style="color: rgb(9, 114, 165);">'.$form_title.'</h4>
					<br/>
					<ul>
						<li style="color: gray;"> '.$msg1.' </li>
						<br/>
						<li style="color: gray;"> '.$msg2.' </li>
					</ul>
				</div>
				<div class="col-md-9" style="border-left: 1px solid #CCCCCC;">
				<form>
					<fieldset>
						<div class="col-md-12">
							<label class="control-label" for="doc_title">Doc Title</label>
							<input class="form-control" type="text" id="doc_title" name="doc_title" value="'.$doc_title.'" placeholder="Please enter the document title">			
						</div>';
					/* Only show the file_name input if in edit mode. 
					 * The file_name info is automatically set already with the initial 'file_input' by the $_POST['files'][0]
					 * Add hidden input with edit_doc_id so that this value can be passed to process file
					**/
					if($edit_doc_id) {
						$html .='
						<div class="col-md-12">
							<label class="control-label" for="file_name">File Name</label>
							<div class="input-group">
								<input class="form-control" type="text" id="file_name" name="file_name" value="'.$file_name.'" placeholder="Please enter the file name">
								<div class="input-group-addon">.pdf</div>
							</div>
						</div>
						<input type="hidden" id="edit_doc_id" name="edit_doc_id" value="'.$edit_doc_id.'" />';
					}
						$html .='
						<div class="col-md-12">
							<label class="control-label" for="doc_desc">Doc Description</label>
							<input class="form-control" type="text" id="doc_desc" name="doc_desc" value="'.$doc_desc.'" placeholder="Enter a doc description, if desired">
						</div>';
					// Only show the file input if you are not in edit mode.  System does not allow you to replace file. Must delete instead.
					if(!$edit_doc_id) {
						$html .='
						<div class="col-md-12">
							<label class="control-label">Select file</label>
							<input id="file_input" name="file_input" type="file" class="file" placeholder="Press \'Browse\' to select file...">
						</div>';
					}
					/* Only show the input submit if editing document data.
					 * The file input plugin automatically creates this after choosing your file,
					 * so there is no need to create it if you are not in edit mode.
					 * Note: .edit_file_submit is used for button styling (margin-top)
					**/
					if($edit_doc_id) {
						$html .='
						<div class="col-md-12">
							<input type="submit" class="btn btn-primary edit-file-submit" id="'.$submit_id.'" name="'.$submit_id.'" value="'.$submit_value.'" /> 
							&nbsp; or <a href="#" class="edit-doc-cancel">cancel</a>
						</div>';
					}
					// Note: show the below action for both edit and file_input actions for the AJAX submit
					$html .='
						<input type="hidden" name="action" id="action" value="file_submit" /> 
					</fieldset>
				</form>
				</div> <!-- .col-md-9 -->
			</div> <!-- .row -->
		</div> <!-- .doc_form -->';
		return $html;
	}
	
	public function getDocData() {
		// Generate sql statement
		$stmt = "SELECT file_id, doc_title, doc_desc, tmp_name, file_name, file_size, create_date, user_name 
				 FROM documents
				 WHERE user_id = ? ";

		// Set POST val for ensuring correct execution of $params[]
		$edit_doc_id = (isset($_POST['edit_doc_id']) && $_POST['edit_doc_id'] != null) ? $_POST['edit_doc_id'] : false;
		
		// Initialize $params
		$params = array($_SESSION['user']['id']);
		
		// If $array['edit_doc_id'], add WHERE clause and add doc id to $params
		if($edit_doc_id) {
			$stmt .= " AND file_id = ? ";
			$params[] = $edit_doc_id;
		}
		
		// Prepare and execute statement
		if(!($stmt = $this->db->prepare($stmt))) {
			//sendErrorNew($this->db->errorInfo(), __LINE__, __FILE__);
			$_SESSION['error'][] = "Oops!  The system was unable to execute the document retrieval instruction.  Please let the administrator know!";
			return false;
		}
		if(!($stmt->execute($params))) {
			//sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			$_SESSION['error'][] = "Oops!  The system was unable to retrieve document data.  Please let the administrator know!";
			return false;
		} else {
			$data = $stmt->fetchAll();
			return $data;
		}
	}
	
	/* Generate documents table.
	 * Array could contain 'msg' (AJAX action user feedback)
	**/
	public function getDocTable($array) {
		// Get Document information
		$data = $this->getDocData();
		
		// Save document count as SESSION var for page heading
		$_SESSION['user_doc_count'] = count($data);
		
		// Build html table. Note the div.doc_table is used with jQuery .html() method
		$html ='
			<div class="doc_table">
				<div class="row">
					<div class="col-lg-12">';
						// Show success/error messages.  Special case here because of AJAX.
						if(isset($array['msg'])) {
							$html .='
							<p style="color: green;">'.$array['msg'].'</p>';
						}
						$html .='
						<div class="table-responsive">
						<div class="table-container">
						<table id="user_doc_table" class="original metric">
							<thead>
								<tr>
									<th style="width: 86px;"><a>Action</a></th>
									<th><a> Doc Name	</a></th>
									<th><a> Description </a></th>
									<th><a> File Name	</a></th>
									<th><a> File Size 	</a></th>
									<th><a> Create Date </a></th>
									<th><a> User	 	</a></th>
								</tr>
							</thead>
							<tbody>';
							$export = "Doc Name, Description, File Name, File Size, Create Date, User\n";
		// Build html table body and export data rows based on increments set above
		for($i=0; $i<count($data); $i++) {
			// Format create_date to show just the date, not the time also
			$date = date("m/d/Y", strtotime($data[$i]['create_date']));
			$html .='
								<tr>
									<td style="width: 86px;">
										<a class="glyphicon glyphicon-trash" title="Delete Doc" style="color: #c7254e;" id="'.$data[$i]['tmp_name'].'" name="remove_doc_icon">&nbsp;</a>
										<a class="glyphicon glyphicon-pencil" title="Edit Doc" style="color: green;" name="edit_doc_icon">&nbsp;</a>
										<form style="display: inline-block;" method="POST" action="getFile.php">
											<input type="hidden" value="'.$data[$i]['file_id'].'" id="view_doc_id" name="view_doc_id" />
											<button type="submit" style="border: none; padding: 0; margin: 0; background: none; background-color: #FFFFFF;">
												<a class="glyphicon glyphicon-download file" title="Download" id="table_doc_select" name="table_doc_select"></a>
											</button>
										</form>
									</td>
									<td>'.$data[$i]['doc_title'].	'</td>
									<td>'.$data[$i]['doc_desc'].	'</td>
									<td>'.$data[$i]['file_name'].	'</td>
									<td>'.$data[$i]['file_size'].	'</td>
									<td>'.$date.					'</td>
									<td>'.$data[$i]['user_name'].	'</td>
								</tr>';
						$export .= $data[$i]['doc_title'].",".$data[$i]['doc_desc'].",".$data[$i]['file_name'].",".$data[$i]['file_size'].",".$data[$i]['create_date'].",".$data[$i]['user_name']."\n";		  				  
		}
		// Close the table body and create the table footer
		$html .='
							</tbody>
						</table>
						</div> <!-- .table-container -->
						</div> <!-- .table-responsive -->
					</div><!-- .large-12 columns -->
				</div><!-- .row -->
			</div> <!-- .doc_table -->';
		
		// Save export as SESSION var
		$_SESSION['export_doc_data'] = $export;
		
		return $html;
	}	
	
	/* Process file upload - insert into db */
	public function processFileUpload() {
		//echo 'what\'s up, man?';
		if(isset($_FILES['file_input'])) {
			//return 'FILES isset';
			// Gather all required data
			$doc_title 	  = $_POST['doc_title'];
        	$doc_desc 	  = $_POST['doc_desc'];
        	// Note: doc_category was taken out of this (which was adapted from your Online Reporting revamp code)
        	//$doc_category = $_POST['doc_category'];
			$file 		  = $_FILES['file_input'];
        	$file_name 	  = $file['name'];
        	$file_type    = $file['type'];
        	$file_size 	  = $file['size'];
        	
        	// Set directory for use below (couldn't get constant to work)
        	define("USER_DOC_DIR", '/home/soscompany/public_html/wollard/sys/user_docs/');
        	
        	// Create the file's new name and destination if there were no errors.  Append a unique identifier to name.
			$tmp_name = sha1($file['name']) . uniqid('', true);
			$dest = USER_DOC_DIR.$tmp_name.'_tmp';
			
			//return 'POST[doc_title]: '.$doc_title.' POST[doc_desc]: '.$doc_desc.' file_name: '.$file_name.' file_type: '.$file_type.' file_size: '.$file_size.' dest: '.$dest.' tmp_name: '.$tmp_name;
			
			// Create $params[] array list for db insert
			$params = array();
			$params[] = $doc_title;
			$params[] = $doc_desc;
			//$params[] = $doc_category;
			$params[] = $tmp_name;
			$params[] = $file_name;
			$params[] = $file_type;
			$params[] = $file_size;
			$params[] = $_SESSION['user']['id'];
			$params[] = $_SESSION['user']['uname'];
			$params[] = date("Y-m-d H:i:s");
			
			// Move the file
			if (move_uploaded_file($file['tmp_name'], $dest)) {
					
				// Make file name available in feedback message
				$feedback_file = $file['name'];
				
				// Prepare and execute file INSERT action
				$stmt = "INSERT INTO documents (doc_title, doc_desc, tmp_name, file_name, file_type, file_size, user_id, user_name, create_date)
						 VALUES (?,?,?,?,?,?,?,?,?)";
						 
				if(!($stmt = $this->db->prepare($stmt))) {
					//sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
					$_SESSION['error'][] = "The file insert instruction failed!  Please see the administrator.";
				}
				if(!($stmt->execute($params))) {
					//sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
					$_SESSION['error'][] = "The file insert instruction failed to execute! Please see the administrator.";
					// Delete the file so that in the case of a query error there will not be a file on the server without a corresponding database reference
					unlink($dest);
				} else {
					// Rename the file and have the _tmp removed from the name so as to distinuish successful from unsuccessful uploads
					$original = USER_DOC_DIR.$tmp_name.'_tmp';
					$dest	  = USER_DOC_DIR.$tmp_name;
					rename($original, $dest);
					return $feedback_file;
				}
			} else {
				// Remove from the directory if INSERT unsuccessful so as not to clutter
				unlink(USER_DOC_DIR.$file['tmp_name']);
				return false;
			}
		} else {
			//return 'FILES !isset';
			//echo '$_FILES was not set';
			return false;
		}
	}
	
	/* Display the file in the browser using the 'Content-Disposition: inline' instruction. 
	 * Much more user-friendly.
	 * Be sure to test this on mobile
	 * $array will contain 'view_doc_id' for passing to db query
	**/
	public function viewFile($array) {
		// Create the query to get the file from the db. Note that tmp_name will match the encrypted file name in user_docs directory
		$stmt = "SELECT tmp_name, file_name, file_type FROM documents WHERE file_id = ?";
		
		//echo 'view_doc_id: '.$array['view_doc_id'];
		
		// Set directory for use below (couldn't get constant to work)
        define("USER_DOC_DIR", '/home/soscompany/public_html/wollard/sys/user_docs/');
		
		// Set $params
		//$params = array($array['file_id']);
		$params = array($array['view_doc_id']);
		
		// Prepare and execute the query
		if(!($stmt = $this->db->prepare($stmt))) {
			//echo 'A query error has occurred!  See the administrator.';
			//sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			$_SESSION['error'][] = "*Error!  The system was unable to compile the viewFile statement.  Please see the administrator.";
			return false;
		}
		if(!($stmt->execute($params))) {
			//echo 'A query error has occurred! Please see the administrator.';
			//sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			$_SESSION['error'][] = "*Error!  The system uas unable to execute the viewFile statement.  Please see the administrator.";
			return false;
		} else {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			//echo 'result: '.var_dump($result);
		}
		
		// Set file type as application/pdf - this is the only file type allowed. $file == encrypted file name. $file_name == actual file name
		$file_mime = $result['file_type'];
		$file_name = $result['file_name'];
		$file	   = USER_DOC_DIR.$result['tmp_name'];
		
		//echo 'file: '.$file.'file_name: '.$file_name.' file_mime: '.$file_mime.' view_doc_id: '.$array['view_doc_id'];
		//exit;
		// Print headers for inline pdf viewing
		
		header('Content-type: '.$file_mime);
		header('Content-Disposition: attachment; filename="'.$file_name.'"');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');
		readfile($file);
		//exit;
	}
	
	// If user issues delete_doc instruction, delete document from db based on file_id
	public function deleteDoc($array) {
		$stmt = "DELETE FROM documents WHERE file_id = ?";
		
		// Add file_id to $params
		$params = array($array['view_doc_id']);
		
		// Get file_name (tmp_name of actual file stored in directory)
		$tmp_name = $array['tmp_name'];
		
		// Prepare and execute the query
		if(!($stmt = $this->db->prepare($stmt))) {
			//echo 'A query error has occurred!  See the administrator.';
			//sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			$_SESSION['error'][] = "*Error! The delete instruction was unable to compile! Please see the administrator.";
			return false;
		}
		if(!($stmt->execute($params))) {
			//echo 'A query error has occurred! Please see the administrator.';
			//sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			$_SESSION['error'][] = "*Error! The delete instruction was unable to execute! Please see the administrator.";
			return false;
		} else {
			// Db DELETE successful.  Now delete document from file folder
			define("USER_DOC_DIR", '/home/soscompany/public_html/wollard/sys/user_docs/');
			//$dir = '/home/soscompany/public_html/wollard/sys/user_docs/';
			unlink(USER_DOC_DIR.$tmp_name);
			// Remove success message, as ajax.inc.php takes care of the message via AJAX
			//$_SESSION['success'][] = "The document was deleted successfully!";
			return true;
		}
	}
	
	/* Update document record in documents table if user submitted document edit form. Note: $_POST values don't need to be passed directly. */
	public function updateDoc() {
		//return 'POST test: '.$_POST['doc_title'].' , '.$_POST['doc_desc'].' , '.$_POST['file_name'].'.pdf , '.$_POST['file_id'];
		$stmt = "UPDATE documents 
				 SET doc_title = ?, doc_desc = ?, file_name = ? WHERE file_id = ?";
				 
		// Add vars to $params. Append '.pdf' string to $_POST['file_name'] as <input> has posfix '.pdf' connected to it
		$params = array($_POST['doc_title'],$_POST['doc_desc'],$_POST['file_name'].".pdf",$_POST['file_id']);
		
		// Prepare and execute the query
		if(!($stmt = $this->db->prepare($stmt))) {
			//echo 'A query error has occurred!  See the administrator.';
			//sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			$_SESSION['error'][] = "*Error!  The system was unable to compile the document update.  Please see the administrator.";
			return false;
		}
		if(!($stmt->execute($params))) {
			//echo 'A query error has occurred! Please see the administrator.';
			//sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			$_SESSION['error'][] = "*Error!  The system was unable to execute the document update instruction.  Please see the administrator.";
			return false;
		} else {
			// Return document file_name so that success message shows user the document which was just edited
			return $_POST['file_name'].'.pdf';
		}
	}
	
	/* Display success or fail msg to user after document form submit action */
	public function getSuccessMsg($array) {
		$html .='
		<div class="row">
			<div class="large-12 columns">
				<p style="color: green; padding: 0; margin: 0;">'.$array['success_msg'].'</p>
			</div>
		</div>';
		return $html;
	}
}