<?php
	class DealerInfo extends DB_Connect {
	
		public function __construct($dbo=NULL) {
			parent::__construct($dbo);
		}
		
		/* Get list of dealer data based on type of user.
		 * Note: dealer user == type_id 3
		 * If user type == dealer, limit dealer records to only their dealer_record_id
		 * Else if user team == 1 or 2, limit dealer records to their team manufacturer
		 * If user team == 'All' (0), show all existing dealer records
		**/
		public function getDealerData() {
			try {
				$sql = "SELECT dealer_record_id, dealer_code, dealer_name, dealer_team_id FROM dealers";
				
				// If user type is dealer, limit dealer selection to their dealer only
				if ($_SESSION['user']['type_id'] == 3) {
					$sql .= " WHERE dealer_record_id = ".$_SESSION['user']['dealer_record_id'];
				// If user type is 1() or 2(), limit records to their team manufacturer
				} elseif ($_SESSION['user']['team_id'] > 0) {
					$sql .= " WHERE dealer_team_id = ".$_SESSION['user']['team_id'];
				}
				
				$sql .= " ORDER BY dealer_name ASC";
				
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (Exception $e) {
				die($e->getMessage());
			}
			return $results;
		}
	}
?>