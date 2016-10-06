<?php
class CostAvgCalcInfo extends DB_Connect {	

	public function __construct($dbo=NULL) {
		parent::__construct($dbo);
	}
	
	/**Test to see if dealer and date combination already has records in cost_avg_data table**/
	public function checkTableData($dealer_record_id, $record_date) {
		try {
			$sql = "SELECT dealer_record_id, record_date FROM cost_avg_data WHERE dealer_record_id = :dealer_record_id AND record_date = :record_date GROUP BY dealer_record_id";
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
	
	// Dynamically creates default cost tables for page load when no cost_avg_data table data for dealer_record_id and cost_date exists
	public function buildDefaultCostTables() {
		// Get services table data
		$service_list = new ServicesInfo;
		$service_list = $service_list->getServicesTableData($id_list = NULL); // Note that the sizeof($service_data) == 8
		$cost_table_array = array();
		foreach ($service_list as $value) {
			$cost_table_array[$value['svc_name']][] = array('svc_id' => $value['svc_id'], 'svc_name' => '', 'cost_desc' => '', 'cost_code' => '', 'cost_ro_count' => '', 'cost_parts_sale' => '', 'cost_parts_cost' => '', 'cost_labor_sale' => '');
		}
		return $cost_table_array;
	}
	
	// Get table data for ajax call
	public function createAjaxCostTable($svc_id) {
		if(isset($_SESSION['profit_dlr_id']) && isset($_SESSION['profit_date'])) {
			// Run query of cost_avg_data table for ajax response
			$sql = "SELECT a.svc_id, c.svc_name, a.cost_id, a.cost_desc, a.cost_code, a.cost_ro_count, a.cost_parts_sale, a.cost_parts_cost, a.cost_labor_sale
				    FROM cost_avg_data a
				    LEFT JOIN dealers b ON (b.dealer_record_id = a.dealer_record_id)
				    LEFT JOIN services c ON (c.svc_id = a.svc_id)
				    WHERE a.dealer_record_id = :dealer_record_id
				    AND a.record_date = :record_date
					AND a.svc_id = :svc_id
					ORDER BY a.cost_id";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->bindParam(":dealer_record_id", $_SESSION['profit_dlr_id'], PDO::PARAM_INT);
				$stmt->bindParam(":record_date", $_SESSION['profit_date'], PDO::PARAM_STR);
				$stmt->bindParam(":svc_id", $svc_id, PDO::PARAM_INT);
				$stmt->execute();
				$cost_ajax_table = $stmt->fetchALL(PDO::FETCH_ASSOC);
				$stmt->closeCursor();
			} catch (Exception $e) {
				die($e->getMessage());
			}
			
			// Build table from results
			$cost_table_array = array();
			foreach ($cost_ajax_table as $table) {
				$cost_table_array[$table['svc_name']][] = $table;
			}
		}
		return $cost_table_array;
	}
	
	// Return table markup from AJAX call
	public function getAjaxCostTable($svc_id) {
		$table = $this->createAjaxCostTable($svc_id);
		foreach ($table as $label => $value) {
			$ajax_table = new CostAvgTable($label, $value);
		}
		$ajax_table = $ajax_table->buildCostAvgTable();
		return $ajax_table; // Return cost avg table for ajax call
	}
	
	/* Get table data from database
	 *
	 *@return array an array of all service table data for Cost Avg Table page
	 */
	public function createCostAvgObject() {
		/* Check to see if dealer and date combo already exists in DB table.  
		 * If so, will need to retrieve data from tables.
		 * If not, will need to bypass table queries and create tables dynamically based on 'services' table
		 */
		if((isset($_SESSION['profit_dlr_id']) && isset($_SESSION['profit_date'])) || (isset($_SESSION['export_dlr_id']) && isset($_SESSION['export_date']))) {
			if(isset($_SESSION['export_dlr_id']) && isset($_SESSION['export_date'])) {
				$dealer = $_SESSION['export_dlr_id'];
				$date = $_SESSION['export_date'];
			} else {
				$dealer = $_SESSION['profit_dlr_id'];
				$date = $_SESSION['profit_date'];
			}
			
			if($this->checkTableData($dealer, $date)) {
			
				// Run query of all services to get total for comparison to below query results
				$services_info = new ServicesInfo();
				$service_list_a = $services_info->getServicesTableData($id_list = NULL); // Will return an array of 8 items (with svc_id and svc_name)
				// echo var_dump($service_list_a);
				
				$sql = "SELECT a.svc_id, b.svc_name 
						FROM cost_avg_data a
						LEFT JOIN services b ON (a.svc_id = b.svc_id)
						WHERE a.dealer_record_id = :dealer_record_id AND a.record_date = :record_date
						GROUP BY a.svc_id 
						ORDER BY a.svc_id";
				try {
					$stmt = $this->db->prepare($sql);
					$stmt->bindParam(":dealer_record_id", $dealer, PDO::PARAM_INT);
					$stmt->bindParam(":record_date", $date, PDO::PARAM_STR);
					$stmt->execute();
					$svc_results = $stmt->fetchALL(PDO::FETCH_ASSOC);
					$stmt->closeCursor();
					$service_list_b = array();
					$service_id_list = array();
					foreach ($svc_results as $output) {
						$service_list_b[$output['svc_name']] = $output['svc_id'];
						$service_id_list[]= $output['svc_id'];
					}
				} catch (Exception $e) {
					die($e->getMessage());
				}
				// echo '$service_list_a: <br>';
				// var_dump($service_list_a);
				// echo '$service_list_b: <br>';
				// var_dump($service_list_b);
				// echo '$service_id_list: <br>';
				// echo var_dump($service_id_list),'<br><br>';
				// die();
				
				// Query all unit table records for specific dealer and date
				$sql = "SELECT c.svc_id, c.svc_name, a.cost_id, a.cost_desc, a.cost_code, a.cost_ro_count, a.cost_parts_sale, a.cost_parts_cost, a.cost_labor_sale
						FROM cost_avg_data a
						LEFT JOIN dealers b ON (b.dealer_record_id = a.dealer_record_id)
						LEFT JOIN services c ON (c.svc_id = a.svc_id)
						WHERE a.dealer_record_id = :dealer_record_id
						AND a.record_date = :record_date
						ORDER BY a.cost_id";
				try {
					$stmt = $this->db->prepare($sql);
					$stmt->bindParam(":dealer_record_id", $dealer, PDO::PARAM_INT);
					$stmt->bindParam(":record_date", $date, PDO::PARAM_STR);
					$stmt->execute();
					$cost_tables = $stmt->fetchALL(PDO::FETCH_ASSOC);
					$stmt->closeCursor();
				} catch (Exception $e) {
					die($e->getMessage());
				}
				//echo '$cost_tables:<br>', var_dump($cost_tables),'<br>';
				//die();
				
				// Build comma-delimited list from $service_id_list for use in services query below (from getServicesTableData() function)
				$query_id_list = '';
				for ($i=0; $i<sizeof($service_id_list); $i++) {
					if ($i == (sizeof($service_id_list) - 1)) {
						$query_id_list .= $service_id_list[$i];
					} else {
						$query_id_list .= $service_id_list[$i].', ';
					} 
				}
				// echo '$query_id_list: <br>',var_dump($query_id_list),'<br><br>';
				
				// If service lists do not have the same number of items, add remaining service categories and cost table arrays 
				// to $service_list_b and $cost_tables arrays so that default tables also appear
				if (sizeof($service_list_a) != sizeof($service_list_b)) {
					// echo 'entered sizeof if statement<br>';
					// Run services query using $query_id_list for 'NOT IN' clause
					$services_append_array = $services_info->getServicesTableData($query_id_list); // This works.
					// echo'$services_append_array: ',var_dump($services_append_array),'<br>';
					// var_dump($services_append_array);
					// die();
					foreach ($services_append_array as $value) {
						$service_list_b[$value['svc_name']] = $value['svc_id'];
						$cost_tables[] = array('svc_id' => $value['svc_id'], 'svc_name' => $value['svc_name'], 'cost_desc' => '', 'cost_code' => '', 'cost_ro_count' => '', 'cost_parts_sale' => '', 'cost_parts_cost' => '', 'cost_labor_sale' => '');
						//$cost_array = ($cost_append_array[$value['svc_name']][] = array("svc_id" => $value['svc_id'], "svc_name" => '', "cost_desc" => '', "cost_code" => '', "cost_ro_count" => '', "cost_parts_sale" => '', "cost_parts_cost" => '', "cost_labor_sale" => ''));
					}
				}
			
				/** 
				* Create two-dimensional array from above query results.
				* 1) After obtaining list of service ID's and names (via GROUP BY) and full array(array()) of table rows (2 queries),
				* 2) Iterate through each service name and through each table row
				* 3) Check to see if 'svc_name' array element == $svc (obtained from 1st service query)
				* 4) If it is equal, then add the array row to $new_array, with $svc as the array key (this will be the service name)
				*/
				$cost_table_array = array();
				foreach ($service_list_b as $svc_name => $value) {
					foreach ($cost_tables as $cost_table) {
						if ($cost_table['svc_name'] == $svc_name) {
							$cost_table_array[$svc_name][] = $cost_table;
						}
					}
				}
				// echo '$cost_table_array:<br>',var_dump($cost_table_array),'<br>';
			} else { // If there is no table data available, will need to build tables dynamically based on 'services' table
				$cost_table_array = $this->buildDefaultCostTables();
			}
		} else {
			$cost_table_array = $this->buildDefaultCostTables();
		}
		return $cost_table_array;
	}
	
	public function getTables() {
		$tables = $this->createCostAvgObject();
		foreach ($tables as $label => $value) {
			$table[] = new CostAvgTable($label, $value);
		}
		return $table; // Return array of Cost Avg Table objects
	}
	
	// Process cost table inputs and insert/remove from database
	public function processCostTableInput() {
	
		// Prevent access to method if no SESSION variables are set
		if(!isset($_SESSION['profit_dlr_id']) && !isset($_SESSION['profit_date'])) {
			//$_SESSION['error'][] = 'Please select a dealer before proceeding!';
			return 'wksht_not_set';
		}
		// If a cost table is submitted, but the user has deleted all rows, ensure that Fatal errors do not show (for non-object)
		if (isset($_POST['cost_table_svc_id'])) {
			if (!isset($_POST['cost_desc'])) {
				//$_SESSION['error'][] = 'You must enter service information before submitting the Cost Average table!';
				return 'no_rows_error';
			}
		}
		
		// Instantiate InputValidation class to validate form input
		$isvalid = new InputValidation();
		
		$svc_id = (int) $_POST['cost_table_svc_id']; // No form validation necessary for svc_id as this is a hidden input field
		
		$svc_name = $_POST['cost_table_svc_name'];  // No form validation necessary for svc_name as this is a hidden input field
		
		foreach ($_POST['cost_desc'] as $desc) {
			if ($desc == '') {
				$_SESSION['error'][] = 'You left a Description field empty!';
			}
			$cost_desc[] = htmlentities($desc, ENT_QUOTES); // No further validation necessary as this item description could be anything
		}
		
		foreach ($_POST['cost_code'] as $cost) {
			if ($cost == '') {
				$_SESSION['error'][] = 'You left a Code field empty!';
			}
			$cost_code[] = htmlentities($cost, ENT_QUOTES); // No further validation necessary as this item code could be anything
		}
		
		foreach ($_POST['cost_rocount'] as $ro_count) {
			if (!$isvalid->validWholeNumber($ro_count) || $ro_count == '') {
				if ($ro_count == '') {
					$_SESSION['error'][] = 'You left an RO Count field empty!';
				} else {
					$_SESSION['error'][] = 'You entered an invalid number in the RO Count field!';
				}
			}
			$cost_rocount[] = htmlentities($ro_count, ENT_QUOTES);
		}
		
		foreach ($_POST['cost_parts_sale'] as $parts_sale) {
			if (!$isvalid->validDollarValue($parts_sale) || $parts_sale == '') {
				$_SESSION['error'][] = 'You entered an invalid Parts Sale amount!';
			}
			$cost_parts_sale[] = htmlentities($parts_sale, ENT_QUOTES);
		}
		
		foreach ($_POST['cost_parts_cost'] as $parts_cost) {
			if (!$isvalid->validDollarValue($parts_cost) || $parts_cost == '') {
				$_SESSION['error'][] = 'You entered an invalid Parts Cost amount!';
			}
			$cost_parts_cost[] = htmlentities($parts_cost, ENT_QUOTES);
		}
		
		foreach ($_POST['cost_labor_sale'] as $labor_sale) {
			if (!$isvalid->validDollarValue($labor_sale) || $labor_sale == '') {
				$_SESSION['error'][] = 'You entered an invalid Labor Sale amount!';
			}
			$cost_labor_sale[] = htmlentities($labor_sale, ENT_QUOTES);
		}
		
		// If there were any errors, return false
		if (!empty($_SESSION['error'])) {
			return false;
		}
		
		// Echo values for testing purposes
		/*
		echo '$svc_id: '.$svc_id.'<br> $svc_name: '.$svc_name.'<br>';
		foreach ($cost_desc as $desc) {
			echo '$cost_desc: '.$desc.'<br>';
		}
		foreach ($cost_code as $code) {
			echo '$cost_code: '.$code.'<br>';
		}
		foreach ($cost_rocount as $count) {
			echo '$cost_rocount: '.$count.'<br>';
		}
		foreach ($cost_parts_sale as $parts_sale) {
			echo '$cost_parts_sale: '.$parts_sale.'<br>';
		}
		foreach ($cost_parts_cost as $parts_cost) {
			echo '$cost_parts_cost: '.$parts_cost.'<br>';
		}
		foreach ($cost_labor_sale as $labor_sale) {
			echo '$cost_labor_sale: '.$labor_sale.'<br>';
		}
		*/
		
		// Check if records already exist for $_SESSION['profit_dlr_id'] and $_SESSION['profit_date'] in cost_avg_data table
		// If so, delete records in table associated with dealer_record_id, record_date, svc_id, and then re-insert record(s)
		if($this->checkTableData($_SESSION['profit_dlr_id'], $_SESSION['profit_date'])) {
			if(!$this->deleteCostData(array('svc_id'=>$svc_id))) {
				return false;
			}
			//$this->deleteCostData(array('svc_id'=>$svc_id));
		}
		
		// Insert or update cost table
		$sql = "INSERT INTO cost_avg_data (dealer_record_id, record_date, svc_id, cost_desc, cost_code, cost_ro_count, cost_parts_sale, cost_parts_cost, cost_labor_sale, create_date, user_id)
				VALUES (:dealer_record_id, :record_date, :svc_id, :cost_desc, :cost_code, :cost_ro_count, :cost_parts_sale, :cost_parts_cost, :cost_labor_sale, NOW(), :user_id)";
		try {
			$stmt = $this->db->prepare($sql);
			for ($i=0; $i<sizeof($cost_desc); $i++) {
				$stmt->bindParam(":dealer_record_id", $_SESSION['profit_dlr_id'], PDO::PARAM_INT);
				$stmt->bindParam(":record_date", $_SESSION['profit_date'], PDO::PARAM_STR);
				$stmt->bindParam(":svc_id", $svc_id, PDO::PARAM_INT);
				$stmt->bindParam(":cost_desc", $cost_desc[$i], PDO::PARAM_STR);
				$stmt->bindParam(":cost_code", $cost_code[$i], PDO::PARAM_INT);
				$stmt->bindParam(":cost_ro_count", $cost_rocount[$i], PDO::PARAM_INT);
				$stmt->bindParam(":cost_parts_sale", $cost_parts_sale[$i], PDO::PARAM_INT);
				$stmt->bindParam(":cost_parts_cost", $cost_parts_cost[$i], PDO::PARAM_INT);
				$stmt->bindParam(":cost_labor_sale", $cost_labor_sale[$i], PDO::PARAM_INT);
				$stmt->bindParam(":user_id", $_SESSION['user']['id'], PDO::PARAM_INT);
				$stmt->execute();
				$stmt->closeCursor();
			}
		} catch(Exception $e) {
			die($e->getMessage());
		}
		//die();
	}
	
	/* Delete cost data if user selects 'Remove' on profit tab, or updates record in cost table
	 * Pass $array['svc_id'] as param if executing cost table row update
	 * This method is also executed in ProfitAnalysisInfo class when user selects 'Remove' worksheet 
	**/
	public function deleteCostData($array) {
		$sql = "DELETE FROM cost_avg_data WHERE dealer_record_id = :dealer_record_id AND record_date = :record_date ";
		if($array['svc_id']) {
			$sql .= " AND svc_id = :svc_id ";
		}
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":dealer_record_id", $_SESSION['profit_dlr_id'], PDO::PARAM_INT);
			$stmt->bindParam(":record_date", $_SESSION['profit_date'], PDO::PARAM_STR);
			if($array['svc_id']) {
				$stmt->bindParam(":svc_id", $array['svc_id'], PDO::PARAM_INT);
			}
			$stmt->execute();
			$stmt->closeCursor();
			$status = true;
		} catch(Exception $e) {
			$status = false;
			die($e->getMessage());
		}
		return $status;
	}
}
?>