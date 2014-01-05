<?php
require_once 'AbstractHtmlValidator.class.php';
/**
 * Provide an validator which validates the given value against the filter_var function
 *
 * Possible parameters:
 * 		value => the value to be checked
 * 		options => the filter args, single value and array are possible, use one of FILTER_VALIDATE_* (http://ch2.php.net/manual/de/filter.constants.php)
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Form.Validators
 */
class FilterValidator extends AbstractHtmlValidator
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
		$options = $this->get_options();
		if (!is_array($options)) {
			$options = array($options);
		}
		if (empty($val)) {
			return true;
		}
		$args = array();
		foreach ($options AS $filter_arg) {
			$args[] = $filter_arg;
		}
		return call_user_func_array("filter_var", $args);
	}

}

