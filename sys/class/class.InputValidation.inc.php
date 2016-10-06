<?php
class InputValidation {
	
	public function validDollarValue($item) {
		// Validate dollar amounts
		$pattern = '/^(?:|\d{1,5}(?:\.\d{2,2})?)$/';
		return preg_match($pattern, $item) == 1 ? TRUE : FALSE;
	}
	
	public function validWholeNumber($item) {
		$pattern = '/^[0-9]{1,}$/';
		return preg_match($pattern, $item) == 1 ? TRUE : FALSE;
	}
	
	public function validDate($item) {
		// Format is in dd/mm/yyyy
		$pattern = '/^([0-1][0-9])\/([0-3][0-9])\/([0-9]{4})$/';
		return preg_match($pattern, $item) == 1 ? TRUE : FALSE;
	}
	
	public function validDecimal($item) {
		$pattern = '/^[0-9]+(\.[0-9]{1,2})?$/';
		return preg_match($pattern, $item) == 1 ? TRUE : FALSE;
	}
	
	public function validPercentage($item) {
		$pattern = '/^(?:|\d{1,2}(?:\.\d{1,2})?)$/';
		return preg_match($pattern, $item) == 1 ? TRUE : FALSE;
	}
}
?>