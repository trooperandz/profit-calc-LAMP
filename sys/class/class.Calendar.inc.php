<?php
/**
 *File: class.calendar.inc.php
 *Purpose: Builds and manipulates an events calendar
 *PHP version 5.1.2
 *@author Matthew Holland
 *
 *History:
 *   Date			Description																by
 *   09/11/2015		Initial design & coding	    											Matt Holland
 *	 11/02/2015		Added event color-coding by dynamically adding class names depending	Matt Holland
 *					on the event type (etype_id) in the buildCaledar() method:
 *						for ($n=0; $n<count($_SESSION['event_type']); $n++) {
 *							if ($event->etype_id == $n) { }
 *						}
 *	 11/03/2015		Changed the } else { return value in the processForm method to:			Matt Holland
 *					'return $id' instead of 'return true'.  'return true' was preventing
 *					successful event_id passing via edit event ajax calls (was returning a
 *					'1' (for true) when it needed to return the actual event_id
 */
 
class Calendar extends DB_Connect {
	/**
	 * The date from which the calendar should be built
	 *
	 * Stored in YYYY-MM-DD HH:MM:SS format
	 *
	 * @var string the date to use for the calendar
	 */
	private $_useDate;
	
	/**
	 * The month for which the calendar is being built
	 *
	 * @var int the month being used 
	 */
	private $_m;
	
	/**
	 * The year from which the month's start day is selected
	 *
	 * @var int the year being used
	 */
	private $_y;
	
	/**
	 * The number of days in the month being used
	 *
	 * @var int the number of days in the month
	 */
	private $_daysInMonth;
	
	/**
	 * The index of the day of the week the month starts on (0-6)
	 *
	 * @var int the day of the week the month starts on
	 */
	private $_startDay;
		// Methods go here
	/**
	 * Creates a database object and stores relevant data
	 *
	 * Upon instantiation, this class accepts a database object
	 * that, if not null, is stored in the object's private $db
	 * property. If null, a new PDO object is created and stored
	 * instead.
	 *
	 * Additional info is gathered and stored in this method, 
	 * including the month from which the calendar is to be built,
	 * how many days are in said month, what day the month starts
	 * on, and what day it is currently.
	 *
	 * @param object $dbo a database object
	 * @param string $useDate the date to use to build the calendar
	 * @return void
	 */
	public function __construct ($dbo=NULL, $useDate=NULL) {
		/**
		 * Call the parent constructor to check for a database 
		 * object.
		 */
		parent::__construct($dbo);
		
		/**
		 * Gather and store data relevant to the month
		 */
		if (isset($_SESSION['useDate'])) {
			$this->_useDate = $_SESSION['useDate'];
		} else {
			$this->_useDate = date('Y-m-d H:i:s');
		}
		
		/**
		 * Convert to a timestamp, and then determine the
		 * month and year to use when building the calendar
		 */
		$ts = strtotime($this->_useDate);
		$this->_m = date('m', $ts);
		$this->_y = date('Y', $ts);
		
		/**
		 * Determine how many days are in the month
		 */
		$this->_daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->_m, $this->_y);
		
		/**
		 * Determine what weekday the month starts on
		 */
		$ts = mktime(0, 0, 0, $this->_m, 1, $this->_y);
		$this->_startDay = date('w', $ts);
	}
	/**
	 * Loads event(s) info into an array
	 *
	 * @param int $id an optional event ID to filter results
	 * @return array an array of events from the database
	 */
	private function _loadEventData($id=NULL) {
		$sql = "SELECT a.event_id, a.event_title, a.event_desc, a.event_etype_id, a.event_location_id, b.dealer_name, b.dealer_code,
			           a.event_start, a.event_end, a.event_team_id
			    FROM events a
				LEFT JOIN dealers b ON (a.event_location_id = b.dealer_record_id)";
		/*
		 * If an event ID is supplied, add a WHERE clause 
		 * so only that event is returned
		 */
		if (!empty($id)) {
			$sql .= " WHERE a.event_id = :id LIMIT 1";
		/*
		 * Otherwise, load all events for the month in use
		 */
		} else {
		
			/*
			 * Find the first and last days of the month
			 */
			$start_ts = mktime(0,0,0, $this->_m, 1, $this->_y);
			$end_ts = mktime(23,59,59, $this->_m+1, 0, $this->_y);
			$start_date = date('Y-m-d H:i:s', $start_ts);
			$end_date = date('Y-m-d H:i:s', $end_ts);
			
			/*
			 * Filter events to only those happening in the
			 * currently selected month
			 */
			$sql .= " WHERE a.event_start
			         BETWEEN '$start_date' AND '$end_date'";

			/*
			 * Filter events to those on the user's team only.
			 * If user team = All, show all events.
			 */
			$event_team_id = $_SESSION['user']['team_id'];
			if ($event_team_id != 0) {
				$sql .= " AND a.event_team_id = $event_team_id";  
			}		  
			$sql .= " ORDER BY a.event_start";
			
			/*
			 * Debug code.  Remove when finished debugging
			 *
			 *echo '$sql: '.$sql.'<br>';
			 */
		}
		
		try {
			/*
			 * Debug code.  Remove when finished debugging
			 *
			 * echo 'Entered try clause:<br>';
			 */
			
			$stmt = $this->db->prepare($sql);
			/*
			 * Debug code.  Remove when finished debugging
			 *
			 * echo 'var_dump of $stmt:<br>';
			 * var_dump($stmt);
			 * echo'<br>';
			 */
			
			/*
			 * Bind the parameter if an ID was passed
			 */
			if (!empty($id)) {
				$stmt->bindParam(":id", $id, PDO::PARAM_INT);
			}
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			/*
			 * Debug code.  Remove when finished debugging
			 *
			 * echo 'var dump of $results:<br>';
			 * var_dump($results);
			 * echo'<br>';
			 */
			
			$stmt->closeCursor();
			return $results;
		}
		catch (Exception $e) {
			die($e->getMessage());
		}
	}
	
	/**
	 * Loads all events for the month into an array
	 *
	 * @return array events info
	 */
	private function _createEventObj() {
		/*
		 * Load the events array
		 */
		$arr = $this->_loadEventData();
		
		/*
		 * Create a new array, then organize the events
		 * by the day of the month on which they occur
		 */
		$events = array();
		foreach ($arr as $event) {
			$day = date('j', strtotime($event['event_start']));
			try {
				$events[$day][] = new Event($event);
			}
			catch ( Exception $e) {
				die($e->getMessage());
			}
		}
		return $events;
	}
	
	/**
	 * Returns a single event object
	 *
	 * @param int $id an event ID
	 * @return object the event object
	 */
	private function _loadEventById($id) {
		/*
		 * If no ID is passes, return NULL
		 */
		if (empty($id)) {
			return NULL;
		}
		
		/*
		 * Load the events info array
		 */
		$event = $this->_loadEventData($id);
		
		/*
		 * Return an event object
		 */
		if (isset($event[0])) {
			return new Event($event[0]);
		} else {
			return NULL;
		}
	}
	
	/**
	 * Generates markup to display administrative links
	 *
	 * @return string markup to display the administrative links
	 */
	private function _adminGeneralOptions() {
		/*
		 * If the user is logged in, display admin controls
		 */
		if (isset($_SESSION['user'])) {
return <<<ADMIN_OPTIONS
<div class="container">
	<div class="row">
		<div class="col-md-12">
			<a href="addevent.php" class="admin btn btn-primary">+ Add a new Event</a>
		</div>
	</div>
</div>
ADMIN_OPTIONS;
		} else {
			return <<<ADMIN_OPTIONS
						<a href="login.php">Log In</a>
ADMIN_OPTIONS;
		}
	}
	
	/**
	 * Generates edit and delete options for a given event ID
	 *
	 * @param int $id the event ID to generate options for
	 * @return string the markup for the edit/delete options
	 */
	private function _adminEntryOptions($id) {
		/*
		 * Display edit and delete controls to authorized users only
		 */
		if (isset($_SESSION['user'])) {
			return <<<ADMIN_OPTIONS
					<div class="admin-options">
						<form action="addevent.php" method="post">
							<p>
								<input type="submit" class="btn btn-primary" name="edit_event" value="Edit This Event" />
								<input type="hidden" name="event_id" value="$id" />
							</p>
						</form>
						<form action="confirmdelete.php" method="post">
							<p>
								<input type="submit" class="btn btn-warning" name="delete_event" value="Delete This Event" />
								<input type="hidden" name="event_id" value="$id" />
							</p>
						</form>
					</div> <!--end .admin-options-->
ADMIN_OPTIONS;
		} else {
			return NULL;
		}
	}
	
	/**
	 * Returns html markup to display the calendar and events
	 *
	 * Using the information stored in class properties, the
	 * events for the given month are loaded, the calendar
	 * is generated, and the whole thing is returned as valid
	 * markup.
	 *
	 * @return string the calendar HTML markup
	 */
	public function buildCalendar() {
		/*
		 * Determine the calendar month and create an array of
		 * weekday abbreviations to label the calendar columns
		 */
		$cal_month = date('F Y', strtotime($this->_useDate));
		$cal_id = date('Y-m', strtotime($this->_useDate));
		$weekdays = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
		$team_name = $_SESSION['user']['team_name'];
		
		/*
		 * Add bootstrap divs for calendar
		 */
		$html =
			"<div class=\"row\">
				<div class=\"col-sm-6\">
					<h3 id=\"month-$cal_id\" class=\"no_bottom_margin min_top_margin\">$team_name Schedule - $cal_month</h3>
				</div>
				<div class=\"col-sm-6\">
					<nav class=\"right_align min_bottom_margin min_top_margin\">
						<div class=\"btn-group\">
							<form id=\"chng_month\" action=\"assets/inc/process.inc.php\" method=\"POST\">
								<button type=\"submit\" name=\"action\" value=\"prev_month\" class=\"btn btn-default month_nav\">&laquo; Prev</button>
								<button type=\"submit\" name=\"action\" value=\"next_month\" class=\"btn btn-default month_nav\">Next &raquo;</button>
								<input type=\"hidden\" name=\"token\" value=\"$_SESSION[token]\" />
							</form>
						</div>
					</nav>
				</div>
			</div>
			<div class=\"row\">
				<div class=\"col-sm-12\">
					<div class=\"table-responsive\">
						<table class=\"table calendar_table table_head\">
							<thead>\n";

		for ($d=0, $labels=NULL; $d<7; ++$d) {
			if ($d == 0) {
				$labels .= "\n\t\t<td style=\"border-left: 1px solid gray;\">".$weekdays[$d]."</td>";
			} elseif ($d == 6) {
				$labels .= "\n\t\t<td style=\"border-right: 1px solid gray;\">".$weekdays[$d]."</td>";
			} else {
				$labels .= "\n\t\t<td>".$weekdays[$d]."</td>";
			}	
		}
		$html .= "\n\t<tr class=\"weekdays\">".$labels."\n\t</tr>\n</thead>\n";
		
		/*
		 * Load events data
		 */
		$events = $this->_createEventObj();
		
		/*
		 * Create the calendar markup
		 */
		$html .= "\n<tbody>"; // Open the table body tag
		$html .= "\n\t<tr>"; // Start a new unordered list
		for ($i=1, $c=1, $t=date('j'), $m=date('m'), $y=date('Y'); $c<=$this->_daysInMonth; ++$i) {
			//echo '$i: ',$i,'<br>';
			//echo '$c: ',$c,'<br>';
			/*
			 * Apply a 'fill' class to the boxes occurring before
			 * the first of the month
			 */
			$class = $i<=$this->_startDay ? "fill" : NULL;
			
			/*
			 * Add a 'today' class if the current date matches
			 * the current date
			 */
			if ($c==$t && $m==$this->_m && $y==$this->_y) {
				$class = "today";
			}
			
			/*
			 * Build the opening and closing <td> tags
			 */
			$ls = sprintf("\n\t\t<td class=\"%s\">", $class);
			$le = "\n\t\t</td>";
			
			/*
			 * Add the day of the month to identify the calendar box
			 */
			if ($this->_startDay<$i && $this->_daysInMonth>=$c) {
			
				/*
				 * Format events data
				 */
				$event_info = NULL; // Clear the variable
				
				if (isset($events[$c])) {
					foreach ($events[$c] as $event) {
						for ($n=0; $n<count($_SESSION['event_type']); $n++) {
							if ($event->etype_id == $n) {
								$link = '<a href="view.php?event_id='.$event->id.'" class="'.$_SESSION['event_type'][$n]['etype_class'].'">'.$event->title.'</a>';
							}
						}
						$event_info .= "\n\t\t\t$link";
					}
				} else {
					$event_info = '';
				}
				$date = sprintf("\n\t\t\t<h5 class=\"calendar_day\">%02d</h5>", $c++);
			} else {
				$date="&nbsp;";
				$event_info = '';
			}
			
			/*
			 *If the current day is a Saturday, wrap to the next row
			 * Note: had to add '&& ($c+1)<=$this->_daysInMonth' in
			 * order for the $wrap to not include an extra calendar line
			 * when the last day of the month ends on the last box in
			 * the row.  This was obtained from the author himself!
			 */
			$wrap = $i!=0 && $i%7==0 ? "\n\t</tr>\n\t<tr>" : NULL;

			/* 
			 * Assemble the pieces into a finished item
			 */
			$html .= $ls.$date.$event_info.$le.$wrap;
		}
		
		/*
		 * Add filler to finish out the last week
		 */
		while ($i%7!=1) {
			$html .= "\n\t\t<td class=\"fill\">&nbsp;</td>";
			++$i;
		}
		
		/*
		 * Close the final unordered list
		 */
		$html .= "\n\t</tr>\n</tbody>\n</table>\n</div><!--end div class table-responsive-->\n</div><!--end div class col-sm-12-->\n</div><!--end div class row-->\n";
		
		/*
		 * If logged in, display the admin options
		 */
		$admin = $this->_adminGeneralOptions();
		
		/*
		 * Return the markup for output
		 */
		return $html.$admin;
	}
	
	/**
	 * Displays a given event's information
	 *
	 * @param int $id the event ID
	 * $return string basic markup to display the event info
	 */
	public function displayEvent($id) {
	
		/*
		 * Make sure an ID was passed
		 */
		if (empty($id)) {
			return NULL;
		}
		
		/*
		 * Make sure the ID is an integer
		 */
		$id = preg_replace('/[^0-9]/', '', $id);
		
		/*
		 * Load the event data from the DB
		 */
		$event = $this->_loadEventById($id);
		
		/*
		 * Generate strings for the event type, date, start, and end time
		 */
		$etype_name = $_SESSION['event_type'][$event->etype_id]['etype_name']; 
		$location_name = $event->location_name;
		$ts = strtotime($event->start);
		$date = date('F d, Y', $ts);
		$start = date('g:ia', $ts);
		$end = date('g:ia', strtotime($event->end));
		
		/*
		 * Load admin options if the user is logged in
		 */
		$admin = $this->_adminEntryOptions($id);
		
		/*
		 * Generate and return the markup
		 */
		return "<h2 class=\"center\">$event->title</h2>
				<h3 class=\"center\" style=\"color: blue; margin-top: 3px;\">$etype_name</h3>
				<h3 class=\"center\">$location_name</h3>
		        <p class=\"dates center\" style=\"text-align: center;\">$date, $start&mdash;$end</p>
				<p class=\"center\">$event->description</p>$admin";
	}
	
	public function displayForm() {
		/*
		 * Check if an ID was passed
		 */
		if (isset($_POST['event_id']) || isset($_SESSION['event_id'])) {
			if (isset($_SESSION['event_id'])) {
				$id = (int) $_SESSION['event_id'];
			} else {
				$id = (int) $_POST['event_id']; // Force integer type to sanitize data
			}	
		} else {
			$id = NULL;
		}
		
		/*
		 * Instantiate the headline/submit button text
		 */
		$submit = "Create New Event";
		
		// Initialize variables.  If you don't, and you are adding a new event, then PHP will throw a notice: 'Undefined variable'
		$event_location_id 	 = NULL;
		$event_location_name = NULL;
		$event_title 		 = NULL;
		$event_start 		 = NULL;
		$event_end 	 		 = NULL;
		$event_desc  		 = NULL;
		$event_id 	 		 = NULL;
		
		/*
		 * If an ID is passed, loads the associated event
		 */
		if (!empty($id)) {
			$event = $this->_loadEventById($id);
			
			// Set variables so that you may use globals OR the ones below with the same variable name below in the $html form
			$event_title		 = $event->title;
			$etype_id   		 = $event->etype_id;
			$etype_name 		 = $_SESSION['event_type'][$event->etype_id]['etype_name'];
			$event_location_id	 = $event->location_id;
			$event_location_name = $event->location_name;
			$event_start		 = $event->start;
			$event_end 			 = $event->end;
			$event_desc 		 = $event->description;
			$event_id 			 = $event->id;
			
			/*
			 * If no object is returned, return NULL
			 */
			if (!is_object($event)) {
				return NULL;
			}
			$submit = "Edit This Event";
		}
		
		if (isset($_SESSION['event_title'])) {
			$event_title		 = $_SESSION['event_title'];
			$etype_id			 = $_SESSION['etype_id'];
			$etype_name 		 = $_SESSION['etype_name'];
			$event_location_id	 = $_SESSION['event_location_id'];
			$event_location_name = $_SESSION['event_location_name'];
			$event_start		 = $_SESSION['event_start'];
			$event_end  		 = $_SESSION['event_end'];
			$event_desc 		 = $_SESSION['event_description'];
			$event_id 			 = $_SESSION['event_id'];
		}
		
		/**
		 *	Form markup test
		 */
		$html = 
		'<form action="assets/inc/process.inc.php" method="post">
			 <fieldset>
				<legend>'.$submit.'</legend>
				
				<label for="event_title">Event Title</label>
				<input type="text" class="form-control" name="event_title" id="event_title" value="'.$event_title.'" />
				
				<label for="event_type">Event Type</label>
				<select class="form-control" name="etype_id" id="etype_id">';
				  // If a form error has occurred and the below global is set, show it as the first <option> item
				if(isset($_SESSION['etype_id']) || !empty($id)) {
					if (isset($_SESSION['etype_id'])) {
						$html .='<option value="'.$etype_id.'">'.$etype_name.'</option>';
					} else {
						$html .='<option value="'.$etype_id.'">'.$etype_name.'</option>';
					}
				  
				    // Run through the event_type array as defined from init.inc.php (saved as $_SESSION['event_type'] global)
				    foreach ($_SESSION['event_type'] as $event) {
					  $html .='<option value="'.$event['etype_id'].'">'.$event['etype_name'].'</option>';
				    }
				} else {
				    // This else stmnt is necessary so as to correctly include the empty <option value=''></option> for initial event creation.  
					// This user must be encouraged to enter something instead of the <option> defaulting to the first real item so as to prevent entry errors.
					$html .='<option value="">Select...</option>';
					foreach ($_SESSION['event_type'] as $event) {
					  $html .='<option value="'.$event['etype_id'].'">'.$event['etype_name'].'</option>';
				    }
				}
				$html .=
			   '</select>
			   
			    <label for="elocation_id">Event Location</label>
				<select class="form-control dlr_list" name="elocation_id" id="elocation_id">';
				// Filter dealer array based on the type of user logged in
				if ($_SESSION['user']['team_id'] == 1) {
					$dlr_array = $_SESSION['dlrs_acura'];
				} elseif ($_SESSION['user']['team_id'] == 2) {
					$dlr_array = $_SESSION['dlrs_nissan'];
				} elseif ($_SESSION['user']['team_id'] == 3) {
					$dlr_array = $_SESSION['dlrs_subaru'];
				} else {
					$dlr_array = $_SESSION['dlrs_all'];
				}
				if (isset($_SESSION['event_location_id']) || !empty($id)) {
					if (isset($_SESSION['event_location_id'])) {
						$html .= '<option value="'.$_SESSION['event_location_id'].','.$_SESSION['event_location_name'].'">'.$_SESSION['event_location_name'].'</option>';
					} else {
						$html .= '<option value="'.$event_location_id.','.$event_location_name.'">'.$event_location_name.'</option>';
					}
				} else {
					$html .= '<option value=" , ">Select...</option>';
				}
				foreach ($dlr_array as $dealer) {
					$html.='<option value="'.$dealer['dealer_record_id'].','.$dealer['dealer_name'].'">'.$dealer['dealer_name'].' '.$dealer['dealer_code'].'</option>';
				}
				$html .=
			   '</select>
				
				<label for="event_start">Start Time</label>
				<input type="text" class="form-control" name="event_start" id="event_start" value="'.$event_start.'" />
				
				<label for="event_end">End Time</label>
				<input type="text" class="form-control" name="event_end" id="event_end" value="'.$event_end.'" />
				
				<label for="event_description">Event Description</label>
				<textarea class="form-control" name="event_description" id="event_description">'.$event_desc.'</textarea>
				
				<input type="hidden" name="event_id" value="'.$event_id.'" />
				<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
				<input type="hidden" name="action" value="event_edit" />
				<input type="submit" class="btn btn-primary form-submit" name="event_submit" value="'.$submit.'" /> or <a href="index.php">cancel</a>
			</fieldset>
		</form>'; 
		return $html;
	}
	
	/**
	 * Navigate to previous month when user selects 'Prev' button
	 * or next month when user selects 'Next' button
	 *
	 * resets $_SESSION['useDate'] for new month display
	 */
	public function incrementMonth() {
		/*
		 * Exit if the action isn't set properly
		 */
		if ($_POST['action'] !='prev_month' && $_POST['action'] !='next_month') {
			return false;
		} else {
			/*
			 * Increment the useDate by +1 month or -1 month
			 */
			if ($_POST['action'] == 'prev_month') {
				return (date('Y-m-d H:i:s', mktime(0,0,0, $this->_m - 1, 1, $this->_y)));
			} elseif ($_POST['action'] == 'next_month') {
				return (date('Y-m-d H:i:s', mktime(0,0,0, $this->_m + 1, 1, $this->_y)));
			}
		}
	}
	
	public function processForm() {
		$errors = array();
		/*
		 * Exit if the action isn't set properly
		 */
		if ($_POST['action'] !='event_edit') {
			return "The method processForm was accessed incorrectly";
		}
		
		/*
		 * Escape data from the form
		 */
		$title 		  = htmlentities($_POST['event_title'], ENT_QUOTES);
		$etype_id 	  = htmlentities($_POST['etype_id'], ENT_QUOTES);
		$location_opt = explode(',', $_POST['elocation_id']);
		$location_id = (int)$location_opt[0]; // Cast as an integer so that when the PDO item is binded, it is an integer for PDO::PARAM_INT
		$desc 		  = htmlentities($_POST['event_description'], ENT_QUOTES);
		$start 		  = htmlentities($_POST['event_start'], ENT_QUOTES);
		$end 		  = htmlentities($_POST['event_end'], ENT_QUOTES);
		
		//echo '$location_id: ',var_dump($location_id),'<br>';
		
		/*
		 * If the start or end dates aren't in a valid format, exit
		 * the script with an error
		 */
		/* 
		if (!$this->_validDate($start) || !$this->_validDate($end)) {
			return "Invalid date format! Use YYY-MM_DD HH:MM:SS";
		}
		*/
		if ($title == '') {
			$_SESSION['error'][] = 'Please enter a title!';
		}
		if ($etype_id == '') {
			$_SESSION['error'][] = 'Please select an event type!';
		}
		if ($location_id == '') {
			$_SESSION['error'][] = 'Please select a location!';
		}
		if (!$this->_validDate($start)) {
			$_SESSION['error'][] = 'Please enter a valid start date! Use YYYY-MM_DD HH:MM:SS';
		}
		if (!$this->_validDate($end)) {
			$_SESSION['error'][] = 'Please enter a valid end date! Use YYYY-MM_DD HH:MM:SS';
		}
		if (isset($_SESSION['error'])) {
			//echo '$_SESSION[error]: ';
			//foreach($_SESSION['error'] as $error) { 
				//echo $error,'<br>';
			//}
			//die();
			return false;
		}
		
		/*
		 * If no event ID passed, create a new event
		 */
		$event_user_id = $_SESSION['user']['id'];
		$event_team_id = $_SESSION['user']['team_id'];
		if (empty($_POST['event_id'])) {
			//echo 'entered INSERT query area.<br>';
			$sql = "INSERT INTO events (event_title, event_desc, event_etype_id, event_location_id, event_start, event_end, event_user_id, event_team_id) 
					VALUES (:title, :description, :etype_id, :location_id, :start, :end, :user_id, :team_id)";
		/*
		 * Update the event if it's being edited
		 */
		} else {
			//echo 'entered UPDATE query area.<br>';
			/*
			 * Cast the event ID as an integer for security
			 */
			if (isset($_SESSION['event_id'])) {
				$id = (int) $_SESSION['event_id'];
			} else {
				$id = (int) $_POST['event_id'];
			}	
			$sql = "UPDATE events
					SET 
						event_title 		= :title,
						event_desc 			= :description,
						event_etype_id 		= :etype_id,
						event_location_id 	= :location_id,
						event_start 		= :start,
						event_end 			= :end,
						event_user_id 		= :user_id,
						event_team_id 		= :team_id
					WHERE event_id 			= $id";	
		}
		
		/*
		 * Execute the create or edit query after binding the data
		 */
		try {
			//echo 'entered try area<br>';
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":title", $title, PDO::PARAM_STR);
			$stmt->bindParam(":description", $desc, PDO::PARAM_STR);
			$stmt->bindParam(":etype_id", $etype_id, PDO::PARAM_INT);
			$stmt->bindParam(":location_id", $location_id, PDO::PARAM_INT);
			$stmt->bindParam(":start", $start, PDO::PARAM_STR);
			$stmt->bindParam(":end", $end, PDO::PARAM_STR);
			$stmt->bindParam(":user_id", $event_user_id, PDO::PARAM_INT);
			$stmt->bindParam(":team_id", $event_team_id, PDO::PARAM_INT);
			//echo '$stmt: ',var_dump($stmt),'<br>';
			$stmt->execute();
			//echo 'stmt should have been executed.<br>';
			//die();
			$stmt->closeCursor();
			
			/*
			 * Returns the ID of the event. Previously was 'return TRUE'.
			 * lastInsertId is a built-in PDO function.
			 * I had to modify this by adding the 'if' statement, as the
			 * result was returning false when the UPDATE statement was
			 * executed.  This was causing the admin.php page to die back to
			 * the $_SESSION['lastpage'], which was itself.
			 */
			if (empty($_POST['event_id'])) {
				return $this->db->lastInsertId();
			} else {
				//return true;  **original**
				return $id;
			}
		}
		catch (Exception $e) {
			die($e->getMessage());
		}
	}
	
	/**
	 * Confirms that an event should be deleted and does so
	 *
	 * Upon clicking the button to delete an event, this
	 * generates a confirmation box.  If the user confirms,
	 * this deletes the event from the database and sends the
	 * user back out to the main calendar view.  If the user
	 * decides not delete the event, they're sent back to 
	 * the main calendar view without deleting anything.
	 *
	 * @param int $id the event ID
	 * @return mixed the form if confirming, void or error if
	 * deleting
	 */
	public function confirmDelete($id) {
		/*
		 * Make sure and ID was passed
		 */
		if (empty($id)) {
			return NULL;
		}
		
		/*
		 * Make sure the ID is an integer
		 */
		$id = preg_replace('/[^0-9]/', '', $id);
		
		/*
		 * If the confirmation form was submitted and the form
		 * has a valid token, check the form submission
		 */
		if (isset($_POST['confirm_delete']) && $_POST['token'] == $_SESSION['token']) {
			/*
			 * If the deletion is confirmed, remove the event
			 * from the database
			 */
			if ($_POST['confirm_delete'] == "Yes, Delete It") {
				$sql = "DELETE FROM events
						WHERE event_id = :id
						LIMIT 1";
				try {
					$stmt = $this->db->prepare($sql);
					$stmt->bindParam(":id", $id, PDO::PARAM_INT);
					$stmt->execute();
					$stmt->closeCursor();
					header("Location: ./");
					return;
				}
				catch (Exception $e) {
					return $e->getMessage();
				}
			/*
			 * If not confirmed, sends the user to the main view
			 */
			} else {
				header("Location: ./");
				return;
			}
		}
		
		/*
		 * If the confirmation form hasn't been submitted,
		 * display it
		 */
		$event = $this->_loadEventById($id);
		
		/*
		 * If no object is returned, return to the main view
		 */
		if (!is_object($event)) {
			header("Location: ./");
		}
		return <<<CONFIRM_DELETE
				<form action="confirmdelete.php" method="post">
					<h2 class="center">Are you sure you want to delete "$event->title"?</h2>
						<p class="center">There is <strong>no undo</strong> if you continue.</p>
						<p class="center">
							<input type="submit" name="confirm_delete" value="Yes, Delete It" />
							<input type="submit" name="confirm_delete" value="Nope! Just Kidding!" />
							<input type="hidden" name="event_id" value="$event->id" />
							<input type="hidden" name="token" value="$_SESSION[token]" />
						</p>
				</form>
CONFIRM_DELETE;
	}
	
	/**
	 * Build table of event data for the user that is logged in
	 *
	 * @return string user events data table
	 */
	public function buildUserEventsTable() {
		/*
		 * Query the events table for current user's events from today's date to six months from then
		 */
		$user = $_SESSION['user']['team_id'];  				// Set the user id
		$date = new DateTime(); 							// Create a new php date object
		$today = $date->format('Y-m-d 00:00:00'); 			// Get today's date in correct format
		$six_months = $date->modify('+6 months'); 			// Modify date object by adding six months
		$six_months = $six_months->format('Y-m-d 23:59:59');// Get six months from now in correct format
		//echo '$user: ',$user,'<br>';
		//echo '$today: ',$today,'<br>';
		//echo '$six_months: ',$six_months,'<br>';
		$sql = "SELECT a.event_user_id, c.user_fname, c.user_lname, a.event_id, a.event_title, a.event_desc, a.event_etype_id, d.etype_name, a.event_location_id, b.dealer_name, b.dealer_code,
			           a.event_start, a.event_end, a.event_team_id
			    FROM events a
				LEFT JOIN dealers b ON (a.event_location_id = b.dealer_record_id)
				LEFT JOIN users c ON (a.event_user_id = c.user_id)
				LEFT JOIN event_type d ON (a.event_etype_id = d.etype_id)
				WHERE a.event_user_id = $user
				AND a.event_start BETWEEN '$today' AND '$six_months'
				ORDER BY a.event_start ASC";
		try {		
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		} catch (Exception $e) {
			die($e->getMessage());
		}
		/*
		 * Now build html table and loop through user's events for display
		 */
		$html = 
		'<div class="container">
			<div class="row">
				<div class="col-md-12">
					<h2 style="color: ##4c4c4c;">My Events <span style="color: gray; font-size: 14px;">(next 6 months)</span> <span style="font-size: 15px;">&nbsp; &nbsp;Total Events Scheduled: '.count($results).'</span></h2>
				</div>
			</div><!--end div row-->
			<div class="row">
				<div class="col-sm-12">
					<div class="table-responsive">
						<table class="table user_events_table table-hover">
							<thead>
								<tr>
									<th>Action</th>
									<th>Name</th>
									<th>Start Date</th>
									<th>Start Day</th>
									<th>Type</th>
									<th>Event Location</th>
									<th>Description</th>
								</tr>
							</thead>
							<tbody>';
							// Now loop through all events to build table rows
							foreach ($results as $event) {
								// Format the date using php DateTime object so that it is in readable format: mm/dd/yyyy
								$date = new DateTime($event['event_start']);
								$date = $date->format('m-d-Y');
								$html .=
								'<tr>
									<td>
										<form action="addevent.php" method="post">
											<input type="submit" class="btn btn-info btn-xs" value="Select" />
											<input type="hidden" id="event_id" name="event_id" value="'.$event['event_id'].'" />
										</form>
									</td>
									<td>'.$event['user_fname'].' '.$event['user_lname'].'</td>
									<td>'.$date.'</td>
									<td>'.date('l',$event['event_start']).'</td>
									<td>'.$event['etype_name'].'</td>
									<td>'.$event['dealer_name'].' '.$event['dealer_code'].'</td>
									<td>'.$event['event_desc'].'</td>
								</tr>';
							}
							// Continue table build
							$html .=
							'</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>';
		// Now return table as $html variable
		return $html;
	}
	
	/**
	 * Validates a date string
	 *
	 *@param string $date the date string to validate
	 *@return bool TRUE on success, FALSE on failure
	 */
	private function _validDate($date) {
		/*
		 * Define a regex pattern to check the date format
		 */
		$pattern = '/^(\d{4}(-\d{2}){2} (\d{2})(:\d{2}){2})$/';
		
		/*
		 * If a match is found, return TRUE.  FALSE otherwise.
		 */
		return preg_match($pattern, $date)==1 ? TRUE : FALSE;
	}
}
?>