<?php
require_once 'AbstractHtmlValidator.class.php';
/**
 * Provide a validator which checks values if it is not empty
 *
 * Possible parameters:
 * 		value => the value which will be checked
 * 		options => not used
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Validators
 */
class RequiredValidator extends AbstractHtmlValidator
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
		$val = $this->get_value();
		if(!is_array($val) && $val."" === "0") {
			return true;
		}
		if ($val === NS) {
			return false;
		}
		return !empty($val);
	}

}

