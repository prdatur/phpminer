<?php
require_once 'AbstractHtmlValidator.class.php';
/**
 * Provide a validator which checks values if it is a number
 * if the value NOT exists
 *
 * Possible parameters:
 * 		value => the value which will be checked
 * 		options => not used
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Validators
 */
class IsNumberValidator extends AbstractHtmlValidator
{

	/**
	 * Validates the value against the rules
	 *
	 * @return boolean if valid true, else false
	 */
	function is_valid() {
		if ($this->is_always_valid()) {
			return true;
		}
		if (is_numeric($this->get_value())) {
			return true;
		}
		return false;
	}

}

