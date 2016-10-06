<?php
class ProfitAnalysisInfo extends DB_Connect {
	
	public function __construct($dbo=NULL) {
		parent::__construct($dbo);
	}
	
	// Generate main page (worksheet) heading for use by both worksheet tabs
	public function getPageHeading() {
		$html = '<div class="row">
					<div class="col-sm-12 col-md-8 col-lg-8">';
		if(!isset($_SESSION['profit_dlr_id'])) {
				$html .='<h3>Profitability Analysis: 
							<span style="font-size: 15px;">(Select a new or existing worksheet to begin)</span> 
		 		 		 </h3>
		 		 	</div>';
		} else {
				$html .='<h3 class="main_profit_title">Profitability Analysis: '.$_SESSION['profit_dlr_name'].' ('.$_SESSION['profit_dlr_code'].') 
							<span style="color: rgb(9, 114, 165); font-size: 19px;">'.$_SESSION['profit_date_display'].'</span>
						 </h3>
					</div> 
					<div class="col-sm-12 col-md-4 col-lg-4">
						<p class="right_align" style="font-size: 15px;"> Created By: '.$_SESSION['profit_user'];
						// Show worksheet privacy status, and build update <div> for changed status verbage
						if($_SESSION['user']['type_id'] == 1) {
							$private_status = ($_SESSION['profit_privacy'] == 1) ? 'Public' : 'Private';
							$html .= ' <span class="privacy_pipe">|</span>
										<a href="" data-toggle="modal" data-target="#privacy_modal" style="color: rgb(9, 114, 165);">
											<span class="privacy_update">
								 	   		'.$private_status.'
								 	   		</span>
							             </a>';
						}
						$html .='
					   </p>
			 		</div>';
		}
		$html .='</div>';
		return $html;
	}
	
	/* Get data from profit_daily_info table for display of 'Express Service Data' table
	 * Note: user_id is retrived for purposes of setting $_SESSION['user_delete_okay'] global var in buildProfitTables() method,
	 * which determines the display of the 'Remove' button on the 'Profit Analysis' tab
	**/
	public function getExpressTableData($dealer_record_id, $record_date) {
		$sql = "SELECT a.days_open_week, a.ros_per_day, a.user_id, b.user_uname
				FROM profit_daily_info a
				LEFT JOIN users b ON(a.user_id = b.user_id)
				WHERE a.dealer_record_id = :dealer_record_id AND a.record_date = :record_date";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
			$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		} catch (Exception $e) {
			die($e->getMessage());
		}
		return $results;
	}
	
	public function getAdvisorTableData($dealer_record_id, $record_date) {
		$sql = "SELECT a.adv_hours, a.adv_rate, b.spiff_rate
			    FROM profit_adv_cost a
				LEFT JOIN profit_adv_spiff b ON(a.record_date = b.record_date AND a.dealer_record_id = b.dealer_record_id)
				WHERE a.dealer_record_id = :dealer_record_id AND a.record_date = :record_date
				ORDER BY a.adv_cost_id";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
			$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			//echo 'var_dump of getAdvisorTableData() method: '.var_dump($results);
			$stmt->closeCursor();
			// Return a table object
			//$table = new ProfitAdvisorTable($results);
		} catch (Exception $e) {
			die($e->getMessage());
		}
		return $results;
	}
	
	public function getTechTableData($dealer_record_id, $record_date) {
		$sql = "SELECT a.tech_hours, a.tech_rate, b.spiff_rate
			    FROM profit_tech_cost a
				LEFT JOIN profit_tech_spiff b ON(a.record_date = b.record_date AND a.dealer_record_id = b.dealer_record_id)
				WHERE a.dealer_record_id = :dealer_record_id AND a.record_date = :record_date
				ORDER BY a.tech_cost_id";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
			$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			// Return a table object
			//$table = new ProfitTechTable($results);
		} catch (Exception $e) {
			die($e->getMessage());
		}
		return $results;
	}
	
	public function getUnitTableData($dealer_record_id, $record_date) {
		$sql = "SELECT a.svc_id, b.svc_name, a.pen_percent, a.labor_sale, a.parts_sale, a.parts_cost
				FROM profit_svc_data a
				LEFT JOIN services b ON(a.svc_id = b.svc_id)
				WHERE a.dealer_record_id = :dealer_record_id and a.record_date = :record_date
				ORDER BY a.svc_data_id";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
			$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		} catch (Exception $e) {
			die($e->getMessage());
		}
		return $results;
	}
	
	/* Process form data from all tables
	 * @param array an array of form submission elements
	 */
	public function processProfitTableInput() {
		/*
		if (!isset($_POST['profit_analysis_entry'])) { // This is the submit button 'name' element
			$_SESSION['error'][] = 'You tried to access an unauthorized location!';
			return false;
		}*/
		
		// Check to see if profit SESSION vars are set.  If not, deny input and return error message
		if(!isset($_SESSION['profit_dlr_id']) &&!isset($_SESSION['profit_date'])) {
			return 'wksht_not_set';
			//$_SESSION['error'][] = 'You must first create a new worksheet before proceeding!';
		} else {
			$dealer_record_id = $_SESSION['profit_dlr_id'];
			$record_date = $_SESSION['profit_date'];
			$user_id = $_SESSION['user']['id'];
			$public_access = $_SESSION['profit_privacy'];
		}
		
		// Instantiate InputValidation class to validate form input
		$isvalid = new InputValidation();
		
		$days_week = htmlentities($_POST['days_week'], ENT_QUOTES);
		if (!$isvalid->validWholeNumber($days_week)) {
			$_SESSION['error'][] = 'You entered an invalid number of weekdays!';
		}
		
		$ros_per_day = htmlentities($_POST['ros_per_day'], ENT_QUOTES);
		if (!$isvalid->validWholeNumber($ros_per_day)) {
			$_SESSION['error'][] = 'You entered an invalid number of daily ROs!';
		}
		
		foreach ($_POST['adv_hours'] as $hours) {
			if (!$isvalid->validDecimal($hours)) {
				$_SESSION['error'][] = 'You entered an invalid number of advisor hours!';
			}
			$adv_hours[] = htmlentities($hours, ENT_QUOTES);
		}
		
		foreach ($_POST['adv_rate'] as $rate) {
			if (!$isvalid->validDollarValue($rate) || $rate == '') {
				$_SESSION['error'][] = 'You entered an invalid advisor rate!';
			}
			$adv_rate[] = htmlentities($rate, ENT_QUOTES);
			
		}
		
		$adv_spiff = $_POST['adv_spiff'];
		if (!$isvalid->validDollarValue($adv_spiff) || $adv_spiff == '') {
			$_SESSION['error'][] = 'You entered an invalid advisor spiff rate!';
		}
			
		foreach ($_POST['tech_hours'] as $hours) {
			if (!$isvalid->validDecimal($hours)) {
				$_SESSION['error'][] = 'You entered an invalid number of tech hours!';
			}
			$tech_hours[] = htmlentities($hours, ENT_QUOTES);
		}
		
		foreach ($_POST['tech_rate'] as $rate) {
			if (!$isvalid->validDollarValue($rate) || $rate == '') {
				$_SESSION['error'][] = 'You entered an invalid tech rate!';
			}
			$tech_rate[] = htmlentities($rate, ENT_QUOTES);
			
		}
		
		$tech_spiff = $_POST['tech_spiff'];
		if (!$isvalid->validDollarValue($tech_spiff) || $tech_spiff == '') {
			$_SESSION['error'][] = 'You entered an invalid tech spiff rate!';
		}
		
		foreach ($_POST['pen_input'] as $pen) {
			if ($pen >= 100) {
				$_SESSION['error'][] = 'You entered a pen % value greater than 100%!';
			}
			if (!$isvalid->validPercentage($pen)) {
				$_SESSION['error'][] = 'You entered an invalid pen percentage!';
			}
			$pen_input[] = htmlentities($pen, ENT_QUOTES);
		}
		
		foreach ($_POST['labor_sale'] as $labor) {
			if (!$isvalid->validDollarValue($labor) || $labor == '') {
				$_SESSION['error'][] = 'You entered an invalid labor sales rate!';
			}
			$labor_sale[] = htmlentities($labor, ENT_QUOTES);
		}
		
		foreach ($_POST['parts_sale'] as $part_sale) {
			if (!$isvalid->validDollarValue($part_sale) || $part_sale == '') {
				$_SESSION['error'][] = 'You entered an invalid parts sales rate!';
			}
			$parts_sale[] = htmlentities($part_sale, ENT_QUOTES);
		}
		foreach ($_POST['parts_cost'] as $part_cost) {
			if (!$isvalid->validDollarValue($part_cost) || $part_cost == '') {
				$_SESSION['error'][] = 'You entered an invalid parts cost rate!';
			}
			$parts_cost[] = htmlentities($part_cost, ENT_QUOTES);
		}
		
		// If there were any errors, return false
		if (!empty($_SESSION['error'])) {
			return false;
		}
		
		// Echo values for testing purposes
		/*
		echo '$days_week: '.$days_week.'<br> $ros_per_day: '.$ros_per_day.'<br>';
		foreach ($adv_hours as $hours) {
			echo '$adv_hours: '.$hours.'<br>';
		}
		foreach ($adv_rate as $rate) {
			echo '$adv_rate: '.$rate.'<br>';
		}
		echo '$adv_spiff: '.$adv_spiff.'<br>';
		foreach ($tech_hours as $hours) {
			echo '$tech_hours: '.$hours.'<br>';
		}
		foreach ($tech_rate as $rate) {
			echo '$tech_rate: '.$rate.'<br>';
		}
		echo '$tech_spiff: '.$tech_spiff.'<br>';
		foreach ($pen_input as $pen) {
			echo '$pen_input: '.$pen.'<br>';
		}
		foreach ($labor_sale as $labor) {
			echo '$labor_sale: '.$labor.'<br>';
		}
		foreach ($parts_sale as $parts) {
			echo '$parts_sale: '.$parts.'<br>';
		}
		foreach ($parts_cost as $cost) {
			echo '$parts_cost: '.$cost.'<br>';
		}*/
		
		// Check to see if records already exist
		/*
		if($this->checkTableData($_SESSION['profit_dlr_id'], $_SESSION['profit_date'])) {
			// If values are returned (record already exists for $dealer_record_id and $record_date), delete all profit records and re-insert
			$sql = "DELETE * FROM profit_daily_info a 
					LEFT JOIN profit_adv_cost b ON(a.dealer_record_id = b.dealer_record_id)
					LEFT JOIN profit_adv_spiff c ON(b.dealer_record_id = c.dealer_record_id)
					LEFT JOIN profit_tech_cost d ON(c.dealer_record_id = d.dealer_record_id)
					LEFT JOIN profit_tech_spiff e ON(d.dealer_record_id = e.dealer_record_id)
					LEFT JOIN profit_svc_data f ON(e.dealer_record_id = f.dealer_record_id)
					WHERE a.dealer_record_id = :dealer_record_id AND a.record_date = :record_date";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
				$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
				$stmt->execute();
				$stmt->closeCursor();
			} catch (Exception $e) {
				die($e->getMessage());
			}
		}*/
		/*
		if($this->checkTableData($_SESSION['profit_dlr_id'], $_SESSION['profit_date'])) {
			// If values are returned (record already exists for $dealer_record_id and $record_date), delete all profit records and re-insert
			$sql = "DELETE profit_daily_info, profit_adv_cost, profit_adv_spiff, profit_tech_cost, profit_tech_spiff, profit_svc_data 
					FROM profit_daily_info a 
					INNER JOIN profit_adv_cost b ON(a.dealer_record_id = b.dealer_record_id)
					INNER JOIN profit_adv_spiff c ON(b.dealer_record_id = c.dealer_record_id)
					INNER JOIN profit_tech_cost d ON(c.dealer_record_id = d.dealer_record_id)
					INNER JOIN profit_tech_spiff e ON(d.dealer_record_id = e.dealer_record_id)
					INNER JOIN profit_svc_data f ON(e.dealer_record_id = f.dealer_record_id)
					WHERE a.dealer_record_id = :dealer_record_id AND a.record_date = :record_date";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
				$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
				var_dump($stmt);
				die();
				$stmt->execute();
				$stmt->closeCursor();
			} catch (Exception $e) {
				die($e->getMessage());
			}
		}*/
		
		/* Note: if table data already exists, delete records before running INSERT statements.  
		 * If delete fails, return false and do not run INSERT statements.
		 * INSERTs will run in both cases: a) records already exist and DELETEs are successful, or v) a new worksheet is being saved
		**/
		if($this->checkTableData($_SESSION['profit_dlr_id'], $_SESSION['profit_date'])) {
			if(!$this->deleteProfitData()) {
				return false;
			}
		}
		
		// If entry is a new entry, OR original data was just deleted, now proceed with INSERT statements
		$sql = "INSERT INTO profit_daily_info (dealer_record_id, record_date, days_open_week, ros_per_day, public_access, create_date, user_id)
				VALUES (:dealer_record_id, :record_date, :days_week, :ros_per_day, :public_access, NOW(), :user_id)";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
			$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
			$stmt->bindParam(":days_week", $days_week, PDO::PARAM_INT);
			$stmt->bindParam(":ros_per_day", $ros_per_day, PDO::PARAM_INT);
			$stmt->bindParam(":public_access", $public_access, PDO::PARAM_INT);
			$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
			$stmt->execute();
			$stmt->closeCursor();
		} catch (Exception $e) {
			die($e->getMessage());
		}
		
		// Insert or update profit_adv_cost table
		$sql = "INSERT INTO profit_adv_cost (dealer_record_id, record_date, adv_hours, adv_rate, create_date, user_id)
				VALUES (:dealer_record_id, :record_date, :adv_hours, :adv_rate, NOW(), :user_id)";
		$stmt = $this->db->prepare($sql);
		for ($i=0; $i<sizeof($adv_hours); $i++) {
			try {
				$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
				$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
				$stmt->bindParam(":adv_hours", $adv_hours[$i], PDO::PARAM_INT);
				$stmt->bindParam(":adv_rate", $adv_rate[$i], PDO::PARAM_INT);
				$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
				$stmt->execute();
				$stmt->closeCursor();
			} catch (Exception $e) {
				die($e->getMessage());
			}
		}
		
		// Insert or update profit_adv_spiff table
		$sql = "INSERT INTO profit_adv_spiff (dealer_record_id, record_date, spiff_rate, create_date, user_id)
				VALUES (:dealer_record_id, :record_date, :spiff_rate, NOW(), :user_id)";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
			$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
			$stmt->bindParam(":spiff_rate", $adv_spiff, PDO::PARAM_INT);
			$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
			$stmt->execute();
			$stmt->closeCursor();
		} catch (Exception $e) {
			die($e->getMessage());
		}
		
		// Insert or update profit_tech_cost table
		$sql = "INSERT INTO profit_tech_cost (dealer_record_id, record_date, tech_hours, tech_rate, create_date, user_id)
				VALUES (:dealer_record_id, :record_date, :tech_hours, :tech_rate, NOW(), :user_id)";
		$stmt = $this->db->prepare($sql);
		for ($i=0; $i<sizeof($tech_hours); $i++) {
			try {
				$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
				$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
				$stmt->bindParam(":tech_hours", $tech_hours[$i], PDO::PARAM_INT);
				$stmt->bindParam(":tech_rate", $tech_rate[$i], PDO::PARAM_INT);
				$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
				$stmt->execute();
				$stmt->closeCursor();
			} catch (Exception $e) {
				die($e->getMessage());
			}
		}
		
		// Insert or update profit_tech_spiff table
		$sql = "INSERT INTO profit_tech_spiff (dealer_record_id, record_date, spiff_rate, create_date, user_id)
				VALUES (:dealer_record_id, :record_date, :spiff_rate, NOW(), :user_id)";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
			$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
			$stmt->bindParam(":spiff_rate", $tech_spiff, PDO::PARAM_INT);
			$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
			$stmt->execute();
			$stmt->closeCursor();
		} catch (Exception $e) {
			die($e->getMessage());
		}
		
		// Insert or update profit_svc_data table
		// NOTE that :svc_id needs to be revisited for different cases etc.
		$sql = "INSERT INTO profit_svc_data (dealer_record_id, record_date, svc_id, pen_percent, labor_sale, parts_sale, parts_cost, create_date, user_id)
				VALUES (:dealer_record_id, :record_date, :svc_id, :pen_percent, :labor_sale, :parts_sale, :parts_cost, NOW(), :user_id)";
		$stmt = $this->db->prepare($sql);
		for ($i=0, $u=1; $i<sizeof($pen_input); $i++, $u++) {
			$pen_input[$i] = ($pen_input[$i]/100); // Divide pen_input by 100 so user may enter whole numbers
			try {		
				$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
				$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
				$stmt->bindParam(":svc_id", $u, PDO::PARAM_INT);
				$stmt->bindParam(":pen_percent", $pen_input[$i], PDO::PARAM_INT);
				$stmt->bindParam(":labor_sale", $labor_sale[$i], PDO::PARAM_INT);
				$stmt->bindParam(":parts_sale", $parts_sale[$i], PDO::PARAM_INT);
				$stmt->bindParam(":parts_cost", $parts_cost[$i], PDO::PARAM_INT);
				$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
				$stmt->execute();
				$stmt->closeCursor();
			} catch (Exception $e) {
				die($e->getMessage());
			}
		}
	}
	
	// Update profit_daily_info table. Used for privacy update submit.
	public function updateProfitDailyInfoTable($array) {
		if($this->checkTableData($_SESSION['profit_dlr_id'], $_SESSION['profit_date'])) {
			$sql = "UPDATE profit_daily_info SET public_access = ? WHERE dealer_record_id = ? AND record_date = ?";
			$params = array($array['privacy'], $_SESSION['profit_dlr_id'], $_SESSION['profit_date']);
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute($params);
				$stmt->closeCursor();
				return true;
			} catch (Exception $e) {
				die($e->getMessage());
			}
		} else {
			return null;
		}
	}
	
	/* Delete data from profit tables if user updates info or selects to 'Remove' worksheet
	 * Note: $array contains: 'dealer_record_id' and 'record_date'
	**/
	public function deleteProfitData() {
		// Set params for dealer_record_id and record_date
		$params = array($_SESSION['profit_dlr_id'], $_SESSION['profit_date']);
		
		// Delete record from profit_daily_info 
		$sql = "DELETE FROM profit_daily_info WHERE dealer_record_id = ? AND record_date = ?";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			$stmt->closeCursor();
			$status = true;
		} catch (Exception $e) {
			$status = false;
			die($e->getMessage());
		}
		
		// Delete records from profit_adv_cost
		$sql = "DELETE FROM profit_adv_cost WHERE dealer_record_id = ? AND record_date = ?";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			$stmt->closeCursor();
			$status = true;
		} catch (Exception $e) {
			$status = false;
			die($e->getMessage());
		}
		
		// Delete record from profit_adv_spiff
		$sql = "DELETE FROM profit_adv_spiff WHERE dealer_record_id = ? AND record_date = ?";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			$stmt->closeCursor();
			$status = true;
		} catch (Exception $e) {
			$status = false;
			die($e->getMessage());
		}
		
		// Delete records from profit_tech_cost
		$sql = "DELETE FROM profit_tech_cost WHERE dealer_record_id = ? AND record_date = ?";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			$stmt->closeCursor();
			$status = true;
		} catch (Exception $e) {
			$status = false;
			die($e->getMessage());
		}
		
		// Delete record from profit_tech_spiff
		$sql = "DELETE FROM profit_tech_spiff WHERE dealer_record_id = ? AND record_date = ?";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			$stmt->closeCursor();
			$status = true;
		} catch (Exception $e) {
			$status = false;
			die($e->getMessage());
		}
		
		// Delete records from profit_svc_data
		$sql = "DELETE FROM profit_svc_data WHERE dealer_record_id = ? AND record_date = ?";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			$stmt->closeCursor();
			$status = true;
		} catch (Exception $e) {
			$status = false;
			die($e->getMessage());
		}
		return $status;
	}
	
	/**Test to see if dealer and date combination already has records**/
	public function checkTableData($dealer_record_id, $record_date) {
		try {
			$sql = "SELECT dealer_record_id, record_date FROM profit_daily_info WHERE dealer_record_id = :dealer_record_id AND record_date = :record_date";
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":dealer_record_id", $dealer_record_id, PDO::PARAM_INT);
			$stmt->bindParam(":record_date", $record_date, PDO::PARAM_STR);
			$stmt->execute();
			$results = $stmt->fetch(PDO::FETCH_ASSOC); // Note that fetch will return an array of two items if record already exists.  fetchAll with return an array(1) of an array of two items.
			$stmt->closeCursor();
		} catch (Exception $e) {
			die($e->getMessage());
		}
		return $results; // Note that if query is successful, the function will return true by default.  If not, it will return false by default.  Therefore, no need to say if....return true etc.
	}
	
	// Process 'New Worksheet' menu selection from profitability side menu.  Sets profitability SESSION variables
	public function processNewProfitWorksheet() {
		// Make sure method is accessed correctly
		if (!isset($_POST['action']) || $_POST['action'] != 'new_profit_worksheet') {
			$_SESSION['error'][] = 'You tried to access an unauthorized location!';
			return false;
		}
		
		// Instantiate InputValidation class to validate form input for date
		$isvalid = new InputValidation();
		
		/* Process dealer selection, separating three values into array elements using ',' as delimiter.  
		 * No validation necessary. 
		 * [0] = dealer_record_id, [1] = dealer_code, [2] = dealer_name.
		 */
		$dealer_info = explode(',', $_POST['dlr_select']);
		$dealer_record_id = $dealer_info[0];
		$dealer_code = $dealer_info[1];
		$dealer_name = $dealer_info[2];
		$dealer_team_id = $dealer_info[3];
		
		// Process date selection.  Validation format is mm/dd/yyyy
		$display_date = htmlentities($_POST['date_select'], ENT_QUOTES);
		if (!$isvalid->validDate($display_date)) {
			$_SESSION['error'][] = 'You entered an invalid date!';
		}
		
		// Date must be converted from dd/mm/yyy format to yyyy-mm-dd format for SQL compatibility
		$datetime = new DateTime($display_date);
		$sql_date = $datetime->format('Y-m-d');
		
		/* Process privacy setting from <select> dropdown, if the POST has been set (will not be set if user != SOS user)
		 * If there is no POST, make default be public (1 value)
		 * This global will be used throughout the editing of the current worksheet
		 * Do not forget to set if user also chooses existing profit worksheet from 'Open Worksheet' menu
		**/
		if(isset($_POST['profit_privacy'])) {
			$privacy = $_POST['profit_privacy'];
		} else {
			$privacy = 1; // Default privacy of public (1 == public, 0 == private) for public_access db field
		}
		
		// Set SESSION variables for profitability worksheet
		$_SESSION['profit_dlr_id'] = $dealer_record_id;
		$_SESSION['profit_dlr_name'] = $dealer_name;
		$_SESSION['profit_dlr_code'] = $dealer_code;
		$_SESSION['profit_date_display'] = $display_date;
		$_SESSION['profit_date'] = $sql_date;
		$_SESSION['profit_user'] = $_SESSION['user']['uname']; // This will be used for 'Created By: ' heading
		$_SESSION['profit_privacy'] = $privacy;
		$_SESSION['svc_manuf_id'] = $dealer_team_id;
	}
	
	/**Process 'View Worksheet' submit from profitability side menu.  Sets profitability worksheet SESSION variables**/
	public function processExistingProfitWorksheet() {
		// Make sure method is accessed correctly
		if (!isset($_POST['action']) || $_POST['action'] != 'existing_profit_worksheet') {
			$_SESSION['error'][] = 'You tried to access an unauthorized location!';
			return false;
		}
		
		/* Process dealer selection, separating three values into array elements using ',' as delimiter.
		 * [0] = dealer_record_id, [1] = dealer_code, [2] = dealer_name, [3] = sql date, [4] = display date [5] = public_access
		 */
		$dealer_info = explode(',', $_POST['dlr_select']);
		$dealer_record_id = $dealer_info[0];
		$dealer_code = $dealer_info[1];
		$dealer_name = $dealer_info[2];
		$sql_date = $dealer_info[3];
		$display_date = $dealer_info[4];
		$privacy = $dealer_info[5];
		$dealer_team_id = $dealer_info[6];
		
		// Set SESSION variables for profitability worksheet
		$_SESSION['profit_dlr_id'] = $dealer_record_id;
		$_SESSION['profit_dlr_name'] = $dealer_name;
		$_SESSION['profit_dlr_code'] = $dealer_code;
		$_SESSION['profit_date_display'] = $display_date;
		$_SESSION['profit_date'] = $sql_date;
		$_SESSION['profit_privacy'] = $privacy;
		$_SESSION['svc_manuf_id'] = $dealer_team_id;
	}
	
	/* Get a list of selectable existing worksheets for display in left nav menu
	 * Note: SOS team 'All' may see all existing worksheets
	 * SOS manufacturer-specific team may see all worksheets under their manufacturer regardless of public_access status
	 * Dealer users may only see worksheets associated with their dealer and with public_access == 1(true)
	**/
	public function getExistingWorksheets() {
		try {
			$sql = "SELECT a.dealer_record_id, a.record_date, b.dealer_code, b.dealer_name, b.dealer_team_id, a.public_access, c.user_uname
				    FROM profit_daily_info a
					LEFT JOIN dealers b ON(a.dealer_record_id = b.dealer_record_id)
					LEFT JOIN users c ON(a.user_id = c.user_id)";
					
			$params = array();
					
			// If user is anything other than SOS team 'All', limit the records to their team
			if($_SESSION['user']['team_id'] > 0) {	
				$sql .= " WHERE b.dealer_team_id = ?";
				$params[] = $_SESSION['user']['team_id'];
			}
			
			/* If user is dealer user, restrict records to all records which have their dealer_record_id and public status == 1(true)
			 * Note: static 'AND' operator is okay given that user type 3 will always have the 'WHERE' clause from above applied to query
			**/
			if($_SESSION['user']['type_id'] == 3) {
				$sql .= " AND a.dealer_record_id = ? AND a.public_access = ? ";
				$params[] = $_SESSION['user']['dealer_record_id'];
				$params[] = 1;
			}
						
			$sql .= " ORDER BY b.dealer_name ASC";
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		} catch (Exception $e) {
			die($e->getMessage());
		}
		return $results;
	}
	
	/* Build All Profit Tables
	 * Note: $_SESSION['user_delete_okay'] determines display of 'Remove' button on 'Profit Analysis' tab
	**/
	public function buildProfitTables() {
		/* Initialize arrays and set initial values == 0 in case it is the first time form is being loaded,
		 * or table data for dealer_record_id and profit_date does not yet exist.  
		 * If table data does exist (checkTableData == true), load values for all tables
		 * Note: $_SESSION['user_delete_okay'] value for 'Remove' button is set inside of this method
		**/
		// Express table
		$days_week = 0;
		$ros_per_day = 0;
		
		// Advisor table
		$advisor_data = array(0);
		$adv_hours[0] = 0;
		$adv_rate[0] = 0;
		$advisor_weekly_cost = 0;
		
		// Tech table
		$tech_data = array(0);
		$tech_hours[0] = 0;
		$tech_rate[0] = 0;
		$tech_weekly_cost = 0;
		
		// Set advisor & tech spiff rates to 0 for first-time worksheet auto-fill
		$adv_spiff_rate[0] = 0;
		$tech_spiff_rate[0] = 0;
		
		/* Build defaults for unit tables in case no table data is supplied by using the 'services' table as a base.  
		 * Remember, you must pass service id's to use for INSERT statement
		 */
		$service_data = new ServicesInfo;
		$service_data = $service_data->getServicesTableData($id_list = NULL); // Note that the sizeof($service_data) == 8
		$unit_data = array();
		$i=0;
		foreach ($service_data as $value) { 
			// Set all unit table default values upon page initialization
			$unit_data[$i]['svc_name'] = $value['svc_name'];
			$unit_data[$i]['svc_id'] = $value['svc_id'];
			$unit_data[$i]['pen_percent'] = 0;
			$unit_data[$i]['labor_sale'] = 0;
			$unit_data[$i]['parts_sale'] = 0;
			$unit_data[$i]['parts_cost'] = 0;
			$total_sale[$i] = 0;
			$total_cost[$i] = 0;
			$net_parts[$i] = 0;
			$net_total[$i] = 0;
			$total_units[$i] = 0;
			$total_daily_sale[$i] = 0;
			$total_gross[$i] = 0;
			$i++;
		}
		// Set Summary table data initialization values
		$cum_daily_sales 	= 0;
		$cum_daily_gross 	= 0;
		$avg_sale_proceeds 	= 0;
		$avg_net_proceeds 	= 0;
		
		/* Check to see if dealer and date combo already exists in DB table.  
		 * If so, will need to retrieve data from tables.
		 * If not, will need to bypass table queries and create tables dynamically based on 'services' table
		 */
		if ((isset($_SESSION['profit_dlr_id']) && isset($_SESSION['profit_date'])) || (isset($_SESSION['export_dlr_id']) && isset($_SESSION['export_date']))) {
			if (isset($_SESSION['export_dlr_id'])) {
				$dealer_record_id = $_SESSION['export_dlr_id'];
				$record_date = $_SESSION['export_date'];
			} else {
				$dealer_record_id = $_SESSION['profit_dlr_id'];
				$record_date = $_SESSION['profit_date'];
			}	
			// Run query on profit_daily_info table to verify if records already exist or not.  If so, proceed with data retrieval.
			if ($this->checkTableData($dealer_record_id, $record_date)) {
				
				// Build express table
				$express_data = $this->getExpressTableData($dealer_record_id, $record_date);
				foreach($express_data as $value) {
					$days_week 	 = $value['days_open_week'];
					$ros_per_day = $value['ros_per_day'];
					$user_id	 = $value['user_id'];
					$user_name   = $value['user_uname'];
				}
				
				// Set global var for profit user display name
				$_SESSION['profit_user'] = $user_name;
				
				/* Set $_SESSION['user_delete_okay'] var based on value of $user_id.  
				 * Will determine visibility of 'Remove' button
				**/
				if($user_id == $_SESSION['user']['id']) {
					//echo 'user == user';
					$_SESSION['user_delete_okay'] = true;
				} else {
					//echo 'user != user';
					$_SESSION['user_delete_okay'] = false;
				}
				//$_SESSION['user_delete_okay'] = ($user_id == $_SESSION['user']['id']) ? true : false;
				
				// Build Advisor table
				$advisor_data = $this->getAdvisorTableData($dealer_record_id, $record_date);
				$i = 0;
				foreach ($advisor_data as $value) {
					$adv_hours[$i] = $value['adv_hours'];
					$adv_rate[$i]  = $value['adv_rate'];
					$adv_spiff_rate[$i]= $value['spiff_rate'];
					$i++;
				}

				// Build Tech table
				$tech_data = $this->getTechTableData($dealer_record_id, $record_date);
				$i=0;
				foreach ($tech_data as $value) {
					$tech_hours[$i] = $value['tech_hours'];
					$tech_rate[$i] = $value['tech_rate'];
					$tech_spiff_rate[$i] = $value['spiff_rate'];
					$i++;
				}

				// Build Unit tables
				$unit_data = $this->getUnitTableData($dealer_record_id, $record_date);
				// Get Service Unit Data
				for ($i=0; $i<sizeof($unit_data); $i++) {
					$total_sale[$i]  	  = ($unit_data[$i]['labor_sale'] + $unit_data[$i]['parts_sale']);
					$net_parts[$i] 	 	  = ($unit_data[$i]['parts_sale'] - $unit_data[$i]['parts_cost']);
					$net_total[$i] 	 	  = ($unit_data[$i]['labor_sale'] + $net_parts[$i]);
					$total_units[$i] 	  = ($unit_data[$i]['pen_percent'] * $ros_per_day);
					$total_daily_sale[$i] = ($total_sale[$i] * $total_units[$i]);
					$total_gross[$i] 	  = ($net_total[$i] * $total_units[$i]);
					
					/** Cumulative values **/
					// Get total daily sale
					$cum_daily_sales += $total_daily_sale[$i];
					// Get total daily gross
					$cum_daily_gross += $total_gross[$i];
					// Get average sale proceeds
					$avg_sale_proceeds += ($total_sale[$i] * $unit_data[$i]['pen_percent']);
					// Get average net proceeds
					$avg_net_proceeds += ($net_total[$i] * $unit_data[$i]['pen_percent']);
				}
				$total_daily_test = ($total_sale[0] * $unit_data[0]['pen_percent']);
			} else {
				/* If no profit worksheets exist for selected dealer and date, 
				 * set global var to true so that 'Remove' button is visible to user.
				 * This is allowable as the current user is creating this new worksheet
				 * and it will be recorded under their user_id.
				 * Also set $created_by var to null so that string is not displayed.
				**/
				$_SESSION['user_delete_okay'] = true;
				//$created_by = null;
				
			}
		}	
		
		// Build Express table html
		// Calculate RO values for express table
		$weekly_ros = ($ros_per_day * $days_week);
		$annual_ros = ($weekly_ros * 52);
		$monthly_ros= number_format(($annual_ros / 12),0);
		
		$html =
	   '<div class="profit_page"><!-- div for replacing page content via AJAX -->'
	   .$this->getPageHeading();
	   
		$html .='
		<div class="row">
			<div class="col-sm-12">
				<form id="profit_analysis_form" action="assets/inc/process.inc.php" method="POST">
				<div class="row">
					<div class="col-sm-12">
						<div class="row">
							<div class="col-sm-12 col-md-8 col-lg-8">
								<p class="section_title">Operations Data:</p>
							</div>
							<div class="col-sm-12 col-md-4 col-lg-4">
								<p class="printer_p">';
							     if(isset($_SESSION['profit_dlr_id'])) {
								   $html .='
								 	<a href="export_profit_calc.php">
								 		<span class="glyphicon glyphicon-download" style="color: green;" aria-hidden="true"></span>
								 	</a>
								 	&nbsp;';
								 }
								 	$html .='
									<a href="" onclick="window.print();">
										<span class="glyphicon glyphicon-print" aria-hidden="true"></span>
									</a> &nbsp; 
									<input type="submit" class="btn btn-primary btn-xs form_submit profit_button" id="profit_analysis_entry" name="profit_analysis_entry" value="Refresh" />';
								// Only show the 'Remove' button if there is a current worksheet being displayed
								if(isset($_SESSION['profit_dlr_id']) && $_SESSION['user_delete_okay'] == true) {
									$html .='
									<input type="submit" class="btn btn-danger btn-xs form_submit profit_button" id="delete_profit_wksht" name="delete_profit_wksht" value="Remove" />';
								}
								$html .='
								</p>
							</div>
						</div>
						<hr>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<div class="table-responsive">
							<table class="table table-hover express_table">
								<thead>
									<tr>
										<th colspan="5" class="bg-blue">Express Service Data</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td class="text-underline">Days Open Per Week</td>
										<td class="text-underline">Daily ROs</td>
										<td class="text-underline">Weekly ROs</td>
										<td class="text-underline">Monthly ROs</td>
										<td class="text-underline">Annual ROs</td>
									</tr>
									<tr>
										<td><input type="text" id="days_week" name="days_week" value="'.$days_week.'" class="unit_input form-control"/></td>
										<td><input type="text" id="ros_per_day" name="ros_per_day" value="'.$ros_per_day.'" class="unit_input form-control"/></td>
										<td>'.$weekly_ros.'</td>
										<td>'.$monthly_ros.'</td>
										<td>'.number_format($annual_ros,0).'</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<hr>
					</div>
				</div>';
		// Build Express Table csv output
		$output = "";
		$output .= "Express Service Data: ";
		$output .= "\n";
		$output .= "Days Open Per Week ,"."Daily ROs ,"."Weekly ROs,"."Monthly ROs,"."Annual ROs\n";
		$output .= $days_week.",".$ros_per_day.",".$weekly_ros.",".$monthly_ros.",".$annual_ros."\n\n";
		
		// Build Advisor table html and csv output
		$output .= "Advisor Cost Data: \n";
		$output .= "Personnel,"."Hours,"."Hourly Rate,"."Total\n";
		$html .=
			'<div class="row">
				<div class="col-sm-12 col-md-12 col-lg-12">
					<div class="row">
						<div class="col-sm-12 col-md-12 col-lg-6">
							<table class="table table-hover advisor_cost_table">
								<thead>
									<tr>
										<th colspan="5"><span class="red_label"> Advisor </span> Cost Schedule &nbsp; <a id="add_advisor_row" class="add_row_link">Add Row</a></th>
									</tr>
								</thead>
								<tbody id="advisor_tbody">
									<tr>
										<td>	 </td>
										<td>	 </td>
										<td class="text-underline">Hours</td>
										<td class="text-underline">Hourly Rate</td>
										<td class="text-underline">Total</td>
									</tr>';
									for ($i=0; $i<sizeof($advisor_data); $i++) {
										$advisor_weekly_cost += ($adv_hours[$i] * $adv_rate[$i]);
										$html .=
										'<tr>
											<td>Advisor: </td>
											<td>
												<span class="glyphicon glyphicon-minus-sign" id="" name="" aria-hidden="true"></span>
											</td>
											<td><input class="unit_input form-control" id="adv_hours[]" name="adv_hours[]" type="text" value="'.$adv_hours[$i].'"/></td>
											<td><input class="unit_input form-control" id="adv_rate[]" name="adv_rate[]" type="text" value="'.$adv_rate[$i].'"/></td>
											<td>$'.number_format(($adv_hours[$i] * $adv_rate[$i]),2).'</td>
										</tr>';
										$output .= "Advisor ".($i+1).": ,".$adv_hours[$i].", ".$adv_rate[$i].", ".($adv_hours[$i] * $adv_rate[$i])."\n";
									}
									$advisor_weekly_cost += $adv_spiff_rate[0];
								$output .= "Spiff: ,".$adv_spiff_rate[0]."\n\n";
								$html .=
							'</tbody>
								<tfoot>
									<tr class="bg-slate">
										<td>Spiff: </td>
										<td> </td>
										<td><input type="text" class="unit_input form-control" id="adv_spiff" name="adv_spiff" value="'.$adv_spiff_rate[0].'" /></td>
										<td> </td>
										<td> </td>
									</tr>
								</tfoot>
							</table>
						</div>'; 
		
		// Build Tech table html and csv output
		$output .= "Tech Cost Data: \n";
		$output .= "Personnel,"."Hours,"."Hourly Rate,"."Total\n";
		$html .=
					'<div class="col-sm-12 col-md-12 col-lg-6">
						<table class="table table-hover tech_cost_table">
							<thead>
								<tr>
									<th colspan="5"><span class="red_label"> Tech </span> Cost Schedule &nbsp; <a id="add_tech_row" class="add_row_link">Add Row</a></th>
								</tr>
							</thead>
							<tbody id="tech_tbody">
								<tr>
									<td>	 </td>
									<td>	 </td>
									<td class="text-underline">Hours</td>
									<td class="text-underline">Hourly Rate</td>
									<td class="text-underline">Total</td>
								</tr>';
								for ($i=0; $i<sizeof($tech_data); $i++) {
									$tech_weekly_cost += ($tech_hours[$i] * $tech_rate[$i]);
									$html .=
								    '<tr>
										<td>Tech: </td>
										<td>
											<span class="glyphicon glyphicon-minus-sign" id="" name="" aria-hidden="true"></span>
										</td>
										<td><input class="unit_input form-control" id="tech_hours[]" name="tech_hours[]" type="text" value="'.$tech_hours[$i].'"/></td>
										<td><input class="unit_input form-control" id="tech_rate[]" name="tech_rate[]" type="text" value="'.$tech_rate[$i].'"/></td>
										<td>$'.number_format(($tech_hours[$i] * $tech_rate[$i]),2).'</td>
									</tr>';
									$output .= "Tech ".($i+1).": ,".$tech_hours[$i].", ".$tech_rate[$i].", ".($tech_hours[$i] * $tech_rate[$i])."\n";
								}
								$tech_weekly_cost += $tech_spiff_rate[0];
							$output .= "Spiff: ,".$tech_spiff_rate[0]."\n";
							$html .=
						'</tbody>
							<tfoot>
								<tr class="bg-slate">
									<td>Spiff: </td>
									<td> </td>
									<td><input type="text" class="unit_input form-control" id="tech_spiff" name="tech_spiff" value="'.$tech_spiff_rate[0].'" /></td>
									<td> </td>
									<td> </td>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
			</div>
		</div>';
		
		// Build Service Unit tables html and csv output
		for ($i=0; $i<sizeof($service_data); $i++) {
		  if ($i == 0) {
			  $html .=
			   '<div class="row">
					<div class="col-sm-12">
						<p class="section_title">Service Data:</p>
						<hr>
					</div>
				</div>';
		  } else {
			  $html .=
			   '<div class="row">
					<div class="col-sm-12">
						<hr>
					</div>
				</div>';
		  }
			$html .=
			   '<div class="row">
					<div class="col-sm-12 col-md-12 col-lg-12">
						<div class="row">
							<div class="col-sm-12 col-md-4 col-lg-4">
								<h4>Service Type: <span class="blue"> '.$unit_data[$i]['svc_name'].' </span></h4>
							</div>
							<div class="col-sm-12 col-md-8 col-lg-8">
								<div class="form-inline">
									<div class="form-group">
										<label for="pen_input">Pen %: </label>
										<input type="text" class="form-control pen_input" id="pen_input[]" name="pen_input[]" value="'.($unit_data[$i]['pen_percent']*100).'" placeholder="" />
										<input type="hidden" id="svc_id[]" name="svc_id[]" value="'.$unit_data[$i]['svc_id'].'" />
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-12 col-md-12 col-lg-7">
								<table class="table table-hover unit_table">
									<thead>
										<tr>
											<th colspan="4">Per Unit Analysis: <span class="red_label"> '.$unit_data[$i]['svc_name'].' </span></th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>	 </td>
											<td class="text-underline">Labor</td>
											<td class="text-underline">Parts</td>
											<td class="text-underline">Total</td>
										</tr>
										<tr>
											<td>Sale: </td>
											<td><input type="text" class="unit_input form-control" id="labor_sale[]" name="labor_sale[]" value="'.$unit_data[$i]['labor_sale'].'"/></td>
											<td><input type="text" class="unit_input form-control" id="parts_sale[]" name="parts_sale[]" value="'.$unit_data[$i]['parts_sale'].'"/></td>
											<td>$'.number_format($total_sale[$i],2).'</td>
										</tr>
										<tr>
											<td>Cost: </td>
											<td>$0.00</td>
											<td><input type="text" class="unit_input form-control" id="parts_cost[]" name="parts_cost[]" value="'.$unit_data[$i]['parts_cost'].'"/></td>
											<td>$'.number_format($unit_data[$i]['parts_cost'],2).'</td>
										</tr>
									</tbody>
									<tfoot>
										<tr class="bg-slate">
											<td>Net: </td>
											<td>$'.number_format($unit_data[$i]['labor_sale'],2).'</td>
											<td>$'.number_format($net_parts[$i],2).'</td>
											<td>$'.number_format($net_total[$i],2).'</td>
										</tr>
									</tfoot>
								</table>
							</div>
							<div class="col-sm-12 col-md-12 col-lg-5">
								<table class="table table-hover daily_table">
									<thead>
										<tr>
											<th colspan="2">Daily Analysis: <span class="red_label"> '.$unit_data[$i]['svc_name'].' </span></th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>Total Units:</td>
											<td>'.number_format($total_units[$i],2).'</td>
										</tr>
										<tr>
											<td>Total Sale:</td>
											<td>$'.number_format($total_daily_sale[$i],2).'</td>
										</tr>
										<tr>
											<td>Total Cost:</td>
											<td>$'.number_format(($total_daily_sale[$i] - $total_gross[$i]),2).'</td>
										</tr>
									</tbody>
									<tfoot>
										<tr class="bg-slate">
											<td>Total Net:</td>
											<td>$'.number_format($total_gross[$i],2).'</td>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>
				</div>';
			$output .= "\n\n";
			$output .= "Per Unit Analysis: ".$unit_data[$i]['svc_name']."\n";
			$output .= "Pen %: ,".$unit_data[$i]['pen_percent']."\n";
			$output .= "Category,"."Labor,"."Parts,"."Total\n";
			$output .= "Sale: ,".$unit_data[$i]['labor_sale'].",".$unit_data[$i]['parts_sale'].",".$total_sale[$i]."\n";
			$output .= "Cost: ,"."0.00,".$unit_data[$i]['parts_cost'].",".$unit_data[$i]['parts_cost']."\n";
			$output .= "Net: ,".$unit_data[$i]['labor_sale'].",".$net_parts[$i].",".$net_total[$i]."\n\n";
			$output .= "Daily Analysis: ".$unit_data[$i]['svc_name']."\n";
			$output .= "Total Units: ,".$total_units[$i]."\n"."Total Sale: ,".$total_daily_sale[$i]."\n"."Total Cost: ,".($total_daily_sale[$i] - $total_gross[$i])."\n"."Total Net: ,".$total_gross[$i]."\n\n";
		}
		// Build Summary Table csv and html
		
		// If $days_week == NULL or 0, set to 1 for below calculations, then reset to NULL after the calculations
		if ($days_week == NULL || $days_week == 0) {
			$days_week = 1;
		}
		
		// Summary Sales Data
		$cum_weekly_sales = ($cum_daily_sales * $days_week);
		$cum_annual_sales = ($cum_weekly_sales * 52);
		$cum_monthly_sales= ($cum_annual_sales / 12);
		
		// Summary Gross Data
		$cum_weekly_gross = ($cum_daily_gross * $days_week);
		$cum_annual_gross = ($cum_weekly_gross * 52);
		$cum_monthly_gross= ($cum_annual_gross / 12);
		
		// Summary Advisor Data
		$advisor_daily_cost = ($advisor_weekly_cost / $days_week);
		$advisor_annual_cost= ($advisor_weekly_cost * 52);
		$advisor_monthly_cost=($advisor_annual_cost / 12);
		
		// Summary Tech Data
		$tech_daily_cost = ($tech_weekly_cost / $days_week);
		$tech_annual_cost= ($tech_weekly_cost * 52);
		$tech_monthly_cost=($tech_annual_cost / 12);
		
		// Summary Team Data
		$team_weekly_cost = ($advisor_weekly_cost + $tech_weekly_cost);
		$team_daily_cost  = ($team_weekly_cost / $days_week);
		$team_annual_cost = ($team_weekly_cost * 52);
		$team_monthly_cost= ($team_annual_cost / 12);
		
		$html .=
	   '<div class="row">
			<div class="col-sm-12">
				<p class="section_title">Summary Data:</p>
				<hr>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12 col-md-12 col-lg-12">
				<div class="table-responsive">
					<table class="table table-hover profit_table">
						<thead>
							<tr>
								<th colspan="5" class="bg-blue">Profit Contribution Data</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>	 </td>
								<td class="text-underline">Daily</td>
								<td class="text-underline">Weekly</td>
								<td class="text-underline">Monthly</td>
								<td class="text-underline">Annual</td>
							</tr>
							<tr>
								<td class="profit_table_left_td">Total Sales: </td>
								<td>$'.number_format($cum_daily_sales,2).'</td>
								<td>$'.number_format($cum_weekly_sales,2).'</td>
								<td>$'.number_format($cum_monthly_sales,2).'</td>
								<td>$'.number_format($cum_annual_sales,2).'</td>
							</tr>
							<tr>
								<td class="profit_table_left_td">Total Net: </td>
								<td class="text-underline">$'.number_format($cum_daily_gross,2).'</td>
								<td class="text-underline">$'.number_format($cum_weekly_gross,2).'</td>
								<td class="text-underline">$'.number_format($cum_monthly_gross,2).'</td>
								<td class="text-underline">$'.number_format($cum_annual_gross,2).'</td>
							</tr>
							<tr>
								<td class="profit_table_left_td">Adv. Cost: </td>
								<td>$'.number_format($advisor_daily_cost,2).'</td>
								<td>$'.number_format($advisor_weekly_cost,2).'</td>
								<td>$'.number_format($advisor_monthly_cost,2).'</td>
								<td>$'.number_format($advisor_annual_cost,2).'</td>
							</tr>
							<tr>
								<td class="profit_table_left_td">Tech Cost: </td>
								<td class="text-underline">$'.number_format($tech_daily_cost,2).'</td>
								<td class="text-underline">$'.number_format($tech_weekly_cost,2).'</td>
								<td class="text-underline">$'.number_format($tech_monthly_cost,2).'</td>
								<td class="text-underline">$'.number_format($tech_annual_cost,2).'</td>
							</tr>
							<tr>
								<td class="profit_table_left_td">Total Team: </td>
								<td><span class="dbl_underline">$'.number_format($team_daily_cost,2).'</span></td>
								<td><span class="dbl_underline">$'.number_format($team_weekly_cost,2).'</span></td>
								<td><span class="dbl_underline">$'.number_format($team_monthly_cost,2).'</span></td>
								<td><span class="dbl_underline">$'.number_format($team_annual_cost,2).'</span></td>
							</tr>
						</tbody>
						<tfoot>
							<tr class="bg-slate">
								<td class="profit_table_left_td">Net: </td>
								<td>$'.number_format(($cum_daily_gross - $team_daily_cost),2).'</td>
								<td>$'.number_format(($cum_weekly_gross- $team_weekly_cost),2).'</td>
								<td>$'.number_format(($cum_monthly_gross-$team_monthly_cost),2).'</td>
								<td>$'.number_format(($cum_annual_gross -$team_annual_cost),2).'</td>
							</tr>
						</tfoot>
					</table>
				</div><!-- end div table-responsive -->
			</div>
		</div>';
		$output .="Profit Contribution Data: \n";
		$output .="Category,"."Daily,"."Weekly,"."Monthly,"."Annual\n";
		$output .="Total Sales: ,".$cum_daily_sales.",".$cum_weekly_sales.",".$cum_monthly_sales.",".$cum_annual_sales."\n";
		$output .="Total Net: ,".$cum_daily_gross.",".$cum_weekly_gross.",".$cum_monthly_gross.",".$cum_annual_gross."\n";
		$output .="Advisor Cost: ,".$advisor_daily_cost.",".$advisor_weekly_cost.",".$advisor_monthly_cost.",".$advisor_annual_cost."\n";
		$output .="Tech Cost: ,".$tech_daily_cost.",".$tech_weekly_cost.",".$tech_monthly_cost.",".$tech_annual_cost."\n";
		$output .="Total Team: ,".$team_daily_cost.",".$team_weekly_cost.",".$team_monthly_cost.",".$team_annual_cost."\n";
		$output .="Net: ,".($cum_daily_gross - $team_daily_cost).",".($cum_weekly_gross- $team_weekly_cost).",".($cum_monthly_gross-$team_monthly_cost).",".($cum_annual_gross -$team_annual_cost)."\n\n";
		
		// Build Profitability Summary Table csv and html
		$html .=
					'<div class="row">
						<div class="col-sm-12 col-md-12 col-lg-12">
							<table class="table table-hover profit_summ_table">
								<thead>
									<tr>
										<th colspan="5" class="bg-success">Profitability Summary</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td class="text-underline">Avg Sale Proceeds</td>
										<td class="text-underline">Avg Net Proceeds</td>
										<td class="text-underline">Break Even Units</td>
									</tr>
									<tr>
										<td>$'.number_format($avg_sale_proceeds,2).'</td>
										<td>$'.number_format($avg_net_proceeds,2).'</td>';
										
										// If $avg_net_proceeds == NULL or 0, set to one to prevent error message
										if ($avg_net_proceeds == NULL || $avg_net_proceeds == 0) {
											$avg_net_proceeds = 1;
										}
										
										$output .= "Profitability Summary: \n";
										$output .= "Avg Sale Proceeds ,"."Avg Net Proceeds ,"."Break Even Units\n";
										$output .= $avg_sale_proceeds.",".$avg_net_proceeds.",".($team_daily_cost / $avg_net_proceeds)."\n";
										$html .='
										<td>'.number_format(($team_daily_cost / $avg_net_proceeds),2).'</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
					<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
					<input type="hidden" name="action" value="profit_analysis_entry" />
					</form><!--end form id=profit_analysis_form-->
				</div><!-- end div col-sm-12 -->
			</div><!-- end div row -->
		</div><!-- end div class profit_page -->';
		// If the requested user action is to export data, $_SESSION['ecport_dlr_id'] will be set and $output should be returned.  Else html should be returned.
		if (isset($_SESSION['export_dlr_id'])) {
			return $output;
		} else {
			return $html;
		}
	}
	
	// Generate comma-delimited export data so that user may export to Excel etc.
	public function exportProfitAnalysisData() {
	
		// Returns an array of Cost Avg service items with the service name as the array key
		$calc_avg_info = new CostAvgCalcInfo();
		$cost_table_array = $calc_avg_info->getTables();
		
		// Initiate csv text
		$output = "";
		// Create export headings
		$output .= "Profitability Analysis Data Export: ".$_SESSION['export_dlr_name'];
		$output .= "\n";
		$output .= "Analysis Date: ".$_SESSION['export_date_display'];
		$output .= "\n";
		$output .= "Report Generated On: ".date('l F d Y');
		$output .= "\n\n";
		// Build profit analysis data
		$output .= "Profit Analysis Data: \n";
		$output .= "**********************************************************************";
		$output .= "\n\n";
		$output .= $this->buildProfitTables();
		$output .= "\n";
		$output .= "\n";
		// Build cost average data
		$output .= "Service Cost Average Data: \n";
		$output .= "**********************************************************************";
		$output .= "\n\n";
		foreach ($cost_table_array as $table) { // Loop through each table object, creating each Cost Average Table
			$output .= $table->exportCostAvgTable();
		}
		return $output;
	}
}
?>