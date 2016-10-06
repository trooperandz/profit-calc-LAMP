<?php
	class ServicesInfo extends DB_Connect {
	
		public function __construct($dbo=NULL) {
			parent::__construct($dbo);
		}
		
		// Get services table data
		public function getServicesTableData($id_list) {
			// echo '$id_list: ',$id_list,'<br>';
			
			if ($id_list != NULL) {
				$sql = "SELECT svc_id, svc_name FROM services WHERE svc_id NOT IN (".$id_list.") ORDER BY svc_id";
			} else {
				$sql = "SELECT * FROM services ORDER BY svc_id";
			}
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (Exception $e) {
				die($e->getMessage());
			}
			return $results;
		}
		/*
		// Get services table data
		public function getServicesTableData() {
			// Load svcs based on user team id (if $_SESSION['user']['team_id'] != 0)
			if (isset($_SESSION['user']['team_id']) && $_SESSION['user']['team_id'] != 0) {
				switch ($_SESSION['user']['team_id']) {
					case 1:
						$field_name = 'svcs_acura';
						break;
					case 2:
						$field_name = 'svcs_nissan';
					case 3:
						$field_name = 'svcs_subaru';
						break;
				}
			// If $_SESSION['user']['team_id'] == 0, check for $_SESSION['svc_manuf_id'] (this is set from 'New' and 'Open' worksheet actions
			} elseif (isset($_SESSION['svc_manuf_id'])) {
				switch ($_SESSION['svc_manuf_id']) {
					case 1:
						$field_name = 'svcs_acura';
						break;
					case 2:
						$field_name = 'svcs_nissan';
					case 3:
						$field_name = 'svcs_subaru';
						break;
				}
			// If none of the above are true, then run default table list (base it on Nissan first)
			} else {
				$field_name = 'svcs_nissan';
			}
			
			$sql = "SELECT * FROM services WHERE ".$field_name." = 1 ORDER BY svc_id ASC";
			
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (Exception $e) {
				die($e->getMessage());
			}
			return $results;
		}*/
	}
?>