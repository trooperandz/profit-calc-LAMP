<?php
/**
 *File: class.event.inc.php
 *Purpose: Store event information
 *PHP version 5.5.29
 *@author Matthew Holland
 *
 *History:
 *   Date			Description						by
 *   09/14/2015		Initial design & coding	    	Matt Holland
 */
 
 class Event {
	/**
	 * The event ID
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * The event title
	 *
	 * @var string
	 */
	public $title;
	
	/**
	 * The event type
	 *
	 * @var int
	 */
	public $etype_id;
	
	/**
	 * The event location
	 *
	 * @var int the location id (meant for the dealer_record_id from the dealers table)
	 * @var string the location name (i.e. dealer_name)
	 */
	public $location_id;
	public $location_name;
	
	/**
	 * The event description
	 *
	 * @var string
	 */
	public $description;
	
	/**
	 * The event start time
	 *
	 * @var string
	 */
	public $start;
	
	/**
	 * The event end time
	 *
	 * @var string
	 */
	public $end;
	
	/**
	 * Accepts an array of event data and stores it
	 *
	 * @param array $event Associative array of event data
	 * @return void
	 */
	public function __construct($event) {
		if (is_array($event)) {
			$this->id = $event['event_id'];
			$this->title = $event['event_title'];
			$this->etype_id = $event['event_etype_id'];
			$this->location_id = $event['event_location_id'];
			$this->location_name = $event['dealer_name'];
			$this->description = $event['event_desc'];
			$this->start = $event['event_start'];
			$this->end = $event['event_end'];
		} else {
			throw new Exception("No event data was supplied.");
		}
	}
 }
 ?>