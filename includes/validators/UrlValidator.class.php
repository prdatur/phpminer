<?php
require_once 'AbstractHtmlValidator.class.php';
/**
 * Provide a validator which checks values if it is a valid url
 *
 * Possible parameters:
 * 		value => the value which will be checked
 * 		options => not used
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Validators
 */
class UrlValidator extends AbstractHtmlValidator
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

		$filter_validator = new FilterValidator($this->get_value(), array(FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED));
		return $filter_validator->is_valid();
	}

}

